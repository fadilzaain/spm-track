<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Iku extends Model
{
    // Nama tabel
    protected $table = 'iku';

    // Tabel ini tidak punya kolom created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'sasaran_iku_id',
        'nama_indikator',
        'frekuensi',
        'urutan',
    ];

    public function sasaran(): BelongsTo
    {
        return $this->belongsTo(SasaranIku::class, 'sasaran_iku_id');
    }

    // Semua realisasi yang pernah diinput untuk indikator ini
    public function nilai(): HasMany
    {
        return $this->hasMany(IkuNilai::class, 'iku_id');
    }

    // Target dan capaian per tahun
    public function tahunan(): HasMany
    {
        return $this->hasMany(IkuTahunan::class, 'iku_id');
    }
}