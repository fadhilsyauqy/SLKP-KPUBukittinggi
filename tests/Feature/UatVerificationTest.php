<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UatVerificationTest extends TestCase
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

    /**
     * 7.1.A Verifikasi Hak Akses Role Karyawan:
     * - Bisa akses formulir Hadir, Tidak Hadir, dan Riwayat.
     * - Di halaman riwayat, hanya melihat laporannya sendiri (bukan milik orang lain).
     * - Dilarang akses Monitoring (403).
     * - Dilarang akses Master Data Admin (403).
     */
    public function test_uat_rbac_karyawan_permissions(): void
    {
        // 1. Karyawan dapat mengakses formulir laporan dan riwayat
        $this->actingAs($this->karyawan)->get('/laporan/hadir')->assertStatus(200);
        $this->actingAs($this->karyawan)->get('/laporan/tidak-hadir')->assertStatus(200);
        $this->actingAs($this->karyawan)->get('/laporan/riwayat')->assertStatus(200);

        // 2. Karyawan hanya melihat laporannya sendiri di riwayat
        Laporan::create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Uraian Khusus Karyawan Sendiri',
            'output' => 'Output Karyawan Sendiri',
        ]);

        Laporan::create([
            'user_id' => $this->supervisor->id,
            'nip' => $this->supervisor->nip,
            'nama' => $this->supervisor->name,
            'jabatan' => 'Kasubag',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Uraian Rahasia Milik Supervisor',
            'output' => 'Output Supervisor',
        ]);

        $riwayatResponse = $this->actingAs($this->karyawan)->get('/laporan/riwayat');
        $riwayatResponse->assertStatus(200);
        $riwayatResponse->assertSee('Uraian Khusus Karyawan Sendiri');
        $riwayatResponse->assertDontSee('Uraian Rahasia Milik Supervisor');

        // 3. Karyawan dilarang mengakses monitoring
        $this->actingAs($this->karyawan)->get('/monitoring')->assertStatus(403);
        $this->actingAs($this->karyawan)->get('/monitoring/cetak')->assertStatus(403);

        // 4. Karyawan dilarang mengakses CRUD master data admin
        $this->actingAs($this->karyawan)->get('/admin/karyawan')->assertStatus(403);
        $this->actingAs($this->karyawan)->get('/admin/jabatan')->assertStatus(403);
        $this->actingAs($this->karyawan)->get('/admin/user')->assertStatus(403);
    }

    /**
     * 7.1.B Verifikasi Hak Akses Role Supervisor:
     * - Bisa akses formulir Hadir, Tidak Hadir, dan Riwayat.
     * - Bisa melihat rekap seluruh pegawai di Monitoring dan Cetak Rekap.
     * - Dilarang akses CRUD Master Data Admin (403).
     */
    public function test_uat_rbac_supervisor_permissions(): void
    {
        // 1. Supervisor dapat mengakses formulir laporan dan riwayat
        $this->actingAs($this->supervisor)->get('/laporan/hadir')->assertStatus(200);
        $this->actingAs($this->supervisor)->get('/laporan/tidak-hadir')->assertStatus(200);
        $this->actingAs($this->supervisor)->get('/laporan/riwayat')->assertStatus(200);

        // 2. Supervisor dapat mengakses monitoring seluruh pegawai
        $monitoringResponse = $this->actingAs($this->supervisor)->get('/monitoring');
        $monitoringResponse->assertStatus(200);
        $monitoringResponse->assertSee('Monitoring Kehadiran &amp; Kinerja Pegawai', false);

        $cetakResponse = $this->actingAs($this->supervisor)->get('/monitoring/cetak');
        $cetakResponse->assertStatus(200);
        $cetakResponse->assertSee('REKAPITULASI LAPORAN KINERJA &amp; PRESENSI HARIAN PEGAWAI', false);

        // 3. Supervisor dilarang mengakses CRUD master data admin
        $this->actingAs($this->supervisor)->get('/admin/karyawan')->assertStatus(403);
        $this->actingAs($this->supervisor)->post('/admin/karyawan', [])->assertStatus(403);
        $this->actingAs($this->supervisor)->get('/admin/jabatan')->assertStatus(403);
        $this->actingAs($this->supervisor)->post('/admin/jabatan', [])->assertStatus(403);
        $this->actingAs($this->supervisor)->get('/admin/user')->assertStatus(403);
        $this->actingAs($this->supervisor)->post('/admin/user', [])->assertStatus(403);
    }

    /**
     * 7.1.C Verifikasi Hak Akses Role Admin:
     * - Akses penuh ke seluruh menu (Laporan Hadir, Tidak Hadir, Riwayat).
     * - Akses penuh ke Monitoring dan Cetak Rekap.
     * - Akses penuh ke seluruh CRUD Master Data (Karyawan, Jabatan, User).
     * - Proteksi keamanan dilarang menghapus akun sendiri.
     */
    public function test_uat_rbac_admin_permissions(): void
    {
        // 1. Admin dapat mengakses menu laporan
        $this->actingAs($this->admin)->get('/laporan/hadir')->assertStatus(200);
        $this->actingAs($this->admin)->get('/laporan/tidak-hadir')->assertStatus(200);
        $this->actingAs($this->admin)->get('/laporan/riwayat')->assertStatus(200);

        // 2. Admin dapat mengakses monitoring
        $this->actingAs($this->admin)->get('/monitoring')->assertStatus(200);
        $this->actingAs($this->admin)->get('/monitoring/cetak')->assertStatus(200);

        // 3. Admin dapat mengakses seluruh master data
        $this->actingAs($this->admin)->get('/admin/karyawan')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/karyawan/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/jabatan')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/user')->assertStatus(200);

        // 4. Admin dilarang menghapus akunnya sendiri
        $this->actingAs($this->admin)->delete('/admin/user/' . $this->admin->id)->assertStatus(403);
    }

    /**
     * 7.2.A Verifikasi Pengiriman Form Presensi Hadir (Sukses Google Sync):
     * - Menyimpan ke database PostgreSQL.
     * - Sinkronisasi ke Google Sheets berhasil (status_sinkron_google = true).
     */
    public function test_uat_form_hadir_submission_and_google_sync(): void
    {
        Http::fake([
            'script.google.com/*' => Http::response(['status' => 'success', 'message' => 'Tersimpan'], 200),
        ]);

        $payloadHadir = [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Mengikuti apel pagi dan verifikasi berkas pemilu',
            'output' => '1 Dokumen laporan apel dan 1 berkas verifikasi',
        ];

        $responseSuccess = $this->actingAs($this->karyawan)->post('/laporan/hadir', $payloadHadir);
        $responseSuccess->assertRedirect('/laporan/riwayat');

        $this->assertDatabaseHas('laporans', [
            'nip' => $this->karyawan->nip,
            'tipe' => 'hadir',
            'uraian' => 'Mengikuti apel pagi dan verifikasi berkas pemilu',
            'status_sinkron_google' => true,
        ]);
    }

    /**
     * 7.2.B Verifikasi Fallback Hadir saat Google Sheets Offline / Error:
     * - Data tetap aman tersimpan di PostgreSQL.
     * - status_sinkron_google = false, catatan_sinkron mencatat error.
     */
    public function test_uat_form_hadir_fallback_when_google_offline(): void
    {
        Http::fake([
            'script.google.com/*' => Http::response('Server Error', 500),
        ]);

        $payloadHadirOffline = [
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-03',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Tugas luar kantor monitoring kotak suara',
            'output' => '1 Berita Acara',
        ];

        $responseOffline = $this->actingAs($this->karyawan)->post('/laporan/hadir', $payloadHadirOffline);
        $responseOffline->assertRedirect('/laporan/riwayat');

        // Data tetap ada di database lokal meskipun Google Sheets offline
        $this->assertDatabaseHas('laporans', [
            'nip' => $this->karyawan->nip,
            'tipe' => 'hadir',
            'uraian' => 'Tugas luar kantor monitoring kotak suara',
            'status_sinkron_google' => false,
        ]);
    }

    /**
     * 7.2.B Verifikasi Pengiriman Form Presensi Tidak Hadir:
     * - Menyimpan laporan izin/cuti/sakit ke database PostgreSQL.
     * - Kalkulasi otomatis lama hari.
     * - Upload bukti lampiran ke disk storage.
     * - Sinkronisasi payload Google Sheets.
     */
    public function test_uat_form_tidak_hadir_submission_with_attachment(): void
    {
        Storage::fake('public');
        Http::fake([
            'script.google.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $fileBukti = UploadedFile::fake()->create('surat_tugas.pdf', 150, 'application/pdf');

        $payloadTidakHadir = [
            'nip' => $this->supervisor->nip,
            'nama' => $this->supervisor->name,
            'jabatan' => 'Kasubag Keuangan, Umum dan Logistik',
            'keterangan' => 'Perjadin',
            'tanggal_in' => '2026-11-05',
            'tanggal_out' => '2026-11-07',
            'alasan' => 'Dinas luar ke KPU Provinsi Sumatera Barat di Padang',
            'attach' => $fileBukti,
        ];

        $response = $this->actingAs($this->supervisor)->post('/laporan/tidak-hadir', $payloadTidakHadir);
        $response->assertRedirect('/laporan/riwayat');

        $laporan = Laporan::where('alasan', 'Dinas luar ke KPU Provinsi Sumatera Barat di Padang')->first();
        $this->assertNotNull($laporan);
        $this->assertEquals('Perjadin', $laporan->keterangan);
        $this->assertEquals('3 Hari', $laporan->lama_hari);
        $this->assertTrue($laporan->status_sinkron_google);
        $this->assertNotNull($laporan->bukti);

        Storage::disk('public')->assertExists($laporan->bukti);
    }

    /**
     * 7.3 Verifikasi Hierarki Jabatan (Sort Order):
     * - Sekretaris (Urutan 1)
     * - Kasubag (Urutan 2)
     * - Penelaah (Urutan 3)
     * - Penyusun (Urutan 4)
     * - Pengolah (Urutan 5)
     * - Default Pegawai / Staf lainnya (Urutan 10)
     * - Karyawan::hierarchy() scope mengurutkan pegawai sesuai prioritas ini.
     */
    public function test_uat_hierarki_jabatan_and_sorting_consistency(): void
    {
        // 1. Uji method helper penentu sort_order sesuai logika legacy KPU Bukittinggi
        $this->assertEquals(1, Karyawan::determineSortOrder('Sekretaris KPU'));
        $this->assertEquals(2, Karyawan::determineSortOrder('Kasubag Keuangan, Umum dan Logistik'));
        $this->assertEquals(2, Karyawan::determineSortOrder('Kasubag Hukum dan SDM'));
        $this->assertEquals(3, Karyawan::determineSortOrder('Penelaah Teknis Kebijakan'));
        $this->assertEquals(4, Karyawan::determineSortOrder('Penyusun Bahan Logistik'));
        $this->assertEquals(5, Karyawan::determineSortOrder('Pengolah Data'));
        $this->assertEquals(6, Karyawan::determineSortOrder('Ahli Pertama'));
        $this->assertEquals(7, Karyawan::determineSortOrder('Operator Komputer'));
        $this->assertEquals(8, Karyawan::determineSortOrder('Pengelola Barang'));
        $this->assertEquals(10, Karyawan::determineSortOrder('Staf Pelaksana'));
        $this->assertEquals(10, Karyawan::determineSortOrder(null));

        // 2. Buat pegawai dengan berbagai jabatan
        $jabatanSekretaris = Jabatan::firstOrCreate(['nama_jabatan' => 'Sekretaris KPU']);
        $jabatanKasubag = Jabatan::firstOrCreate(['nama_jabatan' => 'Kasubag Hukum dan SDM']);
        $jabatanPenelaah = Jabatan::firstOrCreate(['nama_jabatan' => 'Penelaah Teknis Kebijakan']);
        $jabatanStaf = Jabatan::firstOrCreate(['nama_jabatan' => 'Tenaga Teknis Lapangan']);

        // Hapus data lama agar pengujian hierarki bersih
        Karyawan::query()->delete();

        $karyawanStaf = Karyawan::create([
            'nip' => '199501012022011004',
            'nama' => 'Rahmat Staf',
            'jabatan_id' => $jabatanStaf->id,
            'sort_order' => Karyawan::determineSortOrder($jabatanStaf->nama_jabatan),
        ]);

        $karyawanKasubag = Karyawan::create([
            'nip' => '198501012010011002',
            'nama' => 'Siti Kasubag',
            'jabatan_id' => $jabatanKasubag->id,
            'sort_order' => Karyawan::determineSortOrder($jabatanKasubag->nama_jabatan),
        ]);

        $karyawanSekretaris = Karyawan::create([
            'nip' => '197001012000011001',
            'nama' => 'Bambang Sekretaris',
            'jabatan_id' => $jabatanSekretaris->id,
            'sort_order' => Karyawan::determineSortOrder($jabatanSekretaris->nama_jabatan),
        ]);

        $karyawanPenelaah = Karyawan::create([
            'nip' => '199001012015011003',
            'nama' => 'Ahmad Penelaah',
            'jabatan_id' => $jabatanPenelaah->id,
            'sort_order' => Karyawan::determineSortOrder($jabatanPenelaah->nama_jabatan),
        ]);

        // Ambil daftar karyawan menggunakan scope hierarchy
        $ordered = Karyawan::hierarchy()->get();

        $this->assertEquals('Bambang Sekretaris', $ordered[0]->nama);
        $this->assertEquals(1, $ordered[0]->sort_order);

        $this->assertEquals('Siti Kasubag', $ordered[1]->nama);
        $this->assertEquals(2, $ordered[1]->sort_order);

        $this->assertEquals('Ahmad Penelaah', $ordered[2]->nama);
        $this->assertEquals(3, $ordered[2]->sort_order);

        $this->assertEquals('Rahmat Staf', $ordered[3]->nama);
        $this->assertEquals(10, $ordered[3]->sort_order);
    }
}
