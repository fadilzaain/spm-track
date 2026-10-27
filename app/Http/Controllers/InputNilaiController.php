<?php

namespace App\Http\Controllers;

use App\Models\Spm;
use App\Support\PeriodeSpm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InputNilaiController extends Controller
{
    /**
     * Tampilkan form: pilih tahun, bulan, dan pelayanan, lalu semua standar
     * pelayanan itu muncul dengan kolom nilai untuk periode terpilih.
     */
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

        // 2. Ambil standar milik pelayanan terpilih beserta nilai yang sudah ada
        $standar = $this->ambilStandar($layananId);
        $idStandar = $standar->pluck('standar_id')->all();

        // $isi[standar_id][periode_tipe][periode_no] = nilai
        $isi = [];
        if ($idStandar) {
            $dataSpm = DB::table('spm')
                ->where('tahun', $tahun)
                ->whereIn('standar_indikator_id', $idStandar)
                ->get(['standar_indikator_id', 'periode_tipe', 'periode_no', 'nilai']);

            foreach ($dataSpm as $r) {
                $isi[$r->standar_indikator_id][$r->periode_tipe][$r->periode_no] = $r->nilai;
            }
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
            ];
        }

        return view('input.nilai', [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'layananId' => $layananId,
            'namaLayanan' => $daftarLayanan[$layananId] ?? '-',
            'daftarLayanan' => $daftarLayanan,
            'daftarTahun' => PeriodeSpm::daftarTahun($tahun),
            'namaBulan' => PeriodeSpm::NAMA_BULAN,
            'baris' => $baris,
            'bisaIsi' => $this->bisaMengisi($user, $layananId),
        ]);
    }

    /**
     * Simpan semua nilai di form sekaligus.
     * Kolom yang dikosongkan berarti "belum diisi", jadi nilai lamanya dihapus.
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
        ]);

        $tahun = (int) $data['tahun'];
        $bulan = (int) $data['bulan'];
        $layananId = (int) $data['layanan'];
        $kiriman = $data['nilai'] ?? [];

        // Admin boleh mengisi semua pelayanan, petugas unit hanya pelayanannya sendiri
        if (! $this->bisaMengisi($user, $layananId)) {
            abort(403, 'Anda hanya boleh mengisi nilai untuk pelayanan Anda sendiri.');
        }

        $disimpan = 0;
        $dikosongkan = 0;

        // Hanya standar milik pelayanan ini yang diproses, id lain dari form diabaikan
        $standar = $this->ambilStandar($layananId);

        DB::transaction(function () use ($standar, $kiriman, $tahun, $bulan, $user, &$disimpan, &$dikosongkan) {
            foreach ($standar as $s) {
                [$tipe, $no] = PeriodeSpm::kunci((string) $s->frekuensi, $bulan);

                $kunci = [
                    'standar_indikator_id' => (int) $s->standar_id,
                    'tahun' => $tahun,
                    'periode_tipe' => $tipe,
                    'periode_no' => $no,
                ];

                $nilai = trim((string) ($kiriman[$s->standar_id] ?? ''));
                $ada = Spm::where($kunci)->first();

                // Kolom kosong: hapus nilai lama kalau ada
                if ($nilai === '') {
                    if ($ada) {
                        $ada->delete();
                        $dikosongkan++;
                    }

                    continue;
                }

                // Nilai tidak berubah: tidak perlu menyentuh database
                if ($ada && $ada->nilai === $nilai) {
                    continue;
                }

                $baris = $ada ?? new Spm($kunci);
                if (! $ada) {
                    $baris->dibuat_oleh = $user->id;
                }
                $baris->nilai = $nilai;
                $baris->diubah_oleh = $user->id;
                $baris->save();
                $disimpan++;
            }
        });

        $pesan = ($disimpan + $dikosongkan) > 0
            ? "Tersimpan: {$disimpan} nilai diperbarui, {$dikosongkan} dikosongkan."
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