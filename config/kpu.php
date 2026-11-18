<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validasi Jam Dinas & Hari Kerja KPU Kota Bukittinggi
    |--------------------------------------------------------------------------
    |
    | Pengisian presensi laporan kehadiran pegawai dibatasi pada hari kerja aktif
    | (Senin - Jumat) antara pukul 06:30 hingga 19:00 WIB.
    | Terdapat pengecualian saat ada agenda khusus tahapan Pemilu / Pilkada.
    |
    */
    'jam_dinas' => [
        'enforce' => env('KPU_ENFORCE_WORKING_HOURS', true),
        'start' => env('KPU_WORKING_HOURS_START', '06:30'),
        'end' => env('KPU_WORKING_HOURS_END', '19:00'),
        'timezone' => env('KPU_TIMEZONE', 'Asia/Jakarta'),
        'agenda_khusus' => env('KPU_AGENDA_KHUSUS_PEMILU', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Penandatangan Naskah Dinas
    |--------------------------------------------------------------------------
    |
    | Identitas pejabat yang tercetak pada blok tanda tangan rekapitulasi.
    | Nilai sebenarnya wajib diisi melalui berkas .env pada peladen produksi
    | dan tidak disimpan di dalam repositori.
    |
    */
    'penandatangan' => [
        'nama' => env('KPU_SIGNER_NAME', 'Nama Pejabat'),
        'nip' => env('KPU_SIGNER_NIP', '-'),
        'jabatan' => env('KPU_SIGNER_TITLE', 'Sekretaris KPU Kota Bukittinggi'),
    ],

];
