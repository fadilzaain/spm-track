<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('judul', 'SPM Track') · RSUD Kabupaten Jombang</title>

{{-- Pasang tema tersimpan sebelum halaman digambar, supaya gak berkedip --}}
<script>try { document.documentElement.dataset.tema = localStorage.getItem('tema') || 'gelap'; } catch (e) {}</script>

{{-- Muat font lebih awal supaya tulisan tidak berganti bentuk saat halaman terbuka --}}
<link rel="preload" href="{{ asset('fonts/ibm-plex-sans-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>

{{-- ?v=... cegah browser memakai CSS/JS lama dari cache --}}
@foreach (['css/spm.css', 'css/dashboard.css', 'css/akses.css', 'css/akun.css'] as $css)
    <link rel="stylesheet" href="{{ asset($css) }}?v={{ filemtime(public_path($css)) }}">
@endforeach
<script src="{{ asset('js/spm.js') }}?v={{ filemtime(public_path('js/spm.js')) }}" defer></script>