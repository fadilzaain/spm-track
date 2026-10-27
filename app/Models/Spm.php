<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Spm extends Model
{
    protected $table = 'spm';

    protected $fillable = [
        'standar_indikator_id',
        'tahun',
        'periode_tipe',
        'periode_no',
        'nilai',
        'satuan',
        'dibuat_oleh',
        'diubah_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'periode_no' => 'integer',
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