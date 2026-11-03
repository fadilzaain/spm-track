@extends('layouts.app')

@section('judul', 'Laporan IKU')

@section('konten')
    {{-- ===== Kepala halaman + filter + tombol ekspor ===== --}}
    <section class="kepala-halaman">
        <div>
            <h1>Laporan IKU</h1>
            <p class="redup">Indikator Kinerja Utama · Tahun {{ $tahun }}</p>
        </div>

        <div class="aksi-kepala">
            <form method="GET" action="{{ route('iku.laporan') }}" class="filter">
                <x-pilih nama="tahun" label="Tahun" :opsi="array_combine($daftarTahun, $daftarTahun)" :nilai="$tahun" />
            </form>

            {{-- Ekspor mengikuti tahun yang sedang dipilih --}}
            <a class="tombol-utama" href="{{ route('iku.laporan.ekspor', ['tahun' => $tahun]) }}">Ekspor Excel</a>
        </div>
    </section>

    {{-- ===== Tabel IKU ===== --}}
    <section class="panel panel-laporan">
        <header class="panel-kepala">
            <div class="judul-tabel">
                <h2>Realisasi IKU</h2>
                <span class="pil">{{ $jumlahIku }} indikator</span>
            </div>
        </header>

        <p class="redup petunjuk">Laporan IKU: indikator triwulan terisi di Mar, Jun, Sep, dan Des, bulan lainnya bertanda "-". Kotak berwarna sudah terisi.</p>

        @if ($kelompok)
            {{-- Satu area gulir untuk dua arah: header menempel di atas, Kinerja dan Indikator di kiri, Capaian di kanan --}}
            <div class="laporan-gulir">
                <table class="tabel-laporan tabel-iku">
                    <colgroup>
                        <col class="k-ind">
                        <col class="k-std">
                        <col class="k-target">
                        @foreach ($namaBulanPendek as $pendek)
                            <col class="k-bln">
                        @endforeach
                        <col class="k-capai">
                    </colgroup>

                    <thead>
                        <tr>
                            <th class="t-ind">Kinerja</th>
                            <th class="t-std">Indikator</th>
                            <th class="t-target">Target</th>
                            @foreach ($namaBulanPendek as $pendek)
                                <th class="bln">{{ $pendek }}</th>
                            @endforeach
                            <th class="t-capai">Capaian</th>
                        </tr>
                    </thead>

                    {{-- Satu sasaran = satu tbody, sel Kinerja digabung (rowspan) di baris pertamanya --}}
                    @foreach ($kelompok as $g)
                        <tbody>
                            @foreach ($g['indikator'] as $r)
                                <tr class="baris-data {{ $loop->first ? 'awal-indikator' : '' }}">
                                    @if ($loop->first)
                                        <td class="sel-ind" rowspan="{{ count($g['indikator']) }}">{{ $g['nama'] }}</td>
                                    @endif

                                    <td class="sel-std">{{ $r['indikator'] }}</td>
                                    <td class="sel-target">{{ $r['target'] }}</td>

                                    {{-- 12 kotak bulan: bulan bukan bulan lapor = "-", bulan lapor kosong = belum diisi --}}
                                    @foreach ($r['bulan'] as $m => $c)
                                        <td class="bln {{ $c['libur'] ? 'libur' : ($c['nilai'] !== '' ? 'terisi' : '') }}"
                                            title="{{ $namaBulanPendek[$m] }}: {{ $c['libur'] ? 'bukan bulan lapor' : ($c['nilai'] !== '' ? $c['nilai'] : 'belum diisi') }}">
                                            {{ $c['libur'] ? '-' : $c['nilai'] }}
                                        </td>
                                    @endforeach

                                    <td class="sel-capai {{ $r['capaian'] !== '' ? 'terisi' : '' }}">{{ $r['capaian'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>
        @else
            <p class="redup">Belum ada indikator IKU.</p>
        @endif
    </section>
@endsection