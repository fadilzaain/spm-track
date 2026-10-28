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
            : ($selisih >= 0 ? '+' : '').number_format($selisih, 1, ',', '.').' poin dari bulan lalu';
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
    @endphp

    {{-- ===== Hero: sapaan + jam + filter periode ===== --}}
    <section class="kepala-halaman">
        <div>
            <h1>{{ $salam }}, <span class="nama">{{ $pengguna->nama ?: $pengguna->username }}</span></h1>
            <p class="redup">Kelengkapan pengisian Standar Pelayanan Minimal · {{ $namaBulan[$bulan] }} {{ $tahun }}</p>

            @if ($bisaIsi && $jmlLengkap < count($layanan))
                <a class="tombol-utama" href="{{ route('input.nilai', $periode) }}">Isi nilai {{ $namaBulan[$bulan] }}</a>
            @endif
        </div>

        <form method="GET" action="{{ route('dashboard') }}" class="filter">
            <span class="chip-jam"><x-ikon nama="jam" /><time data-jam>--</time></span>
            <x-pilih nama="tahun" label="Tahun" :opsi="array_combine($daftarTahun, $daftarTahun)" :nilai="$tahun" />
            <x-pilih nama="bulan" label="Bulan" :opsi="$namaBulan" :nilai="$bulan" />
        </form>
    </section>

    {{-- ===== Kartu KPI ===== --}}
    <section class="kpi-grid">
        @foreach ($kpi as $k)
            <article class="kartu nada-{{ $k['nada'] }}">
                <header class="kartu-kepala">
                    <span class="ikon-kotak"><x-ikon :nama="$k['ikon']" /></span>
                    <span class="kartu-label">{{ $k['label'] }}</span>
                </header>

                <strong class="kartu-angka">{{ $k['angka'] }}</strong>

                @isset($k['bar'])
                    <span class="bar-trek"><span class="bar-isi" style="width: {{ number_format($k['bar'], 1, '.', '') }}%"></span></span>
                @endisset

                <span class="badge {{ $k['badgeKelas'] }}">{{ $k['badge'] }}</span>
            </article>
        @endforeach
    </section>

    {{-- ===== Bar kelengkapan per pelayanan + donut komposisi ===== --}}
    <section class="baris-dua">
        <article class="panel">
            <header class="panel-kepala">
                <h2>Kelengkapan per pelayanan</h2>
                <span class="redup">Klik nama pelayanan untuk melihat standarnya</span>
            </header>

            <ul class="bar-list">
                @foreach ($layanan as $l)
                    <li class="bar-baris">
                        {{-- Klik nama: tabel di bawah langsung difilter ke pelayanan ini --}}
                        <a class="bar-label" title="{{ $l['nama'] }}"
                           href="{{ route('dashboard', $periode + ['layanan' => $l['id']]) }}#tabel">{{ $l['nama'] }}</a>
                        <span class="bar-trek">
                            <span class="bar-isi bar-{{ $l['status'] }}" style="width: {{ number_format($l['persen'], 1, '.', '') }}%"></span>
                        </span>
                        <span class="bar-nilai">{{ $l['terisi'] }}/{{ $l['total'] }}</span>
                    </li>
                @endforeach
            </ul>
        </article>

        <article class="panel">
            <header class="panel-kepala">
                <h2>Komposisi status pelayanan</h2>
            </header>

            <div class="donut-wadah">
                <div class="donut" style="--a: {{ $a }}%; --b: {{ $b }}%">
                    <div class="donut-isi">
                        <strong>{{ $jmlLengkap }}</strong>
                        <span>dari {{ count($layanan) }} lengkap</span>
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

    {{-- ===== Heatmap pelayanan x bulan ===== --}}
    <section class="panel">
        <header class="panel-kepala">
            <h2>Peta kelengkapan sepanjang {{ $tahun }}</h2>
            <span class="redup">Persentase standar terisi, makin pekat makin lengkap</span>
        </header>

        <div class="tabel-gulir">
            <div class="heat">
                <div class="heat-baris heat-kepala">
                    <span></span>
                    @foreach ($namaBulanPendek as $no => $pendek)
                        <span class="{{ $no === $bulan ? 'aktif' : '' }}">{{ $pendek }}</span>
                    @endforeach
                </div>

                @foreach ($layanan as $l)
                    <div class="heat-baris">
                        <span class="heat-label" title="{{ $l['nama'] }}">{{ $l['nama'] }}</span>
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
    </section>

    {{-- ===== Tabel standar + nilai bulan terpilih ===== --}}
    <section class="panel" id="tabel">
        <header class="panel-kepala">
            <h2>Daftar standar dan nilai {{ $namaBulan[$bulan] }} {{ $tahun }}</h2>

            {{-- Filter pelayanan khusus tabel, tahun dan bulan ikut dibawa --}}
            <form method="GET" action="{{ route('dashboard') }}#tabel" class="filter">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="bulan" value="{{ $bulan }}">
                <x-pilih nama="layanan" label="Pelayanan" :opsi="[0 => 'Semua pelayanan'] + $namaLayanan" :nilai="$layananId" />
            </form>
        </header>

        <div class="tabel-gulir tabel-tinggi">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Pelayanan</th>
                        <th>Indikator</th>
                        <th>Standar</th>
                        <th>Frekuensi</th>
                        <th>Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @php $layananLalu = null; $indikatorLalu = null; @endphp
                    @forelse ($tabel as $r)
                        <tr class="{{ $r['layanan_id'] !== $layananLalu ? 'baris-awal' : '' }}">
                            {{-- Nama pelayanan dan indikator hanya ditulis di baris pertama kelompoknya --}}
                            <td class="sel-layanan">{{ $r['layanan_id'] !== $layananLalu ? $r['layanan'] : '' }}</td>
                            <td>{{ $r['indikator_id'] !== $indikatorLalu ? $r['indikator'] : '' }}</td>
                            <td>{{ $r['standar'] }}</td>
                            <td><span class="pil">{{ ucfirst($r['frekuensi']) }}</span></td>
                            <td>
                                @if (! is_null($r['nilai']))
                                    <span class="nilai">{{ $r['nilai'] }}</span>
                                @else
                                    <span class="pil pil-belum">Belum diisi</span>
                                @endif
                            </td>
                        </tr>
                        @php
                            $layananLalu = $r['layanan_id'];
                            $indikatorLalu = $r['indikator_id'];
                        @endphp
                    @empty
                        <tr><td colspan="5" class="redup">Belum ada standar untuk ditampilkan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection