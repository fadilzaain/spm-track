<?php

namespace App\Support;

use App\Http\Controllers\InputIkuController;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan IKU untuk panel di dashboard: target, realisasi terbaru, capaian tahunan,
 * dan jumlah indikator yang sudah terisi pada periode bulan terpilih.
 * Sistem tidak menghitung atau menilai tercapai/belum, nilai ditampilkan apa adanya.
 */
class RingkasanIku
{
    public static function susun(int $tahun, int $bulan): array
    {
        // Semua indikator berurutan per sasaran (fungsi yang sama dengan halaman Input IKU dan Laporan IKU)
        $daftar = InputIkuController::ambilIku();
        $idIku = $daftar->pluck('iku_id')->all();

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

        $baris = [];
        $terisi = 0;

        foreach ($daftar as $d) {
            $id = (int) $d->iku_id;
            $frekuensi = (string) $d->frekuensi;

            // Terisi atau belum untuk periode yang memuat bulan terpilih (aturan sama seperti SPM)
            [$tipe, $no] = PeriodeSpm::kunci($frekuensi, $bulan);
            if (self::bersihkan($nilai[$id][$tipe][$no] ?? null) !== '') {
                $terisi++;
            }

            // Realisasi terbaru: mundur dari bulan terpilih sampai ketemu periode yang sudah terisi
            $terbaru = null;
            for ($m = $bulan; $m >= 1 && $terbaru === null; $m--) {
                [$t, $n] = PeriodeSpm::kunci($frekuensi, $m);
                $isi = self::bersihkan($nilai[$id][$t][$n] ?? null);

                if ($isi !== '') {
                    $terbaru = ['nilai' => $isi, 'periode' => PeriodeSpm::label($frekuensi, $m)];
                }
            }

            $baris[] = [
                'sasaran_id' => (int) $d->sasaran_id,
                'sasaran' => $d->sasaran,
                'indikator' => $d->nama_indikator,
                'target' => self::bersihkan($tahunan[$id]->target ?? null),
                'terbaru' => $terbaru, // null = belum ada yang terisi sampai bulan ini
                'capaian' => self::bersihkan($tahunan[$id]->capaian ?? null),
            ];
        }

        return [
            'baris' => $baris,
            'jumlah' => count($baris),
            'terisi' => $terisi,
        ];
    }

    // Kosong dan tanda "-" dianggap belum terisi
    private static function bersihkan(?string $nilai): string
    {
        $nilai = trim((string) $nilai);

        return $nilai === '-' ? '' : $nilai;
    }
}