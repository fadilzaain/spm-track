<?php

namespace App\Http\Controllers;

use App\Models\IkuNilai;
use App\Models\IkuTahunan;
use App\Support\PeriodeSpm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InputIkuController extends Controller
{
    /**
     * Tampilkan form: pilih tahun dan bulan, lalu semua indikator IKU muncul
     * dengan kolom target (tahunan), realisasi (periode terpilih), dan capaian (tahunan).
     */
    public function index(Request $request)
    {
        // 1. Baca filter dari URL (?tahun=2026&bulan=6), kalau tidak valid pakai default
        $tahun = (int) $request->query('tahun', now()->year);
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = now()->year;
        }

        $bulan = (int) $request->query('bulan', now()->month);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = now()->month;
        }

        // 2. Ambil semua indikator beserta isian yang sudah ada di tahun terpilih
        $daftarIku = $this->ambilIku();
        $idIku = $daftarIku->pluck('iku_id')->all();

        // $nilai[iku_id][periode_tipe][periode_no] = nilai realisasi
        $nilai = [];
        // $tahunan[iku_id] = baris berisi target dan capaian
        $tahunan = [];

        if ($idIku) {
            $dataNilai = DB::table('iku_nilai')
                ->where('tahun', $tahun)
                ->whereIn('iku_id', $idIku)
                ->get(['iku_id', 'periode_tipe', 'periode_no', 'nilai']);

            foreach ($dataNilai as $r) {
                $nilai[$r->iku_id][$r->periode_tipe][$r->periode_no] = $r->nilai;
            }

            $tahunan = DB::table('iku_tahunan')
                ->where('tahun', $tahun)
                ->whereIn('iku_id', $idIku)
                ->get(['iku_id', 'target', 'capaian'])
                ->keyBy('iku_id')
                ->all();
        }

        // 3. Susun baris untuk tampilan
        $baris = [];
        foreach ($daftarIku as $d) {
            [$tipe, $no] = PeriodeSpm::kunci((string) $d->frekuensi, $bulan);

            $baris[] = [
                'iku_id' => (int) $d->iku_id,
                'sasaran_id' => (int) $d->sasaran_id,
                'sasaran' => $d->sasaran,
                'indikator' => $d->nama_indikator,
                'periode' => PeriodeSpm::label((string) $d->frekuensi, $bulan),
                'target' => $tahunan[$d->iku_id]->target ?? '',
                'realisasi' => $nilai[$d->iku_id][$tipe][$no] ?? '',
                'capaian' => $tahunan[$d->iku_id]->capaian ?? '',
            ];
        }

        return view('input.iku', [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'daftarTahun' => PeriodeSpm::daftarTahun($tahun, ['iku_tahunan', 'iku_nilai']),
            'namaBulan' => PeriodeSpm::NAMA_BULAN,
            'baris' => $baris,
            // Hanya admin yang boleh mengisi IKU, role lain hanya melihat
            'bisaIsi' => $request->user()->isAdmin(),
        ]);
    }

    /**
     * Simpan semua isian di form sekaligus - khusus admin simpan di routes.web
     * Kolom yang dikosongkan berarti "belum diisi", jadi nilai lamanya dihapus.
     */
    public function simpan(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'target' => ['nullable', 'array'],
            'target.*' => ['nullable', 'string', 'max:255'],
            'realisasi' => ['nullable', 'array'],
            'realisasi.*' => ['nullable', 'string', 'max:255'],
            'capaian' => ['nullable', 'array'],
            'capaian.*' => ['nullable', 'string', 'max:255'],
        ]);

        $tahun = (int) $data['tahun'];
        $bulan = (int) $data['bulan'];

        // Jumlah isian yang benar-benar berubah (ditambah, diubah, atau dikosongkan)
        $perubahan = 0;

        // Hanya indikator yang ada di database yang diproses, id lain dari form diabaikan
        $daftarIku = $this->ambilIku();

        DB::transaction(function () use ($daftarIku, $data, $tahun, $bulan, $user, &$perubahan) {
            foreach ($daftarIku as $d) {
                $id = (int) $d->iku_id;

                // ----- Realisasi periode terpilih -----
                [$tipe, $no] = PeriodeSpm::kunci((string) $d->frekuensi, $bulan);

                $kunci = [
                    'iku_id' => $id,
                    'tahun' => $tahun,
                    'periode_tipe' => $tipe,
                    'periode_no' => $no,
                ];

                $nilai = trim((string) ($data['realisasi'][$id] ?? ''));
                $ada = IkuNilai::where($kunci)->first();

                if ($nilai === '') {
                    // Kolom kosong: hapus nilai lama kalau ada
                    if ($ada) {
                        $ada->delete();
                        $perubahan++;
                    }
                } elseif (! $ada || $ada->nilai !== $nilai) {
                    $baru = $ada ?? new IkuNilai($kunci);
                    if (! $ada) {
                        $baru->dibuat_oleh = $user->id;
                    }
                    $baru->nilai = $nilai;
                    $baru->diubah_oleh = $user->id;
                    $baru->save();
                    $perubahan++;
                }

                // ----- Target dan capaian tahunan -----
                $target = trim((string) ($data['target'][$id] ?? ''));
                $capaian = trim((string) ($data['capaian'][$id] ?? ''));
                $tahunan = IkuTahunan::where(['iku_id' => $id, 'tahun' => $tahun])->first();

                // Dua-duanya kosong: baris tahunan tidak perlu ada
                if ($target === '' && $capaian === '') {
                    if ($tahunan) {
                        $tahunan->delete();
                        $perubahan++;
                    }

                    continue;
                }

                $targetBaru = $target === '' ? null : $target;
                $capaianBaru = $capaian === '' ? null : $capaian;

                // Tidak berubah: tidak perlu menyentuh database
                if ($tahunan && $tahunan->target === $targetBaru && $tahunan->capaian === $capaianBaru) {
                    continue;
                }

                $tahunan ??= new IkuTahunan(['iku_id' => $id, 'tahun' => $tahun]);
                if (! $tahunan->exists) {
                    $tahunan->dibuat_oleh = $user->id;
                }
                $tahunan->target = $targetBaru;
                $tahunan->capaian = $capaianBaru;
                $tahunan->diubah_oleh = $user->id;
                $tahunan->save();
                $perubahan++;
            }
        });

        $pesan = $perubahan > 0
            ? "Tersimpan: {$perubahan} isian diperbarui."
            : 'Tidak ada perubahan.';

        return redirect()
            ->route('input.iku', ['tahun' => $tahun, 'bulan' => $bulan])
            ->with('status', $pesan);
    }

    // Semua indikator IKU berurutan per sasaran, lalu per indikator (urutan seperti di spreadsheet)
    public static function ambilIku()
    {
        return DB::table('iku as i')
            ->join('sasaran_iku as s', 's.id', '=', 'i.sasaran_iku_id')
            ->orderBy('s.urutan')
            ->orderBy('s.id')
            ->orderBy('i.urutan')
            ->orderBy('i.id')
            ->get([
                'i.id as iku_id',
                'i.nama_indikator',
                'i.frekuensi',
                's.id as sasaran_id',
                's.nama as sasaran',
            ]);
    }
}