<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pencapaian extends Model
{
    // Nama tabel ditulis manual supaya tidak ditebak Laravel jadi "pencapaians"
    protected $table = 'pencapaian';

    protected $fillable = [
        'standar_indikator_id',
        'tahun',
        'nilai',
        'dibuat_oleh',
        'diubah_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
        ];
    }

    public function standar(): BelongsTo
    {
        return $this->belongsTo(StandarIndikator::class, 'standar_indikator_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }
}