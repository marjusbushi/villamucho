<?php

namespace App\Observers;

use App\Jobs\PushRoomTypeAri;
use App\Mail\NewReservationMail;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReservationObserver
{
    /**
     * Email the hotel on every new reservation. Wrapped so a mail failure
     * (no SMTP, server down, etc.) NEVER breaks the booking.
     */
    public function created(Reservation $reservation): void
    {
        $to = Setting::get('hotel.email');
        if (! $to) {
            return;
        }

        try {
            $reservation->loadMissing(['guest', 'room.roomType']);
            Mail::to($to)->send(new NewReservationMail($reservation));
        } catch (\Throwable $e) {
            Log::warning('New-reservation email failed: ' . $e->getMessage());
        }
    }

    /**
     * Any create / status change / check-in-out / cancel changes how many rooms
     * of this type are free, so re-push that room type's availability to Channex.
     * 'saved' fires on create AND update, covering every booking path at once.
     */
    public function saved(Reservation $reservation): void
    {
        $this->syncChannel($reservation);
    }

    public function deleted(Reservation $reservation): void
    {
        $this->syncChannel($reservation);
    }

    private function syncChannel(Reservation $reservation): void
    {
        // Only when Channex is wired up — never queue no-op jobs otherwise.
        if (! config('services.channex.api_key')) {
            return;
        }

        $roomIds = collect([$reservation->room_id]);

        // A reservation MOVED to another room frees a room on the OLD room's type
        // too, so re-push that type as well (deduped if it's the same type).
        if ($reservation->wasChanged('room_id') && $reservation->getOriginal('room_id')) {
            $roomIds->push($reservation->getOriginal('room_id'));
        }

        Room::whereKey($roomIds->unique()->all())
            ->pluck('room_type_id')
            ->unique()
            ->each(fn ($roomTypeId) => PushRoomTypeAri::dispatch($roomTypeId));
    }
}
