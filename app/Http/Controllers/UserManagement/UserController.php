<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\ResetPasswordNpkRequest;
use App\Http\Requests\UserManagement\UserRequest;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use HasPerPage;

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        $users = User::query()
            ->with('roles', 'karyawan')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($this->resolvePerPage($request))
            ->withQueryString();

        return Inertia::render('users/index', [
            'users' => $users,
            'karyawans' => Karyawan::query()->whereDoesntHave('user')->orderBy('nama')->get(['id', 'nama', 'nip']),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        $user->karyawan_id = $data['karyawan_id'] ?? null;
        $user->save();
        $user->syncRoles($data['roles']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User berhasil ditambahkan.']);

        return back();
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->karyawan_id = $data['karyawan_id'] ?? null;
        $user->save();
        $user->syncRoles($data['roles']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User berhasil diperbarui.']);

        return back();
    }

    /**
     * Reset password user terpilih menjadi NPK karyawan masing-masing
     * (bisa satu atau banyak sekaligus). User tanpa profil karyawan (tidak
     * punya NPK) dan akun admin yang menjalankannya sendiri dilewati.
     */
    public function resetPasswordKeNpk(ResetPasswordNpkRequest $request): RedirectResponse
    {
        $userIds = collect($request->validated('user_ids'))->unique();

        $users = User::query()
            ->with('karyawan')
            ->whereKey($userIds)
            ->whereKeyNot($request->user()->id)
            ->whereHas('karyawan')
            ->get();

        foreach ($users as $user) {
            $user->password = Hash::make($user->karyawan->nip);
            $user->save();
        }

        $dilewati = $userIds->count() - $users->count();

        Inertia::flash('toast', [
            'type' => $users->isEmpty() ? 'error' : 'success',
            'message' => $users->isEmpty()
                ? 'Tidak ada password yang direset. User tanpa profil karyawan (NPK) dan akun Anda sendiri dilewati.'
                : "Password {$users->count()} user berhasil direset menjadi NPK masing-masing."
                    .($dilewati > 0 ? " {$dilewati} user dilewati (tanpa NPK atau akun Anda sendiri)." : ''),
        ]);

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Anda tidak bisa menghapus akun Anda sendiri.']);

            return back();
        }

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User berhasil dihapus.']);

        return back();
    }
}
