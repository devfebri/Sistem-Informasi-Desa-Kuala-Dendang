<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        return view('pengguna.index', ['roles' => User::ROLES]);
    }

    /** Endpoint DataTables server-side (hanya kolom yang ditampilkan). */
    public function data(Request $request)
    {
        $query = User::query()->select(['id', 'name', 'username', 'role', 'nik', 'is_active']);

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'aktif');
        }

        return DataTables::eloquent($query)
            ->filter(function ($q) use ($request) {
                $kata = trim((string) $request->input('search.value'));
                if ($kata !== '') {
                    $q->where(function ($w) use ($kata) {
                        $w->where('username', 'like', $kata.'%')
                            ->orWhere('name', 'like', $kata.'%')
                            ->orWhere('nik', 'like', $kata.'%');
                    });
                }
            })
            ->addIndexColumn()
            ->editColumn('role', fn (User $u) => '<span class="badge text-bg-'.match ($u->role) {
                'admin' => 'primary', 'kades' => 'success', default => 'secondary',
            }.'">'.e($u->roleLabel()).'</span>')
            ->editColumn('is_active', fn (User $u) => $u->is_active
                ? '<span class="badge text-bg-success">Aktif</span>'
                : '<span class="badge text-bg-danger">Nonaktif</span>')
            ->editColumn('nik', fn (User $u) => e($u->nik ?? '-'))
            ->addColumn('aksi', fn (User $u) => view('pengguna._aksi', ['user' => $u])->render())
            ->rawColumns(['role', 'is_active', 'aksi'])
            ->toJson();
    }

    public function store(UserRequest $request): JsonResponse
    {
        User::create($this->payload($request));

        return response()->json(['message' => 'Akun pengguna berhasil ditambahkan.']);
    }

    /** Data untuk mengisi modal edit. */
    public function edit(User $user): JsonResponse
    {
        return response()->json($user->only(['id', 'name', 'username', 'role', 'nik', 'is_active']));
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $data = $this->payload($request);

        if ($user->is(auth()->user()) && (! $data['is_active'] || $data['role'] !== 'admin')) {
            return response()->json([
                'message' => 'Anda tidak dapat menonaktifkan atau mengubah role akun Anda sendiri.',
            ], 422);
        }

        $user->update($data);

        return response()->json(['message' => 'Akun pengguna berhasil diperbarui.']);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->is(auth()->user())) {
            return response()->json(['message' => 'Anda tidak dapat menghapus akun sendiri.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Akun pengguna berhasil dihapus.']);
    }

    private function payload(UserRequest $request): array
    {
        $data = $request->validated();
        $data['nik'] = $data['role'] === 'warga' ? $data['nik'] : null;

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
