@props(['k'])

{{--
    Satu angka ringkasan (label, angka, keterangan), dipakai di panel ringkasan dashboard dan di halaman laporan.
    Kunci opsional: 'utama' (angka diberi warna aksen), 'bar' (persen, bar tipis di bawah angka).
--}}
<article class="kartu">
    <span class="kartu-label">{{ $k['label'] }}</span>
    <strong class="kartu-angka {{ ($k['utama'] ?? false) ? 'kartu-angka-utama' : '' }}">{{ $k['angka'] }}</strong>

    @isset($k['bar'])
        <span class="bar-trek"><span class="bar-isi" style="width: {{ number_format($k['bar'], 1, '.', '') }}%"></span></span>
    @endisset

    <span class="kartu-info {{ $k['badgeKelas'] }}">{{ $k['badge'] }}</span>
</article>