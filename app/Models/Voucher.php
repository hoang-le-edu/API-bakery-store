<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedBy;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Voucher extends Model
{
    use HasFactory, HasUuid, HasCreatedBy;
    protected $fillable = [
        'vourcher_code',
        'status',
        'start_date',
        'end_date',
        'discount_type',
        'discount_amount',
        'discount_percent',
        'config',
        'minimum',
        'limit_per_order',
        'apply_type',
        'limit'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'minimum' => 'decimal:2',
        'limit_per_order' => 'decimal:2',
    ];

    // Relationships
    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function team()
    {
        return $this->belongsTo(Team::class); // Singular: "team"
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_voucher');
    }

    // Query Scopes

    /**
     * Scope to filter active vouchers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter vouchers valid on a specific date
     */
    public function scopeValidDate($query, $date = null)
    {
        $date = $date ?? Carbon::now();
        return $query->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    /**
     * Scope to filter vouchers by team
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->whereHas('teams', function ($q) use ($teamId) {
            $q->where('teams.id', $teamId);
        });
    }

    /**
     * Scope to filter vouchers by apply type
     */
    public function scopeApplyType($query, $type)
    {
        return $query->where('apply_type', $type);
    }

    // Helper Methods

    /**
     * Check if voucher has expired
     */
    public function isExpired(): bool
    {
        return Carbon::now()->gt($this->end_date);
    }

    /**
     * Check if voucher has started
     */
    public function hasStarted(): bool
    {
        return Carbon::now()->gte($this->start_date);
    }

    /**
     * Check if voucher is currently valid (active and within date range)
     */
    public function isValid(): bool
    {
        return $this->status === 'active'
            && $this->hasStarted()
            && !$this->isExpired();
    }

    /**
     * Check if voucher has remaining usage
     */
    public function hasRemainingUsage(): bool
    {
        $usedCount = $this->orders()
            ->whereNotIn('order_status', ['Draft', 'Cancelled'])
            ->count();
        return $usedCount < $this->limit;
    }

    /**
     * Get remaining usage count
     */
    public function getRemainingUsage(): int
    {
        $usedCount = $this->orders()
            ->whereNotIn('order_status', ['Draft', 'Cancelled'])
            ->count();
        return max(0, $this->limit - $usedCount);
    }

    /**
     * Check if order meets minimum requirement
     */
    public function meetsMinimum(float $orderTotal): bool
    {
        if ($this->minimum === null) {
            return true;
        }
        return $orderTotal >= $this->minimum;
    }

    /**
     * Check if voucher applies to specific product
     */
    public function appliesToProduct(string $productId): bool
    {
        // If no product restrictions, applies to all
        $productCount = $this->products()->count();
        if ($productCount === 0) {
            return true;
        }

        // Check if product is in the list
        return $this->products()->where('products.id', $productId)->exists();
    }
}
