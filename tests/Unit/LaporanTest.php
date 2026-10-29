<?php

namespace Tests\Unit;

use App\Http\Controllers\LaporanController;
use App\Support\KelompokStandar;
use PHPUnit\Framework\TestCase;

/**
 * Tes logika murni halaman laporan (tanpa database): kotak sesuai frekuensi, pengelompokan, ringkasan.
 */
class LaporanTest extends TestCase
{
    // Satu baris standar palsu seperti hasil query di LaporanController::siapkanData
    private function standar(int $id, int $layanan, int $indikator, string $frekuensi): object
    {
        return (object) [
            'standar_id' => $id,
            'deskripsi' => "Standar $id",
            'indikator_id' => $indikator,
            'nama_indikator' => "Indikator $indikator",
            'frekuensi' => $frekuensi,
            'jenis_id' => $layanan,
            'nama_pelayanan' => "Pelayanan $layanan",
        ];
    }

    public function test_bulanan_menghasilkan_12_kotak_masing_masing_satu_kolom(): void
    {
        $bulan = array_fill(1, 12, '');
        $bulan[2] = '90%';

        $sel = LaporanController::susunSel('bulanan', $bulan);

        $this->assertCount(12, $sel);
        $this->assertSame(12, array_sum(array_column($sel, 'span')));
        $this->assertSame('Feb', $sel[1]['label']);
        $this->assertSame('90%', $sel[1]['nilai']);
    }

    public function test_triwulan_menghasilkan_4_kotak_selebar_3_bulan_dan_nilainya_dari_bulan_penutup(): void
    {
        $bulan = array_fill(1, 12, '');
        $bulan[3] = '80';
        $bulan[12] = '95';

        $sel = LaporanController::susunSel('triwulan', $bulan);

        $this->assertCount(4, $sel);
        $this->assertSame([3, 3, 3, 3], array_column($sel, 'span'));
        $this->assertSame(['TW1', 'TW2', 'TW3', 'TW4'], array_column($sel, 'label'));
        $this->assertSame(['80', '', '', '95'], array_column($sel, 'nilai'));
    }

    public function test_tahunan_menghasilkan_satu_kotak_selebar_12_bulan(): void
    {
        $bulan = array_fill(1, 12, '');
        $bulan[12] = '100%';

        $sel = LaporanController::susunSel('tahunan', $bulan);

        $this->assertCount(1, $sel);
        $this->assertSame(12, $sel[0]['span']);
        $this->assertSame('100%', $sel[0]['nilai']);
    }

    public function test_susun_baris_menghitung_kotak_terisi_dan_total_sesuai_frekuensi(): void
    {
        $standar = [
            $this->standar(1, 1, 1, 'bulanan'),
            $this->standar(2, 1, 1, 'triwulan'),
            $this->standar(3, 1, 2, 'tahunan'),
        ];
        $isiSpm = [
            1 => ['bulanan' => [1 => '100%', 2 => '-', 3 => '95%']],   // tanda "-" dianggap kosong
            2 => ['triwulan' => [1 => '90']],
        ];
        $pencapaian = [1 => '98%'];

        $baris = LaporanController::susunBaris($standar, $isiSpm, $pencapaian);

        $this->assertSame([2, 1, 0], array_column($baris, 'terisi'));
        $this->assertSame([12, 4, 1], array_column($baris, 'total'));
        $this->assertSame(['98%', '', ''], array_column($baris, 'pencapaian'));
        // 'bulan' (12 nilai) tetap ada untuk ekspor Excel
        $this->assertCount(12, $baris[0]['bulan']);
    }

    public function test_kelompok_standar_menghitung_jumlah_untuk_rowspan(): void
    {
        $baris = LaporanController::susunBaris([
            $this->standar(1, 1, 1, 'bulanan'),
            $this->standar(2, 1, 1, 'bulanan'),
            $this->standar(3, 1, 2, 'bulanan'),
            $this->standar(4, 2, 3, 'bulanan'),
        ], [], []);

        $kelompok = KelompokStandar::susun($baris);

        $this->assertCount(2, $kelompok);
        $this->assertSame(3, $kelompok[0]['jumlah']);
        $this->assertSame([2, 1], array_column($kelompok[0]['indikator'], 'jumlah'));
        $this->assertSame(1, $kelompok[1]['jumlah']);
        $this->assertSame('Pelayanan 2', $kelompok[1]['nama']);
    }

    public function test_ringkasan_per_pelayanan_dan_gabungan(): void
    {
        $baris = LaporanController::susunBaris([
            $this->standar(1, 1, 1, 'tahunan'),   // terisi 1 dari 1
            $this->standar(2, 2, 2, 'triwulan'),  // terisi 0 dari 4
        ], [
            1 => ['tahunan' => [1 => '100%']],
        ], [1 => '100%']);

        $kelompok = LaporanController::ringkasKelompok(KelompokStandar::susun($baris));

        $this->assertSame('lengkap', $kelompok[0]['status']);
        $this->assertSame(100.0, $kelompok[0]['persen']);
        $this->assertSame('belum', $kelompok[1]['status']);

        $ringkasan = LaporanController::hitungRingkasan($kelompok);

        $this->assertSame(2, $ringkasan['standar']);
        $this->assertSame(2, $ringkasan['pelayanan']);
        $this->assertSame(1, $ringkasan['periodeTerisi']);
        $this->assertSame(5, $ringkasan['periodeTotal']);
        $this->assertSame(20.0, $ringkasan['persen']);
        $this->assertSame(1, $ringkasan['pencapaianTerisi']);
    }

    public function test_tanpa_standar_ringkasan_tidak_membagi_nol(): void
    {
        $ringkasan = LaporanController::hitungRingkasan(LaporanController::ringkasKelompok(KelompokStandar::susun([])));

        $this->assertSame(0, $ringkasan['standar']);
        $this->assertSame(0.0, $ringkasan['persen']);
    }
}