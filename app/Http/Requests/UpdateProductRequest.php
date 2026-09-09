<?php

namespace App\Http\Requests;

use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        $foreign = fn ($table) => Rule::exists($table, 'id')->where(fn ($query) => $query->where('client_id', $clientId));

        return ['sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->where(fn ($query) => $query->where('client_id', $clientId))->ignore($this->route('product'))], 'name' => ['nullable', 'string', 'max:180'], 'unit_id' => ['nullable', 'integer', $foreign('units')], 'group_id' => ['nullable', 'integer', $foreign('groups')], 'cost_center_id' => ['nullable', 'integer', $foreign('cost_centers')], 'po_price' => ['nullable', 'numeric', 'min:0'], 'old_employee_rate' => ['nullable', 'numeric', 'min:0'], 'new_employee_rate' => ['nullable', 'numeric', 'min:0'], 'estimated_output_per_hour' => ['nullable', 'integer', 'min:0'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]];
    }
}
