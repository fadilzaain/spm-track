<?php

namespace App\Http\Controllers;

use App\Support\PeriodeSpm;
use App\Support\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanIkuController extends Controller
{
    /**
     * Halaman Laporan IKU (tampilan mirip spreadsheet IKU)
     */
    public function index(Request $request)
    {
        $data = $this->siapkanData($request);

        return view('laporan.iku', $data + [
            'namaBulanPendek' => PeriodeSpm::NAMA_BULAN_PENDEK,
        ]);
    }

    /**
     * Unduh laporan sebagai file Excel.
     */
    public function ekspor(Request $request)
    {
        $data = $this->siapkanData($request);
        $tahun = $data['tahun'];

        $x = new XlsxWriter('Laporan IKU');
        // Kolom: Kinerja, Indikator, Target, 12 bulan, Capaian
        $x->lebarKolom(array_merge([42, 42, 12], array_fill(0, 12, 11), [14]));

        $x->baris(['Laporan IKU Tahun '.$tahun], XlsxWriter::GAYA_JUDUL);
        $x->baris(
            ['RSUD Kabupaten Jombang · Indikator Kinerja Utama · diunduh '.now()->format('d/m/Y H:i')],
            XlsxWriter::GAYA_INFO
        );
        $x->baris([]); // baris kosong pemisah

        // Baris header (baris ke-4)
        $header = ['Kinerja', 'Indikator', 'Target '.$tahun];
        foreach (PeriodeSpm::NAMA_BULAN_PENDEK as $pendek) {
            $header[] = $pendek;
        }
        $header[] = 'Capaian '.$tahun;
        $x->baris($header, XlsxWriter::GAYA_HEADER);

        // Isi: sasaran ditulis di setiap baris supaya mudah difilter di Excel
        foreach ($data['kelompok'] as $g) {
            foreach ($g['indikator'] as $r) {
                $sel = [$g['nama'], $r['indikator'], $r['target']];
                foreach ($r['bulan'] as $c) {
                    // Bulan yang bukan bulan lapor ditulis "-" seperti di spreadsheet
                    $sel[] = $c['libur'] ? '-' : $c['nilai'];
                }
                $sel[] = $r['capaian'];

                $gaya = array_merge(
                    [XlsxWriter::GAYA_SEL, XlsxWriter::GAYA_SEL],
                    array_fill(0, 14, XlsxWriter::GAYA_TENGAH)
                );
                $x->baris($sel, $gaya);
            }
        }

        $x->bekukan(4);
        $x->filterOtomatis(4);

        $path = $x->simpanSementara();

        return response()->download($path, 'laporan-iku-'.$tahun.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // Baca filter lalu ambil dan susun data yang dipakai bersama oleh halaman dan ekspor
    private function siapkanData(Request $request): array
    {
        $tahun = (int) $request->query('tahun', now()->year);
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = now()->year;
        }

        // Semua indikator berurutan per sasaran (fungsi yang sama dengan halaman Input IKU)
        $daftarIku = InputIkuController::ambilIku();
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

        // Kelompokkan per sasaran (dipakai untuk sel Kinerja yang digabung)
        $kelompok = [];
        foreach ($daftarIku as $d) {
            $id = (int) $d->iku_id;
            $frekuensi = (string) $d->frekuensi;

            // Tiap bulan: libur = bukan bulan lapor (tampil "-"), nilai kosong = belum diisi
            $bulan = [];
            for ($m = 1; $m <= 12; $m++) {
                if (! PeriodeSpm::bulanLapor($frekuensi, $m)) {
                    $bulan[$m] = ['libur' => true, 'nilai' => ''];

                    continue;
                }

                [$tipe, $no] = PeriodeSpm::kunci($frekuensi, $m);
                $bulan[$m] = [
                    'libur' => false,
                    'nilai' => $this->bersihkan($nilai[$id][$tipe][$no] ?? null),
                ];
            }

            $kelompok[$d->sasaran_id] ??= [
                'id' => (int) $d->sasaran_id,
                'nama' => $d->sasaran,
                'indikator' => [],
            ];

            $kelompok[$d->sasaran_id]['indikator'][] = [
                'indikator' => $d->nama_indikator,
                'frekuensi' => $frekuensi,
                'target' => $this->bersihkan($tahunan[$id]->target ?? null),
                'bulan' => $bulan,
                'capaian' => $this->bersihkan($tahunan[$id]->capaian ?? null),
            ];
        }

        return [
            'tahun' => $tahun,
            'daftarTahun' => PeriodeSpm::daftarTahun($tahun, ['iku_tahunan', 'iku_nilai']),
            'kelompok' => array_values($kelompok),
            'jumlahIku' => count($daftarIku),
        ];
    }

    // Kosong dan tanda "-" dianggap belum terisi
    private function bersihkan(?string $nilai): string
    {
        $nilai = trim((string) $nilai);

        return $nilai === '-' ? '' : $nilai;
    }
}