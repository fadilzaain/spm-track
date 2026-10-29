@extends('layouts.app')

@section('judul', 'Laporan')

@section('konten')
    @php
        // Pencapaian tahunan yang belum terisi menentukan teks dan warna badge kartu ketiga
        $belumCapai = $ringkasan['standar'] - $ringkasan['pencapaianTerisi'];

        // Tiga kartu dibuat dari satu daftar lalu digambar lewat <x-kartu-kpi> (komponen yang sama dengan dashboard)
        $kartu = [
            ['label' => 'Total standar', 'angka' => $ringkasan['standar'], 'ikon' => 'daftar', 'nada' => 'info',
             'badge' => $ringkasan['pelayanan'].' jenis pelayanan', 'badgeKelas' => ''],
            ['label' => 'Periode terisi', 'angka' => number_format($ringkasan['persen'], 1, ',', '.').'%', 'ikon' => 'persen', 'nada' => 'utama',
             'badge' => $ringkasan['periodeTerisi'].' dari '.$ringkasan['periodeTotal'].' periode setahun', 'badgeKelas' => '',
             'bar' => $ringkasan['persen']],
            ['label' => 'Pencapaian tahunan', 'angka' => $ringkasan['pencapaianTerisi'].'/'.$ringkasan['standar'], 'ikon' => 'target', 'nada' => 'ok',
             'badge' => $belumCapai > 0 ? $belumCapai.' belum terisi' : 'semua terisi', 'badgeKelas' => $belumCapai > 0 ? 'badge-bad' : 'badge-ok'],
        ];

        // Jumlah kolom tabel: Indikator + Standar + 12 bulan + Pencapaian
        $jumlahKolom = 15;
    @endphp

    {{-- ===== Kepala halaman + filter + tombol ekspor ===== --}}
    <section class="kepala-halaman">
        <div>
            <h1>Laporan Rekap SPM</h1>
            <p class="redup">{{ $namaLayanan }} · Tahun {{ $tahun }}</p>
        </div>

        <div class="aksi-kepala">
            <form method="GET" action="{{ route('laporan') }}" class="filter">
                <x-pilih nama="tahun" label="Tahun" :opsi="array_combine($daftarTahun, $daftarTahun)" :nilai="$tahun" />
                <x-pilih nama="layanan" label="Pelayanan" :opsi="[0 => 'Semua pelayanan'] + $daftarLayanan" :nilai="$layananId" />
            </form>

            {{-- Ekspor mengikuti filter yang sedang dipilih --}}
            <a class="tombol-utama" href="{{ route('laporan.ekspor', ['tahun' => $tahun, 'layanan' => $layananId]) }}">Ekspor Excel</a>
        </div>
    </section>

    {{-- ===== Ringkasan ===== --}}
    <section class="kpi-grid kpi-tiga">
        @foreach ($kartu as $k)
            <x-kartu-kpi :k="$k" />
        @endforeach
    </section>

    {{-- ===== Tabel rekap per pelayanan ===== --}}
    <section class="panel panel-laporan">
        <header class="panel-kepala">
            <div class="judul-tabel">
                <h2>Rekap per pelayanan</h2>
                <span class="pil">{{ $ringkasan['standar'] }} standar</span>
            </div>

            @if (count($kelompok) > 1)
                <div class="aksi-lipat">
                    <button type="button" class="tombol-teks" data-lipat-semua="buka">Buka semua</button>
                    <button type="button" class="tombol-teks" data-lipat-semua="tutup">Tutup semua</button>
                </div>
            @endif
        </header>

        <p class="redup petunjuk">Kotak berwarna sudah terisi. Standar triwulan tampil sebagai 4 kotak lebar (TW1 sampai TW4), standar tahunan sebagai satu kotak penuh.</p>

        @if ($kelompok)
            {{-- Satu area gulir untuk dua arah: header kolom menempel di atas, kolom Indikator dan Standar menempel di kiri, Pencapaian di kanan --}}
            <div class="laporan-gulir">
                <table class="tabel-laporan">
                    <colgroup>
                        <col class="k-ind">
                        <col>
                        @foreach ($namaBulanPendek as $pendek)
                            <col class="k-bln">
                        @endforeach
                        <col class="k-capai">
                    </colgroup>

                    <thead>
                        <tr>
                            <th class="t-ind">Indikator</th>
                            <th class="t-std">Standar</th>
                            @foreach ($namaBulanPendek as $pendek)
                                <th class="bln">{{ $pendek }}</th>
                            @endforeach
                            <th class="t-capai">Pencapaian</th>
                        </tr>
                    </thead>

                    @foreach ($kelompok as $g)
                        {{-- Satu pelayanan = satu tbody. Dengan satu pelayanan dipilih langsung terbuka, kalau semua hanya yang pertama --}}
                        @php $terbuka = $layananId > 0 || $loop->first; @endphp

                        <tbody class="grup-laporan {{ $terbuka ? '' : 'tutup' }}">
                            <tr class="grup-baris">
                                <td colspan="{{ $jumlahKolom }}">
                                    <button type="button" class="grup-isi" data-lipat-grup aria-expanded="{{ $terbuka ? 'true' : 'false' }}">
                                        <span class="panah" aria-hidden="true"></span>
                                        <span class="grup-nama">{{ $g['nama'] }}</span>
                                        <span class="pil">{{ $g['jumlah'] }} standar</span>
                                        <span class="bar-trek grup-bar">
                                            <span class="bar-isi bar-{{ $g['status'] }}" style="width: {{ number_format($g['persen'], 1, '.', '') }}%"></span>
                                        </span>
                                        <span class="grup-persen">{{ number_format($g['persen'], 0, ',', '.') }}%</span>
                                    </button>
                                </td>
                            </tr>

                            @foreach ($g['indikator'] as $i)
                                @foreach ($i['baris'] as $r)
                                    <tr class="baris-data {{ $loop->first ? 'awal-indikator' : '' }}">
                                        {{-- Indikator digabung (rowspan) di baris pertamanya, tidak ditulis ulang per standar --}}
                                        @if ($loop->first)
                                            <td class="sel-ind" rowspan="{{ $i['jumlah'] }}">{{ $i['nama'] }}</td>
                                        @endif

                                        <td class="sel-std">{{ $r['standar'] }}</td>

                                        {{-- Kotak sesuai frekuensi: bulanan 12 kotak, triwulan 4 kotak, tahunan 1 kotak --}}
                                        @foreach ($r['sel'] as $c)
                                            <td class="bln {{ $c['nilai'] !== '' ? 'terisi' : '' }}" colspan="{{ $c['span'] }}"
                                                title="{{ $c['label'] }}: {{ $c['nilai'] !== '' ? $c['nilai'] : 'belum diisi' }}">
                                                @if ($c['span'] > 1)
                                                    <span class="cap">{{ $c['label'] }}</span>
                                                @endif
                                                {{ $c['nilai'] }}
                                            </td>
                                        @endforeach

                                        <td class="sel-capai {{ $r['pencapaian'] !== '' ? 'terisi' : '' }}">{{ $r['pencapaian'] }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>
        @else
            <p class="redup">Belum ada standar.</p>
        @endif
    </section>
@endsection