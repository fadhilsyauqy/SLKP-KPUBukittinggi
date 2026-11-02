<?php

namespace App\Http\Requests\Laporan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreHadirRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nip' => ['required', 'string'],
            'nama' => ['required', 'string'],
            'jabatan' => ['nullable', 'string'],
            'tanggal' => ['required', 'date'],
            'jam_masuk' => ['required'],
            'jam_keluar' => ['required'],
            'uraian' => ['required', 'string'],
            'output' => ['required', 'string'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nip.required' => 'NIP wajib diisi.',
            'nama.required' => 'Nama lengkap wajib diisi.',
            'tanggal.required' => 'Tanggal kerja wajib diisi.',
            'jam_masuk.required' => 'Jam masuk wajib diisi.',
            'jam_keluar.required' => 'Jam keluar wajib diisi.',
            'uraian.required' => 'Uraian pekerjaan wajib diisi.',
            'output.required' => 'Output / hasil pekerjaan wajib diisi.',
        ];
    }
}
