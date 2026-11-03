@props(['data', 'aktif' => null, 'nama' => [], 'judul' => ''])

{{--
    Grafik garis kecil: persen kelengkapan per bulan (sumbu tegak tetap 0 sampai 100 supaya tahun-tahun bisa dibandingkan).
    $data: [nomor bulan => persen]. Hanya bulan yang ada di $data yang digambar.
    Titik bulan terpilih ($aktif) diberi lingkaran, bulan lain menampilkan nilainya saat disorot mouse.
--}}
@php
    // Ukuran gambar dalam satuan internal SVG (tampilannya mengikuti lebar wadah); $tepi menjaga titik tidak terpotong
    $lebar = 320;
    $tinggi = 80;
    $tepi = 6;
    $dasar = $tinggi - $tepi;

    $titik = [];
    foreach ($data as $bulan => $persen) {
        $titik[$bulan] = [
            round($tepi + ($bulan - 1) / 11 * ($lebar - 2 * $tepi), 1),
            round($dasar - $persen / 100 * ($dasar - $tepi), 1),
        ];
    }

    $garis = implode(' ', array_map(fn ($t) => $t[0].','.$t[1], $titik));
    $id = 'spark-'.\Illuminate\Support\Str::random(5);   // id gradien harus unik kalau komponen dipakai lebih dari sekali
@endphp

@if ($titik)
    <figure class="spark">
        <figcaption>{{ $judul }}</figcaption>

        <svg viewBox="0 0 {{ $lebar }} {{ $tinggi }}" role="img" aria-label="{{ $judul }}">
            <defs>
                <linearGradient id="{{ $id }}" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" class="spark-stop" stop-opacity="0.28" />
                    <stop offset="1" class="spark-stop" stop-opacity="0" />
                </linearGradient>
            </defs>

            <line class="spark-dasar" x1="0" x2="{{ $lebar }}" y1="{{ $dasar }}" y2="{{ $dasar }}" />
            <polygon fill="url(#{{ $id }})" points="{{ reset($titik)[0] }},{{ $dasar }} {{ $garis }} {{ end($titik)[0] }},{{ $dasar }}" />
            <polyline class="spark-garis" points="{{ $garis }}" pathLength="1" />

            @foreach ($titik as $bulan => [$x, $y])
                <circle class="spark-titik {{ $bulan === $aktif ? 'aktif' : '' }}" cx="{{ $x }}" cy="{{ $y }}" r="{{ $bulan === $aktif ? 3.5 : 6 }}">
                    <title>{{ $nama[$bulan] ?? $bulan }}: {{ number_format($data[$bulan], 1, ',', '.') }}%</title>
                </circle>
            @endforeach
        </svg>

        <div class="spark-sumbu"><span>Jan</span><span>Des</span></div>
    </figure>
@endif