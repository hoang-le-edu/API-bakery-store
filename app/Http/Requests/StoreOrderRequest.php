<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Voucher;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_detail_ids' => 'required|array',
            'order_detail_ids.*' => 'required|exists:order_details,id',
            'receiver_name' => 'required|string|max:255',
            'receiver_address' => 'required|string|max:255',
            'payment_method' => 'required|in:Cash,Banking',
            'voucher_code' => 'nullable|string|max:255', // Changed from voucher UUID to code
            'voucher_shipping_code' => 'nullable|string|max:255', // Changed from voucher_shipping UUID to code
            'note' => 'nullable|string',
            'province' => 'required|string',
            'district' => 'required|string',
            'ward' => 'required|string',
            'street' => 'required|string',
            'phone_number' => 'required|string',
            'shipping_fee' => 'required|numeric|min:0',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validate discount voucher code
            if ($this->filled('voucher_code')) {
                $voucher = Voucher::where('vourcher_code', $this->input('voucher_code'))->first();

                if (!$voucher) {
                    $validator->errors()->add('voucher_code', 'Voucher code not found.');
                } else {
                    // Check if it's a discount type voucher
                    if ($voucher->apply_type !== 'discount') {
                        $validator->errors()->add('voucher_code', 'This voucher is not applicable for order discount.');
                    }

                    // Check if voucher is active
                    if ($voucher->status !== 'active') {
                        $validator->errors()->add('voucher_code', 'This voucher is currently inactive.');
                    }

                    // Check if voucher is expired
                    if ($voucher->isExpired()) {
                        $validator->errors()->add('voucher_code', 'This voucher has expired.');
                    }

                    // Check if voucher has started
                    if (!$voucher->hasStarted()) {
                        $validator->errors()->add('voucher_code', 'This voucher is not yet valid.');
                    }

                    // Check usage limit
                    if (!$voucher->hasRemainingUsage()) {
                        $validator->errors()->add('voucher_code', 'This voucher has reached its usage limit.');
                    }
                }
            }

            // Validate shipping voucher code
            if ($this->filled('voucher_shipping_code')) {
                $voucherShipping = Voucher::where('vourcher_code', $this->input('voucher_shipping_code'))->first();

                if (!$voucherShipping) {
                    $validator->errors()->add('voucher_shipping_code', 'Shipping voucher code not found.');
                } else {
                    // Check if it's a shipping_fee type voucher
                    if ($voucherShipping->apply_type !== 'shipping_fee') {
                        $validator->errors()->add('voucher_shipping_code', 'This voucher is not applicable for shipping fee.');
                    }

                    // Check if voucher is active
                    if ($voucherShipping->status !== 'active') {
                        $validator->errors()->add('voucher_shipping_code', 'This shipping voucher is currently inactive.');
                    }

                    // Check if voucher is expired
                    if ($voucherShipping->isExpired()) {
                        $validator->errors()->add('voucher_shipping_code', 'This shipping voucher has expired.');
                    }

                    // Check if voucher has started
                    if (!$voucherShipping->hasStarted()) {
                        $validator->errors()->add('voucher_shipping_code', 'This shipping voucher is not yet valid.');
                    }

                    // Check usage limit
                    if (!$voucherShipping->hasRemainingUsage()) {
                        $validator->errors()->add('voucher_shipping_code', 'This shipping voucher has reached its usage limit.');
                    }
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'voucher_code.string' => 'Voucher code must be a valid string.',
            'voucher_shipping_code.string' => 'Shipping voucher code must be a valid string.',
            'order_detail_ids.required' => 'Please select at least one item to checkout.',
            'payment_method.in' => 'Payment method must be either Cash or Banking.',
        ];
    }
}
