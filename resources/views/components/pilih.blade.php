@props(['nama', 'label', 'opsi', 'nilai'])

{{--
    Dropdown filter yang langsung mengirim form begitu pilihannya diganti.
    - nama  : nama field di URL (mis. tahun, bulan, layanan)
    - label : tulisan kecil di atas dropdown
    - opsi  : array [nilai => teks tampilan]
    - nilai : nilai yang sedang terpilih
--}}
<label>
    <span>{{ $label }}</span>
    <select name="{{ $nama }}" onchange="this.form.submit()">
        @foreach ($opsi as $nilaiOpsi => $teks)
            <option value="{{ $nilaiOpsi }}" @selected((string) $nilaiOpsi === (string) $nilai)>{{ $teks }}</option>
        @endforeach
    </select>
</label>