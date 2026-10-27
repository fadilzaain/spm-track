<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // Konstanta role supaya tidak salah ketik di banyak tempat
    public const ROLE_ADMIN = 'admin';
    public const ROLE_PETUGAS_UNIT = 'petugas_unit';
    public const ROLE_MANAJEMEN = 'manajemen';

    protected $table = 'users';

    protected $fillable = [
        'nama',
        'username',
        'password',
        'role',
        'jenis_pelayanan_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            // Password otomatis di-hash saat diisi
            'password' => 'hashed',
        ];
    }

    // Layanan tempat petugas unit bertugas (kosong untuk Admin & Manajemen)
    public function jenisPelayanan(): BelongsTo
    {
        return $this->belongsTo(JenisPelayanan::class, 'jenis_pelayanan_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isPetugasUnit(): bool
    {
        return $this->role === self::ROLE_PETUGAS_UNIT;
    }

    public function isManajemen(): bool
    {
        return $this->role === self::ROLE_MANAJEMEN;
    }
}