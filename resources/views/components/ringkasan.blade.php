@props(['r'])

{{--
    Panel ringkasan satu kelompok (SPM atau IKU): angka kelengkapan besar, grafik tren (kalau ada),
    bar status bersegmen, dan deretan angka pendukung. Dipakai dua kali di dashboard.

    $r: judul, angka (teks), label, segmen (lihat <x-bar-segmen>), stat (daftar untuk <x-kartu-kpi>),
        opsional: catatan + catatanKelas (selisih dari bulan lalu), tren (data, aktif, nama, judul untuk <x-sparkline>)
--}}
<article class="panel ringkas">
    <header class="ringkas-kepala">
        <h2>{{ $r['judul'] }}</h2>
    </header>

    <div class="ringkas-utama">
        <div class="ringkas-angka">
            <div class="ringkas-baris">
                <strong>{{ $r['angka'] }}</strong>

                @isset($r['catatan'])
                    <span class="badge {{ $r['catatanKelas'] }}">{{ $r['catatan'] }}</span>
                @endisset
            </div>

            <span class="redup">{{ $r['label'] }}</span>
        </div>

        @isset($r['tren'])
            <x-sparkline :data="$r['tren']['data']" :aktif="$r['tren']['aktif']" :nama="$r['tren']['nama']" :judul="$r['tren']['judul']" />
        @endisset
    </div>

    <x-bar-segmen :segmen="$r['segmen']" />

    <div class="kpi-grid">
        @foreach ($r['stat'] as $k)
            <x-kartu-kpi :k="$k" />
        @endforeach
    </div>
</article>