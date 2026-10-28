@extends('layouts.app')

@section('judul', 'Kelola Pengguna')

@section('konten')
    @use('App\Models\User')

    @php
        $saya = auth()->user();

        // Warna badge status dan tombol yang tampil untuk tiap status (satu daftar, digambar lewat perulangan)
        $badge = ['menunggu' => 'badge-warn', 'aktif' => 'badge-ok', 'ditolak' => 'badge-bad', 'nonaktif' => ''];
        $tombol = [
            'menunggu' => [['setujui', 'Setujui', 'tombol-utama'], ['tolak', 'Tolak', 'tombol-garis bahaya']],
            'aktif'    => [['simpan', 'Simpan', 'tombol-utama'], ['nonaktifkan', 'Nonaktifkan', 'tombol-garis bahaya']],
            'ditolak'  => [['setujui', 'Setujui', 'tombol-utama']],
            'nonaktif' => [['setujui', 'Aktifkan', 'tombol-utama']],
        ];
    @endphp

    {{-- ===== Kepala halaman + filter status ===== --}}
    <section class="kepala-halaman">
        <div>
            <h1>Kelola Pengguna</h1>
            <p class="redup">Setujui pendaftar baru, atur peran dan unit, atau nonaktifkan akun.</p>
        </div>

        <form method="GET" action="{{ route('pengguna') }}" class="filter">
            <x-pilih nama="status" label="Status" :opsi="$opsiStatus" :nilai="$status" />
        </form>
    </section>

    {{-- ===== Pesan ===== --}}
    @if (session('status'))
        <div class="pesan pesan-ok">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="pesan pesan-bad">{{ $errors->first() }}</div>
    @endif

    {{-- ===== Daftar akun: satu form per akun ===== --}}
    <section class="panel">
        <p class="redup petunjuk">
            Petugas Unit mengisi data unitnya sendiri, Manajemen hanya melihat, Admin mengelola semuanya.
            Kolom unit hanya dipakai untuk Petugas Unit.
        </p>

        <div class="pengguna-daftar">
            @forelse ($daftarPengguna as $p)
                @php $dirinya = $p->is($saya); @endphp

                <form method="POST" action="{{ route('pengguna.perbarui', $p) }}" class="pengguna-baris" style="--i: {{ $loop->index }}">
                    @csrf
                    @method('PATCH')

                    <div class="pb-info">
                        <span class="avatar">{{ mb_strtoupper(mb_substr($p->nama ?: $p->username, 0, 2)) }}</span>
                        <div>
                            <strong>{{ $p->nama ?: $p->username }}</strong>
                            <small>{{ $p->username }} · daftar {{ $p->created_at?->format('d/m/Y') ?? '-' }}</small>
                        </div>
                    </div>

                    <span class="badge {{ $badge[$p->status] ?? '' }}">{{ User::LABEL_STATUS[$p->status] ?? $p->status }}</span>

                    <select name="role" class="input-teks sel-role" aria-label="Peran" @disabled($dirinya)>
                        @foreach (User::LABEL_ROLE as $kode => $label)
                            <option value="{{ $kode }}" @selected($p->role === $kode)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <select name="layanan" class="input-teks sel-unit" aria-label="Unit pelayanan" @disabled($dirinya)>
                        <option value="">Tanpa unit</option>
                        @foreach ($daftarLayanan as $id => $nama)
                            <option value="{{ $id }}" @selected((int) $p->jenis_pelayanan_id === $id)>{{ $nama }}</option>
                        @endforeach
                    </select>

                    <div class="pb-aksi">
                        @if ($dirinya)
                            <small>Ini akun Anda</small>
                        @else
                            @foreach ($tombol[$p->status] ?? [] as [$aksi, $teks, $kelas])
                                <button type="submit" name="aksi" value="{{ $aksi }}" class="{{ $kelas }}">{{ $teks }}</button>
                            @endforeach
                        @endif
                    </div>
                </form>
            @empty
                <p class="redup">Tidak ada akun dengan status ini.</p>
            @endforelse
        </div>
    </section>
@endsection