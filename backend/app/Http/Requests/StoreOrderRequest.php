<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Enums\OrderStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'event_id' => 'required|exists:events,id',
            'tickets_count' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:1',
            'status' => 'required|in:' . implode(',', OrderStatusEnum::values()),
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'The status field is required.',
            'status.in' => 'The status field must be a valid status.',
            'user_id.required' => 'The user id field is required.',
            'user_id.exists' => 'The user id field must be a valid user id.',
            'event_id.required' => 'The event id field is required.',
            'event_id.exists' => 'The event id field must be a valid event id.',
            'tickets_count.required' => 'The tickets count field is required.',
            'tickets_count.integer' => 'The tickets count field must be an integer.',
            'tickets_count.min' => 'The tickets count field must be greater than 0.',
            'total_price.required' => 'The total price field is required.',
            'total_price.numeric' => 'The total price field must be a number.',
            'total_price.min' => 'The total price field must be greater than 1.',
        ];
    }   
}
