{{-- Ikon SVG inline (tanpa library atau font eksternal, jadi aman untuk CSP). Dipanggil dengan nama ikon dan ukuran opsional. Ikon bersifat dekoratif (aria-hidden), jadi beri teks atau aria-label pada tombol/link pembungkusnya. --}}
@props(['name', 'size' => 20])
<svg {{ $attributes->merge(['class' => 'icon']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24"
     fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true" focusable="false">
@switch($name)
    @case('home')
        <path d="M4 11.5 12 4l8 7.5"/><path d="M6 10v10h12V10"/><path d="M10 20v-5h4v5"/>
        @break
    @case('receipt')
        <path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>
        @break
    @case('tag')
        <path d="M3 12V4h8l9 9-8 8z"/><circle cx="7.5" cy="8.5" r="1"/>
        @break
    @case('chart')
        <path d="M4 20V11M10 20V4M16 20v-6M3 20h18"/>
        @break
    @case('plus')
        <path d="M12 5v14M5 12h14"/>
        @break
    @case('logout')
        <path d="M10 4H5v16h5"/><path d="M15 8l4 4-4 4M19 12H9"/>
        @break
    @case('key')
        <circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M16 7l3 3"/>
        @break
    @case('users')
        <circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.5 3-6 6.5-6s6.5 2.5 6.5 6"/><path d="M16 4.8a3.5 3.5 0 0 1 0 6.4M18 14.5c2 .8 3.5 2.8 3.5 5.5"/>
        @break
    @case('activity')
        <path d="M3 12h4l3-8 4 16 3-8h4"/>
        @break
    @case('shield')
        <path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/>
        @break
    @case('download')
        <path d="M12 4v11M7 11l5 5 5-5M5 20h14"/>
        @break
    @case('search')
        <circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>
        @break
    @case('edit')
        <path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13.5 6.5l4 4"/>
        @break
    @case('trash')
        <path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>
        @break
    @case('eye')
        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>
        @break
    @case('eye-off')
        <path d="M10.6 5.1A9.7 9.7 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3 3.9M6.6 6.6A16.5 16.5 0 0 0 2 12s3.5 7 10 7c1.7 0 3.2-.4 4.5-1"/><path d="M3 3l18 18"/>
        @break
    @case('arrow-up')
        <path d="M12 19V5M6 11l6-6 6 6"/>
        @break
    @case('arrow-down')
        <path d="M12 5v14M6 13l6 6 6-6"/>
        @break
    @case('chevron-left')
        <path d="M15 5l-7 7 7 7"/>
        @break
    @case('chevron-right')
        <path d="M9 5l7 7-7 7"/>
        @break
    @case('check')
        <path d="M5 12.5l4.5 4.5L19 7.5"/>
        @break
    @case('x')
        <path d="M6 6l12 12M18 6L6 18"/>
        @break
    @case('alert')
        <circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/>
        @break
    @case('wallet')
        <path d="M4 7h14a2 2 0 0 1 2 2v10H6a2 2 0 0 1-2-2z"/><path d="M4 7V6a2 2 0 0 1 2-2h10"/><circle cx="16" cy="13.5" r="1"/>
        @break
    @case('more')
        <circle cx="5" cy="12" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="19" cy="12" r="1.2"/>
        @break
    @case('k')
        <path d="M7.5 4v16M7.5 12.5L16.5 4M11 10l6.5 10" stroke-width="2.4"/>
        @break
@endswitch
</svg>
