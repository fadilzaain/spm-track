<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SasaranIku extends Model
{
    // Nama tabel
    protected $table = 'sasaran_iku';

    public $timestamps = false;

    protected $fillable = ['nama', 'urutan'];

    public function iku(): HasMany
    {
        return $this->hasMany(Iku::class, 'sasaran_iku_id')->orderBy('urutan');
    }
}