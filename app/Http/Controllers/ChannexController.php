<?php

namespace App\Http\Controllers;

use App\Jobs\PushRoomTypeAri;
use Illuminate\Http\RedirectResponse;

class ChannexController extends Controller
{
    /**
     * Manual "Sync now": queue a full availability + rate push for every
     * Channex-mapped room type. The actual pushes run on the queue worker.
     */
    public function sync(): RedirectResponse
    {
        $count = PushRoomTypeAri::dispatchAllMapped();

        if ($count === 0) {
            return back()->with('error', 'Channex nuk eshte konfiguruar ose s\'ka dhoma te lidhura me kanalin.');
        }

        return back()->with('success', "Sinkronizimi me Channex u nis per {$count} tipe dhomash.");
    }
}
