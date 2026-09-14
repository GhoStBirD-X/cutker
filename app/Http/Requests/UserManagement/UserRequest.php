<?php

namespace App\Http\Requests\UserManagement;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('user.manage');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', Password::default()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(['karyawan', 'kepala_bagian', 'koordinator_shift', 'hrd', 'manager', 'admin'])],
            'karyawan_id' => ['nullable', 'integer', 'exists:karyawans,id', Rule::unique('users', 'karyawan_id')->ignore($user)],
        ];
    }
}
