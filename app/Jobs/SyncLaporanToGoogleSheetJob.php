<?php

namespace App\Jobs;

use App\Models\Laporan;
use App\Services\GoogleSheetService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SyncLaporanToGoogleSheetJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah maksimum percobaan pengiriman.
     */
    public int $tries = 3;

    /**
     * Jeda waktu retry berjenjang dalam hitungan detik.
     *
     * @var array<int, int>
     */
    public array $backoff = [15, 60, 180];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Laporan $laporan
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GoogleSheetService $googleSheetService): void
    {
        if (! $this->laporan->exists) {
            return;
        }

        $laporan = $this->laporan->fresh() ?? $this->laporan;

        $fileBase64 = null;
        $fileName = null;

        if ($laporan->tipe === 'hadir') {
            $syncResult = $googleSheetService->sendHadir($laporan);
        } else {
            if ($laporan->bukti && Storage::disk('public')->exists($laporan->bukti)) {
                $fileBase64 = base64_encode(Storage::disk('public')->get($laporan->bukti));
                $fileName = basename($laporan->bukti);
            }

            $syncResult = $googleSheetService->sendTidakHadir($laporan, $fileBase64, $fileName);
        }

        $laporan->update([
            'status_sinkron_google' => $syncResult['success'],
            'catatan_sinkron' => $syncResult['message'],
        ]);

        if (! $syncResult['success']) {
            // Jika dijalankan oleh background queue worker (bukan inline SyncJob), lemparkan exception agar diretry
            if ($this->job && ! ($this->job instanceof SyncJob)) {
                throw new Exception('Google Sheets sync failed: '.$syncResult['message']);
            }

            Log::warning('SyncLaporanToGoogleSheetJob: Sinkronisasi Google Sheets gagal', [
                'laporan_id' => $laporan->id,
                'message' => $syncResult['message'],
            ]);
        }
    }

    /**
     * Tangani kegagalan permanen setelah seluruh percobaan retry habis.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('SyncLaporanToGoogleSheetJob: Sinkronisasi gagal permanen setelah retry', [
            'laporan_id' => $this->laporan->id ?? null,
            'error' => $exception?->getMessage(),
        ]);

        if ($this->laporan->exists) {
            $this->laporan->update([
                'status_sinkron_google' => false,
                'catatan_sinkron' => 'Gagal permanen setelah 3 kali percobaan: '.($exception?->getMessage() ?? 'Koneksi timeout'),
            ]);
        }
    }
}
