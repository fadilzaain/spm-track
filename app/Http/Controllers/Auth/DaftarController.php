<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DaftarController extends Controller
{
    // Pesan galat 
    private const PESAN = [
        'required' => ':attribute wajib diisi.',
        'required_if' => ':attribute wajib dipilih untuk Petugas Unit.',
        'max' => ':attribute maksimal :max karakter.',
        'min' => ':attribute minimal :min karakter.',
        'unique' => ':attribute sudah dipakai.',
        'confirmed' => 'Konfirmasi password tidak sama.',
        'alpha_dash' => ':attribute hanya boleh huruf, angka, strip, dan garis bawah.',
        'in' => ':attribute tidak valid.',
        'exists' => ':attribute tidak valid.',
    ];

    private const ATRIBUT = [
        'nama' => 'Nama lengkap',
        'username' => 'Username',
        'role' => 'Peran',
        'layanan' => 'Unit pelayanan',
        'password' => 'Password',
    ];

    /**
     * Simpan pendaftaran baru. Akun berstatus "menunggu" dan belum bisa login
     * sampai admin menyetujuinya. Admin tidak bisa dibuat lewat sini.
     */
    public function proses(Request $request): RedirectResponse
    {
        // Galat ditaruh di "bag" sendiri supaya tidak tercampur dengan galat form login
        $data = $request->validateWithBag('daftar', [
            'nama' => ['required', 'string', 'max:150'],
            // max 30 supaya pasti muat di kolom username database lama
            'username' => ['required', 'string', 'alpha_dash:ascii', 'min:4', 'max:30', 'unique:users,username'],
            'role' => ['required', Rule::in([User::ROLE_PETUGAS_UNIT, User::ROLE_MANAJEMEN])],
            'layanan' => ['nullable', 'required_if:role,'.User::ROLE_PETUGAS_UNIT, 'integer', 'exists:jenis_pelayanan,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], self::PESAN, self::ATRIBUT);

        User::create([
            'nama' => $data['nama'],
            'username' => $data['username'],
            'password' => $data['password'],
            'role' => $data['role'],
            'status' => User::STATUS_MENUNGGU,
            // Manajemen tidak terikat satu unit
            'jenis_pelayanan_id' => $data['role'] === User::ROLE_PETUGAS_UNIT ? $data['layanan'] : null,
        ]);

        return redirect()->route('login')
            ->with('status', 'Pendaftaran terkirim. Akun Anda bisa dipakai setelah disetujui admin.');
    }
}