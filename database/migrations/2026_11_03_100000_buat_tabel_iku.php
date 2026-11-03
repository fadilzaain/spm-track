<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Sasaran = kolom KINERJA di spreadsheet (satu sasaran bisa punya beberapa indikator)
        Schema::create('sasaran_iku', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama', 255)->unique();
            // Urutan tampil, mengikuti urutan di spreadsheet
            $table->unsignedSmallInteger('urutan')->default(0);
        });

        // 2. Indikator Kinerja Utama
        Schema::create('iku', function (Blueprint $table) {
            $table->increments('id');

            // Tipe harus sama dengan sasaran_iku.id (unsigned int, dibuat di atas)
            $table->unsignedInteger('sasaran_iku_id');
            $table->string('nama_indikator', 255);

            // Frekuensi pelaporan melekat per indikator: bulanan / triwulan / tahunan
            $table->string('frekuensi', 10)->default('triwulan');
            $table->unsignedSmallInteger('urutan')->default(0);

            $table->unique(['sasaran_iku_id', 'nama_indikator'], 'iku_unik');

            $table->foreign('sasaran_iku_id', 'fk_iku_sasaran')
                ->references('id')->on('sasaran_iku')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        // 3. Target dan capaian: keduanya isian tahunan, jadi digabung satu tabel
        Schema::create('iku_tahunan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('iku_id');
            $table->unsignedSmallInteger('tahun');

            // Semua berupa teks apa adanya (contoh: "40‰", "100.00%"), sistem tidak menghitung
            $table->string('target', 255)->nullable();
            $table->string('capaian', 255)->nullable();

            // Siapa yang membuat dan mengubah (tipe sama dengan users.id yaitu int bertanda)
            $table->integer('dibuat_oleh')->nullable();
            $table->integer('diubah_oleh')->nullable();
            $table->timestamps();

            // Satu indikator hanya punya satu target/capaian per tahun
            $table->unique(['iku_id', 'tahun'], 'iku_tahunan_unik');

            $table->foreign('iku_id', 'fk_iku_tahunan_iku')
                ->references('id')->on('iku')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('dibuat_oleh', 'fk_iku_tahunan_dibuat_oleh')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('diubah_oleh', 'fk_iku_tahunan_diubah_oleh')
                ->references('id')->on('users')
                ->nullOnDelete();
        });

        // 4. Realisasi (nilai per periode), aturannya sama dengan tabel spm
        Schema::create('iku_nilai', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('iku_id');

            // Periode pelaporan
            $table->unsignedSmallInteger('tahun');
            $table->string('periode_tipe', 10);        // bulanan / triwulan / tahunan
            $table->unsignedTinyInteger('periode_no'); // bulan 1-12, triwulan 1-4, tahunan = 1

            // Nilai berupa teks, sistem tidak menghitung
            $table->string('nilai', 255);

            $table->integer('dibuat_oleh')->nullable();
            $table->integer('diubah_oleh')->nullable();
            $table->timestamps();

            // Satu indikator hanya boleh punya satu nilai per periode
            $table->unique(['iku_id', 'tahun', 'periode_tipe', 'periode_no'], 'iku_nilai_unik');

            $table->foreign('iku_id', 'fk_iku_nilai_iku')
                ->references('id')->on('iku')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('dibuat_oleh', 'fk_iku_nilai_dibuat_oleh')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('diubah_oleh', 'fk_iku_nilai_diubah_oleh')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Urutan dibalik: tabel anak dihapus dulu sebelum tabel induknya
        Schema::dropIfExists('iku_nilai');
        Schema::dropIfExists('iku_tahunan');
        Schema::dropIfExists('iku');
        Schema::dropIfExists('sasaran_iku');
    }
};