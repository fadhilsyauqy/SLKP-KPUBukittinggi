<?php

namespace Tests\Feature;

use App\Jobs\SyncLaporanToGoogleSheetJob;
use App\Models\Laporan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
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

    public function test_karyawan_submission_sets_initial_pending_approval(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->karyawan)->post('/laporan/tidak-hadir', [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'keterangan' => 'Sakit',
            'tanggal_in' => '2026-11-04',
            'tanggal_out' => '2026-11-04',
            'alasan' => 'Demam tinggi memerlukan istirahat',
        ]);

        $response->assertRedirect('/laporan/riwayat');

        $this->assertDatabaseHas('laporans', [
            'nip' => $this->karyawan->nip,
            'tipe' => 'tidak_hadir',
            'keterangan' => 'Sakit',
            'status_approval' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function test_supervisor_and_admin_can_access_approval_index(): void
    {
        Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
        ]);

        // Supervisor can access
        $responseSupervisor = $this->actingAs($this->supervisor)->get('/approval');
        $responseSupervisor->assertStatus(200);
        $responseSupervisor->assertSee('Persetujuan Izin, Cuti &amp; Dinas Pegawai', false);
        $responseSupervisor->assertSee($this->karyawan->name);

        // Admin can access
        $responseAdmin = $this->actingAs($this->admin)->get('/approval');
        $responseAdmin->assertStatus(200);

        // JSON response support
        $responseJson = $this->actingAs($this->supervisor)->getJson('/approval');
        $responseJson->assertStatus(200);
        $responseJson->assertJsonStructure([
            'status',
            'counts' => ['pending', 'approved', 'rejected', 'total'],
            'data',
        ]);
    }

    public function test_karyawan_cannot_access_approval_index(): void
    {
        $response = $this->actingAs($this->karyawan)->get('/approval');
        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login_when_accessing_approval(): void
    {
        $response = $this->get('/approval');
        $response->assertRedirect('/login');
    }

    public function test_supervisor_can_approve_pending_absence(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
        ]);

        $response = $this->actingAs($this->supervisor)->post("/approval/{$laporan->id}/approve", [
            'catatan_approval' => 'Disetujui untuk istirahat dinas.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $laporan->refresh();
        $this->assertEquals('approved', $laporan->status_approval);
        $this->assertEquals($this->supervisor->id, $laporan->approved_by);
        $this->assertNotNull($laporan->approved_at);
        $this->assertEquals('Disetujui untuk istirahat dinas.', $laporan->catatan_approval);
        $this->assertTrue($laporan->isApproved());
        $this->assertFalse($laporan->isPending());
    }

    public function test_supervisor_can_reject_pending_absence_with_reason(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
        ]);

        $response = $this->actingAs($this->supervisor)->post("/approval/{$laporan->id}/reject", [
            'catatan_approval' => 'Lampiran surat dokter tidak terlampir.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $laporan->refresh();
        $this->assertEquals('rejected', $laporan->status_approval);
        $this->assertEquals($this->supervisor->id, $laporan->approved_by);
        $this->assertNotNull($laporan->approved_at);
        $this->assertEquals('Lampiran surat dokter tidak terlampir.', $laporan->catatan_approval);
        $this->assertTrue($laporan->isRejected());
    }

    public function test_rejection_modal_contains_quick_template_options(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
        ]);

        $response = $this->actingAs($this->supervisor)->get('/approval?status=pending');

        $response->assertOk();
        $response->assertSee('Pilih Template Alasan Cepat');
        $response->assertSee('Lampiran surat/bukti pendukung tidak jelas atau belum diunggah.');
        $response->assertSee('Harap sesuaikan tanggal dengan jadwal agenda dinas.');
        $response->assertSee('Kuota cuti pada periode ini sudah penuh.');
        $response->assertSee('Lainnya (Tulis manual)');
    }

    public function test_supervisor_can_reject_using_quick_template_notes(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'keterangan' => 'Cuti Tahunan',
        ]);

        $templateReason = 'Kuota cuti pada periode ini sudah penuh.';

        $response = $this->actingAs($this->supervisor)->post("/approval/{$laporan->id}/reject", [
            'catatan_approval' => $templateReason,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $laporan->refresh();
        $this->assertEquals('rejected', $laporan->status_approval);
        $this->assertEquals($templateReason, $laporan->catatan_approval);
    }

    public function test_reject_validation_requires_reason(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
        ]);

        $response = $this->actingAs($this->supervisor)->post("/approval/{$laporan->id}/reject", [
            'catatan_approval' => '',
        ]);

        $response->assertSessionHasErrors(['catatan_approval']);

        $laporan->refresh();
        $this->assertEquals('pending', $laporan->status_approval);
    }

    public function test_karyawan_cannot_approve_or_reject_requests(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
        ]);

        $responseApprove = $this->actingAs($this->karyawan)->post("/approval/{$laporan->id}/approve");
        $responseApprove->assertStatus(403);

        $responseReject = $this->actingAs($this->karyawan)->post("/approval/{$laporan->id}/reject", [
            'catatan_approval' => 'Ditolak',
        ]);
        $responseReject->assertStatus(403);
    }

    public function test_karyawan_history_shows_approval_status_and_rejection_notes(): void
    {
        // 1. Pending report
        Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'alasan' => 'Izin keperluan keluarga mendesak',
        ]);

        // 2. Rejected report
        Laporan::factory()->tidakHadir()->rejected($this->supervisor)->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'alasan' => 'Cuti mendadak tanpa koordinasi',
            'catatan_approval' => 'Harap lampirkan surat resmi subbag.',
        ]);

        $response = $this->actingAs($this->karyawan)->get('/laporan/riwayat');

        $response->assertStatus(200);
        $response->assertSee('Menunggu Verifikasi');
        $response->assertSee('Ditolak');
        $response->assertSee('Harap lampirkan surat resmi subbag.');
    }

    public function test_supervisor_can_bulk_approve_multiple_pending_absences(): void
    {
        Queue::fake();

        $laporans = Laporan::factory()->count(5)->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
        ]);

        $ids = $laporans->pluck('id')->toArray();

        $response = $this->actingAs($this->supervisor)->post('/approval/bulk-approve', [
            'ids' => $ids,
            'catatan_approval' => 'Disetujui serentak untuk tugas dinas pemilu.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        foreach ($laporans as $laporan) {
            $laporan->refresh();
            $this->assertEquals('approved', $laporan->status_approval);
            $this->assertEquals($this->supervisor->id, $laporan->approved_by);
            $this->assertNotNull($laporan->approved_at);
            $this->assertEquals('Disetujui serentak untuk tugas dinas pemilu.', $laporan->catatan_approval);
            $this->assertTrue($laporan->isApproved());
        }

        Queue::assertPushed(SyncLaporanToGoogleSheetJob::class, 5);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->supervisor->id,
            'action' => 'BULK_APPROVE',
            'module' => 'APPROVAL',
        ]);
    }

    public function test_admin_can_bulk_approve_pending_absences(): void
    {
        Queue::fake();

        $laporans = Laporan::factory()->count(3)->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
        ]);

        $response = $this->actingAs($this->admin)->post('/approval/bulk-approve', [
            'ids' => $laporans->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        foreach ($laporans as $laporan) {
            $laporan->refresh();
            $this->assertEquals('approved', $laporan->status_approval);
            $this->assertEquals($this->admin->id, $laporan->approved_by);
        }

        Queue::assertPushed(SyncLaporanToGoogleSheetJob::class, 3);
    }

    public function test_bulk_approve_json_response(): void
    {
        Queue::fake();

        $laporans = Laporan::factory()->count(2)->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
        ]);

        $response = $this->actingAs($this->supervisor)->postJson('/approval/bulk-approve', [
            'ids' => $laporans->pluck('id')->toArray(),
            'catatan_approval' => 'Batch approve via API.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'total_approved' => 2,
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'total_approved',
                'data',
            ]);
    }

    public function test_karyawan_cannot_bulk_approve(): void
    {
        $laporans = Laporan::factory()->count(2)->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
        ]);

        $response = $this->actingAs($this->karyawan)->post('/approval/bulk-approve', [
            'ids' => $laporans->pluck('id')->toArray(),
        ]);

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login_when_bulk_approving(): void
    {
        $response = $this->post('/approval/bulk-approve', [
            'ids' => [1, 2],
        ]);

        $response->assertRedirect('/login');
    }

    public function test_bulk_approve_validates_required_ids(): void
    {
        $response = $this->actingAs($this->supervisor)->post('/approval/bulk-approve', [
            'ids' => [],
        ]);

        $response->assertSessionHasErrors(['ids']);
    }

    public function test_bulk_approve_handles_no_pending_reports_gracefully(): void
    {
        $approvedReport = Laporan::factory()->tidakHadir()->approved($this->supervisor)->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
        ]);

        $response = $this->actingAs($this->supervisor)->post('/approval/bulk-approve', [
            'ids' => [$approvedReport->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');
    }

    public function test_approval_index_renders_bulk_approve_ui_elements(): void
    {
        Laporan::factory()->count(2)->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
        ]);

        $response = $this->actingAs($this->supervisor)->get('/approval');

        $response->assertStatus(200)
            ->assertSee('id="selectAllCheckbox"', false)
            ->assertSee('class="form-check-input bulk-checkbox"', false)
            ->assertSee('id="bulkActionBar"', false)
            ->assertSee('id="modalBulkApprove"', false)
            ->assertSee('id="bulkApproveForm"', false)
            ->assertSee('id="btnConfirmBulkApprove"', false);
    }
}
