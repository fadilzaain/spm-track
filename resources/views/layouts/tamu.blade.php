<!DOCTYPE html>
<html lang="id" data-tema="gelap">
<head>
    @include('layouts.kepala')
</head>
<body>
    {{-- Tombol ganti tema di pojok kanan atas (ditangani spm.js) --}}
    <button type="button" class="tombol-ikon tema-pojok" data-ganti-tema title="Ganti tema" aria-label="Ganti tema gelap atau terang">
        <x-ikon nama="bulan" class="ikon-bulan" />
        <x-ikon nama="matahari" class="ikon-matahari" />
    </button>

    @yield('konten')

    @include('layouts.kaki', ['kelas' => 'kaki-tamu'])
</body>
</html>