<?php

namespace App\Http\Requests;

use App\Models\Group;
use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->status === 'active' && app(CurrentClientService::class)->isResolved();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $group = $this->route('group');
        $clientId = app(CurrentClientService::class)->id();

        return ['code' => ['nullable', 'string', 'max:50', Rule::unique('groups', 'code')->where(fn ($query) => $query->where('client_id', $clientId))->ignore($group instanceof Group ? $group->getKey() : null)], 'name' => ['required', 'string', 'max:100'], 'status' => ['required', Rule::in(['active', 'inactive'])]];
    }

    public function messages(): array
    {
        return ['code.unique' => 'Kode group sudah digunakan pada client ini.', 'name.required' => 'Nama group wajib diisi.', 'status.in' => 'Status group tidak valid.'];
    }
}
