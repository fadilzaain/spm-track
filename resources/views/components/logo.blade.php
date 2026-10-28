{{--
    Logo RSUD, satu-satunya tempat yang mengurus logo (dipakai sidebar dan halaman login).
    Taruh filenya di public/img dengan nama logo-rsud-jombang.svg / .png / .webp / .jpg (dicari berurutan).
    Kalau belum ada filenya, otomatis tampil kotak cadangan bertuliskan RSUD.
    Ukuran diatur lewat class: <x-logo /> (kecil, untuk sidebar) atau <x-logo class="logo-besar" />.
--}}
@php
    $berkas = collect(['svg', 'png', 'webp', 'jpg'])
        ->map(fn ($ekstensi) => "img/logo-rsud-jombang.$ekstensi")
        ->first(fn ($path) => file_exists(public_path($path)));
@endphp

@if ($berkas)
    <img src="{{ asset($berkas) }}" alt="Logo RSUD Kabupaten Jombang" {{ $attributes->merge(['class' => 'logo']) }}>
@else
    <span {{ $attributes->merge(['class' => 'logo merek-tanda']) }}>RSUD</span>
@endif