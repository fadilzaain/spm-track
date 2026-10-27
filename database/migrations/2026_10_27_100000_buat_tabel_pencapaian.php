<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pencapaian', function (Blueprint $table) {
            $table->increments('id');

            // Tipe harus sama dengan standar_indikator.id (int bertanda), makanya bukan foreignId()
            $table->integer('standar_indikator_id');

            // Pencapaian bersifat tahunan: satu nilai per standar per tahun
            $table->unsignedSmallInteger('tahun');

            // Nilai pencapaian diinput unit berupa teks, sistem tidak menghitung
            $table->string('nilai', 255);

            // Siapa yang membuat dan mengubah (tipe sama dengan users.id)
            $table->integer('dibuat_oleh')->nullable();
            $table->integer('diubah_oleh')->nullable();
            $table->timestamps();

            // Satu standar hanya boleh punya satu pencapaian per tahun
            $table->unique(['standar_indikator_id', 'tahun'], 'pencapaian_unik');

            $table->foreign('standar_indikator_id', 'fk_pencapaian_standar_indikator')
                ->references('id')->on('standar_indikator')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('dibuat_oleh', 'fk_pencapaian_dibuat_oleh')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('diubah_oleh', 'fk_pencapaian_diubah_oleh')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pencapaian');
    }
};