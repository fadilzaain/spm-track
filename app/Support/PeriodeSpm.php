<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Kumpulan fungsi kecil soal periode SPM, dipakai bersama oleh halaman input.
 * Aturan periodenya sama dengan yang dipakai DashboardController.
 */
class PeriodeSpm
{
    // Nama bulan untuk tampilan (kunci 1 sampai 12)
    public const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * Kunci periode yang dipakai di tabel spm untuk satu bulan.
     * bulanan = bulan itu sendiri, triwulan = triwulan yang memuat bulan itu, tahunan = periode 1.
     * Hasil: [periode_tipe, periode_no]
     */
    public static function kunci(string $frekuensi, int $bulan): array
    {
        return match ($frekuensi) {
            'triwulan' => ['triwulan', (int) ceil($bulan / 3)],
            'tahunan' => ['tahunan', 1],
            default => ['bulanan', $bulan],
        };
    }

    // Label periode untuk ditampilkan di form (contoh: "Triwulan 2")
    public static function label(string $frekuensi, int $bulan): string
    {
        return match ($frekuensi) {
            'triwulan' => 'Triwulan '.(int) ceil($bulan / 3),
            'tahunan' => 'Tahunan',
            default => self::NAMA_BULAN[$bulan],
        };
    }

    // Daftar tahun untuk pilihan filter: tahun yang sudah ada datanya + tahun berjalan + tahun terpilih
    public static function daftarTahun(int $tahunDipilih): array
    {
        $daftar = DB::table('spm')->distinct()->pluck('tahun')->map(fn ($t) => (int) $t)->all();

        foreach ([now()->year, $tahunDipilih] as $t) {
            if (! in_array($t, $daftar, true)) {
                $daftar[] = $t;
            }
        }

        rsort($daftar);

        return $daftar;
    }
}