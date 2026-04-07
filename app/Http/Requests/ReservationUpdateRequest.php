<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;

class ReservationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_reservations');
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'guest_id' => ['required', 'exists:guests,id'],
            'check_in_date' => ['required', 'date'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'status' => ['sometimes', 'in:pending,confirmed,checked_in,checked_out,cancelled'],
            'adults' => ['required', 'integer', 'min:1', 'max:10'],
            'children' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->room_id && $this->check_in_date && $this->check_out_date) {
                $excludeId = $this->route('reservation')->id;
                if (!Reservation::isRoomAvailable($this->room_id, $this->check_in_date, $this->check_out_date, $excludeId)) {
                    $validator->errors()->add('room_id', 'Kjo dhome eshte e zene per keto data.');
                }
            }
        });
    }
}
