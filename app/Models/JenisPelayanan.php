<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisPelayanan extends Model
{
    // Nama tabel di database tidak pakai "s", jadi harus ditulis manual
    protected $table = 'jenis_pelayanan';

    // Tabel ini tidak punya kolom created_at / updated_at
    public $timestamps = false;

    protected $fillable = ['nama_pelayanan'];

    public function indikator(): HasMany
    {
        return $this->hasMany(Indikator::class, 'jenis_pelayanan_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'jenis_pelayanan_id');
    }
}