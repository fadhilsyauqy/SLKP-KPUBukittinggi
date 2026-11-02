<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PdfSuratIzinTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawan;

    protected User $otherKaryawan;

    protected User $supervisor;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->karyawan = User::where('username', 'karyawan')->first();
        $this->supervisor = User::where('username', 'supervisor')->first();
        $this->admin = User::where('username', 'admin')->first();

        $this->otherKaryawan = User::factory()->create([
            'role' => 'karyawan',
            'nip' => '198501012010011999',
            'username' => 'pegawai_lain',
        ]);
    }

    public function test_qr_code_service_generates_valid_svg_data_uri(): void
    {
        $service = new QrCodeService;
        $dataUri = $service->generateSvgDataUri('https://example.com/test-verify');

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUri);
    }

    public function test_approved_tidak_hadir_can_be_downloaded_as_pdf(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->approved($this->supervisor)->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'keterangan' => 'Cuti',
            'alasan' => 'Cuti tahunan keperluan keluarga di luar kota',
        ]);

        $response = $this->actingAs($this->karyawan)->get("/laporan/{$laporan->id}/pdf");

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertNotEmpty($response->getContent());
    }

    public function test_pending_or_rejected_cannot_be_downloaded_as_pdf(): void
    {
        // 1. Pending report
        $pendingLaporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
        ]);

        $responsePending = $this->actingAs($this->karyawan)->get("/laporan/{$pendingLaporan->id}/pdf");
        $responsePending->assertRedirect();
        $responsePending->assertSessionHas('error', 'Surat pengesahan hanya dapat dicetak setelah pengajuan disetujui oleh atasan.');

        // 2. Rejected report
        $rejectedLaporan = Laporan::factory()->tidakHadir()->rejected($this->supervisor)->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
        ]);

        $responseRejected = $this->actingAs($this->karyawan)->get("/laporan/{$rejectedLaporan->id}/pdf");
        $responseRejected->assertRedirect();
        $responseRejected->assertSessionHas('error', 'Surat pengesahan hanya dapat dicetak setelah pengajuan disetujui oleh atasan.');
    }

    public function test_hadir_report_cannot_be_downloaded_as_leave_pdf(): void
    {
        $hadirLaporan = Laporan::factory()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'tipe' => 'hadir',
        ]);

        $response = $this->actingAs($this->karyawan)->get("/laporan/{$hadirLaporan->id}/pdf");
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_karyawan_cannot_download_other_karyawan_pdf(): void
    {
        $otherLaporan = Laporan::factory()->tidakHadir()->approved($this->supervisor)->create([
            'user_id' => $this->otherKaryawan->id,
            'nip' => $this->otherKaryawan->nip,
        ]);

        $response = $this->actingAs($this->karyawan)->get("/laporan/{$otherLaporan->id}/pdf");
        $response->assertStatus(403);
    }

    public function test_supervisor_and_admin_can_download_any_employee_pdf(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->approved($this->supervisor)->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
        ]);

        // Supervisor can download
        $responseSupervisor = $this->actingAs($this->supervisor)->get("/laporan/{$laporan->id}/pdf");
        $responseSupervisor->assertStatus(200);
        $this->assertEquals('application/pdf', $responseSupervisor->headers->get('Content-Type'));

        // Admin can download
        $responseAdmin = $this->actingAs($this->admin)->get("/laporan/{$laporan->id}/pdf");
        $responseAdmin->assertStatus(200);
        $this->assertEquals('application/pdf', $responseAdmin->headers->get('Content-Type'));
    }

    public function test_public_verification_endpoint_for_valid_approved_document(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->approved($this->supervisor)->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'keterangan' => 'Perjadin',
            'alasan' => 'Bimtek pemutakhiran data pemilih',
        ]);

        // HTML Public view (no login required)
        $response = $this->get("/verifikasi-surat/{$laporan->uuid}");
        $response->assertStatus(200);
        $response->assertSee('DOKUMEN RESMI TERVERIFIKASI');
        $response->assertSee($this->karyawan->name);
        $response->assertSee('Perjadin');
        $response->assertSee($this->supervisor->name);

        // JSON Public API
        $responseJson = $this->getJson("/verifikasi-surat/{$laporan->uuid}");
        $responseJson->assertStatus(200);
        $responseJson->assertJson([
            'status' => 'valid',
            'isValid' => true,
            'data' => [
                'uuid' => $laporan->uuid,
                'nama' => $this->karyawan->name,
                'keterangan' => 'Perjadin',
            ],
        ]);
    }

    public function test_public_verification_endpoint_for_pending_document(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
        ]);

        $response = $this->get("/verifikasi-surat/{$laporan->uuid}");
        $response->assertStatus(200);
        $response->assertSee('DALAM PROSES VERIFIKASI');

        $responseJson = $this->getJson("/verifikasi-surat/{$laporan->uuid}");
        $responseJson->assertStatus(200);
        $responseJson->assertJson([
            'status' => 'pending',
            'isValid' => false,
        ]);
    }

    public function test_public_verification_endpoint_for_rejected_document(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->rejected($this->supervisor)->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'catatan_approval' => 'Jadwal berbenturan dengan agenda pleno rekapitulasi.',
        ]);

        $response = $this->get("/verifikasi-surat/{$laporan->uuid}");
        $response->assertStatus(200);
        $response->assertSee('DOKUMEN DITOLAK');
        $response->assertSee('Jadwal berbenturan dengan agenda pleno rekapitulasi.');

        $responseJson = $this->getJson("/verifikasi-surat/{$laporan->uuid}");
        $responseJson->assertStatus(200);
        $responseJson->assertJson([
            'status' => 'rejected',
            'isValid' => false,
        ]);
    }

    public function test_public_verification_endpoint_for_unknown_uuid(): void
    {
        $randomUuid = (string) Str::uuid();

        $response = $this->get("/verifikasi-surat/{$randomUuid}");
        $response->assertStatus(200);
        $response->assertSee('DOKUMEN TIDAK DITEMUKAN');

        $responseJson = $this->getJson("/verifikasi-surat/{$randomUuid}");
        $responseJson->assertStatus(404);
        $responseJson->assertJson([
            'status' => 'invalid',
        ]);
    }
}
