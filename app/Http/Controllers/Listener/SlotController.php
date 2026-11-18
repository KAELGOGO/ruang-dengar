<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Models\AvailabilitySlot;
use Illuminate\Http\Request;

// Module 2: listener availability slots.
class SlotController extends Controller
{
    public function index()
    {
        return view('placeholder', [
            'modul' => 'Modul 2: Jadwal listener',
            'judul' => 'Slot ketersediaan',
            'todo' => [
                'Daftar slot milik listener yang login (yang sudah dibooking ditandai).',
                'Form tambah slot: tanggal, jam mulai, jam selesai (route listener.slots.store).',
                'Hapus slot hanya kalau belum dibooking (route listener.slots.destroy).',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        // TODO (Module 2): reject overlaps with the listener's own slots, then
        // $request->user()->availabilitySlots()->create($data)
        abort(501, 'Belum dibuat (Modul 2)');
    }

    public function destroy(AvailabilitySlot $slot)
    {
        abort_unless($slot->listener_id === auth()->id(), 403);

        // TODO (Module 2): refuse when $slot->counselingSession exists, else delete.
        abort(501, 'Belum dibuat (Modul 2)');
    }
}
