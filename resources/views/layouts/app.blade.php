<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'SPM Track') · RSUD Kabupaten Jombang</title>
    <link rel="stylesheet" href="{{ asset('css/spm.css') }}">
</head>
<body>
    @php
        // Nama tampilan user dan label role (role disimpan sebagai admin / petugas_unit / manajemen)
        $pengguna = auth()->user();
        $labelRole = ['admin' => 'Admin', 'petugas_unit' => 'Petugas Unit', 'manajemen' => 'Manajemen'];
    @endphp

    <header class="topbar">
        <div class="topbar-isi">
            <a class="merek" href="{{ route('dashboard') }}">
                <span class="merek-tanda">SPM</span>
                <span class="merek-teks">
                    <strong>SPM Track</strong>
                    <small>RSUD Kabupaten Jombang</small>
                </span>
            </a>

            <nav class="menu">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'aktif' : '' }}">Dashboard</a>
                <a href="{{ route('input.nilai') }}" class="{{ request()->routeIs('input.nilai*') ? 'aktif' : '' }}">Input Nilai</a>
                <a href="{{ route('pencapaian') }}" class="{{ request()->routeIs('pencapaian*') ? 'aktif' : '' }}">Pencapaian</a>
            </nav>

            <div class="pengguna">
                <div class="pengguna-info">
                    <strong>{{ $pengguna->nama ?: $pengguna->username }}</strong>
                    <span class="pil">{{ $labelRole[$pengguna->role] ?? $pengguna->role }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="tombol-garis">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="halaman">
        @yield('konten')
    </main>
</body>
</html>