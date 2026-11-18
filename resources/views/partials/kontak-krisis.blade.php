{{-- Crisis contacts from config/krisis.php. Also meant for the chat page (Module 3). --}}
<ul class="list-group mb-3">
    @foreach (config('krisis.kontak') as $kontak)
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>{{ $kontak['nama'] }}</span>
            <span>
                <strong>{{ $kontak['nomor'] }}</strong>
                @if ($kontak['link'])
                    · <a href="{{ $kontak['link'] }}" target="_blank" rel="noopener">{{ parse_url($kontak['link'], PHP_URL_HOST) }}</a>
                @endif
            </span>
        </li>
    @endforeach
</ul>
