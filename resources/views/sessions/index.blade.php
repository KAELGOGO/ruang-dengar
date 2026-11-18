@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 860px">
    <h1 class="h3 fw-bold">Sesi saya</h1>

    {{-- TODO (Module 2): nicer layout, upcoming vs past; Module 3/4 add chat and rating buttons. --}}
    <table class="table align-middle">
        <thead>
            <tr><th>Jadwal</th><th>Dengan</th><th>Topik</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($sessions as $sesi)
                @php($lawan = $sesi->user_id === auth()->id() ? $sesi->listener : $sesi->user)
                <tr>
                    <td>{{ $sesi->scheduled_at->format('d M Y H:i') }}</td>
                    <td>{{ $lawan->name }}</td>
                    <td>{{ \App\Models\CounselingSession::TOPICS[$sesi->topic] ?? $sesi->topic }}</td>
                    <td><span class="badge text-bg-secondary">{{ $sesi->status }}</span></td>
                    <td class="text-end">
                        @can('view', $sesi)
                            <a href="{{ route('chat.show', $sesi) }}" class="btn btn-sm btn-primary">Chat</a>
                        @endcan
                        @can('cancel', $sesi)
                            <form method="POST" action="{{ route('sessions.cancel', $sesi) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger">Batalkan</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">Belum ada sesi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
