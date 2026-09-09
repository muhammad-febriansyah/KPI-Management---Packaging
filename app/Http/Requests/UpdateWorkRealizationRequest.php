<?php

namespace App\Http\Requests;

use App\Models\WorkRealization;
use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkRealizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $realization = $this->route('realization');

        return $realization instanceof WorkRealization
            && $this->user()?->status === 'active'
            && app(CurrentClientService::class)->isResolved()
            && $this->user()->roleCodeFor(app(CurrentClientService::class)->get()) === 'employee'
            && $this->user()->can('update', $realization);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'total_output' => ['nullable', 'numeric', 'min:0'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'report' => ['nullable', 'string'],
        ];
    }
}
