<?php

namespace Tests\Feature;

use App\Jobs\SyncLaporanToGoogleSheetJob;
use App\Models\Laporan;
use App\Models\User;
use App\Services\GoogleSheetService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueSyncGoogleSheetTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawan;

    protected User $supervisor;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->karyawan = User::where('username', 'karyawan')->first();
        $this->supervisor = User::where('username', 'supervisor')->first();
        $this->admin = User::where('username', 'admin')->first();
    }

    public function test_store_hadir_dispatches_sync_laporan_to_google_sheet_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->karyawan)->post('/laporan/hadir', [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-03',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Mengikuti koordinasi tahapan rekapitulasi',
            'output' => '1 Berkas resume rapat',
        ]);

        $response->assertRedirect('/laporan/riwayat');

        Queue::assertPushed(SyncLaporanToGoogleSheetJob::class, function ($job) {
            return $job->laporan->nip === $this->karyawan->nip
                && $job->laporan->tipe === 'hadir';
        });
    }

    public function test_store_tidak_hadir_dispatches_sync_laporan_to_google_sheet_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->karyawan)->post('/laporan/tidak-hadir', [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'keterangan' => 'Cuti',
            'tanggal_in' => '2026-11-04',
            'tanggal_out' => '2026-11-05',
            'alasan' => 'Cuti tahunan keperluan keluarga',
        ]);

        $response->assertRedirect('/laporan/riwayat');

        Queue::assertPushed(SyncLaporanToGoogleSheetJob::class, function ($job) {
            return $job->laporan->nip === $this->karyawan->nip
                && $job->laporan->tipe === 'tidak_hadir';
        });
    }

    public function test_job_handles_hadir_sync_and_updates_database(): void
    {
        Http::fake([
            'script.google.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $laporan = Laporan::factory()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'status_sinkron_google' => false,
        ]);

        $service = app(GoogleSheetService::class);
        $job = new SyncLaporanToGoogleSheetJob($laporan);
        $job->handle($service);

        $laporan->refresh();

        $this->assertTrue($laporan->status_sinkron_google);
        $this->assertStringContainsString('disinkronkan', $laporan->catatan_sinkron);
    }

    public function test_job_failed_method_updates_laporan_with_error_status(): void
    {
        $laporan = Laporan::factory()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'status_sinkron_google' => false,
        ]);

        $job = new SyncLaporanToGoogleSheetJob($laporan);
        $job->failed(new Exception('Network connection timed out'));

        $laporan->refresh();

        $this->assertFalse($laporan->status_sinkron_google);
        $this->assertStringContainsString('Gagal permanen setelah 3 kali percobaan', $laporan->catatan_sinkron);
        $this->assertStringContainsString('Network connection timed out', $laporan->catatan_sinkron);
    }

    public function test_supervisor_and_admin_can_trigger_retry_sync(): void
    {
        Queue::fake();

        $laporan = Laporan::factory()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'status_sinkron_google' => false,
        ]);

        // Supervisor trigger retry
        $responseSupervisor = $this->actingAs($this->supervisor)->post("/monitoring/{$laporan->id}/retry-sync");
        $responseSupervisor->assertRedirect();
        $responseSupervisor->assertSessionHas('success');

        Queue::assertPushed(SyncLaporanToGoogleSheetJob::class, function ($job) use ($laporan) {
            return $job->laporan->id === $laporan->id;
        });

        // Admin trigger retry via JSON request
        $responseAdmin = $this->actingAs($this->admin)->postJson("/monitoring/{$laporan->id}/retry-sync");
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertJson([
            'status' => 'success',
        ]);
    }

    public function test_karyawan_cannot_trigger_retry_sync(): void
    {
        $laporan = Laporan::factory()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
        ]);

        $response = $this->actingAs($this->karyawan)->post("/monitoring/{$laporan->id}/retry-sync");
        $response->assertStatus(403);
    }
}
