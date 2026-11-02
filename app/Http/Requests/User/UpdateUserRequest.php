<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')?->id ?? $this->user;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username,'.$userId],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:admin,supervisor,karyawan'],
            'nip' => ['nullable', 'string', 'exists:karyawans,nip', 'unique:users,nip,'.$userId],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$userId],
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
            'name.required' => 'Nama lengkap pengguna wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan oleh akun lain.',
            'password.min' => 'Password minimal harus 6 karakter jika ingin diganti.',
            'role.required' => 'Role hak akses wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'nip.exists' => 'NIP yang dipilih tidak ditemukan dalam data master karyawan.',
            'nip.unique' => 'NIP sudah ditautkan ke akun pengguna lain.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar untuk pengguna lain.',
        ];
    }
}
