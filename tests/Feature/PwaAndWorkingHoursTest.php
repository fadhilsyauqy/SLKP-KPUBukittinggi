<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PwaAndWorkingHoursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Http::fake([
            'script.google.com/*' => Http::response(['status' => 'success'], 200),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    public function test_manifest_json_file_exists_and_has_valid_attributes(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertTrue(File::exists($manifestPath), 'File manifest.json tidak ditemukan di folder public.');

        $content = File::get($manifestPath);
        $json = json_decode($content, true);

        $this->assertIsArray($json, 'File manifest.json tidak valid sebagai JSON.');
        $this->assertEquals('Sistem Laporan Kinerja Pegawai KPU Kota Bukittinggi', $json['name']);
        $this->assertEquals('SLKP KPU', $json['short_name']);
        $this->assertEquals('standalone', $json['display']);
        $this->assertEquals('#7a1c1c', $json['theme_color']);
        $this->assertNotEmpty($json['icons'], 'Manifest harus memiliki konfigurasi icons.');
    }

    public function test_service_worker_file_exists_and_contains_cache_logic(): void
    {
        $swPath = public_path('sw.js');
        $this->assertTrue(File::exists($swPath), 'File sw.js tidak ditemukan di folder public.');

        $content = File::get($swPath);
        $this->assertStringContainsString('CACHE_NAME', $content);
        $this->assertStringContainsString("addEventListener('install'", $content);
        $this->assertStringContainsString("addEventListener('activate'", $content);
        $this->assertStringContainsString("addEventListener('fetch'", $content);
    }

    public function test_application_layouts_render_pwa_meta_tags_and_sw_registration(): void
    {
        // 1. Periksa Layout Guest (Login)
        $responseLogin = $this->get('/login');
        $responseLogin->assertStatus(200);
        $responseLogin->assertSee('rel="manifest"', false);
        $responseLogin->assertSee('content="#7a1c1c"', false);
        $responseLogin->assertSee('apple-mobile-web-app-capable', false);
        $responseLogin->assertSee('serviceWorker.register', false);

        // 2. Periksa Layout App Terotentikasi
        $karyawan = User::where('username', 'karyawan')->first();
        $responseApp = $this->actingAs($karyawan)->get('/laporan/hadir');
        $responseApp->assertStatus(200);
        $responseApp->assertSee('rel="manifest"', false);
        $responseApp->assertSee('serviceWorker.register', false);
    }

    public function test_working_hours_validation_allows_submission_during_active_work_hours(): void
    {
        config(['kpu.jam_dinas.enforce' => true]);
        config(['kpu.jam_dinas.agenda_khusus' => false]);

        // Atur waktu pengujian: Senin pukul 08:30 WIB (dalam jam kerja 06:30 - 19:00)
        Carbon::setTestNow(Carbon::parse('2026-11-02 08:30:00', 'Asia/Jakarta'));

        $karyawan = User::where('username', 'karyawan')->first();

        $response = $this->actingAs($karyawan)->post('/laporan/hadir', [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '08:00',
            'jam_keluar' => '16:00',
            'uraian' => 'Menyusun naskah dinas rekapitulasi',
            'output' => '1 Berkas dokumen',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/laporan/riwayat');

        $this->assertDatabaseHas('laporans', [
            'nip' => $karyawan->nip,
            'tipe' => 'hadir',
        ]);
    }

    public function test_working_hours_validation_blocks_submission_outside_hours(): void
    {
        config(['kpu.jam_dinas.enforce' => true]);
        config(['kpu.jam_dinas.agenda_khusus' => false]);

        $karyawan = User::where('username', 'karyawan')->first();

        // 1. Pengujian sebelum jam dinas: Senin pukul 05:00 WIB
        Carbon::setTestNow(Carbon::parse('2026-11-02 05:00:00', 'Asia/Jakarta'));

        $responseEarly = $this->actingAs($karyawan)->post('/laporan/hadir', [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '05:00',
            'jam_keluar' => '16:00',
            'uraian' => 'Uraian pekerjaan',
            'output' => 'Output pekerjaan',
        ]);

        $responseEarly->assertSessionHasErrors('jam_masuk');

        // 2. Pengujian sesudah jam dinas: Senin pukul 21:00 WIB
        Carbon::setTestNow(Carbon::parse('2026-11-02 21:00:00', 'Asia/Jakarta'));

        $responseLate = $this->actingAs($karyawan)->post('/laporan/hadir', [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '21:00',
            'jam_keluar' => '22:00',
            'uraian' => 'Uraian pekerjaan malam',
            'output' => 'Output pekerjaan malam',
        ]);

        $responseLate->assertSessionHasErrors('jam_masuk');
    }

    public function test_working_hours_validation_blocks_submission_on_weekends(): void
    {
        config(['kpu.jam_dinas.enforce' => true]);
        config(['kpu.jam_dinas.agenda_khusus' => false]);

        // Atur waktu pengujian: Hari Minggu pukul 10:00 WIB
        Carbon::setTestNow(Carbon::parse('2026-11-01 10:00:00', 'Asia/Jakarta'));

        $karyawan = User::where('username', 'karyawan')->first();

        $responseWeekend = $this->actingAs($karyawan)->post('/laporan/hadir', [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-01',
            'jam_masuk' => '09:00',
            'jam_keluar' => '16:00',
            'uraian' => 'Uraian pekerjaan di hari libur',
            'output' => 'Output berkas',
        ]);

        $responseWeekend->assertSessionHasErrors('jam_masuk');
    }

    public function test_working_hours_validation_allows_exception_during_special_election_agenda(): void
    {
        config(['kpu.jam_dinas.enforce' => true]);
        // Aktifkan agenda khusus tahapan Pemilu / Pilkada
        config(['kpu.jam_dinas.agenda_khusus' => true]);

        // Hari Minggu pukul 22:00 WIB (biasanya dilarang, namun agenda khusus mengizinkan)
        Carbon::setTestNow(Carbon::parse('2026-11-01 22:00:00', 'Asia/Jakarta'));

        $karyawan = User::where('username', 'karyawan')->first();

        $responseSpecial = $this->actingAs($karyawan)->post('/laporan/hadir', [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tanggal' => '2026-11-01',
            'jam_masuk' => '08:00',
            'jam_keluar' => '22:00',
            'uraian' => 'Rapat pleno rekapitulasi perhitungan suara pilkada hingga larut malam',
            'output' => '1 Berkas Berita Acara Pleno',
        ]);

        $responseSpecial->assertSessionHasNoErrors();
        $responseSpecial->assertRedirect('/laporan/riwayat');

        $this->assertDatabaseHas('laporans', [
            'nip' => $karyawan->nip,
            'tipe' => 'hadir',
            'uraian' => 'Rapat pleno rekapitulasi perhitungan suara pilkada hingga larut malam',
        ]);
    }
}
