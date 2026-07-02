<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * Anyone can send a message — no authentication required.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for a new chat message.
     *
     * @return array<string, list<string|\Illuminate\Contracts\Validation\ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'message' => [
                'required',
                'string',
                'min:1',
                'max:500',
            ],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Please type a message before sending.',
            'message.min'      => 'Message cannot be empty.',
            'message.max'      => 'Message must not exceed 500 characters.',
        ];
    }
}
