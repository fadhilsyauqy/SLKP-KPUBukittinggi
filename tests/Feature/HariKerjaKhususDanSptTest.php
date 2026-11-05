<?php

namespace Tests\Feature;

use App\Jobs\SyncLaporanToGoogleSheetJob;
use App\Models\HariLibur;
use App\Models\Laporan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HariKerjaKhususDanSptTest extends TestCase
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
    }

    public function test_admin_can_create_hari_kerja_khusus_pemilu(): void
    {
        // 2026-11-07 adalah hari Sabtu
        $response = $this->actingAs($this->admin)->post('/admin/hari-libur', [
            'tanggal' => '2026-11-07',
            'keterangan' => 'Rapat Pleno Rekapitulasi Suara Pilkada',
            'tipe' => 'hari_kerja_khusus',
        ]);

        $response->assertRedirect('/admin/hari-libur?tahun=2026');

        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => '2026-11-07',
            'keterangan' => 'Rapat Pleno Rekapitulasi Suara Pilkada',
            'is_hari_kerja' => true,
            'is_cuti_bersama' => false,
        ]);

        $this->assertTrue(HariLibur::isHariKerjaKhusus('2026-11-07'));
        $this->assertFalse(HariLibur::isHoliday('2026-11-07'));
    }

    public function test_admin_can_filter_hari_kerja_khusus_in_index(): void
    {
        HariLibur::create([
            'tanggal' => '2026-11-07',
            'keterangan' => 'Sortir dan Pelipatan Surat Suara',
            'is_hari_kerja' => true,
            'is_cuti_bersama' => false,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/hari-libur?tahun=2026&tipe=hari_kerja_khusus');
        $response->assertStatus(200);
        $response->assertSee('Sortir dan Pelipatan Surat Suara');
        $response->assertSee('Kerja Khusus Pemilu');
    }

    public function test_employee_can_report_on_weekend_with_hari_kerja_khusus_without_spt(): void
    {
        Queue::fake();

        // Daftarkan Sabtu 07 Nov 2026 sebagai Hari Kerja Khusus Pemilu
        HariLibur::create([
            'tanggal' => '2026-11-07',
            'keterangan' => 'Bimbingan Teknis Sirekap Pilkada',
            'is_hari_kerja' => true,
            'is_cuti_bersama' => false,
        ]);

        // Simulasikan Sabtu pukul 08:30 WIB
        Carbon::setTestNow(Carbon::parse('2026-11-07 08:30:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawan)->post('/laporan/hadir', [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-07',
            'jam_masuk' => '08:00',
            'jam_keluar' => '16:00',
            'uraian' => 'Mengikuti bimbingan teknis penggunaan Sirekap',
            'output' => '1 Berita Acara Bimtek',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/laporan/riwayat');

        $this->assertDatabaseHas('laporans', [
            'nip' => $this->karyawan->nip,
            'tanggal' => '2026-11-07',
            'tipe' => 'hadir',
            'status_approval' => 'approved',
        ]);

        Queue::assertPushed(SyncLaporanToGoogleSheetJob::class);
    }

    public function test_employee_submitting_on_regular_weekend_with_spt_is_saved_as_pending_approval(): void
    {
        Queue::fake();

        // Simulasikan Minggu 08 Nov 2026 pukul 09:00 WIB (Bukan hari kerja khusus)
        Carbon::setTestNow(Carbon::parse('2026-11-08 09:00:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawan)->post('/laporan/hadir', [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-08',
            'jam_masuk' => '08:00',
            'jam_keluar' => '15:00',
            'uraian' => 'Piket penerimaan logistik bilik suara dari penyedia',
            'output' => 'Dokumen Tanda Terima Logistik',
            'nomor_spt' => '088/ST/KPU-BKT/XI/2026',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/laporan/riwayat');

        $this->assertDatabaseHas('laporans', [
            'nip' => $this->karyawan->nip,
            'tanggal' => '2026-11-08',
            'tipe' => 'hadir',
            'nomor_spt' => '088/ST/KPU-BKT/XI/2026',
            'status_approval' => 'pending',
        ]);

        // Karena pending approval, sinkronisasi Google Sheets belum di-dispatch
        Queue::assertNotPushed(SyncLaporanToGoogleSheetJob::class);
    }

    public function test_supervisor_can_approve_weekend_hadir_with_spt_and_dispatches_sync(): void
    {
        Queue::fake();

        $laporan = Laporan::create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-08',
            'jam_masuk' => '08:00',
            'jam_keluar' => '15:00',
            'uraian' => 'Piket logistik akhir pekan',
            'output' => 'BAP Serah Terima',
            'nomor_spt' => '088/ST/KPU-BKT/XI/2026',
            'status_approval' => 'pending',
            'status_sinkron_google' => false,
        ]);

        // Supervisor melihat di daftar approval
        $responseIndex = $this->actingAs($this->supervisor)->get('/approval');
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Penugasan Hadir (SPT)');
        $responseIndex->assertSee('088/ST/KPU-BKT/XI/2026');

        // Supervisor menyetujui
        $responseApprove = $this->actingAs($this->supervisor)->post("/approval/{$laporan->id}/approve", [
            'catatan_approval' => 'Disetujui sesuai Surat Perintah Tugas.',
        ]);

        $responseApprove->assertRedirect();

        $laporan->refresh();
        $this->assertEquals('approved', $laporan->status_approval);
        $this->assertEquals($this->supervisor->id, $laporan->approved_by);
        $this->assertEquals('Disetujui sesuai Surat Perintah Tugas.', $laporan->catatan_approval);

        // Setelah disetujui, background sync job ter-dispatch
        Queue::assertPushed(SyncLaporanToGoogleSheetJob::class);
    }
}
