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

    // Tulisan role untuk tampilan (satu sumber, dipakai sidebar dan halaman Kelola Pengguna)
    public const LABEL_ROLE = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_PETUGAS_UNIT => 'Petugas Unit',
        self::ROLE_MANAJEMEN => 'Manajemen',
    ];

    // Status akun: pendaftar baru "menunggu" sampai disetujui admin
    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_NONAKTIF = 'nonaktif';

    public const LABEL_STATUS = [
        self::STATUS_MENUNGGU => 'Menunggu',
        self::STATUS_AKTIF => 'Aktif',
        self::STATUS_DITOLAK => 'Ditolak',
        self::STATUS_NONAKTIF => 'Nonaktif',
    ];

    // Pesan di halaman login untuk akun yang belum boleh masuk
    public const PESAN_STATUS = [
        self::STATUS_MENUNGGU => 'Akun Anda masih menunggu persetujuan admin.',
        self::STATUS_DITOLAK => 'Pendaftaran Anda ditolak. Silakan hubungi admin.',
        self::STATUS_NONAKTIF => 'Akun Anda dinonaktifkan. Silakan hubungi admin.',
    ];

    protected $table = 'users';

    protected $fillable = [
        'nama',
        'username',
        'password',
        'role',
        'status',
        'wajib_ganti_password',
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
            // Penanda password sementara dari admin (wajib diganti saat login)
            'wajib_ganti_password' => 'boolean',
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

    // Hanya akun aktif yang boleh memakai sistem
    public function isAktif(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }
}