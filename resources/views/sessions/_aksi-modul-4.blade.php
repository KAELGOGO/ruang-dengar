{{-- Module 4: buttons per session in "Sesi saya". $sesi = CounselingSession. --}}
@can('rate', $sesi)
    <a href="{{ route('ratings.create', $sesi) }}" class="btn btn-sm btn-outline-success">Beri rating</a>
@endcan
{{-- Not for psychologists: they are the ones cases are handed over to. --}}
@if ($sesi->status !== 'cancelled' && ! auth()->user()->isPsychologist())
    @can('escalate', $sesi)
        @include('escalations._form', ['sesi' => $sesi, 'id' => 'oper-sesi-'.$sesi->id, 'mengambang' => false])
    @endcan
@endif
