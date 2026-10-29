@props(['k'])

{{--
    Kartu angka ringkasan, dipakai dashboard dan laporan.
--}}
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