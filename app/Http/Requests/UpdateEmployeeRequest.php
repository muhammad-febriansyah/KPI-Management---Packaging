<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->status === 'active';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $clientService = app(CurrentClientService::class);
        $currentClientId = $clientService->id();
        $allowedClientIds = $clientService->availableFor($this->user())->modelKeys();
        $clientId = $this->user()?->is_super_admin && $this->filled('client_id')
            ? $this->integer('client_id')
            : $currentClientId;

        $employee = $this->route('employee');
        $userId = $employee instanceof Employee ? $employee->user_id : null;
        $currentGroupId = $employee instanceof Employee ? $employee->group_id : null;

        return [
            'client_id' => $this->user()?->is_super_admin
                ? ['sometimes', 'nullable', 'integer', Rule::in($allowedClientIds)]
                : ['nullable', 'integer', Rule::in([$currentClientId])],
            'sim_id' => ['nullable', 'string', 'max:100'],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['required', 'string', 'max:30'],
            'join_date' => ['required', 'date'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'employee_status' => ['required', Rule::in(['permanent', 'contract', 'daily'])],
            'marital_status' => ['required', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'group_id' => ['nullable', 'integer', Rule::exists('groups', 'id')->where(function ($query) use ($clientId, $currentGroupId): void {
                $query->where('client_id', $clientId)
                    ->where(function ($query) use ($currentGroupId): void {
                        $query->where('status', 'active');

                        if ($currentGroupId !== null) {
                            $query->orWhere('id', $currentGroupId);
                        }
                    });
            })],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ];
    }
}
