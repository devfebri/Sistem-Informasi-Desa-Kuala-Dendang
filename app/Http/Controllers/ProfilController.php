<?php

namespace App\Http\Controllers;

use App\Models\Penduduk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Profil milik pengguna yang sedang login (semua role). */
class ProfilController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return view('profil.show', [
            'user' => $user,
            'penduduk' => $user->nik ? Penduduk::with(['wilayah:id,nama_dusun,rt,rw'])->where('nik', $user->nik)->first() : null,
        ]);
    }

    /** Hanya nama yang dapat diubah sendiri; username & role diatur admin. */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']], [], ['name' => 'nama lengkap']);

        $request->user()->update($data);

        return response()->json(['message' => 'Profil berhasil diperbarui.']);
    }

    public function password(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed', 'different:password_lama'],
        ], [
            'password_lama.current_password' => 'Kata sandi lama tidak sesuai.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi lama.',
        ], ['password_lama' => 'kata sandi lama', 'password' => 'kata sandi baru']);

        $request->user()->update(['password' => $data['password']]);

        return response()->json(['message' => 'Kata sandi berhasil diganti.']);
    }
}
