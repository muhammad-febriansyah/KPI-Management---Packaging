<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('client'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $client = $this->route('client');

        return ['code' => ['required', 'string', 'max:50', Rule::unique('clients', 'code')->ignore($client instanceof Client ? $client->getKey() : null)], 'name' => ['required', 'string', 'max:150'], 'timezone' => ['required', 'timezone'], 'status' => ['required', Rule::in(['active', 'inactive'])], 'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']];
    }
}
