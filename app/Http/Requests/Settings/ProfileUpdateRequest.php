<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_hp.regex' => 'Nomor HP hanya boleh berisi angka, spasi, tanda hubung, dan awalan +.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()->id),
            'no_hp' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]+$/'],
        ];
    }
}
