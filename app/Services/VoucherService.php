<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Exception;

class VoucherService
{
    /**
     * Validate and apply voucher to order by voucher CODE
     *
     * @param string $voucherCode Voucher code (not UUID)
     * @param Order $order Order instance
     * @param string $applyType 'discount' or 'shipping_fee'
     * @param Collection $orderItems OrderDetail collection
     * @return array ['valid' => bool, 'voucher' => Voucher|null, 'discount' => float, 'message' => string]
     */
    public function validateVoucherByCode(string $voucherCode, Order $order, string $applyType, Collection $orderItems)
    {
        try {
            // Find voucher by code
            $voucher = Voucher::where('vourcher_code', $voucherCode)->first();

            if (!$voucher) {
                return $this->errorResponse('Voucher code not found');
            }

            // Check if voucher is active
            if ($voucher->status !== 'active') {
                return $this->errorResponse('Voucher is inactive');
            }

            // Check apply_type matches
            if ($voucher->apply_type !== $applyType) {
                $expectedType = $applyType === 'discount' ? 'product discount' : 'shipping fee';
                return $this->errorResponse("This voucher is not applicable for {$expectedType}");
            }

            // Check date validity
            if ($this->isExpired($voucher)) {
                return $this->errorResponse('Voucher has expired');
            }

            // Check if voucher has started
            if (Carbon::now()->lt(Carbon::parse($voucher->start_date))) {
                return $this->errorResponse('Voucher is not yet valid');
            }

            // Check usage limit
            if (!$this->hasRemainingUsage($voucher)) {
                return $this->errorResponse('Voucher usage limit has been reached');
            }

            // Determine order total for minimum check
            $orderTotal = $applyType === 'discount' ? $order->order_total : $order->shipping_fee;

            // Check minimum order amount
            if (!$this->meetsMinimum($voucher, $orderTotal)) {
                $minimum = number_format($voucher->minimum, 0, ',', '.');
                return $this->errorResponse("Order must be at least {$minimum} VND to use this voucher");
            }

            // Calculate discount
            $discount = $this->calculateDiscount($voucher, $orderTotal);

            return [
                'valid' => true,
                'voucher' => $voucher,
                'discount' => $discount,
                'message' => 'Voucher applied successfully'
            ];
        } catch (Exception $e) {
            return $this->errorResponse('Error validating voucher: ' . $e->getMessage());
        }
    }

    /**
     * Validate and apply voucher to order (original method with UUID - kept for backward compatibility)
     *
     * @param string $voucherId Voucher UUID
     * @param Order $order Order instance
     * @param string $applyType 'discount' or 'shipping_fee'
     * @param Collection $orderItems OrderDetail collection
     * @return array ['valid' => bool, 'voucher' => Voucher|null, 'discount' => float, 'message' => string]
     */
    public function validateVoucher(string $voucherId, Order $order, string $applyType, Collection $orderItems)
    {
        try {
            // Find voucher
            $voucher = Voucher::find($voucherId);

            if (!$voucher) {
                return $this->errorResponse('Voucher not found');
            }

            // Check if voucher is active
            if ($voucher->status !== 'active') {
                return $this->errorResponse('Voucher is inactive');
            }

            // Check apply_type matches
            if ($voucher->apply_type !== $applyType) {
                $expectedType = $applyType === 'discount' ? 'product discount' : 'shipping fee';
                return $this->errorResponse("This voucher is not applicable for {$expectedType}");
            }

            // Check date validity
            if ($this->isExpired($voucher)) {
                return $this->errorResponse('Voucher has expired');
            }

            // Check if voucher has started
            if (Carbon::now()->lt(Carbon::parse($voucher->start_date))) {
                return $this->errorResponse('Voucher is not yet valid');
            }

            // Check usage limit
            if (!$this->hasRemainingUsage($voucher)) {
                return $this->errorResponse('Voucher usage limit has been reached');
            }

            // Determine order total for minimum check
            $orderTotal = $applyType === 'discount' ? $order->order_total : $order->shipping_fee;

            // Check minimum order amount
            if (!$this->meetsMinimum($voucher, $orderTotal)) {
                $minimum = number_format($voucher->minimum, 0, ',', '.');
                return $this->errorResponse("Order must be at least {$minimum} VND to use this voucher");
            }

            // Calculate discount
            $discount = $this->calculateDiscount($voucher, $orderTotal);

            return [
                'valid' => true,
                'voucher' => $voucher,
                'discount' => $discount,
                'message' => 'Voucher applied successfully'
            ];
        } catch (Exception $e) {
            return $this->errorResponse('Error validating voucher: ' . $e->getMessage());
        }
    }

    /**
     * Calculate discount amount from voucher
     *
     * @param Voucher $voucher
     * @param float $orderTotal
     * @return float
     */
    public function calculateDiscount(Voucher $voucher, float $orderTotal): float
    {
        $discount = 0;

        if ($voucher->discount_type === 'percent') {
            $discount = ($orderTotal * $voucher->discount_percent) / 100;
        } else {
            // Fixed discount
            $discount = $voucher->discount_amount;
        }

        // Apply limit_per_order cap if set
        if ($voucher->limit_per_order && $discount > $voucher->limit_per_order) {
            $discount = $voucher->limit_per_order;
        }

        // Discount cannot exceed order total
        if ($discount > $orderTotal) {
            $discount = $orderTotal;
        }

        return round($discount, 2);
    }

    /**
     * Check if voucher has expired
     *
     * @param Voucher $voucher
     * @return bool
     */
    public function isExpired(Voucher $voucher): bool
    {
        return Carbon::now()->gt(Carbon::parse($voucher->end_date));
    }

    /**
     * Check if voucher has remaining usage
     *
     * @param Voucher $voucher
     * @return bool
     */
    public function hasRemainingUsage(Voucher $voucher): bool
    {
        $usedCount = $this->calculateUsedVouchers($voucher);
        return $usedCount < $voucher->limit;
    }

    /**
     * Check if order meets minimum amount
     *
     * @param Voucher $voucher
     * @param float $orderTotal
     * @return bool
     */
    public function meetsMinimum(Voucher $voucher, float $orderTotal): bool
    {
        if ($voucher->minimum === null) {
            return true;
        }
        return $orderTotal >= $voucher->minimum;
    }

    /**
     * Calculate how many times a voucher has been used
     *
     * @param Voucher $voucher
     * @return int
     */
    public function calculateUsedVouchers(Voucher $voucher): int
    {
        // Count completed orders that used this voucher
        return $voucher->orders()
            ->whereNotIn('order_status', ['Draft', 'Cancelled'])
            ->count();
    }

    /**
     * Check if voucher applies to products in cart
     * Currently all vouchers apply to all products (no product restrictions)
     *
     * @param Voucher $voucher
     * @param Collection $orderItems OrderDetail collection
     * @return bool
     */
    public function checkProductRestrictions(Voucher $voucher, Collection $orderItems): bool
    {
        // All vouchers apply to all products - no restrictions
        return true;
    }

    /**
     * Get available vouchers for a team
     *
     * @param string $teamId
     * @return array ['discount' => Collection, 'shipping_fee' => Collection]
     */
    public function getAvailableVouchers(string $teamId): array
    {
        $currentDate = Carbon::now();

        $vouchers = Voucher::where('status', 'active')
            ->whereDate('start_date', '<=', $currentDate)
            ->whereDate('end_date', '>=', $currentDate)
            ->whereHas('teams', function ($query) use ($teamId) {
                $query->where('teams.id', $teamId);
            })
            ->get();

        // Add remaining count to each voucher
        $vouchers = $vouchers->map(function ($voucher) {
            $used = $this->calculateUsedVouchers($voucher);
            $voucher->remaining = max(0, $voucher->limit - $used);
            return $voucher;
        });

        // Filter out vouchers with no remaining usage
        $vouchers = $vouchers->filter(function ($voucher) {
            return $voucher->remaining > 0;
        });

        // Group by apply_type
        return [
            'discount' => $vouchers->where('apply_type', 'discount')->values(),
            'shipping_fee' => $vouchers->where('apply_type', 'shipping_fee')->values(),
        ];
    }

    /**
     * Create error response
     *
     * @param string $message
     * @return array
     */
    private function errorResponse(string $message): array
    {
        return [
            'valid' => false,
            'voucher' => null,
            'discount' => 0,
            'message' => $message
        ];
    }
}
