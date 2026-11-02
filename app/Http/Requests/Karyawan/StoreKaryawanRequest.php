<?php

namespace App\Http\Requests\Karyawan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreKaryawanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nip' => ['required', 'string', 'unique:karyawans,nip'],
            'nama' => ['required', 'string', 'max:255'],
            'jabatan_id' => ['required', 'exists:jabatans,id'],
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
            'nip.required' => 'NIP pegawai wajib diisi.',
            'nip.unique' => 'NIP ini sudah terdaftar di sistem.',
            'nama.required' => 'Nama lengkap pegawai wajib diisi.',
            'jabatan_id.required' => 'Jabatan wajib dipilih.',
            'jabatan_id.exists' => 'Jabatan yang dipilih tidak valid.',
        ];
    }
}
