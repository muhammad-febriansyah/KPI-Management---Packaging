<?php

namespace App\Http\Requests;

use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkRealizationRequest extends FormRequest
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
     * The assignment section renders a blank employee row as its "add another" affordance,
     * so the form always submits one empty value. Assignment is optional here, and an
     * untouched blank row must not fail validation.
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
            'work_date' => ['nullable', 'date'],
            'shift_id' => ['nullable', Rule::exists('shifts', 'id')->where(fn ($query) => $query->where('client_id', $clientId))],
            'batch_id' => ['nullable', Rule::exists('batches', 'id')->where(function ($query) use ($clientId): void {
                $query->where('client_id', $clientId);

                if (filled($this->input('product_id'))) {
                    $query->where('product_id', $this->input('product_id'));
                }
            })],
            'product_id' => ['nullable', Rule::exists('products', 'id')->where(fn ($query) => $query->where('client_id', $clientId)->where('status', 'active'))],
            'total_output' => ['nullable', 'numeric', 'min:0'],
            'start_time' => ['nullable', 'date_format:H:i,H:i:s'],
            'end_time' => ['nullable', 'date_format:H:i,H:i:s'],
            'report' => ['nullable', 'string'],
            'result_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'is_complaint' => ['boolean'],
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
