<?php

namespace App\Http\Requests;

use App\Models\Payment;
use App\Enums\PaymentStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Payment::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0',
            'provider' => 'required|string|max:255',
            'provider_transaction_id' => 'required|string|max:255|unique:payments,provider_transaction_id',
            'payment_method' => 'nullable|string|max:255',
            'status' => 'required|in:' . implode(',', PaymentStatusEnum::values()),
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'The order id field is required.',
            'order_id.exists' => 'The order id field must be a valid order id.',
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount field must be a number.',
            'amount.min' => 'The amount field must be at least 0.',
            'provider.required' => 'The provider field is required.',
            'provider.string' => 'The provider field must be a string.',
            'provider.max' => 'The provider field must be less than 255 characters.',
            'provider_transaction_id.required' => 'The provider transaction id field is required.',
            'provider_transaction_id.string' => 'The provider transaction id field must be a string.',
            'provider_transaction_id.max' => 'The provider transaction id field must be less than 255 characters.',
            'provider_transaction_id.unique' => 'The provider transaction id field must be unique.',
            'payment_method.string' => 'The payment method field must be a string.',
            'payment_method.max' => 'The payment method field must be less than 255 characters.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The status field must be a valid status.',
            'notes.string' => 'The notes field must be a string.',
        ];
    }
}
