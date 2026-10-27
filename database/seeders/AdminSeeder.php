<?php

namespace Database\Seeders;

use App\Models\JenisPelayanan;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Password sementara untuk semua akun contoh.
        // WAJIB diganti atau akun contoh dihapus sebelum dipakai di server RS.
        $passwordSementara = 'Spm@12345';

        // Akun Admin awal (tidak terikat layanan)
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'nama' => 'Administrator',
                'password' => $passwordSementara,
                'role' => User::ROLE_ADMIN,
                'jenis_pelayanan_id' => null,
            ]
        );

        // Akun contoh Manajemen, untuk tes role (hapus nanti)
        User::updateOrCreate(
            ['username' => 'manajemen'],
            [
                'nama' => 'Contoh Manajemen',
                'password' => $passwordSementara,
                'role' => User::ROLE_MANAJEMEN,
                'jenis_pelayanan_id' => null,
            ]
        );

        // Akun contoh Petugas Unit, dikaitkan ke layanan pertama di database (hapus nanti)
        $layanan = JenisPelayanan::orderBy('id')->first();

        if ($layanan) {
            User::updateOrCreate(
                ['username' => 'petugas'],
                [
                    'nama' => 'Contoh Petugas Unit',
                    'password' => $passwordSementara,
                    'role' => User::ROLE_PETUGAS_UNIT,
                    'jenis_pelayanan_id' => $layanan->id,
                ]
            );
        }
    }
}