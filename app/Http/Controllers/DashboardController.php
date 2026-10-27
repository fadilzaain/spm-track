<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Nama bulan untuk tampilan (kunci 1 sampai 12)
    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    private const NAMA_BULAN_PENDEK = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    public function index(Request $request)
    {
        // 1. Baca filter dari URL (?tahun=2025&bulan=10&layanan=3), kalau tidak valid pakai default
        $tahun = (int) $request->query('tahun', now()->year);
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = now()->year;
        }

        $bulan = (int) $request->query('bulan', now()->month);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = now()->month;
        }

        $layananId = (int) $request->query('layanan', 0); // 0 = semua pelayanan (khusus tabel)

        // 2. Ambil semua standar beserta indikator dan jenis pelayanannya (sekitar 99 baris)
        $standar = DB::table('standar_indikator as s')
            ->join('indikator as i', 'i.id', '=', 's.indikator_id')
            ->join('jenis_pelayanan as j', 'j.id', '=', 'i.jenis_pelayanan_id')
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

        // 3. Ambil nilai yang sudah diisi pada tahun terpilih, disusun jadi
        //    $isi[standar_id][periode_tipe][periode_no] = nilai
        $isi = [];
        $dataSpm = DB::table('spm')
            ->where('tahun', $tahun)
            ->get(['standar_indikator_id', 'periode_tipe', 'periode_no', 'nilai']);

        foreach ($dataSpm as $r) {
            $isi[$r->standar_indikator_id][$r->periode_tipe][$r->periode_no] = $r->nilai;
        }

        // Daftar nama pelayanan: [id => nama], urut sesuai id
        $namaLayanan = [];
        foreach ($standar as $s) {
            $namaLayanan[(int) $s->jenis_id] = $s->nama_pelayanan;
        }

        // 4. Rekap bulan terpilih: per pelayanan berapa standar yang sudah terisi
        $rekap = $this->rekapBulan($standar, $isi, $bulan);

        $layanan = [];
        $jmlLengkap = 0;
        $jmlSebagian = 0;
        $jmlBelum = 0;

        foreach ($namaLayanan as $id => $nama) {
            $total = $rekap[$id]['total'] ?? 0;
            $terisi = $rekap[$id]['terisi'] ?? 0;

            // Lengkap = semua standar terisi, Sebagian = ada yang terisi, Belum = kosong semua
            if ($total > 0 && $terisi === $total) {
                $status = 'lengkap';
                $jmlLengkap++;
            } elseif ($terisi > 0) {
                $status = 'sebagian';
                $jmlSebagian++;
            } else {
                $status = 'belum';
                $jmlBelum++;
            }

            $layanan[] = [
                'id' => $id,
                'nama' => $nama,
                'total' => $total,
                'terisi' => $terisi,
                'persen' => $total > 0 ? round($terisi / $total * 100, 1) : 0,
                'status' => $status,
            ];
        }

        // 5. Angka ringkasan untuk kartu KPI
        $totalStandar = array_sum(array_column($rekap, 'total'));
        $totalTerisi = array_sum(array_column($rekap, 'terisi'));
        $totalBelum = $totalStandar - $totalTerisi;
        $persenKini = $totalStandar > 0 ? round($totalTerisi / $totalStandar * 100, 1) : 0;

        // Selisih dengan bulan sebelumnya (hanya kalau masih di tahun yang sama)
        $selisih = null;
        if ($bulan > 1) {
            $rekapLalu = $this->rekapBulan($standar, $isi, $bulan - 1);
            $terisiLalu = array_sum(array_column($rekapLalu, 'terisi'));
            $persenLalu = $totalStandar > 0 ? round($terisiLalu / $totalStandar * 100, 1) : 0;
            $selisih = round($persenKini - $persenLalu, 1);
        }

        // 6. Heatmap: kelengkapan tiap pelayanan di tiap bulan (nilai 0 sampai 1)
        $heatmap = [];
        for ($b = 1; $b <= 12; $b++) {
            $rekapB = $this->rekapBulan($standar, $isi, $b);
            foreach ($namaLayanan as $id => $nama) {
                $t = $rekapB[$id]['total'] ?? 0;
                $heatmap[$id][$b] = $t > 0 ? ($rekapB[$id]['terisi'] / $t) : 0;
            }
        }

        // 7. Tabel standar + nilai bulan terpilih (bisa difilter per pelayanan)
        $tabel = [];
        foreach ($standar as $s) {
            if ($layananId > 0 && (int) $s->jenis_id !== $layananId) {
                continue;
            }

            $nilai = $this->nilaiPeriode($isi, (int) $s->standar_id, (string) $s->frekuensi, $bulan);

            $tabel[] = [
                'layanan_id' => (int) $s->jenis_id,
                'layanan' => $s->nama_pelayanan,
                'indikator_id' => (int) $s->indikator_id,
                'indikator' => $s->nama_indikator,
                'standar' => $s->deskripsi,
                'frekuensi' => $s->frekuensi,
                'nilai' => $nilai, // null = belum diisi
            ];
        }

        // 8. Daftar tahun untuk pilihan filter: tahun yang ada datanya + tahun berjalan + tahun terpilih
        $daftarTahun = DB::table('spm')->distinct()->pluck('tahun')->map(fn ($t) => (int) $t)->all();
        foreach ([now()->year, $tahun] as $t) {
            if (! in_array($t, $daftarTahun, true)) {
                $daftarTahun[] = $t;
            }
        }
        rsort($daftarTahun);

        return view('dashboard', [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'layananId' => $layananId,
            'namaBulan' => self::NAMA_BULAN,
            'namaBulanPendek' => self::NAMA_BULAN_PENDEK,
            'daftarTahun' => $daftarTahun,
            'namaLayanan' => $namaLayanan,
            'totalStandar' => $totalStandar,
            'totalTerisi' => $totalTerisi,
            'totalBelum' => $totalBelum,
            'persenKini' => $persenKini,
            'selisih' => $selisih,
            'layanan' => $layanan,
            'jmlLengkap' => $jmlLengkap,
            'jmlSebagian' => $jmlSebagian,
            'jmlBelum' => $jmlBelum,
            'heatmap' => $heatmap,
            'tabel' => $tabel,
        ]);
    }

    /**
     * Ambil nilai satu standar untuk bulan tertentu.
     * Periode yang dicek mengikuti frekuensi indikatornya:
     * bulanan = bulan itu sendiri, triwulan = triwulan yang memuat bulan itu, tahunan = periode 1.
     * Mengembalikan null kalau belum diisi (kosong atau tanda "-" dianggap belum terisi).
     */
    private function nilaiPeriode(array $isi, int $standarId, string $frekuensi, int $bulan): ?string
    {
        [$tipe, $no] = match ($frekuensi) {
            'triwulan' => ['triwulan', (int) ceil($bulan / 3)],
            'tahunan' => ['tahunan', 1],
            default => ['bulanan', $bulan],
        };

        $nilai = $isi[$standarId][$tipe][$no] ?? null;
        if ($nilai === null) {
            return null;
        }

        $nilai = trim($nilai);

        return ($nilai === '' || $nilai === '-') ? null : $nilai;
    }

    /**
     * Hitung per pelayanan: jumlah standar (total) dan yang sudah terisi, untuk satu bulan.
     * Hasil: [jenis_id => ['total' => n, 'terisi' => n]]
     */
    private function rekapBulan($standar, array $isi, int $bulan): array
    {
        $hasil = [];

        foreach ($standar as $s) {
            $id = (int) $s->jenis_id;
            $hasil[$id] ??= ['total' => 0, 'terisi' => 0];
            $hasil[$id]['total']++;

            if ($this->nilaiPeriode($isi, (int) $s->standar_id, (string) $s->frekuensi, $bulan) !== null) {
                $hasil[$id]['terisi']++;
            }
        }

        return $hasil;
    }
}