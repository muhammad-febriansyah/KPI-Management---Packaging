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
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'report' => ['nullable', 'string'],
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
