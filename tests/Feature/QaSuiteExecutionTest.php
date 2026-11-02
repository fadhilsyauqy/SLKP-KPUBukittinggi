<?php

namespace Tests\Feature;

use App\Jobs\SyncLaporanToGoogleSheetJob;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\GoogleSheetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Enterprise QA & UAT Automated Verification Test Suite.
 * Directly maps to QA_PLAN.md Test Suites 1 through 8:
 * - SUITE 1: SEC-01 to SEC-05 (Account Security & Force Password Change)
 * - SUITE 2: HDR-01 to HDR-05 (Presence & Working Hours Validation)
 * - SUITE 3: APV-01 to APV-06 (Absence & Multi-tier Approval Workflow)
 * - SUITE 4: PDF-01 to PDF-05 (Official PDF & QR Code Verification)
 * - SUITE 5: VSA-01 to VSA-04 (Visual Analytics & Excel Export)
 * - SUITE 6: PWA-01 to PWA-05 (Progressive Web App & Mobile Readiness)
 * - SUITE 7: LOG-01 to LOG-05 (Audit Trail & Forensic Logs)
 * - SUITE 8: STR-01 to STR-02 (Peak Concurrency Simulation & File Boundaries)
 */
class QaSuiteExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $supervisor;

    protected User $karyawan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('username', 'admin')->first();
        $this->supervisor = User::where('username', 'supervisor')->first();
        $this->karyawan = User::where('username', 'karyawan')->first();

        Http::fake([
            'script.google.com/*' => Http::response(['status' => 'success', 'message' => 'Synced'], 200),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    // ==========================================
    // 🧪 SUITE 1: Keamanan Akun & Ganti Password (SEC-01 - SEC-05)
    // ==========================================

    public function test_qa_suite_1_sec_01_force_password_change_on_first_login(): void
    {
        $this->karyawan->update(['must_change_password' => true]);

        $response = $this->post('/login', [
            'username' => 'karyawan',
            'password' => 'karyawan123',
        ]);

        $this->assertAuthenticatedAs($this->karyawan);
        $response->assertRedirect('/password/change');
    }

    public function test_qa_suite_1_sec_02_bypass_prevented_when_must_change_password_active(): void
    {
        $this->karyawan->update(['must_change_password' => true]);

        // Attempting to bypass directly to report or history routes
        $responseHadir = $this->actingAs($this->karyawan)->get('/laporan/hadir');
        $responseHadir->assertRedirect('/password/change');

        $responseRiwayat = $this->actingAs($this->karyawan)->get('/laporan/riwayat');
        $responseRiwayat->assertRedirect('/password/change');
    }

    public function test_qa_suite_1_sec_03_password_validation_rules(): void
    {
        $this->karyawan->update(['must_change_password' => true]);

        // Wrong current password
        $responseWrongCurrent = $this->actingAs($this->karyawan)->post('/password/change', [
            'current_password' => 'passwordsalah',
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ]);
        $responseWrongCurrent->assertSessionHasErrors('current_password');

        // Short new password (< 6 chars)
        $responseShort = $this->actingAs($this->karyawan)->post('/password/change', [
            'current_password' => 'karyawan123',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);
        $responseShort->assertSessionHasErrors('password');

        // Password confirmation mismatch
        $responseMismatch = $this->actingAs($this->karyawan)->post('/password/change', [
            'current_password' => 'karyawan123',
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'BedaPassword123',
        ]);
        $responseMismatch->assertSessionHasErrors('password');
    }

    public function test_qa_suite_1_sec_04_successful_password_change_clears_flag_and_logs(): void
    {
        $this->karyawan->update(['must_change_password' => true]);

        $response = $this->actingAs($this->karyawan)->post('/password/change', [
            'current_password' => 'karyawan123',
            'password' => 'PasswordAman2026',
            'password_confirmation' => 'PasswordAman2026',
        ]);

        $response->assertRedirect('/laporan/riwayat');
        $this->karyawan->refresh();
        $this->assertFalse((bool) $this->karyawan->must_change_password);
        $this->assertTrue(Hash::check('PasswordAman2026', $this->karyawan->password));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->karyawan->id,
            'action' => 'UPDATE',
            'module' => 'AUTH',
        ]);
    }

    public function test_qa_suite_1_sec_05_login_rate_limiting_protects_brute_force(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'username' => 'karyawan',
                'password' => 'passwordsalah',
            ]);
        }

        // 6th attempt triggers throttle lock
        $responseThrottled = $this->post('/login', [
            'username' => 'karyawan',
            'password' => 'passwordsalah',
        ]);

        $responseThrottled->assertSessionHasErrors('username');
        $errors = session('errors')->get('username');
        $this->assertStringContainsString('Terlalu banyak upaya login', $errors[0]);
    }

    // ==========================================
    // 🧪 SUITE 2: Pelaporan Hadir & Jam Dinas (HDR-01 - HDR-05)
    // ==========================================

    public function test_qa_suite_2_hdr_01_submission_during_normal_working_hours(): void
    {
        Queue::fake();

        // Simulate Wednesday at 08:30 WIB (within 06:30 - 19:00 WIB)
        Carbon::setTestNow(Carbon::parse('2026-11-04 08:30:00', 'Asia/Jakarta'));

        $startTime = microtime(true);
        $response = $this->actingAs($this->karyawan)->post('/laporan/hadir', [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Mengikuti apel dinas dan verifikasi data pemilih',
            'output' => '1 Berkas rekapitulasi data pemilih',
        ]);
        $durationMs = (microtime(true) - $startTime) * 1000;

        $response->assertRedirect('/laporan/riwayat');
        $this->assertLessThan(500, $durationMs, 'Response time should be prompt (< 500ms).');

        $this->assertDatabaseHas('laporans', [
            'user_id' => $this->karyawan->id,
            'tipe' => 'hadir',
            'uraian' => 'Mengikuti apel dinas dan verifikasi data pemilih',
        ]);

        Queue::assertPushed(SyncLaporanToGoogleSheetJob::class);
    }

    public function test_qa_suite_2_hdr_02_submission_outside_working_hours_is_blocked(): void
    {
        config(['kpu.jam_dinas.enforce' => true]);
        config(['kpu.jam_dinas.agenda_khusus' => false]);

        // Simulate Wednesday at 21:30 WIB (outside 06:30 - 19:00 WIB)
        Carbon::setTestNow(Carbon::parse('2026-11-04 21:30:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawan)->post('/laporan/hadir', [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Mencoba mengisi laporan larut malam',
            'output' => 'Laporan',
        ]);

        $response->assertSessionHasErrors('jam_masuk');
        $errors = session('errors')->get('jam_masuk');
        $this->assertStringContainsString('06:30 – 19:00 WIB', $errors[0]);
    }

    public function test_qa_suite_2_hdr_03_submission_on_weekend_is_blocked(): void
    {
        config(['kpu.jam_dinas.enforce' => true]);
        config(['kpu.jam_dinas.agenda_khusus' => false]);

        // Simulate Sunday at 10:00 WIB
        Carbon::setTestNow(Carbon::parse('2026-11-08 10:00:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawan)->post('/laporan/hadir', [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-08',
            'jam_masuk' => '08:00',
            'jam_keluar' => '12:00',
            'uraian' => 'Mencoba submit di hari Minggu',
            'output' => 'Laporan',
        ]);

        $response->assertSessionHasErrors('jam_masuk');
    }

    public function test_qa_suite_2_hdr_04_special_election_agenda_override(): void
    {
        config(['kpu.jam_dinas.enforce' => true]);
        config(['kpu.jam_dinas.agenda_khusus' => true]); // Override active

        // Simulate Sunday evening at 22:00 WIB
        Carbon::setTestNow(Carbon::parse('2026-11-08 22:00:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawan)->post('/laporan/hadir', [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-08',
            'jam_masuk' => '08:00',
            'jam_keluar' => '22:00',
            'uraian' => 'Rapat pleno penetapan hasil pemilu larut malam',
            'output' => 'Berita Acara Pleno Pemilu',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/laporan/riwayat');

        $this->assertDatabaseHas('laporans', [
            'user_id' => $this->karyawan->id,
            'uraian' => 'Rapat pleno penetapan hasil pemilu larut malam',
        ]);
    }

    public function test_qa_suite_2_hdr_05_queue_background_sync_processing(): void
    {
        $laporan = Laporan::factory()->create([
            'tipe' => 'hadir',
            'status_sinkron_google' => false,
        ]);

        $job = new SyncLaporanToGoogleSheetJob($laporan);
        $job->handle(app(GoogleSheetService::class));

        $laporan->refresh();
        $this->assertTrue((bool) $laporan->status_sinkron_google);
        $this->assertEquals('Data berhasil disinkronkan ke Google Spreadsheet.', $laporan->catatan_sinkron);
    }

    // ==========================================
    // 🧪 SUITE 3: Permohonan Izin & Approval (APV-01 - APV-06)
    // ==========================================

    public function test_qa_suite_3_apv_01_leave_submission_defaults_to_pending(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('surat_dokter.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->karyawan)->post('/laporan/tidak-hadir', [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'keterangan' => 'Sakit',
            'tanggal_in' => '2026-11-10',
            'tanggal_out' => '2026-11-11',
            'alasan' => 'Rawat inap di RSUD Bukittinggi',
            'attach' => $file,
        ]);

        $response->assertRedirect('/laporan/riwayat');

        $laporan = Laporan::where('alasan', 'Rawat inap di RSUD Bukittinggi')->first();
        $this->assertNotNull($laporan);
        $this->assertEquals('pending', $laporan->status_approval);
        $this->assertNull($laporan->approved_by);
        $this->assertNull($laporan->approved_at);
        $this->assertNotNull($laporan->bukti);
        Storage::disk('public')->assertExists($laporan->bukti);
    }

    public function test_qa_suite_3_apv_02_supervisor_and_admin_view_pending_queue(): void
    {
        Laporan::factory()->tidakHadir()->pending()->create([
            'nama' => 'Staf Penguji QA',
        ]);

        $response = $this->actingAs($this->supervisor)->get('/approval');
        $response->assertStatus(200);
        $response->assertSee('Staf Penguji QA');

        // Test JSON API queue counter
        $json = $this->actingAs($this->supervisor)->getJson('/approval');
        $json->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $json->json('counts.pending'));
    }

    public function test_qa_suite_3_apv_03_supervisor_approves_request(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
        ]);

        $response = $this->actingAs($this->supervisor)->post("/approval/{$laporan->id}/approve", [
            'catatan_approval' => 'Disetujui untuk izin istirahat sakit.',
        ]);

        $response->assertRedirect();
        $laporan->refresh();

        $this->assertEquals('approved', $laporan->status_approval);
        $this->assertEquals($this->supervisor->id, $laporan->approved_by);
        $this->assertNotNull($laporan->approved_at);
        $this->assertEquals('Disetujui untuk izin istirahat sakit.', $laporan->catatan_approval);
    }

    public function test_qa_suite_3_apv_04_supervisor_reject_requires_reason(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
        ]);

        $response = $this->actingAs($this->supervisor)->post("/approval/{$laporan->id}/reject", [
            'catatan_approval' => '',
        ]);

        $response->assertSessionHasErrors('catatan_approval');
        $laporan->refresh();
        $this->assertEquals('pending', $laporan->status_approval);
    }

    public function test_qa_suite_3_apv_05_supervisor_reject_with_valid_reason(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
        ]);

        $response = $this->actingAs($this->supervisor)->post("/approval/{$laporan->id}/reject", [
            'catatan_approval' => 'Tugas pleno tidak dapat diwakilkan.',
        ]);

        $response->assertRedirect();
        $laporan->refresh();

        $this->assertEquals('rejected', $laporan->status_approval);
        $this->assertEquals($this->supervisor->id, $laporan->approved_by);
        $this->assertEquals('Tugas pleno tidak dapat diwakilkan.', $laporan->catatan_approval);

        // Employee history should show the rejection notice
        $responseHistory = $this->actingAs($this->karyawan)->get('/laporan/riwayat');
        $responseHistory->assertStatus(200);
        $responseHistory->assertSee('Ditolak');
        $responseHistory->assertSee('Tugas pleno tidak dapat diwakilkan.');
    }

    public function test_qa_suite_3_apv_06_employee_cannot_approve_or_reject_requests(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
        ]);

        $this->actingAs($this->karyawan)->post("/approval/{$laporan->id}/approve")->assertStatus(403);
        $this->actingAs($this->karyawan)->post("/approval/{$laporan->id}/reject", [
            'catatan_approval' => 'Tolak sendiri',
        ])->assertStatus(403);
    }

    // ==========================================
    // 🧪 SUITE 4: Cetak Dokumen PDF & QR Code (PDF-01 - PDF-05)
    // ==========================================

    public function test_qa_suite_4_pdf_01_pending_and_rejected_cannot_be_downloaded(): void
    {
        $pending = Laporan::factory()->tidakHadir()->pending()->create(['user_id' => $this->karyawan->id]);
        $rejected = Laporan::factory()->tidakHadir()->rejected($this->supervisor)->create(['user_id' => $this->karyawan->id]);

        $responsePending = $this->actingAs($this->karyawan)->get("/laporan/{$pending->id}/pdf");
        $responsePending->assertRedirect();
        $responsePending->assertSessionHas('error');

        $responseRejected = $this->actingAs($this->karyawan)->get("/laporan/{$rejected->id}/pdf");
        $responseRejected->assertRedirect();
        $responseRejected->assertSessionHas('error');
    }

    public function test_qa_suite_4_pdf_02_and_03_approved_leave_generates_official_pdf_with_kop(): void
    {
        $approved = Laporan::factory()->tidakHadir()->approved($this->supervisor)->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'keterangan' => 'Cuti',
            'alasan' => 'Cuti Tahunan',
        ]);

        $response = $this->actingAs($this->karyawan)->get("/laporan/{$approved->id}/pdf");
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertNotEmpty($response->getContent());
    }

    public function test_qa_suite_4_pdf_04_public_qr_verification_endpoint(): void
    {
        $approved = Laporan::factory()->tidakHadir()->approved($this->supervisor)->create([
            'uuid' => (string) Str::uuid(),
            'nama' => 'Rahmat Hidayat',
            'keterangan' => 'Perjadin',
            'alasan' => 'Konsultasi Hukum KPU RI Jakarta',
        ]);

        $response = $this->get("/verifikasi-surat/{$approved->uuid}");
        $response->assertStatus(200);
        $response->assertSee('DOKUMEN RESMI TERVERIFIKASI');
        $response->assertSee('SAH &amp; VALID', false);
        $response->assertSee('Rahmat Hidayat');
        $response->assertSee('Konsultasi Hukum KPU RI Jakarta');
        $response->assertSee($this->supervisor->name);
    }

    public function test_qa_suite_4_pdf_05_unknown_qr_token_shows_invalid_status(): void
    {
        $fakeUuid = (string) Str::uuid();
        $response = $this->get("/verifikasi-surat/{$fakeUuid}");
        $response->assertStatus(200);
        $response->assertSee('DOKUMEN TIDAK DITEMUKAN');
    }

    // ==========================================
    // 🧪 SUITE 5: Dashboard Visual Analytics (VSA-01 - VSA-04)
    // ==========================================

    public function test_qa_suite_5_vsa_01_to_03_monitoring_visual_charts_rendering_and_json(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        Laporan::factory()->create([
            'tipe' => 'hadir',
            'tanggal' => $today,
            'jabatan' => 'Kasubag Keuangan, Umum dan Logistik',
        ]);

        Laporan::factory()->tidakHadir()->create([
            'keterangan' => 'Cuti',
            'tanggal' => $today,
            'jabatan' => 'Kasubag Hukum dan SDM',
        ]);

        // VSA-01: HTML View Canvas
        $responseView = $this->actingAs($this->supervisor)->get('/monitoring');
        $responseView->assertStatus(200);
        $responseView->assertSee('trendChart');
        $responseView->assertSee('absenceChart');
        $responseView->assertSee('divisionChart');

        // VSA-02 & VSA-03: JSON Chart Analytics
        $responseJson = $this->actingAs($this->admin)->getJson('/monitoring/charts');
        $responseJson->assertStatus(200);
        $responseJson->assertJsonStructure([
            'status',
            'charts' => [
                'daily_trend' => ['labels', 'dates', 'hadir', 'tidak_hadir'],
                'absence_distribution' => ['labels', 'data', 'colors'],
                'division_distribution' => ['labels', 'hadir', 'tidak_hadir'],
            ],
        ]);
    }

    public function test_qa_suite_5_vsa_04_excel_export_endpoints(): void
    {
        $responseHadir = $this->actingAs($this->supervisor)->get('/monitoring/export-excel/hadir');
        $responseHadir->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $responseHadir->headers->get('Content-Type'));

        $responseTidakHadir = $this->actingAs($this->supervisor)->get('/monitoring/export-excel/tidak-hadir');
        $responseTidakHadir->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $responseTidakHadir->headers->get('Content-Type'));
    }

    // ==========================================
    // 🧪 SUITE 6: Progressive Web App & Mobile (PWA-01 - PWA-05)
    // ==========================================

    public function test_qa_suite_6_pwa_01_to_03_manifest_configuration(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertTrue(File::exists($manifestPath));

        $manifest = json_decode(File::get($manifestPath), true);
        $this->assertEquals('Sistem Laporan Kinerja Pegawai KPU Kota Bukittinggi', $manifest['name']);
        $this->assertEquals('SLKP KPU', $manifest['short_name']);
        $this->assertEquals('standalone', $manifest['display']);
        $this->assertEquals('#7a1c1c', $manifest['theme_color']);
        $this->assertNotEmpty($manifest['icons']);
    }

    public function test_qa_suite_6_pwa_04_and_05_sw_and_responsive_viewport(): void
    {
        // Service Worker
        $swPath = public_path('sw.js');
        $this->assertTrue(File::exists($swPath));
        $swContent = File::get($swPath);
        $this->assertStringContainsString('CACHE_NAME', $swContent);

        // Viewport & PWA tags on layout
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('name="viewport"', false);
        $response->assertSee('width=device-width, initial-scale=1.0', false);
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('serviceWorker.register', false);
    }

    // ==========================================
    // 🧪 SUITE 7: Audit Trail & Forensik (LOG-01 - LOG-05)
    // ==========================================

    public function test_qa_suite_7_log_01_to_04_admin_audit_trail_and_filters(): void
    {
        ActivityLogger::log('DELETE', 'JABATAN', 'Menghapus jabatan uji coba', ['id' => 99], $this->admin);

        $response = $this->actingAs($this->admin)->get('/admin/activity-log?action=DELETE');
        $response->assertStatus(200);
        $response->assertSee('Menghapus jabatan uji coba');
        $response->assertSee('JABATAN');
        $response->assertSee('&quot;id&quot;: 99', false);
    }

    public function test_qa_suite_7_log_05_non_admin_forbidden_from_audit_logs(): void
    {
        $this->actingAs($this->supervisor)->get('/admin/activity-log')->assertStatus(403);
        $this->actingAs($this->karyawan)->get('/admin/activity-log')->assertStatus(403);
    }

    // ==========================================
    // 🧪 SUITE 8: Konkurensi & Batas Beban (STR-01 - STR-02)
    // ==========================================

    public function test_qa_suite_8_str_01_simulation_30_employees_morning_rush_hour_submissions(): void
    {
        Queue::fake();

        // Simulate Wednesday at 07:45 WIB (morning peak presensi)
        Carbon::setTestNow(Carbon::parse('2026-11-04 07:45:00', 'Asia/Jakarta'));

        $jabatan = Jabatan::firstOrCreate(['nama_jabatan' => 'Staf Pelaksana Teknis']);

        $benchmarkStart = microtime(true);
        $employeeCount = 30;

        for ($i = 1; $i <= $employeeCount; $i++) {
            $nip = sprintf('19900101202201%04d', $i);
            $user = User::factory()->create([
                'username' => 'staf_'.$i,
                'nip' => $nip,
                'name' => 'Staf KPU '.$i,
                'role' => 'karyawan',
                'must_change_password' => false,
            ]);

            Karyawan::create([
                'nip' => $nip,
                'nama' => 'Staf KPU '.$i,
                'jabatan_id' => $jabatan->id,
                'sort_order' => 10,
            ]);

            $response = $this->actingAs($user)->post('/laporan/hadir', [
                'nip' => $nip,
                'nama' => 'Staf KPU '.$i,
                'jabatan' => $jabatan->nama_jabatan,
                'tanggal' => '2026-11-04',
                'jam_masuk' => '07:45',
                'jam_keluar' => '16:00',
                'uraian' => 'Melaksanakan tugas piket dan verifikasi logistik batch '.$i,
                'output' => 'Laporan piket '.$i,
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect('/laporan/riwayat');
        }

        $totalDurationMs = (microtime(true) - $benchmarkStart) * 1000;
        $avgDurationPerRequest = $totalDurationMs / $employeeCount;

        // Verify all 30 reports are persisted in the database
        $this->assertEquals($employeeCount, Laporan::where('tanggal', '2026-11-04')->count());

        // Verify 30 queue jobs were dispatched
        Queue::assertPushed(SyncLaporanToGoogleSheetJob::class, $employeeCount);

        // Concurrency performance SLA: Average execution per simulated request under 200ms
        $this->assertLessThan(200, $avgDurationPerRequest, 'Average submission time during rush hour should be < 200ms.');
    }

    public function test_qa_suite_8_str_02_upload_attachment_size_limit_and_error_handling(): void
    {
        Storage::fake('public');

        // 1. Valid attachment under 5MB (e.g. 2MB)
        $validFile = UploadedFile::fake()->create('surat_dokter_2mb.pdf', 2048, 'application/pdf');

        $responseValid = $this->actingAs($this->karyawan)->post('/laporan/tidak-hadir', [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'keterangan' => 'Sakit',
            'tanggal_in' => '2026-11-12',
            'tanggal_out' => '2026-11-12',
            'alasan' => 'Demam flu',
            'attach' => $validFile,
        ]);

        $responseValid->assertSessionHasNoErrors();
        $responseValid->assertRedirect('/laporan/riwayat');

        // 2. Oversized attachment exceeding 5MB (e.g. 8MB)
        $oversizedFile = UploadedFile::fake()->create('foto_kamera_8mb.jpg', 8192, 'image/jpeg');

        $responseOversized = $this->actingAs($this->karyawan)->post('/laporan/tidak-hadir', [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'keterangan' => 'Sakit',
            'tanggal_in' => '2026-11-13',
            'tanggal_out' => '2026-11-13',
            'alasan' => 'Izin periksa ke dokter',
            'attach' => $oversizedFile,
        ]);

        // Validation should fail gracefully without a 500 server crash
        $responseOversized->assertStatus(302);
        $responseOversized->assertSessionHasErrors(['attach']);
        $errors = session('errors')->get('attach');
        $this->assertStringContainsString('Ukuran file lampiran maksimal 5MB.', $errors[0]);
    }
}
