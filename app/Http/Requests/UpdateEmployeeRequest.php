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
        $clientId = app(CurrentClientService::class)->id();

        $employee = $this->route('employee');
        $userId = $employee instanceof Employee ? $employee->user_id : null;

        return ['sim_id' => ['nullable', 'string', 'max:100'], 'full_name' => ['required', 'string', 'max:150'], 'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)], 'phone' => ['required', 'string', 'max:30'], 'join_date' => ['required', 'date'], 'gender' => ['required', Rule::in(['male', 'female'])], 'employee_status' => ['required', Rule::in(['permanent', 'contract', 'daily'])], 'marital_status' => ['required', Rule::in(['single', 'married', 'divorced', 'widowed'])], 'group_id' => ['nullable', 'integer', Rule::exists('groups', 'id')->where(fn ($query) => $query->where('client_id', $clientId))], 'password' => ['nullable', 'string', 'min:8', 'max:255']];
    }
}
