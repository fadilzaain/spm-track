@extends('layouts.tamu')

@section('judul', $tab === 'daftar' ? 'Daftar' : 'Masuk')

@section('konten')
    @php
        // Poin di panel kiri: dibuat dari satu daftar lalu digambar lewat perulangan
        $poin = [
            ['ikon' => 'persen',  'teks' => 'Pantau kelengkapan pengisian SPM tiap unit'],
            ['ikon' => 'input',   'teks' => 'Input nilai bulanan dan pencapaian tahunan'],
            ['ikon' => 'laporan', 'teks' => 'Laporan rekap siap diekspor ke Excel'],
        ];

        // Pilihan peran saat daftar: judul + penjelasan singkat. Admin tidak ada di sini (hanya dibuat oleh admin).
        $peran = [
            'petugas_unit' => ['Petugas Unit', 'Mengisi data unitnya sendiri'],
            'manajemen'    => ['Manajemen', 'Hanya melihat data'],
        ];
        $roleDipilih = $tab === 'daftar' ? old('role', 'petugas_unit') : 'petugas_unit';
    @endphp

    <main class="akses">
        {{-- ===== Panel kiri: identitas ===== --}}
        <section class="akses-brand">
            <span class="orb orb-1"></span>
            <span class="orb orb-2"></span>

            <div class="akses-brand-isi">
                <x-logo class="logo-besar naik-urut" style="--i: 0" />
                <h1 class="naik-urut" style="--i: 1">RSUD <span>Jombang</span></h1>
                <p class="akses-sub naik-urut" style="--i: 2">Web Standar Pelayanan Minimal RS</p>

                <ul class="akses-poin">
                    @foreach ($poin as $i => $p)
                        <li class="naik-urut" style="--i: {{ $i + 3 }}">
                            <span class="ikon-kotak nada-utama"><x-ikon :nama="$p['ikon']" /></span>
                            {{ $p['teks'] }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <small class="akses-kaki">© RSUD Kabupaten Jombang · SPM Track</small>
        </section>

        {{-- ===== Panel kanan: form Masuk / Daftar ===== --}}
        <section class="akses-panel">
            <div class="panel akses-kartu naik-urut" style="--i: 1">
                {{-- Radio penyimpan tab aktif. Perpindahan tab dan animasinya murni CSS (:has), tanpa JavaScript --}}
                <input type="radio" name="tab" id="tab-masuk" class="tab-radio" @checked($tab === 'masuk')>
                <input type="radio" name="tab" id="tab-daftar" class="tab-radio" @checked($tab === 'daftar')>

                <div>
                    <h2>Selamat datang di SPM Track</h2>
                    <p class="redup">Pantau kelengkapan data Standar Pelayanan Minimal.</p>
                </div>

                <div class="tab-bar">
                    <label for="tab-masuk">Masuk</label>
                    <label for="tab-daftar">Daftar</label>
                    <span class="tab-geser"></span>
                </div>

                @if (session('status'))
                    <div class="pesan pesan-ok">{{ session('status') }}</div>
                @endif

                <div class="tab-isi">
                    {{-- ----- Form Masuk ----- --}}
                    <form method="POST" action="{{ route('login.proses') }}" class="tab-form" id="form-masuk">
                        @csrf
                        <x-isian nama="username" label="Username" ikon="pengguna" auto="username"
                                 :aktif="$tab === 'masuk'" autofocus />
                        <x-isian nama="password" label="Password" ikon="kunci" tipe="password" auto="current-password"
                                 :aktif="$tab === 'masuk'" />

                        <label class="centang">
                            <input type="checkbox" name="ingat_saya" value="1"> Ingat saya
                        </label>

                        <button type="submit" class="tombol-utama blok">Masuk</button>
                        <p class="akses-alih">Belum punya akun? <label for="tab-daftar">Daftar</label></p>
                    </form>

                    {{-- ----- Form Daftar ----- --}}
                    <form method="POST" action="{{ route('daftar.proses') }}" class="tab-form" id="form-daftar">
                        @csrf
                        <x-isian nama="nama" label="Nama lengkap" ikon="pengguna" auto="name"
                                 bag="daftar" :aktif="$tab === 'daftar'" />
                        <x-isian nama="username" label="Username" ikon="pengguna" auto="username"
                                 bag="daftar" :aktif="$tab === 'daftar'" />

                        <div class="isian">
                            <span class="isian-label">Daftar sebagai</span>
                            <div class="segmen" role="radiogroup">
                                @foreach ($peran as $kode => [$judul, $ket])
                                    <input type="radio" name="role" id="role-{{ $kode }}" value="{{ $kode }}" @checked($roleDipilih === $kode)>
                                    <label for="role-{{ $kode }}"><strong>{{ $judul }}</strong><small>{{ $ket }}</small></label>
                                @endforeach
                            </div>
                            @if ($tab === 'daftar' && $errors->daftar->has('role'))
                                <span class="isian-galat">{{ $errors->daftar->first('role') }}</span>
                            @endif
                        </div>

                        {{-- Kolom unit hanya relevan untuk Petugas Unit, jadi melipat halus saat Manajemen dipilih --}}
                        <div class="lipat">
                            <div>
                                <x-isian nama="layanan" label="Unit pelayanan" ikon="gedung" :opsi="$daftarLayanan"
                                         kosong="Pilih unit pelayanan" bag="daftar" :aktif="$tab === 'daftar'" />
                            </div>
                        </div>

                        <div class="dua-kolom">
                            <x-isian nama="password" label="Password" ikon="kunci" tipe="password" auto="new-password"
                                     bag="daftar" :aktif="$tab === 'daftar'" />
                            <x-isian nama="password_confirmation" label="Ulangi password" ikon="kunci" tipe="password" auto="new-password"
                                     bag="daftar" :aktif="$tab === 'daftar'" />
                        </div>

                        <p class="redup">Akun baru bisa dipakai setelah disetujui admin.</p>
                        <button type="submit" class="tombol-utama blok">Kirim pendaftaran</button>
                        <p class="akses-alih">Sudah punya akun? <label for="tab-masuk">Masuk</label></p>
                    </form>
                </div>
            </div>
        </section>
    </main>
@endsection