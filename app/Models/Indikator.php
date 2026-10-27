<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indikator extends Model
{
    // Pilihan frekuensi pelaporan (melekat per indikator)
    public const FREKUENSI_BULANAN = 'bulanan';
    public const FREKUENSI_TRIWULAN = 'triwulan';
    public const FREKUENSI_TAHUNAN = 'tahunan';

    protected $table = 'indikator';

    // Tabel ini tidak punya kolom created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'jenis_pelayanan_id',
        'nama_indikator',
        'frekuensi',
    ];

    public function jenisPelayanan(): BelongsTo
    {
        return $this->belongsTo(JenisPelayanan::class, 'jenis_pelayanan_id');
    }

    public function standar(): HasMany
    {
        return $this->hasMany(StandarIndikator::class, 'indikator_id');
    }
}