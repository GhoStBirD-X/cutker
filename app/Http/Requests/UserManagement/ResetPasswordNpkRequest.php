<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordNpkRequest extends FormRequest
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
        return [
            'user_ids' => ['required', 'array', 'max:1000'],
            'user_ids.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_ids.required' => 'Pilih minimal satu user.',
        ];
    }
}
