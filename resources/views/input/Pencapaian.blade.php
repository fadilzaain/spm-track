@extends('layouts.app')

@section('judul', 'Pencapaian')

@section('konten')
    {{-- ===== Kepala halaman + filter ===== --}}
    <section class="kepala-halaman">
        <div>
            <h1>Pencapaian Tahunan</h1>
            <p class="redup">{{ $namaLayanan }} · Tahun {{ $tahun }}</p>
        </div>

        <form method="GET" action="{{ route('pencapaian') }}" class="filter">
            <label>
                <span>Tahun</span>
                <select name="tahun" onchange="this.form.submit()">
                    @foreach ($daftarTahun as $t)
                        <option value="{{ $t }}" @selected($t === $tahun)>{{ $t }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Pelayanan</span>
                <select name="layanan" onchange="this.form.submit()">
                    @foreach ($daftarLayanan as $id => $nama)
                        <option value="{{ $id }}" @selected($id === $layananId)>{{ $nama }}</option>
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
        <div class="pesan">Mode lihat saja. Anda hanya bisa mengisi pencapaian untuk pelayanan Anda sendiri.</div>
    @endunless

    {{-- ===== Form pencapaian ===== --}}
    <form method="POST" action="{{ route('pencapaian.simpan') }}" class="panel">
        @csrf
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <input type="hidden" name="layanan" value="{{ $layananId }}">

        @if ($bisaIsi)
            <p class="redup petunjuk">Pencapaian diisi satu kali per tahun untuk tiap standar. Kosongkan kolom untuk menandai belum diisi.</p>
        @endif

        <div class="tabel-gulir">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Indikator</th>
                        <th>Standar</th>
                        <th>Pencapaian {{ $tahun }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $indikatorLalu = null; @endphp
                    @forelse ($baris as $r)
                        <tr class="{{ $r['indikator_id'] !== $indikatorLalu ? 'baris-awal' : '' }}">
                            {{-- Nama indikator hanya ditulis di baris pertama kelompoknya --}}
                            <td class="sel-layanan">{{ $r['indikator_id'] !== $indikatorLalu ? $r['indikator'] : '' }}</td>
                            <td>{{ $r['standar'] }}</td>
                            <td class="sel-input">
                                @if ($bisaIsi)
                                    <input type="text" class="input-teks" maxlength="255"
                                           name="nilai[{{ $r['standar_id'] }}]"
                                           value="{{ old('nilai.'.$r['standar_id'], $r['nilai']) }}">
                                @elseif ($r['nilai'] !== '')
                                    <span class="nilai">{{ $r['nilai'] }}</span>
                                @else
                                    <span class="pil pil-belum">Belum diisi</span>
                                @endif
                            </td>
                        </tr>
                        @php $indikatorLalu = $r['indikator_id']; @endphp
                    @empty
                        <tr><td colspan="3" class="redup">Pelayanan ini belum punya standar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($bisaIsi && count($baris) > 0)
            <div class="aksi-form">
                <button type="submit" class="tombol-utama">Simpan pencapaian</button>
            </div>
        @endif
    </form>
@endsection