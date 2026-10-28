<?php

namespace App\Http\Controllers;

use App\Models\JenisPelayanan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PenggunaController extends Controller
{
    // Tombol di form => [status baru (null = status tetap), kata untuk pesan hasil]
    private const AKSI = [
        'setujui' => [User::STATUS_AKTIF, 'disetujui dan sudah aktif'],
        'tolak' => [User::STATUS_DITOLAK, 'ditolak'],
        'nonaktifkan' => [User::STATUS_NONAKTIF, 'dinonaktifkan'],
        'simpan' => [null, 'diperbarui'],
    ];

    // Daftar akun. Filter lewat ?status= (default: yang menunggu persetujuan)
    public function index(Request $request)
    {
        $status = (string) $request->query('status', User::STATUS_MENUNGGU);
        if ($status !== 'semua' && ! isset(User::LABEL_STATUS[$status])) {
            $status = User::STATUS_MENUNGGU;
        }

        // Jumlah akun per status, ditampilkan di pilihan filter
        $jumlah = User::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status')->all();

        $opsiStatus = ['semua' => 'Semua ('.array_sum($jumlah).')'];
        foreach (User::LABEL_STATUS as $kode => $label) {
            $opsiStatus[$kode] = $label.' ('.($jumlah[$kode] ?? 0).')';
        }

        return view('pengguna.index', [
            'status' => $status,
            'opsiStatus' => $opsiStatus,
            'daftarPengguna' => User::when($status !== 'semua', fn ($q) => $q->where('status', $status))
                ->orderByDesc('created_at')
                ->orderBy('id')
                ->get(),
            'daftarLayanan' => JenisPelayanan::orderBy('id')->pluck('nama_pelayanan', 'id')->all(),
        ]);
    }

    // Setujui, tolak, nonaktifkan, atau simpan perubahan role/unit satu akun
    public function perbarui(Request $request, User $pengguna): RedirectResponse
    {
        // Admin tidak boleh mengubah akunnya sendiri, supaya tidak terkunci dari sistem
        if ($pengguna->is($request->user())) {
            return back()->withErrors(['aksi' => 'Akun Anda sendiri tidak bisa diubah dari halaman ini.']);
        }

        $data = $request->validate([
            'aksi' => ['required', Rule::in(array_keys(self::AKSI))],
            'role' => ['nullable', Rule::in(array_keys(User::LABEL_ROLE))],
            'layanan' => ['nullable', 'integer', 'exists:jenis_pelayanan,id'],
        ]);

        [$statusBaru, $kataKerja] = self::AKSI[$data['aksi']];
        $ubah = $statusBaru ? ['status' => $statusBaru] : [];

        // Role dan unit hanya diubah saat menyetujui atau menyimpan (bukan saat menolak/menonaktifkan)
        if (in_array($data['aksi'], ['setujui', 'simpan'], true)) {
            $role = $data['role'] ?? $pengguna->role;
            $layanan = $role === User::ROLE_PETUGAS_UNIT ? ($data['layanan'] ?? null) : null;

            if ($role === User::ROLE_PETUGAS_UNIT && ! $layanan) {
                return back()->withErrors(['layanan' => 'Petugas Unit wajib punya unit pelayanan.']);
            }

            $ubah += ['role' => $role, 'jenis_pelayanan_id' => $layanan];
        }

        $pengguna->update($ubah);

        return back()->with('status', 'Akun '.($pengguna->nama ?: $pengguna->username)." {$kataKerja}.");
    }
}