@extends('layouts.app')

@section('judul', 'SPM TRACK')

@section('konten')
    @php
        $pengguna = auth()->user();

        // Sapaan mengikuti jam server (zona waktu Asia/Jakarta di config/app.php)
        $jam = now()->hour;
        $salam = match (true) {
            $jam < 11 => 'Selamat pagi',
            $jam < 15 => 'Selamat siang',
            $jam < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        // Hanya admin dan petugas unit yang boleh mengisi, jadi tombol "Isi nilai" hanya untuk mereka
        $bisaIsi = in_array($pengguna->role, ['admin', 'petugas_unit'], true);

        // Tahun dan bulan dibawa ke semua tautan supaya periode yang dipilih tidak hilang
        $periode = ['tahun' => $tahun, 'bulan' => $bulan];

        // Teks dan warna badge selisih kelengkapan dari bulan lalu
        $teksSelisih = is_null($selisih)
            ? 'awal tahun'
            : ($selisih >= 0 ? '+' : '').number_format($selisih, 1, ',', '.').' poin vs bulan lalu';
        $badgeSelisih = is_null($selisih) ? '' : ($selisih >= 0 ? 'badge-ok' : 'badge-bad');

        // Empat kartu KPI dibuat dari satu daftar ini lalu digambar lewat perulangan
        $kpi = [
            ['label' => 'Total standar', 'angka' => $totalStandar, 'ikon' => 'daftar', 'nada' => 'info',
             'badge' => count($layanan).' jenis pelayanan', 'badgeKelas' => ''],
            ['label' => 'Sudah terisi', 'angka' => $totalTerisi, 'ikon' => 'centang', 'nada' => 'ok',
             'badge' => $jmlLengkap.' pelayanan lengkap', 'badgeKelas' => 'badge-ok'],
            ['label' => 'Belum terisi', 'angka' => $totalBelum, 'ikon' => 'peringatan', 'nada' => 'bad',
             'badge' => $jmlBelum.' pelayanan kosong', 'badgeKelas' => 'badge-bad'],
            ['label' => 'Kelengkapan', 'angka' => number_format($persenKini, 1, ',', '.').'%', 'ikon' => 'persen', 'nada' => 'utama',
             'badge' => $teksSelisih, 'badgeKelas' => $badgeSelisih, 'bar' => $persenKini],
        ];

        // Sudut donut dalam persen: lengkap | sebagian | belum
        $n = max(count($layanan), 1);
        $a = round($jmlLengkap / $n * 100, 2);
        $b = round(($jmlLengkap + $jmlSebagian) / $n * 100, 2);

        // Tabel standar dikelompokkan: pelayanan -> indikator -> standar (helper yang sama dengan halaman laporan).
        // Tujuannya supaya sel pelayanan dan indikator bisa digabung (rowspan), tidak diulang atau dikosongkan per baris
        $kelompokTabel = \App\Support\KelompokStandar::susun($tabel);
        $jumlahBarisTabel = count($tabel);
        $jumlahTerisiTabel = collect($tabel)->whereNotNull('nilai')->count();
    @endphp

    {{-- ===== Hero ringkas: sapaan, jam, filter periode, tombol isi nilai ===== --}}
    <section class="kepala-halaman kepala-ringkas">
        <div>
            <h1>{{ $salam }}, <span class="nama">{{ $pengguna->nama ?: $pengguna->username }}</span></h1>
            <p class="redup">Kelengkapan pengisian Standar Pelayanan Minimal · {{ $namaBulan[$bulan] }} {{ $tahun }}</p>
        </div>

        <div class="aksi-kepala">
            <form method="GET" action="{{ route('dashboard') }}" class="filter">
                <span class="chip-jam"><x-ikon nama="jam" /><time data-jam>--</time></span>
                <x-pilih nama="tahun" label="Tahun" :opsi="array_combine($daftarTahun, $daftarTahun)" :nilai="$tahun" />
                <x-pilih nama="bulan" label="Bulan" :opsi="$namaBulan" :nilai="$bulan" />
            </form>

            @if ($bisaIsi && $jmlLengkap < count($layanan))
                <a class="tombol-utama" href="{{ route('input.nilai', $periode) }}">Isi nilai {{ $namaBulan[$bulan] }}</a>
            @endif
        </div>
    </section>

    {{-- ===== Baris ringkasan: 4 kartu KPI + 1 kartu komposisi status (donut kecil) ===== --}}
    <section class="kpi-grid">
        @foreach ($kpi as $k)
            <x-kartu-kpi :k="$k" />
        @endforeach

        <article class="kartu kartu-komposisi">
            <span class="kartu-label">Komposisi status pelayanan</span>

            <div class="komposisi-isi">
                <div class="donut" style="--a: {{ $a }}%; --b: {{ $b }}%">
                    <div class="donut-isi">
                        <strong>{{ $jmlLengkap }}</strong>
                        <span>dari {{ count($layanan) }}</span>
                    </div>
                </div>

                <ul class="legenda">
                    <li><i class="titik bar-lengkap"></i> Lengkap <b>{{ $jmlLengkap }}</b></li>
                    <li><i class="titik bar-sebagian"></i> Sebagian <b>{{ $jmlSebagian }}</b></li>
                    <li><i class="titik bar-belum"></i> Belum diisi <b>{{ $jmlBelum }}</b></li>
                </ul>
            </div>
        </article>
    </section>

    {{-- ===== Baris utama: bar kelengkapan (kiri) dan peta kelengkapan setahun (kanan), tinggi barisnya dibuat sama ===== --}}
    <section class="baris-utama">
        <article class="panel panel-matriks">
            <header class="panel-kepala">
                <h2>Kelengkapan per pelayanan</h2>
                <span class="redup">Klik nama untuk melihat standarnya</span>
            </header>

            <div class="tabel-gulir">
                <div class="matriks matriks-bar">
                    <div class="matriks-judul">
                        <span>Pelayanan</span>
                        <span>Progres {{ $namaBulan[$bulan] }}</span>
                        <span class="rata-kanan">Terisi</span>
                    </div>

                    @foreach ($layanan as $l)
                        <div class="matriks-baris">
                            {{-- Klik nama: tabel di bawah langsung difilter ke pelayanan ini --}}
                            <a class="matriks-label" title="{{ $l['nama'] }}"
                               href="{{ route('dashboard', $periode + ['layanan' => $l['id']]) }}#tabel">{{ $l['nama'] }}</a>
                            <span class="bar-trek">
                                <span class="bar-isi bar-{{ $l['status'] }}" style="width: {{ number_format($l['persen'], 1, '.', '') }}%"></span>
                            </span>
                            <span class="bar-nilai">{{ $l['terisi'] }}/{{ $l['total'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="panel panel-matriks">
            <header class="panel-kepala">
                <h2>Peta kelengkapan {{ $tahun }}</h2>

                {{-- Skala warna heatmap: makin pekat makin lengkap --}}
                <span class="skala"><small>0%</small><i></i><small>100%</small></span>
            </header>

            <div class="tabel-gulir">
                <div class="matriks matriks-heat">
                    <div class="matriks-judul">
                        <span>Pelayanan</span>
                        @foreach ($namaBulanPendek as $no => $pendek)
                            <span class="{{ $no === $bulan ? 'aktif' : '' }}">{{ $pendek }}</span>
                        @endforeach
                    </div>

                    @foreach ($layanan as $l)
                        <div class="matriks-baris">
                            <span class="matriks-label" title="{{ $l['nama'] }}">{{ $l['nama'] }}</span>
                            @for ($m = 1; $m <= 12; $m++)
                                @php $p = $heatmap[$l['id']][$m] ?? 0; @endphp
                                <span class="heat-sel {{ $m === $bulan ? 'aktif' : '' }}"
                                      style="--p: {{ number_format($p, 2, '.', '') }}"
                                      @if ($p >= 0.6) data-tinggi @endif
                                      title="{{ $l['nama'] }} · {{ $namaBulan[$m] }}: {{ round($p * 100) }}%">{{ $p > 0 ? round($p * 100) : '' }}</span>
                            @endfor
                        </div>
                    @endforeach
                </div>
            </div>
        </article>
    </section>

    {{-- ===== Tabel standar + nilai bulan terpilih ===== --}}
    <section class="panel panel-tabel" id="tabel">
        <header class="panel-kepala">
            <div class="judul-tabel">
                <h2>Daftar standar dan nilai {{ $namaBulan[$bulan] }} {{ $tahun }}</h2>
                <span class="pil">{{ $jumlahBarisTabel }} standar</span>
                <span class="pil badge-ok">{{ $jumlahTerisiTabel }} terisi</span>
            </div>

            {{-- Filter pelayanan khusus tabel, tahun dan bulan ikut dibawa --}}
            <form method="GET" action="{{ route('dashboard') }}#tabel" class="filter">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="bulan" value="{{ $bulan }}">
                <x-pilih nama="layanan" label="Pelayanan" :opsi="[0 => 'Semua pelayanan'] + $namaLayanan" :nilai="$layananId" />
            </form>
        </header>

        {{-- Satu area gulir saja: tingginya mengikuti tinggi layar, header kolom tetap menempel di atas --}}
        <div class="tabel-spm-gulir">
            <table class="tabel-spm">
                <colgroup>
                    <col class="k-layanan">
                    <col class="k-indikator">
                    <col>
                    <col class="k-frekuensi">
                    <col class="k-nilai">
                </colgroup>

                <thead>
                    <tr>
                        <th>Pelayanan</th>
                        <th>Indikator</th>
                        <th>Standar</th>
                        <th>Frekuensi</th>
                        <th>Nilai</th>
                    </tr>
                </thead>

                @forelse ($kelompokTabel as $g)
                    <tbody>
                        @foreach ($g['indikator'] as $i)
                            @foreach ($i['baris'] as $r)
                                @php
                                    // Baris pertama pelayanan = baris pertama indikator pertama
                                    $awalLayanan = $loop->parent->first && $loop->first;
                                @endphp

                                {{-- awal-layanan = garis tebal pemisah pelayanan, awal-indikator = garis sedang pemisah indikator --}}
                                <tr class="{{ $awalLayanan ? 'awal-layanan' : ($loop->first ? 'awal-indikator' : '') }}">
                                    @if ($awalLayanan)
                                        <td class="sel-layanan-grup" rowspan="{{ $g['jumlah'] }}">{{ $g['nama'] }}</td>
                                    @endif

                                    @if ($loop->first)
                                        <td class="sel-indikator-grup" rowspan="{{ $i['jumlah'] }}">{{ $i['nama'] }}</td>
                                    @endif

                                    <td>{{ $r['standar'] }}</td>
                                    <td><span class="pil">{{ ucfirst($r['frekuensi']) }}</span></td>
                                    <td>
                                        @if (! is_null($r['nilai']))
                                            <span class="nilai">{{ $r['nilai'] }}</span>
                                        @else
                                            <span class="pil pil-kosong">Belum diisi</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                @empty
                    <tbody>
                        <tr><td colspan="5" class="redup">Belum ada standar untuk ditampilkan.</td></tr>
                    </tbody>
                @endforelse
            </table>
        </div>
    </section>
@endsection