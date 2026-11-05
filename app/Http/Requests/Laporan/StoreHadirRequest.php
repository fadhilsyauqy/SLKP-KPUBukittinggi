<?php

namespace App\Http\Requests\Laporan;

use App\Models\HariLibur;
use App\Models\Laporan;
use Carbon\Carbon;
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
            'nomor_spt' => ['nullable', 'string', 'max:100'],
            'bukti_spt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
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

    /**
     * Konfigurasi validator tambahan untuk validasi jam dinas aktif dan pencegahan entri ganda.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->shouldValidateWorkingHours() && ! $this->isWithinWorkingHours()) {
                $validator->errors()->add(
                    'jam_masuk',
                    'Pelaporan presensi kehadiran hanya diperkenankan antara pukul 06:30 – 19:00 WIB pada hari kerja (Senin–Jumat), kecuali memiliki Surat Perintah Tugas (SPT) atau telah ditetapkan sebagai Hari Kerja Khusus.'
                );
            }

            $this->validateNoDuplicateHadir($validator);
        });
    }

    /**
     * Validasi pencegahan entri ganda pada tanggal kerja yang sama (TASK-1.3).
     */
    protected function validateNoDuplicateHadir($validator): void
    {
        $nip = $this->input('nip');
        $tanggal = $this->input('tanggal');

        if (! $nip || ! $tanggal) {
            return;
        }

        try {
            $parsedDate = Carbon::parse($tanggal);
            $dateString = $parsedDate->format('Y-m-d');
            $formattedDate = $parsedDate->format('d/m/Y');
        } catch (\Throwable $e) {
            return;
        }

        $exists = Laporan::query()
            ->where('tipe', 'hadir')
            ->where('nip', $nip)
            ->whereDate('tanggal', $dateString)
            ->exists();

        if ($exists) {
            $validator->errors()->add(
                'tanggal',
                "Anda sudah mengirimkan laporan kehadiran untuk tanggal {$formattedDate}. Silakan periksa menu Riwayat Laporan."
            );
        }
    }

    /**
     * Tentukan apakah validasi jam dinas harus diterapkan.
     */
    public function shouldValidateWorkingHours(): bool
    {
        // Lewati validasi jika terdapat instruksi agenda khusus Pemilu / Pilkada
        if (config('kpu.jam_dinas.agenda_khusus', false)) {
            return false;
        }

        return (bool) config('kpu.jam_dinas.enforce', true);
    }

    /**
     * Periksa apakah waktu saat ini berada dalam rentang jam dinas dan hari kerja.
     */
    public function isWithinWorkingHours(): bool
    {
        $timezone = config('kpu.jam_dinas.timezone', 'Asia/Jakarta');
        $now = now($timezone);
        $tanggalInput = $this->input('tanggal', $now->toDateString());

        $isSpecialWorkingDay = HariLibur::isHariKerjaKhusus($tanggalInput) || HariLibur::isHariKerjaKhusus($now);
        $hasSpt = $this->filled('nomor_spt');

        // Batasi hari kerja resmi: akhir pekan hanya diizinkan jika merupakan hari kerja khusus atau memiliki SPT
        if (! $now->isWeekday() && ! $isSpecialWorkingDay && ! $hasSpt) {
            return false;
        }

        $currentTime = $now->format('H:i');
        $start = config('kpu.jam_dinas.start', '06:30');
        $end = config('kpu.jam_dinas.end', '19:00');

        return $currentTime >= $start && $currentTime <= $end;
    }
}
