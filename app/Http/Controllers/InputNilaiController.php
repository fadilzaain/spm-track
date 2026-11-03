<?php

namespace App\Http\Controllers;

use App\Models\Pencapaian;
use App\Models\Spm;
use App\Support\PeriodeSpm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InputNilaiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Baca filter dari URL (?tahun=2025&bulan=10&layanan=3), kalau tidak valid pakai default
        $tahun = (int) $request->query('tahun', now()->year);
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = now()->year;
        }

        $bulan = (int) $request->query('bulan', now()->month);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = now()->month;
        }

        // Daftar semua pelayanan: [id => nama]. Semua role boleh melihat semuanya.
        $daftarLayanan = DB::table('jenis_pelayanan')->orderBy('id')->pluck('nama_pelayanan', 'id')->all();

        // Kalau pelayanan tidak dipilih: petugas unit langsung ke pelayanannya sendiri, yang lain ke yang pertama
        $layananId = (int) $request->query('layanan', 0);
        if (! isset($daftarLayanan[$layananId])) {
            $layananId = ($user->isPetugasUnit() && isset($daftarLayanan[(int) $user->jenis_pelayanan_id]))
                ? (int) $user->jenis_pelayanan_id
                : (int) array_key_first($daftarLayanan);
        }

        // 2. Ambil standar milik pelayanan terpilih beserta nilai dan pencapaian yang sudah ada
        $standar = $this->ambilStandar($layananId);
        $idStandar = $standar->pluck('standar_id')->all();

        // $isi[standar_id][periode_tipe][periode_no] = nilai
        $isi = [];
        // $capai[standar_id] = pencapaian tahunan
        $capai = [];

        if ($idStandar) {
            $dataSpm = DB::table('spm')
                ->where('tahun', $tahun)
                ->whereIn('standar_indikator_id', $idStandar)
                ->get(['standar_indikator_id', 'periode_tipe', 'periode_no', 'nilai']);

            foreach ($dataSpm as $r) {
                $isi[$r->standar_indikator_id][$r->periode_tipe][$r->periode_no] = $r->nilai;
            }

            $capai = DB::table('pencapaian')
                ->where('tahun', $tahun)
                ->whereIn('standar_indikator_id', $idStandar)
                ->pluck('nilai', 'standar_indikator_id')
                ->all();
        }

        // 3. Susun baris untuk tampilan
        $baris = [];
        foreach ($standar as $s) {
            [$tipe, $no] = PeriodeSpm::kunci((string) $s->frekuensi, $bulan);

            $baris[] = [
                'standar_id' => (int) $s->standar_id,
                'indikator_id' => (int) $s->indikator_id,
                'indikator' => $s->nama_indikator,
                'standar' => $s->deskripsi,
                'periode' => PeriodeSpm::label((string) $s->frekuensi, $bulan),
                'nilai' => $isi[$s->standar_id][$tipe][$no] ?? '',
                'pencapaian' => $capai[$s->standar_id] ?? '',
            ];
        }

        return view('input.nilai', [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'layananId' => $layananId,
            'namaLayanan' => $daftarLayanan[$layananId] ?? '-',
            'daftarLayanan' => $daftarLayanan,
            'daftarTahun' => PeriodeSpm::daftarTahun($tahun, ['spm', 'pencapaian']),
            'namaBulan' => PeriodeSpm::NAMA_BULAN,
            'baris' => $baris,
            'bisaIsi' => $this->bisaMengisi($user, $layananId),
        ]);
    }

    /**
     * Simpan semua nilai dan pencapaian di form sekaligus (satu transaksi).
     * Kolom yang dikosongkan berarti "belum diisi", jadi isi lamanya dihapus.
     */
    public function simpan(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'layanan' => ['required', 'integer', 'exists:jenis_pelayanan,id'],
            'nilai' => ['nullable', 'array'],
            'nilai.*' => ['nullable', 'string', 'max:255'],
            'pencapaian' => ['nullable', 'array'],
            'pencapaian.*' => ['nullable', 'string', 'max:255'],
        ]);

        $tahun = (int) $data['tahun'];
        $bulan = (int) $data['bulan'];
        $layananId = (int) $data['layanan'];
        $kirimanNilai = $data['nilai'] ?? [];
        $kirimanCapai = $data['pencapaian'] ?? [];

        // Admin boleh mengisi semua pelayanan, petugas unit hanya pelayanannya sendiri
        if (! $this->bisaMengisi($user, $layananId)) {
            abort(403, 'Anda hanya boleh mengisi data untuk pelayanan Anda sendiri.');
        }

        // Jumlah isian yang benar-benar berubah (ditambah, diubah, atau dikosongkan)
        $perubahan = 0;

        // Hanya standar milik pelayanan ini yang diproses, id lain dari form diabaikan
        $standar = $this->ambilStandar($layananId);

        DB::transaction(function () use ($standar, $kirimanNilai, $kirimanCapai, $tahun, $bulan, $user, &$perubahan) {
            foreach ($standar as $s) {
                $id = (int) $s->standar_id;

                // ----- Nilai periode terpilih (bulanan / triwulan / tahunan) -----
                [$tipe, $no] = PeriodeSpm::kunci((string) $s->frekuensi, $bulan);

                $kunciNilai = [
                    'standar_indikator_id' => $id,
                    'tahun' => $tahun,
                    'periode_tipe' => $tipe,
                    'periode_no' => $no,
                ];

                $nilai = trim((string) ($kirimanNilai[$id] ?? ''));
                $adaNilai = Spm::where($kunciNilai)->first();

                if ($nilai === '') {
                    // Kolom kosong: hapus nilai lama kalau ada
                    if ($adaNilai) {
                        $adaNilai->delete();
                        $perubahan++;
                    }
                } elseif (! $adaNilai || $adaNilai->nilai !== $nilai) {
                    $baru = $adaNilai ?? new Spm($kunciNilai);
                    if (! $adaNilai) {
                        $baru->dibuat_oleh = $user->id;
                    }
                    $baru->nilai = $nilai;
                    $baru->diubah_oleh = $user->id;
                    $baru->save();
                    $perubahan++;
                }

                // ----- Pencapaian tahunan -----
                $kunciCapai = [
                    'standar_indikator_id' => $id,
                    'tahun' => $tahun,
                ];

                $capai = trim((string) ($kirimanCapai[$id] ?? ''));
                $adaCapai = Pencapaian::where($kunciCapai)->first();

                if ($capai === '') {
                    // Kolom kosong: hapus pencapaian lama kalau ada
                    if ($adaCapai) {
                        $adaCapai->delete();
                        $perubahan++;
                    }
                } elseif (! $adaCapai || $adaCapai->nilai !== $capai) {
                    $baru = $adaCapai ?? new Pencapaian($kunciCapai);
                    if (! $adaCapai) {
                        $baru->dibuat_oleh = $user->id;
                    }
                    $baru->nilai = $capai;
                    $baru->diubah_oleh = $user->id;
                    $baru->save();
                    $perubahan++;
                }
            }
        });

        $pesan = $perubahan > 0
            ? "Tersimpan: {$perubahan} isian diperbarui."
            : 'Tidak ada perubahan.';

        return redirect()
            ->route('input.nilai', ['tahun' => $tahun, 'bulan' => $bulan, 'layanan' => $layananId])
            ->with('status', $pesan);
    }

    // Standar milik satu pelayanan, berurutan per indikator
    private function ambilStandar(int $layananId)
    {
        return DB::table('standar_indikator as s')
            ->join('indikator as i', 'i.id', '=', 's.indikator_id')
            ->where('i.jenis_pelayanan_id', $layananId)
            ->orderBy('i.id')
            ->orderBy('s.id')
            ->get([
                's.id as standar_id',
                's.deskripsi',
                'i.id as indikator_id',
                'i.nama_indikator',
                'i.frekuensi',
            ]);
    }

    // Admin: semua pelayanan. Petugas unit: hanya pelayanannya sendiri. Manajemen: hanya lihat.
    private function bisaMengisi($user, int $layananId): bool
    {
        return $user->isAdmin()
            || ($user->isPetugasUnit() && (int) $user->jenis_pelayanan_id === $layananId);
    }
}