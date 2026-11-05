<?php

namespace App\Http\Requests\HariLibur;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreHariLiburRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date', 'unique:hari_liburs,tanggal'],
            'keterangan' => ['required', 'string', 'max:255'],
            'is_cuti_bersama' => ['nullable'],
            'is_hari_kerja' => ['nullable'],
            'tipe' => ['nullable', 'string', 'in:libur_nasional,cuti_bersama,hari_kerja_khusus'],
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
            'tanggal.required' => 'Tanggal hari libur wajib diisi.',
            'tanggal.date' => 'Format tanggal tidak valid.',
            'tanggal.unique' => 'Tanggal libur tersebut sudah pernah terdaftar di master kalender.',
            'keterangan.required' => 'Keterangan nama hari libur wajib diisi.',
            'keterangan.max' => 'Keterangan nama hari libur maksimal 255 karakter.',
        ];
    }
}
