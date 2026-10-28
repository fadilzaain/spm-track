<!DOCTYPE html>
<html lang="id" data-tema="gelap">
<head>
    @include('layouts.kepala')
</head>
<body>
    @use('App\Models\User')

    @php
        $pengguna  = auth()->user();
        $namaTampil = $pengguna->nama ?: $pengguna->username;

        // Daftar menu dibuat satu kali di sini, lalu digambar lewat perulangan (tidak ditulis berulang).
        // rute = nama route, cocok = pola route yang menandai menu aktif, lencana = angka kecil di kanan menu (opsional)
        $menu = [
            'Pemantauan' => [
                ['rute' => 'dashboard',    'cocok' => 'dashboard',     'nama' => 'Dashboard',   'ikon' => 'dashboard'],
                ['rute' => 'laporan',      'cocok' => 'laporan*',      'nama' => 'Laporan',     'ikon' => 'laporan'],
            ],
            'Pengisian data' => [
                ['rute' => 'input.nilai',  'cocok' => 'input.nilai*',  'nama' => 'Input Nilai', 'ikon' => 'input'],
                ['rute' => 'pencapaian',   'cocok' => 'pencapaian*',   'nama' => 'Pencapaian',  'ikon' => 'target'],
            ],
        ];

        // Menu khusus admin: lencananya menunjukkan jumlah pendaftar yang menunggu persetujuan
        if ($pengguna->isAdmin()) {
            $menu['Administrasi'] = [
                ['rute' => 'pengguna', 'cocok' => 'pengguna*', 'nama' => 'Kelola Pengguna', 'ikon' => 'pengguna',
                 'lencana' => User::where('status', User::STATUS_MENUNGGU)->count()],
            ];
        }

        // Menu akun untuk semua role
        $menu['Akun'] = [
            ['rute' => 'akun.password', 'cocok' => 'akun.password*', 'nama' => 'Ganti Password', 'ikon' => 'kunci'],
        ];

        // Nomor urut dipakai untuk jeda animasi masuk tiap item sidebar (muncul satu per satu)
        $urut = 0;
    @endphp

    {{-- Checkbox penyimpan status buka/tutup sidebar untuk layar sentuh dan keyboard (tanpa JavaScript) --}}
    <input type="checkbox" id="sidebar-toggle" class="sidebar-toggle" aria-label="Buka atau tutup menu samping">
    <label for="sidebar-toggle" class="tombol-menu" title="Menu"><x-ikon nama="menu" /></label>

    {{-- Zona sensor di tepi kiri layar: mouse masuk ke sini, sidebar muncul --}}
    <div class="sidebar-zona" aria-hidden="true"></div>

    <aside class="sidebar" aria-label="Menu samping">
        <a class="merek masuk" style="--i: {{ $urut++ }}" href="{{ route('dashboard') }}">
            <x-logo />
            <span class="merek-teks">
                <strong>SPM Track</strong>
                <small>RSUD Kabupaten Jombang</small>
            </span>
        </a>

        @foreach ($menu as $grup => $butir)
            <nav class="menu" aria-label="{{ $grup }}">
                <p class="sidebar-grup masuk" style="--i: {{ $urut++ }}">{{ $grup }}</p>

                @foreach ($butir as $m)
                    @php $aktif = request()->routeIs($m['cocok']); @endphp
                    <a class="masuk {{ $aktif ? 'aktif' : '' }}" style="--i: {{ $urut++ }}"
                       href="{{ route($m['rute']) }}" @if ($aktif) aria-current="page" @endif>
                        <span class="ikon-kotak"><x-ikon :nama="$m['ikon']" /></span>
                        {{ $m['nama'] }}
                        @if (! empty($m['lencana']))
                            <span class="pil nada-warn lencana">{{ $m['lencana'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        @endforeach

        <div class="sidebar-bawah masuk" style="--i: {{ $urut++ }}">
            {{-- Tombol ganti tema gelap/terang (ditangani spm.js) --}}
            <div class="menu">
                <button type="button" data-ganti-tema>
                    <span class="ikon-kotak">
                        <x-ikon nama="bulan" class="ikon-bulan" />
                        <x-ikon nama="matahari" class="ikon-matahari" />
                    </span>
                    <span>Tema <small class="tema-nama"></small></span>
                </button>
            </div>

            <div class="pengguna">
                <span class="avatar">{{ mb_strtoupper(mb_substr($namaTampil, 0, 2)) }}</span>
                <span class="pengguna-info">
                    <strong>{{ $namaTampil }}</strong>
                    <small>{{ User::LABEL_ROLE[$pengguna->role] ?? $pengguna->role }}</small>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="tombol-ikon" title="Keluar" aria-label="Keluar">
                        <x-ikon nama="keluar" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Tirai gelap di belakang sidebar (hanya saat dibuka lewat tombol menu): klik untuk menutup --}}
    <label for="sidebar-toggle" class="sidebar-tirai" aria-hidden="true"></label>

    <main class="halaman">
        @yield('konten')
    </main>
</body>
</html>