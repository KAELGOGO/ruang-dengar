<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Models\AvailabilitySlot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

// Module 2: listener availability slots. These slots are what ListenerMatcher books.
class SlotController extends Controller
{
    public function index(Request $request)
    {
        $slots = $request->user()->availabilitySlots()
            ->with('counselingSession.user')
            ->upcoming()
            ->get();

        return view('listener.slots.index', [
            'slots' => $slots,
            'isVerified' => $request->user()->isVerifiedListener(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ], [
            'date.required' => 'Tanggal wajib diisi.',
            'date.date_format' => 'Format tanggal tidak valid.',
            'date.after_or_equal' => 'Tanggal tidak boleh di masa lalu.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'start_time.date_format' => 'Format jam mulai tidak valid.',
            'end_time.required' => 'Jam selesai wajib diisi.',
            'end_time.date_format' => 'Format jam selesai tidak valid.',
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        $startsAt = Carbon::parse("{$data['date']} {$data['start_time']}");
        $endsAt = Carbon::parse("{$data['date']} {$data['end_time']}");

        $this->ensureSlotIsValid($request, $startsAt, $endsAt);

        $request->user()->availabilitySlots()->create(['starts_at' => $startsAt, 'ends_at' => $endsAt]);

        return redirect()->route('listener.slots.index')
            ->with('status', 'Slot '.$startsAt->format('d M Y, H:i').'–'.$endsAt->format('H:i').' ditambahkan.');
    }

    public function destroy(AvailabilitySlot $slot)
    {
        abort_unless($slot->listener_id === auth()->id(), 403);

        // A cancelled session releases its slot (availability_slot_id = null), so only live bookings block this.
        if ($slot->counselingSession()->exists()) {
            return back()->with('status', 'Slot sudah dibooking, jadi tidak bisa dihapus.');
        }

        $slot->delete();

        return back()->with('status', 'Slot dihapus.');
    }

    /** Rules that need the combined date and time: not in the past, 30-120 minutes, no overlap. */
    private function ensureSlotIsValid(Request $request, Carbon $startsAt, Carbon $endsAt): void
    {
        if ($startsAt->isPast()) {
            throw ValidationException::withMessages(['start_time' => 'Jam mulai sudah lewat.']);
        }

        $minutes = $startsAt->diffInMinutes($endsAt);
        if ($minutes < AvailabilitySlot::MIN_MINUTES || $minutes > AvailabilitySlot::MAX_MINUTES) {
            throw ValidationException::withMessages([
                'end_time' => 'Durasi slot harus '.AvailabilitySlot::MIN_MINUTES.'–'.AvailabilitySlot::MAX_MINUTES.' menit.',
            ]);
        }

        if ($request->user()->availabilitySlots()->overlapping($startsAt, $endsAt)->exists()) {
            throw ValidationException::withMessages(['start_time' => 'Slot ini bertumpuk dengan slotmu yang lain.']);
        }
    }
}
