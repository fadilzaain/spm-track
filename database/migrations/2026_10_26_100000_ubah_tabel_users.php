<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_jenis_pelayanan');
        });

        // Ubah kolom lama dan tambah kolom baru
        Schema::table('users', function (Blueprint $table) {
            // Admin & Manajemen tidak terikat satu layanan, jadi boleh kosong
            $table->integer('jenis_pelayanan_id')->nullable()->change();
            $table->string('nama', 150)->nullable()->after('username');
            // Role: admin / petugas_unit / manajemen
            $table->string('role', 20)->default('petugas_unit')->after('password');
            $table->rememberToken();
            $table->timestamps();
            // Username harus unik buat login
            $table->unique('username');
        });

        // Pasang lagi FK dengan nama yang sama seperti aslinya
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('jenis_pelayanan_id', 'fk_users_jenis_pelayanan')
                ->references('id')->on('jenis_pelayanan')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_jenis_pelayanan');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['nama', 'role', 'remember_token', 'created_at', 'updated_at']);
            // Catatan: gagal kalau sudah ada baris dengan jenis_pelayanan_id kosong
            $table->integer('jenis_pelayanan_id')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('jenis_pelayanan_id', 'fk_users_jenis_pelayanan')
                ->references('id')->on('jenis_pelayanan')
                ->onUpdate('cascade');
        });
    }
};