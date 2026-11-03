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

        // Tabel standar dikelompokkan: pelayanan -> indikator -> standar (helper yang sama dengan halaman laporan).
        // Tujuannya supaya sel pelayanan dan indikator bisa digabung (rowspan), tidak diulang atau dikosongkan per baris
        $kelompokTabel = \App\Support\KelompokStandar::susun($tabel);
        $jumlahBarisTabel = count($tabel);
        $jumlahTerisiTabel = collect($tabel)->whereNotNull('nilai')->count();

        // Angka dasar IKU untuk panel ringkasan di bawah
        $jumlahIku = $iku['jumlah'];
        $terisiIku = $iku['terisi'];
        $belumIku = $jumlahIku - $terisiIku;
        $persenIku = $jumlahIku > 0 ? round($terisiIku / $jumlahIku * 100, 1) : 0;

        // Jumlah sasaran unik dan jumlah indikator yang capaian tahunannya sudah terisi
        $jumlahSasaran = count(array_unique(array_column($iku['baris'], 'sasaran_id')));
        $capaianIku = count(array_filter($iku['baris'], fn ($r) => $r['capaian'] !== ''));
        $belumCapaiIku = $jumlahIku - $capaianIku;

        // Dua panel ringkasan (SPM dan IKU) dibuat dari daftar di bawah lalu digambar oleh <x-ringkasan>
        $ringkasSpm = [
            'judul' => 'Standar Pelayanan Minimal',
            'angka' => number_format($persenKini, 1, ',', '.').'%',
            'label' => 'Kelengkapan pengisian',
            'catatan' => $teksSelisih,
            'catatanKelas' => $badgeSelisih,
            'tren' => ['data' => $tren, 'aktif' => $bulan, 'nama' => $namaBulan, 'judul' => 'Tren kelengkapan '.$tahun],
            'segmen' => [
                ['n' => $jmlLengkap, 'kelas' => 'bar-lengkap', 'label' => 'Lengkap'],
                ['n' => $jmlSebagian, 'kelas' => 'bar-sebagian', 'label' => 'Sebagian'],
                ['n' => $jmlBelum, 'kelas' => 'bar-belum', 'label' => 'Belum diisi'],
            ],
            'stat' => [
                ['label' => 'Total standar', 'angka' => $totalStandar,
                 'badge' => count($layanan).' pelayanan', 'badgeKelas' => ''],
                ['label' => 'Sudah terisi', 'angka' => $totalTerisi,
                 'badge' => $jmlLengkap.' pelayanan lengkap', 'badgeKelas' => 'badge-ok'],
                ['label' => 'Belum terisi', 'angka' => $totalBelum,
                 'badge' => $jmlBelum.' pelayanan kosong', 'badgeKelas' => 'badge-bad'],
            ],
        ];

        $ringkasIku = [
            'judul' => 'Indikator Kinerja Utama',
            'angka' => number_format($persenIku, 1, ',', '.').'%',
            'label' => 'Kelengkapan periode '.$namaBulan[$bulan],
            'segmen' => [
                ['n' => $terisiIku, 'kelas' => 'bar-lengkap', 'label' => 'Terisi'],
                ['n' => $belumIku, 'kelas' => 'bar-belum', 'label' => 'Belum diisi'],
            ],
            'stat' => [
                ['label' => 'Total IKU', 'angka' => $jumlahIku,
                 'badge' => $jumlahSasaran.' sasaran', 'badgeKelas' => ''],
                ['label' => 'Sudah terisi', 'angka' => $terisiIku,
                 'badge' => 'periode '.$namaBulan[$bulan], 'badgeKelas' => 'badge-ok'],
                ['label' => 'Capaian tahunan', 'angka' => $capaianIku.'/'.$jumlahIku,
                 'badge' => $belumCapaiIku > 0 ? $belumCapaiIku.' belum terisi' : 'semua terisi',
                 'badgeKelas' => $belumCapaiIku > 0 ? 'badge-bad' : 'badge-ok'],
            ],
        ];
    @endphp

    {{-- ===== Hero ringkas: sapaan, jam, filter periode, tombol isi nilai ===== --}}
    <section class="kepala-halaman kepala-ringkas">
        <div>
            <h1>{{ $salam }}, <span class="nama">{{ $pengguna->nama ?: $pengguna->username }}</span></h1>
            <p class="redup">Pemantauan SPM dan IKU · {{ $namaBulan[$bulan] }} {{ $tahun }}</p>
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

    {{-- ===== Ringkasan: dua panel (SPM dengan grafik tren, IKU) ===== --}}
    <section class="ringkasan" id="ringkasan">
        <x-ringkasan :r="$ringkasSpm" />
        <x-ringkasan :r="$ringkasIku" />
    </section>

    {{-- ===== Bagian 2: Kelengkapan pengisian SPM, bar (kiri) dan peta setahun (kanan), tinggi barisnya dibuat sama ===== --}}
    <section class="bagian" id="kelengkapan">
        <header class="bagian-kepala">
            <div>
                <h2>Kelengkapan pengisian SPM</h2>
                <p class="redup">Progres tiap pelayanan bulan ini dan peta kelengkapan sepanjang tahun</p>
            </div>
        </header>

        <div class="baris-utama">
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
        </div>
    </section>

    {{-- ===== Bagian 3: Detail, dua tab (SPM | IKU). Tab dipindah oleh public/js/spm.js ===== --}}
    <section class="bagian" id="detail" data-tab-wadah>
        <header class="bagian-kepala">
            <div>
                <h2>Detail</h2>
                <p class="redup">Daftar standar SPM dan indikator IKU beserta nilainya</p>
            </div>

            <div class="tab-baris" role="tablist" aria-label="Pilih detail yang ditampilkan">
                <button type="button" role="tab" class="tab-tombol aktif" data-tab-tombol="spm" aria-selected="true">
                    SPM <span class="pil">{{ $totalStandar }}</span>
                </button>
                <button type="button" role="tab" class="tab-tombol" data-tab-tombol="iku" aria-selected="false">
                    IKU <span class="pil">{{ $jumlahIku }}</span>
                </button>
            </div>
        </header>

        {{-- ----- Tab SPM: tabel standar + nilai bulan terpilih ----- --}}
        <div class="tab-isi aktif" data-tab-isi="spm" role="tabpanel">
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
        </div>

        {{-- ----- Tab IKU: target, realisasi terbaru, dan capaian tahunan ----- --}}
        <div class="tab-isi" data-tab-isi="iku" role="tabpanel">
            <section class="panel panel-tabel" id="iku">
                <header class="panel-kepala">
                    <div class="judul-tabel">
                        <h2>Indikator Kinerja Utama {{ $tahun }}</h2>
                        <span class="pil">{{ $jumlahIku }} indikator</span>
                        <span class="pil {{ $jumlahIku > 0 && $terisiIku === $jumlahIku ? 'badge-ok' : '' }}">{{ $terisiIku }} terisi periode {{ $namaBulan[$bulan] }}</span>
                    </div>

                    <div class="aksi-kepala">
                        <a class="tombol-teks" href="{{ route('iku.laporan', ['tahun' => $tahun]) }}">Lihat laporan IKU</a>

                        {{-- Hanya admin yang boleh mengisi IKU --}}
                        @if ($pengguna->isAdmin())
                            <a class="tombol-utama" href="{{ route('input.iku', $periode) }}">Isi IKU</a>
                        @endif
                    </div>
                </header>

                <div class="tabel-gulir">
                    <table class="tabel">
                        <thead>
                            <tr>
                                <th>Sasaran</th>
                                <th>Indikator</th>
                                <th>Target</th>
                                <th>Realisasi terbaru</th>
                                <th>Capaian {{ $tahun }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $sasaranLalu = null; @endphp
                            @forelse ($iku['baris'] as $r)
                                <tr class="{{ $r['sasaran_id'] !== $sasaranLalu ? 'baris-awal' : '' }}">
                                    {{-- Nama sasaran hanya ditulis di baris pertama kelompoknya --}}
                                    <td class="sel-layanan">{{ $r['sasaran_id'] !== $sasaranLalu ? $r['sasaran'] : '' }}</td>
                                    <td>{{ $r['indikator'] }}</td>
                                    <td>
                                        @if ($r['target'] !== '')
                                            <span class="nilai">{{ $r['target'] }}</span>
                                        @else
                                            <span class="pil pil-kosong">Belum diisi</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($r['terbaru'])
                                            <span class="nilai">{{ $r['terbaru']['nilai'] }}</span>
                                            <span class="pil">{{ $r['terbaru']['periode'] }}</span>
                                        @else
                                            <span class="pil pil-kosong">Belum diisi</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($r['capaian'] !== '')
                                            <span class="nilai">{{ $r['capaian'] }}</span>
                                        @else
                                            <span class="pil pil-kosong">Belum diisi</span>
                                        @endif
                                    </td>
                                </tr>
                                @php $sasaranLalu = $r['sasaran_id']; @endphp
                            @empty
                                <tr><td colspan="5" class="redup">Belum ada indikator IKU.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>
@endsection