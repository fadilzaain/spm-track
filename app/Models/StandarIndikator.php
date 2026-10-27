<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StandarIndikator extends Model
{
    protected $table = 'standar_indikator';

    // Tabel ini tidak punya kolom created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'indikator_id',
        'deskripsi',
    ];

    public function indikator(): BelongsTo
    {
        return $this->belongsTo(Indikator::class, 'indikator_id');
    }

    // Semua nilai yang pernah diinput untuk standar ini
    public function nilai(): HasMany
    {
        return $this->hasMany(Spm::class, 'standar_indikator_id');
    }
}