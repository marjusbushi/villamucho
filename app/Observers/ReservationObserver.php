<?php

namespace App\Observers;

use App\Jobs\PushRoomTypeAri;
use App\Mail\NewReservationMail;
use App\Models\Reservation;
use App\Models\ReservationStatusLog;
use App\Models\Room;
use App\Models\Setting;
use Carbon\CarbonImmutable;
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
        $this->logStatus($reservation, null, (string) $reservation->status);

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
     * Append-only status history: every transition (confirm, check-in/out,
     * cancel, no-show) gets a row — this is where exact cancellation times
     * come from for pricing analytics. 'updated' still sees getOriginal().
     */
    public function updated(Reservation $reservation): void
    {
        if ($reservation->wasChanged('status')) {
            $this->logStatus(
                $reservation,
                (string) $reservation->getOriginal('status'),
                (string) $reservation->status,
            );
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

    /** Best-effort: an audit-log failure must never break a booking write. */
    private function logStatus(Reservation $reservation, ?string $from, string $to): void
    {
        try {
            ReservationStatusLog::create([
                'reservation_id' => $reservation->id,
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => auth()->id(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Reservation status log failed: '.$e->getMessage());
        }
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

        // A reservation event only changes availability for the nights it occupies,
        // so push ONLY that window — not the whole default year. If nothing in the
        // sellable future changed (the stay is entirely in the past), don't push.
        [$from, $to] = $this->changedWindow($reservation);
        if ($from === null) {
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
            ->each(fn ($roomTypeId) => PushRoomTypeAri::dispatch($roomTypeId, $from, $to));
    }

    /**
     * The inclusive Y-m-d window whose availability THIS reservation event affects:
     * the occupied nights [check_in .. check_out-1] (half-open — the check-out day
     * is free), widened to the UNION of the old and new nights when the dates moved
     * (so freed dates are re-pushed too), and floored at today (past dates are
     * unsellable and the full-window path never pushes them either). Returns
     * [null, null] when the whole window is in the past — nothing to push.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function changedWindow(Reservation $reservation): array
    {
        $checkIn = CarbonImmutable::parse($reservation->check_in_date);
        $checkOut = CarbonImmutable::parse($reservation->check_out_date);

        // On an update that moved the dates, getOriginal() still holds the pre-change
        // values ('saved' fires before syncOriginal), so cover both old and new.
        if ($reservation->wasChanged('check_in_date') && $reservation->getOriginal('check_in_date')) {
            $checkIn = $checkIn->min(CarbonImmutable::parse($reservation->getOriginal('check_in_date')));
        }
        if ($reservation->wasChanged('check_out_date') && $reservation->getOriginal('check_out_date')) {
            $checkOut = $checkOut->max(CarbonImmutable::parse($reservation->getOriginal('check_out_date')));
        }

        $lastNight = $checkOut->subDay(); // check-out day is free
        $today = CarbonImmutable::today();

        if ($lastNight->lt($today)) {
            return [null, null];
        }

        $from = $checkIn->lt($today) ? $today : $checkIn;

        return [$from->toDateString(), $lastNight->toDateString()];
    }
}
