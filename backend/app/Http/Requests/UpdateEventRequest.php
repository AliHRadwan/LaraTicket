<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->event);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:events,slug,' . $this->event->id,
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'total_tickets' => 'required|integer|min:1',
            'start_datetime' => 'required|date',
            'end_datetime' => 'nullable|date|after:start_datetime',
            'location' => 'required|string|max:255',
            'image_url' => 'nullable|url|max:2048',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'title.string' => 'The title field must be a string.',
            'title.max' => 'The title field must be less than 255 characters.',
            'slug.string' => 'The slug field must be a string.',
            'slug.max' => 'The slug field must be less than 255 characters.',
            'slug.unique' => 'The slug field must be unique.',
            'description.required' => 'The description field is required.',
            'description.string' => 'The description field must be a string.',
            'price.required' => 'The price field is required.',
            'price.numeric' => 'The price field must be a number.',
            'price.min' => 'The price field must be at least 0.',
            'total_tickets.required' => 'The total tickets field is required.',
            'total_tickets.integer' => 'The total tickets field must be an integer.',
            'total_tickets.min' => 'The total tickets field must be at least 1.',
            'start_datetime.required' => 'The start datetime field is required.',
            'start_datetime.date' => 'The start datetime field must be a date.',
            'end_datetime.date' => 'The end datetime field must be a date.',
            'end_datetime.after' => 'The end datetime must be after the start datetime.',
            'location.required' => 'The location field is required.',
            'location.string' => 'The location field must be a string.',
            'location.max' => 'The location field must be less than 255 characters.',
            'image_url.url' => 'The image URL must be a valid URL.',
            'image_url.max' => 'The image URL must be less than 2048 characters.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a JPG, PNG, WebP, or GIF.',
            'image.max' => 'The image must not exceed 5 MB.',
        ];
    }
}
