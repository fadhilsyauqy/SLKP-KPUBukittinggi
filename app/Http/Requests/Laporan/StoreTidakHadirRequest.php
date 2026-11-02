<?php

namespace App\Http\Requests\Laporan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTidakHadirRequest extends FormRequest
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
            'keterangan' => ['required', 'string', 'in:Cuti,Izin,Sakit,Perjadin'],
            'tanggal_in' => ['required', 'date'],
            'tanggal_out' => ['required', 'date', 'after_or_equal:tanggal_in'],
            'lama_hari' => ['nullable', 'string'],
            'alasan' => ['required', 'string'],
            'attach' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
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
            'keterangan.required' => 'Keterangan tidak hadir wajib dipilih.',
            'tanggal_in.required' => 'Tanggal awal tidak hadir wajib diisi.',
            'tanggal_out.required' => 'Tanggal akhir tidak hadir wajib diisi.',
            'tanggal_out.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.',
            'alasan.required' => 'Alasan wajib diisi.',
            'attach.max' => 'Ukuran file lampiran maksimal 5MB.',
        ];
    }
}
