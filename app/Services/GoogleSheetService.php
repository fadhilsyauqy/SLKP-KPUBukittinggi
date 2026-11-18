<?php

namespace App\Services;

use App\Models\Laporan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleSheetService
{
    /**
     * URL Web App Google Apps Script untuk data Hadir / Laporan Harian.
     */
    protected string $hadirUrl;

    /**
     * URL Web App Google Apps Script untuk data Tidak Hadir / Keterangan.
     */
    protected string $tidakHadirUrl;

    public function __construct()
    {
        $this->hadirUrl = (string) config('services.google_sheet.hadir_url', '');
        $this->tidakHadirUrl = (string) config('services.google_sheet.tidakhadir_url', '');
    }

    /**
     * Kirim data Laporan Hadir ke Google Sheets dengan mekanisme retry dan fallback.
     *
     * @return array{success: bool, message: string, data: mixed}
     */
    public function sendHadir(Laporan $laporan): array
    {
        $payload = [
            'nip' => $laporan->nip ?? '',
            'nama' => $laporan->nama,
            'jabatan' => $laporan->jabatan ?? '',
            'tanggal' => $laporan->tanggal ? $laporan->tanggal->format('Y-m-d') : '',
            'jam_masuk' => $laporan->jam_masuk ?? '',
            'jam_keluar' => $laporan->jam_keluar ?? '',
            'uraian' => $laporan->uraian ?? '',
            'hasil' => $laporan->output ?? '',
            'fileContent' => '',
            'filename' => '',
        ];

        try {
            $response = Http::retry(2, 300)
                ->timeout(10)
                ->asForm()
                ->post($this->hadirUrl, $payload);

            if ($response->successful()) {
                $body = $response->json();
                $isSuccess = is_array($body) && ($body['status'] ?? '') === 'success';

                return [
                    'success' => $isSuccess,
                    'message' => $isSuccess
                        ? 'Data berhasil disinkronkan ke Google Spreadsheet.'
                        : ($body['message'] ?? 'Respons Google Sheets tidak menunjukkan status success.'),
                    'data' => $body,
                ];
            }

            Log::warning('GoogleSheetService: Respons gagal saat kirim Hadir', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Google Sheets merespons dengan kode HTTP '.$response->status(),
                'data' => null,
            ];
        } catch (Throwable $e) {
            Log::error('GoogleSheetService: Gagal terhubung ke Google Sheets (Hadir)', [
                'error' => $e->getMessage(),
                'laporan_id' => $laporan->id,
            ]);

            return [
                'success' => false,
                'message' => 'Koneksi ke Google Sheets terputus / gagal: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Kirim data Laporan Tidak Hadir ke Google Sheets dengan mekanisme retry dan fallback.
     *
     * @return array{success: bool, message: string, data: mixed}
     */
    public function sendTidakHadir(Laporan $laporan, ?string $fileBase64 = null, ?string $fileName = null): array
    {
        $payload = [
            'nip' => $laporan->nip ?? '',
            'nama' => $laporan->nama,
            'jabatan' => $laporan->jabatan ?? '',
            'keterangan' => $laporan->keterangan ?? 'Izin',
            'tanggal_in' => $laporan->tanggal ? $laporan->tanggal->format('Y-m-d') : '',
            'tanggal_out' => $laporan->tanggal_selesai
                ? $laporan->tanggal_selesai->format('Y-m-d')
                : ($laporan->tanggal ? $laporan->tanggal->format('Y-m-d') : ''),
            'lama_hari' => $laporan->lama_hari ?? '1 Hari',
            'alasan' => $laporan->alasan ?? '',
            'fileContent' => $fileBase64 ?? '',
            'filename' => $fileName ?? ($laporan->bukti ? basename($laporan->bukti) : 'Tidak Menyertakan Bukti Tidak Hadir'),
        ];

        try {
            $response = Http::retry(2, 300)
                ->timeout(10)
                ->asForm()
                ->post($this->tidakHadirUrl, $payload);

            if ($response->successful()) {
                $body = $response->json();
                $isSuccess = is_array($body) && ($body['status'] ?? '') === 'success';

                return [
                    'success' => $isSuccess,
                    'message' => $isSuccess
                        ? 'Data berhasil disinkronkan ke Google Spreadsheet.'
                        : ($body['message'] ?? 'Respons Google Sheets tidak menunjukkan status success.'),
                    'data' => $body,
                ];
            }

            Log::warning('GoogleSheetService: Respons gagal saat kirim Tidak Hadir', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Google Sheets merespons dengan kode HTTP '.$response->status(),
                'data' => null,
            ];
        } catch (Throwable $e) {
            Log::error('GoogleSheetService: Gagal terhubung ke Google Sheets (Tidak Hadir)', [
                'error' => $e->getMessage(),
                'laporan_id' => $laporan->id,
            ]);

            return [
                'success' => false,
                'message' => 'Koneksi ke Google Sheets terputus / gagal: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }
}
