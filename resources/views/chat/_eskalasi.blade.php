{{-- Module 4: escalation button inside the chat page. Module 3 includes this file; Module 4 fills it.
     $sesi = CounselingSession. --}}
@can('escalate', $sesi)
    {{-- TODO (Module 4): "Perlu tindakan lanjut?" form (reason) posting to route('escalations.store', $sesi). --}}
@endcan
