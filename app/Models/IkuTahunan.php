<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IkuTahunan extends Model
{
    // Nama tabel 
    protected $table = 'iku_tahunan';

    protected $fillable = [
        'iku_id',
        'tahun',
        'target',
        'capaian',
        'dibuat_oleh',
        'diubah_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
        ];
    }

    public function iku(): BelongsTo
    {
        return $this->belongsTo(Iku::class, 'iku_id');
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