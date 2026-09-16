<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('client'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $client = $this->route('client');

        $accountUser = $this->accountUser($client);

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('clients', 'code')->ignore($client instanceof Client ? $client->getKey() : null)],
            'name' => ['required', 'string', 'max:150'],
            'account_name' => ['required', 'string', 'max:150'],
            'login_username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($accountUser?->getKey())],
            'login_email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($accountUser?->getKey())],
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed'],
            'password_confirmation' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    private function accountUser(mixed $client): ?User
    {
        if (! $client instanceof Client) {
            return null;
        }

        $clientRoleId = Role::query()->where('code', 'client')->value('id');

        return $clientRoleId
            ? $client->users()->wherePivot('role_id', $clientRoleId)->orderByPivot('is_default', 'desc')->first()
            : null;
    }
}
