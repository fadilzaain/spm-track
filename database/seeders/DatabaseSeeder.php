<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Untuk saat ini hanya membuat akun awal
        $this->call([
            AdminSeeder::class,
        ]);
    }
}