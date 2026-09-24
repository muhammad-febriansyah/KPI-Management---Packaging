<?php

namespace App\Http\Requests;

use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignWorkRealizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $client = app(CurrentClientService::class);

        return $user?->status === 'active'
            && $client->isResolved()
            && ($user->is_super_admin || $user->roleCodeFor($client->get()) === 'employee');
    }

    /**
     * The assignment form renders a blank employee row as its "add another" affordance,
     * so drop the empty values before the rules below see them. An assignment with no
     * employee at all still fails on the "required" rule for employee_ids itself.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('employee_ids')) {
            return;
        }

        $this->merge([
            'employee_ids' => array_values(array_filter(
                (array) $this->input('employee_ids'),
                fn ($employeeId): bool => filled($employeeId),
            )),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $clientId = app(CurrentClientService::class)->id();

        return [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('employees', 'id')->where(fn ($query) => $query
                    ->where('client_id', $clientId)
                    ->where('status', 'active')
                    ->whereNotNull('user_id')),
            ],
        ];
    }
}
