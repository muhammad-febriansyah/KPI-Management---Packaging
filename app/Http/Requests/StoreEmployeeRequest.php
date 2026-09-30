<?php

namespace App\Http\Requests;

use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('client_id') && app(CurrentClientService::class)->isResolved()) {
            $this->merge(['client_id' => app(CurrentClientService::class)->id()]);
        }
    }

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

        return ['client_id' => ['required', 'integer', Rule::in($allowedClientIds)], 'sim_id' => ['nullable', 'string', 'max:100'], 'full_name' => ['required', 'string', 'max:150'], 'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')], 'phone' => ['required', 'string', 'max:30'], 'join_date' => ['required', 'date'], 'birth_date' => ['nullable', 'date', 'before:today'], 'gender' => ['required', Rule::in(['male', 'female'])], 'employee_status' => ['required', Rule::in(['permanent', 'contract', 'daily'])], 'marital_status' => ['required', Rule::in(['single', 'married', 'divorced', 'widowed'])], 'group_id' => ['nullable', 'integer', Rule::exists('groups', 'id')->where(fn ($query) => $query->where('status', 'active'))], 'password' => ['nullable', 'string', 'min:8', 'max:255']];
    }
}
