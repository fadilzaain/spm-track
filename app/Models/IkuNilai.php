<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IkuNilai extends Model
{
    protected $table = 'iku_nilai';

    protected $fillable = [
        'iku_id',
        'tahun',
        'periode_tipe',
        'periode_no',
        'nilai',
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