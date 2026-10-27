<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel spm lama kosong (0 baris) dan nyambung ke indikator,
        // jadi aman di-drop lalu dibuat ulang supaya nyambung ke standar_indikator
        Schema::dropIfExists('spm');

        Schema::create('spm', function (Blueprint $table) {
            $table->increments('id');

            // Tipe harus sama dengan standar_indikator.id (int bertanda), makanya bukan foreignId()
            $table->integer('standar_indikator_id');

            // Periode pelaporan
            $table->unsignedSmallInteger('tahun');
            $table->string('periode_tipe', 10);        // bulanan / triwulan / tahunan
            $table->unsignedTinyInteger('periode_no'); // bulan 1-12, triwulan 1-4, tahunan = 1

            // Nilai diinput Divisi Perencanaan berupa teks, sistem tidak menghitung
            $table->string('nilai', 255);
            $table->string('satuan', 255)->nullable();

            // Siapa yang membuat dan mengubah (tipe sama dengan users.id)
            $table->integer('dibuat_oleh')->nullable();
            $table->integer('diubah_oleh')->nullable();
            $table->timestamps();

            // Satu standar hanya boleh punya satu nilai per periode
            $table->unique(
                ['standar_indikator_id', 'tahun', 'periode_tipe', 'periode_no'],
                'spm_nilai_unik'
            );

            $table->foreign('standar_indikator_id', 'fk_spm_standar_indikator')
                ->references('id')->on('standar_indikator')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('dibuat_oleh', 'fk_spm_dibuat_oleh')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('diubah_oleh', 'fk_spm_diubah_oleh')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Struktur tabel spm yang lama tidak dikembalikan, cukup dihapus
        Schema::dropIfExists('spm');
    }
};