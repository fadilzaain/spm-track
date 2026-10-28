@props(['nama', 'label', 'bag' => 'default', 'aktif' => false, 'tipe' => 'text', 'ikon' => null, 'opsi' => null, 'auto' => null, 'kosong' => 'Pilih...'])

{{--
    Satu isian form lengkap: label, ikon, kolom, dan pesan galat. Dipakai di form Masuk dan Daftar.
    - nama  : name field        - label : tulisan di atas kolom
    - tipe  : text / password   - opsi  : kalau diisi array [nilai => teks], tampil sebagai dropdown
    - bag   : kelompok galat (form daftar memakai "daftar")
    - aktif : true kalau form ini yang barusan dikirim, supaya isian lama (old) dan galatnya hanya muncul di form yang benar
--}}
@php
    $pesan = $aktif ? $errors->getBag($bag)->first($nama) : null;
    $isi = $aktif ? old($nama) : null;
@endphp

<label class="isian">
    <span class="isian-label">{{ $label }}</span>

    <span class="isian-kotak {{ $pesan ? 'salah' : '' }}">
        @if ($ikon)
            <x-ikon :nama="$ikon" />
        @endif

        @if ($opsi !== null)
            <select name="{{ $nama }}" class="input-teks">
                <option value="">{{ $kosong }}</option>
                @foreach ($opsi as $nilai => $teks)
                    <option value="{{ $nilai }}" @selected((string) $isi === (string) $nilai)>{{ $teks }}</option>
                @endforeach
            </select>
        @else
            <input class="input-teks" type="{{ $tipe }}" name="{{ $nama }}"
                   @if ($tipe !== 'password') value="{{ $isi }}" @endif
                   @if ($auto) autocomplete="{{ $auto }}" @endif
                   {{ $attributes }}>

            @if ($tipe === 'password')
                {{-- Tombol mata: tampilkan/sembunyikan password (ditangani spm.js) --}}
                <button type="button" class="isian-mata" data-lihat-sandi aria-label="Tampilkan atau sembunyikan password">
                    <x-ikon nama="mata" class="ikon-mata" />
                    <x-ikon nama="mata-coret" class="ikon-coret" />
                </button>
            @endif
        @endif
    </span>

    @if ($pesan)
        <span class="isian-galat">{{ $pesan }}</span>
    @endif
</label>