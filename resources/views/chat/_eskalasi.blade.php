{{-- Module 4: escalation button inside the chat page. Module 3 includes this file; Module 4 fills it.
     $sesi = CounselingSession. --}}
{{-- Not for psychologists: they are the ones cases are handed over to. --}}
@if ($sesi->status !== 'cancelled' && ! auth()->user()->isPsychologist())
    @can('escalate', $sesi)
        @include('escalations._form', ['sesi' => $sesi, 'id' => 'oper-chat', 'mengambang' => true])
    @endcan
@endif
