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
            && ($user->is_super_admin || in_array($user->roleCodeFor($client->get()), ['admin', 'leader', 'employee'], true));
    }

    /**
     * Remove blank assignment rows before requiring at least one employee.
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
            'work_date' => ['required', 'date'],
            'shift_id' => ['required', Rule::exists('shifts', 'id')->where(fn ($query) => $query->where('client_id', $clientId))],
            'batch_no' => ['required', 'string', 'regex:/^B-\d{8}-\d{4}$/'],
            'batch_id' => ['nullable', Rule::exists('batches', 'id')->where(function ($query) use ($clientId): void {
                $query->where('client_id', $clientId);

                if (filled($this->input('product_id'))) {
                    $query->where('product_id', $this->input('product_id'));
                }
            })],
            'product_id' => ['required', Rule::exists('products', 'id')->where(fn ($query) => $query->where('client_id', $clientId)->where('status', 'active'))],
            'total_output' => ['required', 'numeric', 'min:0'],
            'start_time' => ['required', 'date_format:H:i,H:i:s'],
            'end_time' => ['required', 'date_format:H:i,H:i:s'],
            'report' => ['required', 'string'],
            'result_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'is_complaint' => ['boolean'],
            'employee_ids' => ['required', 'array', 'min:1'],
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

    /**
     * Get the validation messages for the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'work_date.required' => 'Tanggal borongan wajib diisi.',
            'shift_id.required' => 'Shift wajib dipilih.',
            'batch_no.required' => 'Nomor batch belum tersedia. Pilih tanggal borongan kembali.',
            'product_id.required' => 'SKU wajib dipilih.',
            'total_output.required' => 'Total output wajib diisi.',
            'start_time.required' => 'Waktu pengerjaan pertama wajib diisi.',
            'end_time.required' => 'Waktu pengerjaan kedua wajib diisi.',
            'report.required' => 'Report wajib diisi.',
            'employee_ids.required' => 'Pilih minimal satu karyawan pada tab Assign.',
            'employee_ids.min' => 'Pilih minimal satu karyawan pada tab Assign.',
            'employee_ids.*.required' => 'Karyawan wajib dipilih.',
        ];
    }
}
