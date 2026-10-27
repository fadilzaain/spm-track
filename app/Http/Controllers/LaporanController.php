<?php

namespace App\Http\Controllers;

use App\Support\PeriodeSpm;
use App\Support\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LaporanController extends Controller
{
    // Nama bulan singkat untuk judul kolom (kunci 1 sampai 12)
    private const NAMA_BULAN_PENDEK = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    /**
     * Halaman laporan: semua standar (atau satu pelayanan) dalam satu tabel panjang,
     * kolom Januari sampai Desember ditambah Pencapaian tahunan.
     */
    public function index(Request $request)
    {
        $data = $this->siapkanData($request);

        return view('laporan.rekap', $data + [
            'namaBulanPendek' => self::NAMA_BULAN_PENDEK,
            'namaBulan' => PeriodeSpm::NAMA_BULAN,
        ]);
    }

    /**
     * Unduh laporan sebagai file Excel. Isinya mengikuti filter yang sedang dipilih di layar
     * (?tahun=&layanan=).
     */
    public function ekspor(Request $request)
    {
        $data = $this->siapkanData($request);
        $tahun = $data['tahun'];

        $x = new XlsxWriter('Rekap SPM');
        $x->lebarKolom(array_merge([30, 38, 48], array_fill(0, 12, 11), [18]));

        $x->baris(['Rekap SPM Tahun '.$tahun], XlsxWriter::GAYA_JUDUL);
        $x->baris(
            ['RSUD Kabupaten Jombang · '.$data['namaLayanan'].' · diunduh '.now()->format('d/m/Y H:i')],
            XlsxWriter::GAYA_INFO
        );
        $x->baris([]); // baris kosong pemisah

        // Baris header (baris ke-4)
        $header = ['Pelayanan', 'Indikator', 'Standar'];
        foreach (self::NAMA_BULAN_PENDEK as $pendek) {
            $header[] = $pendek;
        }
        $header[] = 'Pencapaian '.$tahun;
        $x->baris($header, XlsxWriter::GAYA_HEADER);

        // Isi: pelayanan dan indikator ditulis di setiap baris supaya mudah difilter di Excel
        foreach ($data['baris'] as $r) {
            $sel = [$r['layanan'], $r['indikator'], $r['standar']];
            foreach ($r['bulan'] as $nilai) {
                $sel[] = $nilai;
            }
            $sel[] = $r['pencapaian'];

            $gaya = array_merge(
                [XlsxWriter::GAYA_SEL, XlsxWriter::GAYA_SEL, XlsxWriter::GAYA_SEL],
                array_fill(0, 13, XlsxWriter::GAYA_TENGAH)
            );
            $x->baris($sel, $gaya);
        }

        $x->bekukan(4);
        $x->filterOtomatis(4);

        $path = $x->simpanSementara();

        $nama = 'rekap-spm-'.$tahun;
        if ($data['layananId'] > 0) {
            $nama .= '-'.Str::slug($data['namaLayanan']);
        }
        $nama .= '.xlsx';

        return response()->download($path, $nama, [
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

        // Daftar semua pelayanan: [id => nama]
        $daftarLayanan = DB::table('jenis_pelayanan')->orderBy('id')->pluck('nama_pelayanan', 'id')->all();

        // 0 = semua pelayanan
        $layananId = (int) $request->query('layanan', 0);
        if (! isset($daftarLayanan[$layananId])) {
            $layananId = 0;
        }

        // Semua standar (atau milik satu pelayanan) beserta indikator dan pelayanannya
        $standar = DB::table('standar_indikator as s')
            ->join('indikator as i', 'i.id', '=', 's.indikator_id')
            ->join('jenis_pelayanan as j', 'j.id', '=', 'i.jenis_pelayanan_id')
            ->when($layananId > 0, fn ($q) => $q->where('j.id', $layananId))
            ->orderBy('j.id')
            ->orderBy('i.id')
            ->orderBy('s.id')
            ->get([
                's.id as standar_id',
                's.deskripsi',
                'i.id as indikator_id',
                'i.nama_indikator',
                'i.frekuensi',
                'j.id as jenis_id',
                'j.nama_pelayanan',
            ]);

        $idStandar = $standar->pluck('standar_id')->all();

        // $isiSpm[standar_id][periode_tipe][periode_no] = nilai
        $isiSpm = [];
        $pencapaian = [];

        if ($idStandar) {
            $dataSpm = DB::table('spm')
                ->where('tahun', $tahun)
                ->whereIn('standar_indikator_id', $idStandar)
                ->get(['standar_indikator_id', 'periode_tipe', 'periode_no', 'nilai']);

            foreach ($dataSpm as $r) {
                $isiSpm[$r->standar_indikator_id][$r->periode_tipe][$r->periode_no] = $r->nilai;
            }

            // $pencapaian[standar_id] = nilai pencapaian tahunan
            $pencapaian = DB::table('pencapaian')
                ->where('tahun', $tahun)
                ->whereIn('standar_indikator_id', $idStandar)
                ->pluck('nilai', 'standar_indikator_id')
                ->all();
        }

        return [
            'tahun' => $tahun,
            'layananId' => $layananId,
            'namaLayanan' => $layananId > 0 ? $daftarLayanan[$layananId] : 'Semua pelayanan',
            'daftarLayanan' => $daftarLayanan,
            'daftarTahun' => PeriodeSpm::daftarTahun($tahun),
            'baris' => self::susunBaris($standar, $isiSpm, $pencapaian),
        ];
    }

    /**
     * Susun baris laporan dari data mentah (tanpa akses database, jadi mudah dites).
     * Setiap baris punya 12 nilai bulan dan satu pencapaian.
     */
    public static function susunBaris(iterable $standar, array $isiSpm, array $pencapaian): array
    {
        $baris = [];

        foreach ($standar as $s) {
            $id = (int) $s->standar_id;
            $frekuensi = (string) $s->frekuensi;

            $bulan = [];
            for ($m = 1; $m <= 12; $m++) {
                $bulan[$m] = self::nilaiLaporan($isiSpm, $id, $frekuensi, $m);
            }

            $baris[] = [
                'layanan_id' => (int) $s->jenis_id,
                'layanan' => $s->nama_pelayanan,
                'indikator_id' => (int) $s->indikator_id,
                'indikator' => $s->nama_indikator,
                'standar' => $s->deskripsi,
                'frekuensi' => $frekuensi,
                'bulan' => $bulan,
                'pencapaian' => self::bersihkan($pencapaian[$id] ?? null),
            ];
        }

        return $baris;
    }

    /**
     * Nilai satu standar yang ditampilkan di kolom satu bulan.
     * Bulanan: nilai bulan itu. Triwulan: hanya di bulan penutup (Mar, Jun, Sep, Des) seperti di Excel.
     * Tahunan: hanya di Desember.
     */
    private static function nilaiLaporan(array $isiSpm, int $standarId, string $frekuensi, int $bulan): string
    {
        if ($frekuensi === 'triwulan' && $bulan % 3 !== 0) {
            return '';
        }
        if ($frekuensi === 'tahunan' && $bulan !== 12) {
            return '';
        }

        [$tipe, $no] = PeriodeSpm::kunci($frekuensi, $bulan);

        return self::bersihkan($isiSpm[$standarId][$tipe][$no] ?? null);
    }

    // Kosong dan tanda "-" dianggap belum terisi
    private static function bersihkan(?string $nilai): string
    {
        $nilai = trim((string) $nilai);

        return $nilai === '-' ? '' : $nilai;
    }
}