<?php

namespace App\Http\Requests;

use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupRequest extends FormRequest
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
        $client = app(CurrentClientService::class);
        $clientId = $this->integer('client_id') ?: $client->id();
        $allowedClientIds = $this->user()?->is_super_admin
            ? $client->availableFor($this->user())->modelKeys()
            : [$client->id()];

        return ['client_id' => ['nullable', 'integer', Rule::in($allowedClientIds)], 'code' => ['nullable', 'string', 'max:50', Rule::unique('groups', 'code')->where(fn ($query) => $query->where('client_id', $clientId))], 'name' => ['required', 'string', 'max:100'], 'status' => ['required', Rule::in(['active', 'inactive'])]];
    }

    public function messages(): array
    {
        return ['client_id.in' => 'Client yang dipilih tidak valid.', 'code.unique' => 'Kode group sudah digunakan pada client ini.', 'name.required' => 'Nama group wajib diisi.', 'status.in' => 'Status group tidak valid.'];
    }
}
