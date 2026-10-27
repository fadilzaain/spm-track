@extends('layouts.app')

@section('judul', 'Dashboard')

@section('konten')
    {{-- ===== Kepala halaman + filter periode ===== --}}
    <section class="kepala-halaman">
        <div>
            <h1>Dashboard SPM</h1>
            <p class="redup">Kelengkapan pengisian Standar Pelayanan Minimal · {{ $namaBulan[$bulan] }} {{ $tahun }}</p>
        </div>

        <form method="GET" action="{{ route('dashboard') }}" class="filter">
            <label>
                <span>Tahun</span>
                <select name="tahun" onchange="this.form.submit()">
                    @foreach ($daftarTahun as $t)
                        <option value="{{ $t }}" @selected($t === $tahun)>{{ $t }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Bulan</span>
                <select name="bulan" onchange="this.form.submit()">
                    @foreach ($namaBulan as $no => $nama)
                        <option value="{{ $no }}" @selected($no === $bulan)>{{ $nama }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    </section>

    {{-- ===== Kartu KPI ===== --}}
    <section class="kpi-grid">
        <article class="kartu">
            <span class="kartu-label">Total standar</span>
            <strong class="kartu-angka">{{ $totalStandar }}</strong>
            <span class="badge">{{ count($layanan) }} jenis pelayanan</span>
        </article>

        <article class="kartu">
            <span class="kartu-label">Sudah terisi</span>
            <strong class="kartu-angka">{{ $totalTerisi }}</strong>
            <span class="badge badge-ok">{{ $jmlLengkap }} pelayanan lengkap</span>
        </article>

        <article class="kartu">
            <span class="kartu-label">Belum terisi</span>
            <strong class="kartu-angka">{{ $totalBelum }}</strong>
            <span class="badge badge-bad">{{ $jmlBelum }} pelayanan kosong</span>
        </article>

        <article class="kartu kartu-utama">
            <span class="kartu-label">Kelengkapan</span>
            <strong class="kartu-angka">{{ number_format($persenKini, 1, ',', '.') }}%</strong>
            @if (! is_null($selisih))
                <span class="badge {{ $selisih >= 0 ? 'badge-ok' : 'badge-bad' }}">
                    {{ $selisih >= 0 ? '+' : '' }}{{ number_format($selisih, 1, ',', '.') }} poin dari bulan lalu
                </span>
            @else
                <span class="badge">awal tahun</span>
            @endif
        </article>
    </section>

    {{-- ===== Bar kelengkapan per pelayanan + donut komposisi ===== --}}
    <section class="baris-dua">
        <article class="panel">
            <header class="panel-kepala">
                <h2>Kelengkapan per pelayanan</h2>
                <span class="redup">{{ $namaBulan[$bulan] }} {{ $tahun }}</span>
            </header>

            <ul class="bar-list">
                @foreach ($layanan as $l)
                    <li class="bar-baris">
                        <span class="bar-label" title="{{ $l['nama'] }}">{{ $l['nama'] }}</span>
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

            @php
                // Sudut donut dalam persen: lengkap | sebagian | belum
                $n = max(count($layanan), 1);
                $a = round($jmlLengkap / $n * 100, 2);
                $b = round(($jmlLengkap + $jmlSebagian) / $n * 100, 2);
            @endphp

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

        <div class="heat-gulir">
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
    <section class="panel">
        <header class="panel-kepala">
            <h2>Daftar standar dan nilai {{ $namaBulan[$bulan] }} {{ $tahun }}</h2>

            {{-- Filter pelayanan khusus tabel, tahun dan bulan ikut dibawa --}}
            <form method="GET" action="{{ route('dashboard') }}" class="filter">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="bulan" value="{{ $bulan }}">
                <label>
                    <span>Pelayanan</span>
                    <select name="layanan" onchange="this.form.submit()">
                        <option value="0">Semua pelayanan</option>
                        @foreach ($namaLayanan as $id => $nama)
                            <option value="{{ $id }}" @selected($id === $layananId)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
        </header>

        <div class="tabel-gulir">
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
                    @foreach ($tabel as $r)
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
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection