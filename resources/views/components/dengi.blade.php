{{--
    Dengi, the Ruang Dengar apple: the friendly "receptionist" who greets people before a listener takes over.
    Usage: <x-dengi /> or <x-dengi size="64" :wave="false">Optional speech bubble text</x-dengi>
--}}
@props(['size' => 140, 'wave' => true])

<div {{ $attributes->class(['dengi d-inline-flex align-items-center gap-3']) }}>
    <svg class="dengi-apple" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 200 200" role="img" aria-label="Dengi, si apel Ruang Dengar">
        <ellipse cx="100" cy="190" rx="52" ry="7" fill="#3b3950" opacity=".08"/>
        {{-- leaf and stem --}}
        <path d="M100 56q-3-20 7-32" stroke="#8a6a55" stroke-width="6" fill="none" stroke-linecap="round"/>
        <path class="dengi-leaf" d="M108 34c10-16 32-19 44-9-11 14-31 18-44 9z" fill="#7cc4a4"/>
        {{-- body --}}
        <path d="M100 58C80 40 28 44 28 104c0 50 36 86 72 76 36 10 72-26 72-76 0-60-52-64-72-46z" fill="#ffb3a7"/>
        <path d="M100 58C80 40 28 44 28 104c0 50 36 86 72 76" fill="none" stroke="#f59a8e" stroke-width="3" opacity=".5"/>
        <ellipse cx="62" cy="86" rx="13" ry="8" fill="#fff" opacity=".55" transform="rotate(-30 62 86)"/>
        {{-- face --}}
        <g class="dengi-eyes">
            <ellipse cx="80" cy="112" rx="7" ry="9" fill="#3b3950"/>
            <ellipse cx="120" cy="112" rx="7" ry="9" fill="#3b3950"/>
            <circle cx="82.5" cy="108" r="2.6" fill="#fff"/>
            <circle cx="122.5" cy="108" r="2.6" fill="#fff"/>
        </g>
        <ellipse cx="64" cy="132" rx="11" ry="6.5" fill="#ff8a8a" opacity=".45"/>
        <ellipse cx="136" cy="132" rx="11" ry="6.5" fill="#ff8a8a" opacity=".45"/>
        <path d="M91 130q9 10 18 0" stroke="#3b3950" stroke-width="4.5" fill="none" stroke-linecap="round"/>
        {{-- little waving arm --}}
        @if ($wave)
            <path class="dengi-arm" d="M168 118q16-6 18-24" stroke="#ffb3a7" stroke-width="11" fill="none" stroke-linecap="round"/>
        @endif
    </svg>

    @if ($slot->isNotEmpty())
        <div class="dengi-bubble">{{ $slot }}</div>
    @endif
</div>
