<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetPasswordPengguna extends Command
{
    /**
     * Jalan terakhir kalau admin sendiri lupa password (tidak ada admin lain yang bisa mereset):
     *
     *     php artisan spm:reset-password username_admin
     */
    protected $signature = 'spm:reset-password {username : Username akun yang direset}';

    protected $description = 'Reset password satu akun lewat terminal (untuk admin yang lupa password)';

    public function handle(): int
    {
        $user = User::where('username', $this->argument('username'))->first();

        if (! $user) {
            $this->error('Username tidak ditemukan.');

            return self::FAILURE;
        }

        // Admin wajib lebih panjang
        $minimal = $user->isAdmin() ? 12 : 8;

        $baru = (string) $this->secret("Password baru untuk \"{$user->username}\" (minimal {$minimal} karakter)");
        $ulangi = (string) $this->secret('Ketik ulang password');

        if (mb_strlen($baru) < $minimal) {
            $this->error("Password terlalu pendek (minimal {$minimal} karakter). Tidak ada yang diubah.");

            return self::FAILURE;
        }

        if ($baru !== $ulangi) {
            $this->error('Kedua password tidak sama. Tidak ada yang diubah.');

            return self::FAILURE;
        }

        // Password otomatis di-hash oleh model User. Token "ingat saya" diganti biar cookie lama gk berlaku.
        $user->forceFill([
            'password' => $baru,
            'wajib_ganti_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        // Keluarkan sesi lama akun ini (hanya kalau sesi disimpan di database)
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        $this->info("Password \"{$user->username}\" berhasil direset.");

        return self::SUCCESS;
    }
}