<?php

namespace App\Http\Controllers;

use App\Models\CleaningTask;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CleaningTaskController extends Controller
{
    public function index(Request $request): Response
    {
        $query = CleaningTask::select(
            'id', 'room_id', 'assigned_to', 'type', 'status', 'priority',
            'notes', 'issue_reported', 'completed_at', 'created_at'
        )
            ->with(['room:id,room_number,floor', 'room.roomType:id,name', 'assignedUser:id,name'])
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'pending' THEN 1 WHEN 'completed' THEN 2 WHEN 'inspected' THEN 3 ELSE 4 END")
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END");

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('floor')) {
            $query->whereHas('room', fn($q) => $q->where('floor', $request->floor));
        }

        $housekeepers = User::role('housekeeping')->select('id', 'name')->get();

        return Inertia::render('Housekeeping/Index', [
            'tasks' => $query->paginate(20),
            'housekeepers' => $housekeepers,
            'filters' => $request->only('status', 'floor'),
            'stats' => [
                'pending' => CleaningTask::where('status', 'pending')->count(),
                'in_progress' => CleaningTask::where('status', 'in_progress')->count(),
                'completed' => CleaningTask::whereDate('completed_at', today())->where('status', 'completed')->count(),
                'urgent' => CleaningTask::where('priority', 'urgent')->whereIn('status', ['pending', 'in_progress'])->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'type' => ['required', 'in:checkout_clean,stayover_clean,deep_clean,inspection'],
            'priority' => ['sometimes', 'in:normal,urgent'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        CleaningTask::create([
            'room_id' => $request->room_id,
            'type' => $request->type,
            'priority' => $request->priority ?? 'normal',
            'assigned_to' => $request->assigned_to,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Detyra u krijua.');
    }

    public function updateStatus(Request $request, CleaningTask $cleaningTask): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,inspected'],
        ]);

        $newStatus = $request->status;
        $data = ['status' => $newStatus];

        if ($newStatus === 'completed') {
            $data['completed_at'] = now();
        }

        // When task completed → set room to available
        if ($newStatus === 'completed' || $newStatus === 'inspected') {
            DB::transaction(function () use ($cleaningTask, $data) {
                $cleaningTask->update($data);
                $room = $cleaningTask->room;
                if ($room->status === 'cleaning') {
                    $room->update(['status' => 'available']);
                }
            });
        } else {
            $cleaningTask->update($data);
        }

        return back()->with('success', 'Statusi u perditesua.');
    }

    public function assign(Request $request, CleaningTask $cleaningTask): RedirectResponse
    {
        $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        $cleaningTask->update(['assigned_to' => $request->assigned_to]);

        return back()->with('success', 'Detyra u caktua.');
    }

    public function reportIssue(Request $request, CleaningTask $cleaningTask): RedirectResponse
    {
        $request->validate([
            'issue_reported' => ['required', 'string', 'max:1000'],
        ]);

        $cleaningTask->update([
            'issue_reported' => $request->issue_reported,
        ]);

        // Set room to maintenance if serious
        if ($request->boolean('set_maintenance')) {
            $cleaningTask->room->update(['status' => 'maintenance']);
        }

        return back()->with('success', 'Problemi u raportua.');
    }
}
