@props(['nama'])

@php
    // Satu-satunya tempat daftar ikon (gaya garis, kanvas 24x24).
    // Mau tambah ikon baru? Cukup tambah satu baris di sini, lalu pakai <x-ikon nama="..." />
    $daftarIkon = [
        'dashboard'  => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'laporan'    => '<path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8Z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>',
        'input'      => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'target'     => '<path d="M5 21V4"/><path d="M5 4h11l-2 4 2 4H5"/>',
        'daftar'     => '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/>',
        'centang'    => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'peringatan' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h.01"/>',
        'persen'     => '<path d="M19 5 5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/>',
        'jam'        => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'keluar'     => '<path d="M9 21H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'bulan'      => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>',
        'matahari'   => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'menu'       => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    ];
@endphp

<svg viewBox="0 0 24 24" aria-hidden="true" {{ $attributes->merge(['class' => 'ikon']) }}>{!! $daftarIkon[$nama] ?? '' !!}</svg>