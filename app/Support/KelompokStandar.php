<?php

namespace App\Support;

/**
 * Mengelompokkan baris standar menjadi pelayanan - indikator - standar.
 */
class KelompokStandar
{
    /**
     * Setiap baris wajib punya: layanan_id, layanan, indikator_id, indikator.
     * Urutan baris dipertahankan seperti yang diterima.
     *
     * Hasil: daftar pelayanan, masing-masing berisi
     *   id, nama, jumlah (semua baris pelayanan itu), baris (semua baris pelayanan itu),
     *   indikator => daftar [id, nama, jumlah, baris]
     */
    public static function susun(iterable $baris): array
    {
        $hasil = [];

        foreach ($baris as $r) {
            $layananId = (int) $r['layanan_id'];
            $indikatorId = (int) $r['indikator_id'];

            $hasil[$layananId] ??= [
                'id' => $layananId,
                'nama' => $r['layanan'],
                'jumlah' => 0,
                'baris' => [],
                'indikator' => [],
            ];
            $hasil[$layananId]['indikator'][$indikatorId] ??= [
                'id' => $indikatorId,
                'nama' => $r['indikator'],
                'jumlah' => 0,
                'baris' => [],
            ];

            $hasil[$layananId]['jumlah']++;
            $hasil[$layananId]['baris'][] = $r;
            $hasil[$layananId]['indikator'][$indikatorId]['jumlah']++;
            $hasil[$layananId]['indikator'][$indikatorId]['baris'][] = $r;
        }

        // Buang kunci id supaya hasilnya daftar biasa yang rapi dipakai @foreach
        return array_values(array_map(function (array $g) {
            $g['indikator'] = array_values($g['indikator']);

            return $g;
        }, $hasil));
    }
}