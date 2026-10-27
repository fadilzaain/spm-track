@extends('layouts.app')

@section('judul', 'Laporan')

@section('konten')
    {{-- ===== Kepala halaman + filter + tombol ekspor ===== --}}
    <section class="kepala-halaman">
        <div>
            <h1>Laporan Rekap SPM</h1>
            <p class="redup">{{ $namaLayanan }} · Tahun {{ $tahun }}</p>
        </div>

        <div class="aksi-kepala">
            <form method="GET" action="{{ route('laporan') }}" class="filter">
                <label>
                    <span>Tahun</span>
                    <select name="tahun" onchange="this.form.submit()">
                        @foreach ($daftarTahun as $t)
                            <option value="{{ $t }}" @selected($t === $tahun)>{{ $t }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Pelayanan</span>
                    <select name="layanan" onchange="this.form.submit()">
                        <option value="0">Semua pelayanan</option>
                        @foreach ($daftarLayanan as $id => $nama)
                            <option value="{{ $id }}" @selected($id === $layananId)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </label>
            </form>

            {{-- Ekspor mengikuti filter yang sedang dipilih --}}
            <a class="tombol-utama" href="{{ route('laporan.ekspor', ['tahun' => $tahun, 'layanan' => $layananId]) }}">Ekspor Excel</a>
        </div>
    </section>

    {{-- ===== Tabel rekap ===== --}}
    <section class="panel">
        <p class="redup petunjuk">Standar yang dilaporkan per triwulan hanya terisi di bulan penutup triwulan (Maret, Juni, September, Desember).</p>

        <div class="tabel-gulir">
            <table class="tabel tabel-lebar">
                <thead>
                    <tr>
                        <th>Pelayanan</th>
                        <th>Indikator</th>
                        <th>Standar</th>
                        @foreach ($namaBulanPendek as $pendek)
                            <th class="bln">{{ $pendek }}</th>
                        @endforeach
                        <th class="bln">Pencapaian</th>
                    </tr>
                </thead>
                <tbody>
                    @php $layananLalu = null; $indikatorLalu = null; @endphp
                    @forelse ($baris as $r)
                        <tr class="{{ $r['layanan_id'] !== $layananLalu ? 'baris-awal' : '' }}">
                            {{-- Nama pelayanan dan indikator hanya ditulis di baris pertama kelompoknya --}}
                            <td class="sel-layanan">{{ $r['layanan_id'] !== $layananLalu ? $r['layanan'] : '' }}</td>
                            <td>{{ $r['indikator_id'] !== $indikatorLalu ? $r['indikator'] : '' }}</td>
                            <td>{{ $r['standar'] }}</td>
                            @foreach ($r['bulan'] as $nilai)
                                <td class="bln">{{ $nilai }}</td>
                            @endforeach
                            <td class="bln pencapaian">{{ $r['pencapaian'] }}</td>
                        </tr>
                        @php
                            $layananLalu = $r['layanan_id'];
                            $indikatorLalu = $r['indikator_id'];
                        @endphp
                    @empty
                        <tr><td colspan="16" class="redup">Belum ada standar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection