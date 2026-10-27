<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indikator', function (Blueprint $table) {
            // Frekuensi pelaporan melekat per indikator: bulanan / triwulan / tahunan
            // Semua indikator awalnya bulanan, nanti disesuaikan pas impor Excel 2025
            $table->string('frekuensi', 10)->default('bulanan')->after('nama_indikator');
        });
    }

    public function down(): void
    {
        Schema::table('indikator', function (Blueprint $table) {
            $table->dropColumn('frekuensi');
        });
    }
};