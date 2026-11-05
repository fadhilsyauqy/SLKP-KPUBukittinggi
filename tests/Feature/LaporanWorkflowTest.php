<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\Laporan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LaporanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Carbon::setTestNow('2026-11-04 09:00:00'); // Rabu pukul 09:00 WIB (hari dan jam dinas aktif)
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_duplicate_hadir_report_on_same_date_is_rejected_via_json_with_422(): void
    {
        Http::fake([
            'script.google.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $karyawan = User::where('username', 'karyawan')->first();

        // 1. Kirim laporan kehadiran pertama pada tanggal 2026-11-04
        $firstPayload = [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Menyusun laporan verifikasi berkas tahap pertama',
            'output' => '1 Berkas rekap verifikasi',
        ];

        $firstResponse = $this->actingAs($karyawan)->postJson('/laporan/hadir', $firstPayload);
        $firstResponse->assertStatus(201);
        $this->assertDatabaseCount('laporans', 1);

        // 2. Upaya mengirimkan laporan kedua pada tanggal yang sama wajib ditolak HTTP 422
        $secondPayload = [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '08:00',
            'jam_keluar' => '16:30',
            'uraian' => 'Melakukan entri ganda yang tidak sengaja terkirim',
            'output' => 'Tidak valid',
        ];

        $secondResponse = $this->actingAs($karyawan)->postJson('/laporan/hadir', $secondPayload);
        $secondResponse->assertStatus(422);
        $secondResponse->assertJsonValidationErrors(['tanggal']);
        $secondResponse->assertJsonFragment([
            'tanggal' => ['Anda sudah mengirimkan laporan kehadiran untuk tanggal 04/11/2026. Silakan periksa menu Riwayat Laporan.'],
        ]);

        // Verifikasi tidak ada data duplikat di database lokal
        $this->assertDatabaseCount('laporans', 1);
        $this->assertEquals(1, Laporan::where('nip', $karyawan->nip)->where('tipe', 'hadir')->count());
    }

    public function test_duplicate_hadir_report_on_same_date_is_rejected_via_web_with_session_errors(): void
    {
        Http::fake([
            'script.google.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $karyawan = User::where('username', 'karyawan')->first();

        // Buat laporan kehadiran yang sudah ada di database
        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Laporan awal',
            'output' => '1 Berkas',
            'status_approval' => 'approved',
            'status_sinkron_google' => true,
        ]);

        $payload = [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Upaya submit ulang via browser form',
            'output' => '1 Berkas',
        ];

        $response = $this->actingAs($karyawan)->post('/laporan/hadir', $payload);

        $response->assertSessionHasErrors([
            'tanggal' => 'Anda sudah mengirimkan laporan kehadiran untuk tanggal 04/11/2026. Silakan periksa menu Riwayat Laporan.',
        ]);

        $this->assertEquals(1, Laporan::where('nip', $karyawan->nip)->count());
    }

    public function test_employee_can_submit_hadir_reports_on_different_dates(): void
    {
        Http::fake([
            'script.google.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $karyawan = User::where('username', 'karyawan')->first();

        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-03',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Laporan kemarin',
            'output' => '1 Dokumen',
            'status_approval' => 'approved',
            'status_sinkron_google' => true,
        ]);

        $response = $this->actingAs($karyawan)->postJson('/laporan/hadir', [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Laporan hari ini',
            'output' => '1 Dokumen baru',
        ]);

        $response->assertStatus(201);
        $this->assertEquals(2, Laporan::where('nip', $karyawan->nip)->count());
    }

    public function test_different_employees_can_submit_hadir_reports_on_same_date(): void
    {
        Http::fake([
            'script.google.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $karyawan = User::where('username', 'karyawan')->first();
        $supervisor = User::where('username', 'supervisor')->first();

        // Karyawan sudah melapor pada tanggal 2026-11-04
        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Laporan karyawan',
            'output' => '1 Berkas',
            'status_approval' => 'approved',
            'status_sinkron_google' => true,
        ]);

        // Supervisor juga melapor pada tanggal yang sama 2026-11-04 dengan NIP miliknya
        $response = $this->actingAs($supervisor)->postJson('/laporan/hadir', [
            'nip' => $supervisor->nip,
            'nama' => $supervisor->name,
            'jabatan' => 'Kepala Subbagian',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Monitoring dan evaluasi pekerjaan staf',
            'output' => '1 Berita Acara Supervisi',
        ]);

        $response->assertStatus(201);
        $this->assertEquals(2, Laporan::where('tanggal', '2026-11-04')->count());
    }

    public function test_check_date_endpoint_returns_duplicate_status_when_already_reported(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Laporan presensi',
            'output' => '1 Berkas',
            'status_approval' => 'approved',
            'status_sinkron_google' => true,
        ]);

        $response = $this->actingAs($karyawan)->getJson('/laporan/hadir/check-date?tanggal=2026-11-04');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'duplicate',
            'exists' => true,
            'message' => 'Anda sudah mengirimkan laporan kehadiran untuk tanggal 04/11/2026. Silakan periksa menu Riwayat Laporan.',
        ]);
    }

    public function test_check_date_endpoint_returns_available_status_when_not_reported(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        $response = $this->actingAs($karyawan)->getJson('/laporan/hadir/check-date?tanggal=2026-11-05');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'available',
            'exists' => false,
        ]);
    }

    public function test_supervisor_or_admin_can_check_date_by_nip(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();
        $supervisor = User::where('username', 'supervisor')->first();

        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Laporan presensi staf',
            'output' => '1 Berkas',
            'status_approval' => 'approved',
            'status_sinkron_google' => true,
        ]);

        $response = $this->actingAs($supervisor)->getJson('/laporan/hadir/check-date?tanggal=2026-11-04&nip='.$karyawan->nip);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'duplicate',
            'exists' => true,
        ]);
    }

    public function test_guest_cannot_access_check_date_endpoint(): void
    {
        $response = $this->getJson('/laporan/hadir/check-date?tanggal=2026-11-04');
        $response->assertStatus(401);
    }

    public function test_riwayat_renders_yellow_warning_widget_when_user_has_not_reported_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-04 09:00:00', 'Asia/Jakarta'));

        $karyawan = User::where('username', 'karyawan')->first();

        $response = $this->actingAs($karyawan)->get('/laporan/riwayat');

        $response->assertStatus(200);
        $response->assertViewHas('hasReportedToday', false);
        $response->assertSee('id="todayStatusWidget"', false);
        $response->assertSee('Anda belum mengisi laporan kinerja hari ini.');
        $response->assertSee('Lapor Hadir');
        $response->assertSee(route('laporan.hadir'));
        $response->assertSee('Izin / Cuti');
        $response->assertSee(route('laporan.tidakhadir'));
        $response->assertDontSee('Anda telah mengisi laporan kinerja hari ini pada pukul');
    }

    public function test_riwayat_renders_green_success_widget_when_user_has_reported_hadir_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-04 08:15:00', 'Asia/Jakarta'));

        $karyawan = User::where('username', 'karyawan')->first();

        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Verifikasi dokumen pencalonan hari ini',
            'output' => '1 Rekapitulasi',
            'status_approval' => 'approved',
            'status_sinkron_google' => true,
        ]);

        $response = $this->actingAs($karyawan)->get('/laporan/riwayat');

        $response->assertStatus(200);
        $response->assertViewHas('hasReportedToday', true);
        $response->assertSee('id="todayStatusWidget"', false);
        $response->assertSee('Anda telah mengisi laporan kinerja hari ini pada pukul 08:15 WIB.');
        $response->assertSee('Sudah Presensi');
        $response->assertDontSee('Anda belum mengisi laporan kinerja hari ini.');
    }

    public function test_riwayat_renders_green_widget_when_user_has_leave_covering_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));

        $karyawan = User::where('username', 'karyawan')->first();

        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'tidak_hadir',
            'tanggal' => '2026-11-03',
            'tanggal_selesai' => '2026-11-05',
            'keterangan' => 'Cuti Tahunan',
            'alasan' => 'Keperluan keluarga di luar kota',
            'lama_hari' => '3 Hari',
            'status_approval' => 'approved',
            'status_sinkron_google' => true,
        ]);

        $response = $this->actingAs($karyawan)->get('/laporan/riwayat');

        $response->assertStatus(200);
        $response->assertViewHas('hasReportedToday', true);
        $response->assertSee('id="todayStatusWidget"', false);
        $response->assertSee('Anda telah mengisi laporan kinerja hari ini pada pukul 10:00 WIB.');
        $response->assertSee('Cuti Tahunan');
    }

    public function test_riwayat_json_endpoint_returns_has_reported_today_status(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-04 09:00:00', 'Asia/Jakarta'));

        $karyawan = User::where('username', 'karyawan')->first();

        $responseEmpty = $this->actingAs($karyawan)->getJson('/laporan/riwayat');
        $responseEmpty->assertStatus(200);
        $responseEmpty->assertJson([
            'status' => 'success',
            'has_reported_today' => false,
            'today_report' => null,
        ]);

        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Rapat koordinasi logistik',
            'output' => '1 Notula',
            'status_approval' => 'approved',
            'status_sinkron_google' => true,
        ]);

        $responseFilled = $this->actingAs($karyawan)->getJson('/laporan/riwayat');
        $responseFilled->assertStatus(200);
        $responseFilled->assertJson([
            'status' => 'success',
            'has_reported_today' => true,
        ]);
        $this->assertNotNull($responseFilled->json('today_report'));
    }
}
