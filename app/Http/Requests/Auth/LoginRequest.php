<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Username atau email wajib diisi.',
            'login.max' => 'Username atau email tidak boleh lebih dari 150 karakter.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $login = trim($this->string('login')->toString());
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([
            $field => $login,
            'password' => $this->string('password')->toString(),
            'status' => 'active',
        ], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'Username atau password tidak sesuai.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $user = Auth::user();

        if (! $user instanceof User) {
            Auth::logout();

            throw ValidationException::withMessages([
                'login' => 'Username atau password tidak sesuai.',
            ]);
        }

        return $user;
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => "Terlalu banyak percobaan masuk. Coba kembali dalam {$seconds} detik.",
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')->toString()).'|'.$this->ip());
    }
}
