<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaporanControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_karyawan_can_view_create_hadir_form(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        $response = $this->actingAs($karyawan)->get('/laporan/hadir');

        $response->assertStatus(200);
        $response->assertSee('FORM LAPORAN KINERJA PEGAWAI HARIAN');
    }

    public function test_karyawan_can_store_hadir_report_and_sync_with_google(): void
    {
        Http::fake([
            'script.google.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $karyawan = User::where('username', 'karyawan')->first();

        $response = $this->actingAs($karyawan)->post('/laporan/hadir', [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Menyusun laporan verifikasi administrasi',
            'output' => '1 Berkas rekap verifikasi',
        ]);

        $response->assertRedirect('/laporan/riwayat');

        $this->assertDatabaseHas('laporans', [
            'nip' => $karyawan->nip,
            'tipe' => 'hadir',
            'status_sinkron_google' => true,
        ]);
    }

    public function test_karyawan_can_store_tidak_hadir_with_file_upload(): void
    {
        Storage::fake('public');
        Http::fake([
            'script.google.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $karyawan = User::where('username', 'karyawan')->first();
        $file = UploadedFile::fake()->create('surat_sakit.pdf', 100, 'application/pdf');

        $response = $this->actingAs($karyawan)->post('/laporan/tidak-hadir', [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'keterangan' => 'Sakit',
            'tanggal_in' => '2026-11-02',
            'tanggal_out' => '2026-11-03',
            'alasan' => 'Rawat inap demam',
            'attach' => $file,
        ]);

        $response->assertRedirect('/laporan/riwayat');

        $this->assertDatabaseHas('laporans', [
            'nip' => $karyawan->nip,
            'tipe' => 'tidak_hadir',
            'keterangan' => 'Sakit',
            'lama_hari' => '2 Hari',
            'status_sinkron_google' => true,
        ]);
    }

    public function test_karyawan_can_view_own_history_and_filters(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        // Create dummy reports
        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Kegiatan audit regulasi',
            'output' => '1 Berkas audit',
        ]);

        $response = $this->actingAs($karyawan)->get('/laporan/riwayat?tipe=hadir');

        $response->assertStatus(200);
        $response->assertSee('Kegiatan audit regulasi');
    }

    public function test_guest_cannot_access_last_hadir_endpoint(): void
    {
        $response = $this->getJson('/laporan/hadir/last');

        $response->assertStatus(401);
    }

    public function test_karyawan_receives_empty_when_no_previous_hadir_report(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        $response = $this->actingAs($karyawan)->getJson('/laporan/hadir/last');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'empty',
                'message' => 'Belum ada riwayat laporan kehadiran sebelumnya.',
                'data' => null,
            ]);
    }

    public function test_karyawan_receives_their_own_latest_hadir_report(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();
        $supervisor = User::where('username', 'supervisor')->first();

        // 1. Older hadir report for karyawan
        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-01',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Uraian Kemarin Dulu',
            'output' => 'Output Kemarin Dulu',
        ]);

        // 2. Latest hadir report for karyawan
        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Uraian Tugas Terbaru Karyawan',
            'output' => 'Output Terbaru Karyawan',
        ]);

        // 3. Permohonan izin (tidak_hadir) - should be ignored
        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'tidak_hadir',
            'tanggal' => '2026-11-03',
            'keterangan' => 'Izin',
            'alasan' => 'Urusan keluarga',
        ]);

        // 4. Laporan hadir orang lain (supervisor) dengan tanggal lebih baru
        Laporan::create([
            'user_id' => $supervisor->id,
            'nip' => $supervisor->nip,
            'nama' => $supervisor->name,
            'jabatan' => 'Kasubbag Keuangan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Uraian Rahasia Atasan',
            'output' => 'Output Rahasia Atasan',
        ]);

        $response = $this->actingAs($karyawan)->getJson('/laporan/hadir/last');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'tanggal' => '02/11/2026',
                    'uraian' => 'Uraian Tugas Terbaru Karyawan',
                    'output' => 'Output Terbaru Karyawan',
                ],
            ]);
    }

    public function test_karyawan_cannot_access_other_employee_report_via_nip_parameter(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();
        $supervisor = User::where('username', 'supervisor')->first();

        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Uraian Asli Karyawan',
            'output' => 'Output Asli Karyawan',
        ]);

        Laporan::create([
            'user_id' => $supervisor->id,
            'nip' => $supervisor->nip,
            'nama' => $supervisor->name,
            'jabatan' => 'Kasubbag Keuangan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-03',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Uraian Milik Supervisor',
            'output' => 'Output Milik Supervisor',
        ]);

        // Karyawan tries to inject supervisor's NIP
        $response = $this->actingAs($karyawan)->getJson('/laporan/hadir/last?nip='.$supervisor->nip);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'uraian' => 'Uraian Asli Karyawan',
                    'output' => 'Output Asli Karyawan',
                ],
            ]);
    }

    public function test_supervisor_or_admin_can_retrieve_karyawan_last_hadir_report_by_nip(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();
        $supervisor = User::where('username', 'supervisor')->first();

        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Uraian Karyawan Pilihan',
            'output' => 'Output Karyawan Pilihan',
        ]);

        $response = $this->actingAs($supervisor)->getJson('/laporan/hadir/last?nip='.$karyawan->nip);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'uraian' => 'Uraian Karyawan Pilihan',
                    'output' => 'Output Karyawan Pilihan',
                ],
            ]);
    }
}
