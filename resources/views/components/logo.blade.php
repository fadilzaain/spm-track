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