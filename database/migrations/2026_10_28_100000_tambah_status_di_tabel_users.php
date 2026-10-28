<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Status akun: menunggu / aktif / ditolak / nonaktif.
            // Akun yang sudah ada otomatis "aktif" (lewat default), pendaftar baru diisi "menunggu" oleh kode.
            $table->string('status', 12)->default('aktif')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};