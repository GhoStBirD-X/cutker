<?php

namespace App\Http\Requests\Approval;

use App\Models\Approval;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApprovalActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Approval $approval */
        $approval = $this->route('approval');

        return $this->user()->can('act', $approval);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
