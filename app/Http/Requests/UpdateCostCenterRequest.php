<?php

namespace App\Http\Requests;

use App\Models\CostCenter;
use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCostCenterRequest extends FormRequest
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
        $costCenter = $this->route('cost_center');
        $clientId = app(CurrentClientService::class)->id();

        return ['code' => ['required', 'string', 'max:50', Rule::unique('cost_centers', 'code')->where(fn ($query) => $query->where('client_id', $clientId))->ignore($costCenter instanceof CostCenter ? $costCenter->getKey() : null)], 'name' => ['required', 'string', 'max:100'], 'status' => ['required', Rule::in(['active', 'inactive'])]];
    }
}
