{{-- Module 4: buttons per session in "Sesi saya". $sesi = CounselingSession. --}}
@can('rate', $sesi)
    <a href="{{ route('ratings.create', $sesi) }}" class="btn btn-sm btn-outline-success">Beri rating</a>
@endcan
{{-- TODO (Module 4): "Perlu tindakan lanjut?" button for the listener (@can('escalate', $sesi)), with a reason field. --}}
