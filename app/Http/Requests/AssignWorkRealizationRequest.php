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
        return $this->user()?->is_super_admin === true
            && $this->user()?->status === 'active'
            && app(CurrentClientService::class)->isResolved();
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
