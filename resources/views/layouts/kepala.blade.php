{{-- Isi <head> yang dipakai bersama layout aplikasi (app) dan layout tamu (login/daftar) --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('judul', 'SPM Track') · RSUD Kabupaten Jombang</title>

{{-- Pasang tema tersimpan sebelum halaman digambar, supaya tidak berkedip --}}
<script>try { document.documentElement.dataset.tema = localStorage.getItem('tema') || 'gelap'; } catch (e) {}</script>

{{-- ?v=... mencegah browser memakai CSS/JS lama dari cache --}}
@foreach (['css/spm.css', 'css/akses.css'] as $css)
    <link rel="stylesheet" href="{{ asset($css) }}?v={{ filemtime(public_path($css)) }}">
@endforeach
<script src="{{ asset('js/spm.js') }}?v={{ filemtime(public_path('js/spm.js')) }}" defer></script>