<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\User;
use App\Services\GoogleSheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleSheetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_send_hadir_success(): void
    {
        Http::fake([
            'script.google.com/*' => Http::response(['status' => 'success', 'message' => 'Row added'], 200),
        ]);

        $user = User::where('username', 'karyawan')->first();
        $laporan = Laporan::create([
            'user_id' => $user->id,
            'nip' => $user->nip,
            'nama' => $user->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Mengikuti rapat pleno',
            'output' => '1 Notulensi rapat',
        ]);

        $service = new GoogleSheetService;
        $result = $service->sendHadir($laporan);

        $this->assertTrue($result['success']);
        $this->assertEquals('Data berhasil disinkronkan ke Google Spreadsheet.', $result['message']);
    }

    public function test_send_hadir_handles_google_failure_gracefully(): void
    {
        Http::fake([
            'script.google.com/*' => Http::response('Internal Server Error', 500),
        ]);

        $user = User::where('username', 'karyawan')->first();
        $laporan = Laporan::create([
            'user_id' => $user->id,
            'nip' => $user->nip,
            'nama' => $user->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Mengikuti rapat pleno',
            'output' => '1 Notulensi rapat',
        ]);

        $service = new GoogleSheetService;
        $result = $service->sendHadir($laporan);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('500', $result['message']);
    }

    public function test_send_tidak_hadir_success_with_attachment(): void
    {
        Http::fake([
            'script.google.com/*' => Http::response(['status' => 'success', 'message' => 'Row and file saved'], 200),
        ]);

        $user = User::where('username', 'karyawan')->first();
        $laporan = Laporan::create([
            'user_id' => $user->id,
            'nip' => $user->nip,
            'nama' => $user->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'tidak_hadir',
            'tanggal' => '2026-11-02',
            'tanggal_selesai' => '2026-11-03',
            'lama_hari' => '2 Hari',
            'keterangan' => 'Sakit',
            'alasan' => 'Demam dan flu berat',
            'bukti' => 'bukti/surat_dokter.pdf',
        ]);

        $service = new GoogleSheetService;
        $result = $service->sendTidakHadir($laporan, base64_encode('fake-pdf-content'), 'surat_dokter.pdf');

        $this->assertTrue($result['success']);
    }
}
