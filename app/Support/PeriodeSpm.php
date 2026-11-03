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

    // Nama bulan singkat untuk judul kolom tabel laporan (kunci 1 sampai 12)
    public const NAMA_BULAN_PENDEK = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
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

    /**
     * Apakah bulan ini termasuk bulan lapor untuk frekuensi tersebut?
     * Bulanan: semua bulan. Triwulan: hanya bulan penutup (Mar, Jun, Sep, Des). Tahunan: hanya Desember.
     */
    public static function bulanLapor(string $frekuensi, int $bulan): bool
    {
        return match ($frekuensi) {
            'triwulan' => $bulan % 3 === 0,
            'tahunan' => $bulan === 12,
            default => true,
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

    /**
     * Daftar tahun untuk pilihan filter: tahun yang sudah ada datanya + tahun berjalan + tahun terpilih.
     * $tabel = tabel yang dicek kolom `tahun`-nya (default tabel spm, IKU memakai tabel miliknya sendiri).
     */
    public static function daftarTahun(int $tahunDipilih, array $tabel = ['spm']): array
    {
        $daftar = [now()->year, $tahunDipilih];

        foreach ($tabel as $nama) {
            $daftar = array_merge(
                $daftar,
                DB::table($nama)->distinct()->pluck('tahun')->map(fn ($t) => (int) $t)->all()
            );
        }

        // Buang duplikat lalu urutkan dari tahun terbaru
        $daftar = array_values(array_unique($daftar));
        rsort($daftar);

        return $daftar;
    }
}