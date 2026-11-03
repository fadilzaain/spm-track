@extends('layouts.app')

@section('judul', 'Input IKU')

@section('konten')
    {{-- ===== Kepala halaman + filter ===== --}}
    <section class="kepala-halaman">
        <div>
            <h1>Input IKU</h1>
            <p class="redup">Indikator Kinerja Utama · {{ $namaBulan[$bulan] }} {{ $tahun }}</p>
        </div>

        <form method="GET" action="{{ route('input.iku') }}" class="filter">
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

    {{-- ===== Pesan ===== --}}
    @if (session('status'))
        <div class="pesan pesan-ok">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="pesan pesan-bad">{{ $errors->first() }}</div>
    @endif

    @unless ($bisaIsi)
        <div class="pesan">Mode lihat saja. IKU hanya bisa diisi oleh Admin.</div>
    @endunless

    {{-- ===== Form IKU ===== --}}
    <form method="POST" action="{{ route('input.iku.simpan') }}" class="panel">
        @csrf
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <input type="hidden" name="bulan" value="{{ $bulan }}">

        @if ($bisaIsi)
            <p class="redup petunjuk">Target dan Capaian diisi satu kali per tahun. Realisasi mengikuti periode di kolom Periode: untuk triwulan, satu nilai berlaku untuk seluruh bulan dalam triwulan itu. Kosongkan kolom untuk menandai belum diisi.</p>
        @endif

        <div class="tabel-gulir">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Sasaran</th>
                        <th>Indikator</th>
                        <th>Periode</th>
                        <th>Target {{ $tahun }}</th>
                        <th>Realisasi</th>
                        <th>Capaian {{ $tahun }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $sasaranLalu = null; @endphp
                    @forelse ($baris as $r)
                        <tr class="{{ $r['sasaran_id'] !== $sasaranLalu ? 'baris-awal' : '' }}">
                            {{-- Nama sasaran hanya ditulis di baris pertama kelompoknya --}}
                            <td class="sel-layanan">{{ $r['sasaran_id'] !== $sasaranLalu ? $r['sasaran'] : '' }}</td>
                            <td>{{ $r['indikator'] }}</td>
                            <td><span class="pil">{{ $r['periode'] }}</span></td>

                            @foreach (['target', 'realisasi', 'capaian'] as $kolom)
                                <td class="sel-input">
                                    @if ($bisaIsi)
                                        <input type="text" class="input-teks" maxlength="255"
                                               name="{{ $kolom }}[{{ $r['iku_id'] }}]"
                                               value="{{ old($kolom.'.'.$r['iku_id'], $r[$kolom]) }}">
                                    @elseif ($r[$kolom] !== '')
                                        <span class="nilai">{{ $r[$kolom] }}</span>
                                    @else
                                        <span class="pil pil-belum">Belum diisi</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        @php $sasaranLalu = $r['sasaran_id']; @endphp
                    @empty
                        <tr><td colspan="6" class="redup">Belum ada indikator IKU.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($bisaIsi && count($baris) > 0)
            <div class="aksi-form">
                <button type="submit" class="tombol-utama">Simpan IKU</button>
            </div>
        @endif
    </form>
@endsection