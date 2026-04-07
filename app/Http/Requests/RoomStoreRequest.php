<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_rooms');
    }

    public function rules(): array
    {
        return [
            'room_type_id' => ['required', 'exists:room_types,id'],
            'room_number' => ['required', 'string', 'max:10', 'unique:rooms,room_number'],
            'floor' => ['required', 'integer', 'min:1', 'max:50'],
            'status' => ['sometimes', 'in:available,occupied,cleaning,maintenance'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_number.unique' => 'Ky numer dhome ekziston tashme.',
        ];
    }
}
