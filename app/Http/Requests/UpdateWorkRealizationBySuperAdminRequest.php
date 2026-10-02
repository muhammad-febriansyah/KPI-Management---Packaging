<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\WorkRealization;
use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkRealizationBySuperAdminRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $realization = $this->route('realization');

        if ($realization instanceof WorkRealization && $realization->product_id !== null) {
            $productId = $this->input('product_id');
            $productExistsForClient = filled($productId)
                && Product::query()->where('client_id', app(CurrentClientService::class)->id())->whereKey($productId)->exists();

            if (! $productExistsForClient) {
                $this->merge(['product_id' => $realization->product_id]);
            }
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $client = app(CurrentClientService::class);

        return $user?->status === 'active'
            && $user->is_super_admin
            && $client->isResolved()
            && $this->route('realization') instanceof WorkRealization;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $clientId = app(CurrentClientService::class)->id();
        $realization = $this->route('realization');

        return [
            'work_date' => ['nullable', 'date'],
            'shift_id' => ['nullable', Rule::exists('shifts', 'id')->where(fn ($query) => $query->where('client_id', $clientId))],
            'batch_no' => [
                'nullable',
                'string',
                Rule::unique('batches', 'batch_no')->where(fn ($query) => $query->where('client_id', $clientId))->ignore($realization?->batch_id),
            ],
            'product_id' => ['nullable', Rule::exists('products', 'id')->where(fn ($query) => $query->where('client_id', $clientId))],
            'total_output' => ['nullable', 'numeric', 'min:0'],
            'start_time' => ['nullable', 'date_format:H:i,H:i:s'],
            'end_time' => ['nullable', 'date_format:H:i,H:i:s'],
            'report' => ['nullable', 'string'],
            'result_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'employee_ids' => ['nullable', 'array'],
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
