<?php

namespace App\Http\Requests;

use App\Models\Payment;
use App\Enums\PaymentStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->payment);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:' . implode(',', PaymentStatusEnum::values()),
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The status field must be a valid status.',
            'notes.string' => 'The notes field must be a string.',
        ];
    }
}
