<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->status === 'active';
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->user() instanceof User ? $this->user()->getKey() : null)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($this->user()?->employee) {
            $rules += [
                'sim_id' => ['nullable', 'string', 'max:100'],
                'phone' => ['required', 'string', 'max:30'],
                'marital_status' => ['required', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            ];
        } else {
            $rules['username'] = ['nullable', 'string', 'max:100', Rule::unique('users', 'username')->ignore($this->user() instanceof User ? $this->user()->getKey() : null)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'avatar.image' => 'Avatar harus berupa gambar.',
            'avatar.mimes' => 'Avatar harus berformat JPG, PNG, atau WEBP.',
            'avatar.max' => 'Ukuran avatar maksimal 2 MB.',
        ];
    }
}
