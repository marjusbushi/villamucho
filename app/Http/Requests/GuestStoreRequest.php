<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_guests');
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'document_type' => ['nullable', 'in:id_card,passport,drivers_license'],
            'document_number' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:3'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'preferences' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
