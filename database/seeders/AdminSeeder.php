<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Membuat akun Admin
     *
     *     php artisan db:seed --class=AdminSeeder
     *
     */
    public function run(): void
    {
        $username = trim((string) $this->command->ask('Username admin', 'admin'));
        $nama = trim((string) $this->command->ask('Nama lengkap admin', 'Administrator'));

        // Jangan menimpa akun yang sudah ada (mencegah password admin lama terganti tanpa sengaja)
        if (User::where('username', $username)->exists()) {
            $this->command->error("Username \"{$username}\" sudah ada. Tidak ada yang diubah.");

            return;
        }

        $password = (string) $this->command->secret('Password admin (minimal 12 karakter)');
        $ulangi = (string) $this->command->secret('Ketik ulang password');

        if (mb_strlen($password) < 12) {
            $this->command->error('Password terlalu pendek (minimal 12 karakter). Akun tidak dibuat.');

            return;
        }

        if ($password !== $ulangi) {
            $this->command->error('Kedua password tidak sama. Akun tidak dibuat.');

            return;
        }

        // Password otomatis di-hash oleh model User (lihat casts di User.php)
        User::create([
            'nama' => $nama,
            'username' => $username,
            'password' => $password,
            'role' => User::ROLE_ADMIN,
            'jenis_pelayanan_id' => null, // Admin tidak terikat satu unit pelayanan
        ]);

        $this->command->info("Akun Admin \"{$username}\" berhasil dibuat.");
    }
}