<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Impor data SPM tahun 2025 (dari REKAP_SPM_2025.xlsx) ke tabel `spm`.
 *
 * Sumber data : database/data/spm_2025.csv
 * Kolom CSV   : standar_indikator_id, tahun, periode_tipe, periode_no, nilai
 * Aturan      : sel "-" / kosong di Excel tidak ikut diimpor (dianggap belum terisi)
 *
 * Aman dijalankan berulang kali: baris yang sama akan di-update, bukan dobel.
 * Jalankan: php artisan db:seed --class=Spm2025Seeder
 */
class Spm2025Seeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/spm_2025.csv');

        // Pastikan file CSV ada di tempat yang benar
        if (! is_file($path)) {
            $this->command->error("File tidak ditemukan: {$path}");

            return;
        }

        // Daftar id standar yang benar-benar ada di database (untuk validasi)
        $standarValid = DB::table('standar_indikator')->pluck('id')->flip()->all();

        $handle = fopen($path, 'r');
        fgetcsv($handle, 0, ',', '"', '\\'); // lewati baris header

        $sekarang = now();
        $baris = [];
        $dilewati = 0;
        $standarTriwulan = [];

        while (($kolom = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            // Lewati baris kosong
            if (count($kolom) < 5) {
                continue;
            }

            [$standarId, $tahun, $periodeTipe, $periodeNo, $nilai] = $kolom;
            $standarId = (int) $standarId;

            // Lewati kalau id standar tidak ada di database
            if (! isset($standarValid[$standarId])) {
                $dilewati++;

                continue;
            }

            if ($periodeTipe === 'triwulan') {
                $standarTriwulan[$standarId] = true;
            }

            $baris[] = [
                'standar_indikator_id' => $standarId,
                'tahun' => (int) $tahun,
                'periode_tipe' => $periodeTipe,
                'periode_no' => (int) $periodeNo,
                'nilai' => trim($nilai), // nilai disimpan sebagai teks, apa adanya
                'satuan' => null,
                'dibuat_oleh' => null,   // data awal hasil impor, bukan input user
                'diubah_oleh' => null,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ];
        }
        fclose($handle);

        DB::transaction(function () use ($baris, $standarTriwulan) {
            // Masukkan per 100 baris; kalau kombinasi unik sudah ada, nilainya di-update
            foreach (array_chunk($baris, 100) as $paket) {
                DB::table('spm')->upsert(
                    $paket,
                    ['standar_indikator_id', 'tahun', 'periode_tipe', 'periode_no'],
                    ['nilai', 'updated_at']
                );
            }

            // Indikator yang dilaporkan per triwulan: frekuensinya diubah dari default 'bulanan'
            if ($standarTriwulan) {
                $indikatorIds = DB::table('standar_indikator')
                    ->whereIn('id', array_keys($standarTriwulan))
                    ->pluck('indikator_id');

                DB::table('indikator')
                    ->whereIn('id', $indikatorIds)
                    ->update(['frekuensi' => 'triwulan']);
            }
        });

        $this->command->info('Impor selesai: '.count($baris).' nilai masuk ke tabel spm.');

        if ($dilewati > 0) {
            $this->command->warn("{$dilewati} baris dilewati karena id standar tidak ada di database.");
        }
    }
}