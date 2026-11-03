@props(['segmen'])

{{--
    Satu bar terbagi menurut jumlah tiap status, lengkap dengan legenda di bawahnya.
    $segmen: [['n' => jumlah, 'kelas' => 'bar-lengkap|bar-sebagian|bar-belum', 'label' => 'teks'], ...]
--}}
<div class="segmen">
    <div class="segmen-bar" role="img"
         aria-label="{{ collect($segmen)->map(fn ($s) => $s['label'].' '.$s['n'])->implode(', ') }}">
        @foreach ($segmen as $s)
            @if ($s['n'] > 0)
                <i class="{{ $s['kelas'] }}" style="flex-grow: {{ $s['n'] }}" title="{{ $s['label'] }}: {{ $s['n'] }}"></i>
            @endif
        @endforeach
    </div>

    <ul class="legenda">
        @foreach ($segmen as $s)
            <li><i class="titik {{ $s['kelas'] }}"></i>{{ $s['label'] }} <b>{{ $s['n'] }}</b></li>
        @endforeach
    </ul>
</div>