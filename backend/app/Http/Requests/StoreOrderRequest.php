<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\Order;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => 'required|exists:events,id',
            'tickets_count' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $event = Event::find($this->event_id);
                    if ($event && $value > $event->available_tickets) {
                        $fail('The requested number of tickets exceeds the available tickets.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'event_id.required' => 'The event id field is required.',
            'event_id.exists' => 'The event id field must be a valid event id.',
            'tickets_count.required' => 'The tickets count field is required.',
            'tickets_count.integer' => 'The tickets count field must be an integer.',
            'tickets_count.min' => 'The tickets count field must be at least 1.',
        ];
    }
}
