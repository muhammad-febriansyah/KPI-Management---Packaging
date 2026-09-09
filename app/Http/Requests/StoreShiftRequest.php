<?php

namespace App\Http\Requests;

use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftRequest extends FormRequest
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
        $clientId = app(CurrentClientService::class)->id();

        return ['code' => ['required', 'string', 'max:30', Rule::unique('shifts', 'code')->where(fn ($query) => $query->where('client_id', $clientId))], 'name' => ['required', 'string', 'max:100'], 'start_time' => ['required', 'date_format:H:i'], 'end_time' => ['required', 'date_format:H:i'], 'status' => ['required', Rule::in(['active', 'inactive'])]];
    }
}
