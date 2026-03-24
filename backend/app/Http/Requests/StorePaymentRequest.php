<?php

namespace App\Http\Requests;

use App\Models\Payment;
use App\Enums\PaymentStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Payment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:1',
            'provider' => 'required|string|max:255',
            'provider_transaction_id' => 'required|string|max:255|unique:payments,provider_transaction_id',
            'payment_method' => 'required|string|max:255',
            'status' => 'required|in:' . implode(',', PaymentStatusEnum::values()),
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'The status field is required.',
            'status.in' => 'The status field must be a valid status.',
            'notes.string' => 'The notes field must be a string.',
            'notes.max' => 'The notes field must be less than 255 characters.',
            'order_id.required' => 'The order id field is required.',
            'order_id.exists' => 'The order id field must be a valid order id.',
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount field must be a number.',
            'amount.min' => 'The amount field must be greater than 1.',
            'provider.required' => 'The provider field is required.',
            'provider.string' => 'The provider field must be a string.',
            'provider.max' => 'The provider field must be less than 255 characters.',
            'provider_transaction_id.required' => 'The provider transaction id field is required.',
            'provider_transaction_id.string' => 'The provider transaction id field must be a string.',
            'provider_transaction_id.max' => 'The provider transaction id field must be less than 255 characters.',
            'provider_transaction_id.unique' => 'The provider transaction id field must be unique.',
            'payment_method.required' => 'The payment method field is required.',
            'payment_method.string' => 'The payment method field must be a string.',
            'payment_method.max' => 'The payment method field must be less than 255 characters.',
        ];
    }
}
