@extends('layouts.app')

@section('judul', 'Ganti Password')

@section('konten')
    @php $wajib = (bool) auth()->user()->wajib_ganti_password; @endphp

    <section class="kepala-halaman">
        <div>
            <h1>Ganti Password</h1>
            <p class="redup">Password baru dipakai untuk login berikutnya.</p>
        </div>
    </section>

    @if (session('status'))
        <div class="pesan pesan-ok">{{ session('status') }}</div>
    @endif

    @if ($wajib)
        <div class="pesan nada-warn">
            Password Anda adalah password sementara dari admin. Ganti dulu sebelum memakai menu lain.
        </div>
    @endif

    <section class="panel akun-kartu">
        <form method="POST" action="{{ route('akun.password.simpan') }}">
            @csrf
            @method('PUT')

            <x-isian nama="password_lama" label="Password lama" ikon="kunci" tipe="password" auto="current-password"
                     :aktif="true" autofocus />
            <x-isian nama="password" label="Password baru" ikon="kunci" tipe="password" auto="new-password"
                     :aktif="true" />
            <x-isian nama="password_confirmation" label="Ulangi password baru" ikon="kunci" tipe="password" auto="new-password"
                     :aktif="true" />

            <p class="redup">Minimal 8 karakter dan harus berbeda dari password lama.</p>

            <div class="akun-aksi">
                <button type="submit" class="tombol-utama">Simpan password</button>
                @unless ($wajib)
                    <a href="{{ route('dashboard') }}" class="tombol-garis">Kembali ke dashboard</a>
                @endunless
            </div>
        </form>
    </section>
@endsection