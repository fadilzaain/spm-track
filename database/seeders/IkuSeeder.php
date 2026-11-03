<?php

namespace Database\Seeders;

use App\Models\Iku;
use App\Models\IkuNilai;
use App\Models\IkuTahunan;
use App\Models\SasaranIku;
use App\Support\PeriodeSpm;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Impor data IKU tahun 2025 dan 2026 (dari spreadsheet IKU) ke tabel IKU.
 */
class IkuSeeder extends Seeder
{
    public function run(): void
    {
        // Sasaran (kolom KINERJA) - berdasarkan data spreadsheet
        $sasaran = [
            'pasien' => 'Terwujudnya Pelayanan Kesehatan yang Unggul, Aman, dan Berorientasi pada Keselamatan Pasien',
            'mutu' => 'Meningkatnya Kualitas Pelayanan Kesehatan di RSUD Kabupaten Jombang',
            'birokrasi' => 'Terwujudnya Tata Kelola Birokrasi Perangkat Daerah yang bersih dan efektif',
        ];

        // Indikator + target tahunan + realisasi.
        $indikator = [
            [
                'sasaran' => 'pasien',
                'nama' => 'Nilai NDR',
                'tahun' => [
                    2025 => [
                        'target' => '40‰',
                        'realisasi' => [3 => '37,21‰', 6 => '39,27‰', 9 => '44,54‰', 12 => '46,62%'],
                    ],
                    2026 => [
                        'target' => '38‰',
                        'realisasi' => [3 => '44,17‰', 6 => '38,61‰'],
                    ],
                ],
            ],
            [
                'sasaran' => 'mutu',
                'nama' => 'Persentase Capaian Indikator Nasional Mutu (INM) RS',
                'tahun' => [
                    2025 => [
                        'target' => '80.00%',
                        'realisasi' => [3 => '76,92%', 6 => '84,62%', 9 => '84,62%', 12 => '84,62%'],
                    ],
                    2026 => [
                        'target' => '82.00%',
                        'realisasi' => [3 => '100.00%', 6 => '100.00%'],
                    ],
                ],
            ],
            [
                'sasaran' => 'mutu',
                'nama' => 'Status Akreditasi',
                'tahun' => [
                    2025 => [
                        'target' => '100.00%',
                        'realisasi' => [3 => '100.00%', 6 => '100.00%', 9 => '100.00%', 12 => '100'],
                    ],
                    2026 => [
                        'target' => '100.00%',
                        'realisasi' => [3 => '100.00%', 6 => '100.00%'],
                    ],
                ],
            ],
            [
                'sasaran' => 'birokrasi',
                'nama' => 'Indeks Reformasi Birokrasi Perangkat Daerah (IRB PD)',
                'tahun' => [
                    2025 => [
                        'target' => '82.00%',
                        'realisasi' => [6 => '81,93', 9 => '81,93'],
                    ],
                    2026 => [
                        'target' => '82,5%',
                        'realisasi' => [6 => '80,74'],
                    ],
                ],
            ],
        ];

        $jumlahNilai = 0;

        // Satu transaksi: kalau ada yang gagal, tidak ada data setengah jadi
        DB::transaction(function () use ($sasaran, $indikator, &$jumlahNilai) {
            // 1. Simpan sasaran, catat id-nya
            $idSasaran = [];
            $urutSasaran = 1;
            foreach ($sasaran as $kunci => $nama) {
                $idSasaran[$kunci] = SasaranIku::updateOrCreate(
                    ['nama' => $nama],
                    ['urutan' => $urutSasaran++]
                )->id;
            }

            // 2. Simpan tiap indikator beserta target dan realisasinya
            foreach ($indikator as $urut => $i) {
                $iku = Iku::updateOrCreate(
                    [
                        'sasaran_iku_id' => $idSasaran[$i['sasaran']],
                        'nama_indikator' => $i['nama'],
                    ],
                    [
                        'frekuensi' => 'triwulan',
                        'urutan' => $urut + 1,
                    ]
                );

                foreach ($i['tahun'] as $tahun => $isi) {
                    // Hanya kolom target yang di-update, kolom capaian dibiarkan
                    IkuTahunan::updateOrCreate(
                        ['iku_id' => $iku->id, 'tahun' => $tahun],
                        ['target' => $isi['target']]
                    );

                    foreach ($isi['realisasi'] as $bulan => $nilai) {
                        // Bulan penutup triwulan dikonversi ke kunci periode (triwulan, 1-4)
                        [$tipe, $no] = PeriodeSpm::kunci($iku->frekuensi, $bulan);

                        IkuNilai::updateOrCreate(
                            [
                                'iku_id' => $iku->id,
                                'tahun' => $tahun,
                                'periode_tipe' => $tipe,
                                'periode_no' => $no,
                            ],
                            ['nilai' => $nilai]
                        );
                        $jumlahNilai++;
                    }
                }
            }
        });

        $this->command->info('Impor IKU selesai: '.count($sasaran).' sasaran, '.count($indikator)." indikator, {$jumlahNilai} nilai realisasi.");
    }
}