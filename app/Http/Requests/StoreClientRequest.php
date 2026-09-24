<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Client::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $requiresAccount = $this->boolean('provision_account')
            || $this->filled('account_name')
            || $this->filled('login_username')
            || $this->filled('login_email')
            || $this->filled('password');

        return [
            'code' => ['required', 'string', 'max:50', 'unique:clients,code'],
            'name' => ['required', 'string', 'max:150'],
            'account_name' => [$requiresAccount ? 'required' : 'nullable', 'string', 'max:150'],
            'login_username' => [$requiresAccount ? 'required' : 'nullable', 'string', 'max:100', 'unique:users,username'],
            'login_email' => [$requiresAccount ? 'required' : 'nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => [$requiresAccount ? 'required' : 'nullable', 'string', 'min:8', 'max:255', 'confirmed'],
            'password_confirmation' => [$requiresAccount ? 'required' : 'nullable', 'string'],
            'provision_account' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
