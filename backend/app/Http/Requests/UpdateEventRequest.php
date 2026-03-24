<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:events,slug,' . $this->event->id,
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'total_tickets' => 'required|integer|min:0',
            'available_tickets' => 'required|integer|min:0',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date',
            'location' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'title.string' => 'The title field must be a string.',
            'title.max' => 'The title field must be less than 255 characters.',
            'slug.required' => 'The slug field is required.',
            'slug.string' => 'The slug field must be a string.',
            'slug.max' => 'The slug field must be less than 255 characters.',
            'slug.unique' => 'The slug field must be unique.',
            'description.required' => 'The description field is required.',
            'description.string' => 'The description field must be a string.',
            'price.required' => 'The price field is required.',
            'price.numeric' => 'The price field must be a number.',
            'price.min' => 'The price field must be greater than 0.',
            'total_tickets.required' => 'The total tickets field is required.',
            'total_tickets.integer' => 'The total tickets field must be an integer.',
            'total_tickets.min' => 'The total tickets field must be greater than 0.',
            'available_tickets.required' => 'The available tickets field is required.',
            'available_tickets.integer' => 'The available tickets field must be an integer.',
            'available_tickets.min' => 'The available tickets field must be greater than 0.',
            'start_datetime.required' => 'The start datetime field is required.',
            'start_datetime.date' => 'The start datetime field must be a date.',
            'end_datetime.required' => 'The end datetime field is required.',
            'end_datetime.date' => 'The end datetime field must be a date.',
            'location.required' => 'The location field is required.',
            'location.string' => 'The location field must be a string.',
            'location.max' => 'The location field must be less than 255 characters.',
        ];
    }
}
