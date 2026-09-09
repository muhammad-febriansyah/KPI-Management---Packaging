<?php

namespace App\Http\Requests;

use App\Models\Unit;
use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
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
        $unit = $this->route('unit');
        $clientId = app(CurrentClientService::class)->id();

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('units', 'code')->where(fn ($query) => $query->where('client_id', $clientId))->ignore($unit instanceof Unit ? $unit->getKey() : null)],
            'name' => ['required', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode satuan wajib diisi.',
            'code.unique' => 'Kode satuan sudah digunakan pada client ini.',
            'name.required' => 'Nama satuan wajib diisi.',
            'status.in' => 'Status satuan tidak valid.',
        ];
    }
}
