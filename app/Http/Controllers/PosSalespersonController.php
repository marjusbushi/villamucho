<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PosOrder;
use App\Services\PosSalespersonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PosSalespersonController extends Controller
{
    public function __construct(private readonly PosSalespersonService $salespeople) {}

    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'pin' => ['required', 'digits:4'],
        ]);
        $user = $this->salespeople->switch($request, (int) $data['user_id'], $data['pin']);

        return back()->with('success', "Salesperson aktiv: {$user->name}.");
    }

    public function transfer(Request $request, PosOrder $posOrder): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'pin' => ['required', 'digits:4'],
        ]);
        if ($posOrder->status !== 'open') {
            return back()->with('error', 'Vetëm porositë e hapura mund të transferohen.');
        }

        $user = $this->salespeople->verifyPin((int) $data['user_id'], $data['pin']);
        $previous = $posOrder->salesperson_id ?: $posOrder->created_by;
        $posOrder->update(['salesperson_id' => $user->id]);
        AuditLog::record('pos.salesperson.transferred', $posOrder, [
            'from_salesperson_id' => $previous,
            'to_salesperson_id' => $user->id,
            'changed_by' => $request->user()->id,
        ]);

        return back()->with('success', "Porosia iu kalua {$user->name}.");
    }
}
