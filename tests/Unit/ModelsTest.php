<?php

namespace Tests\Unit;

use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_roles(): void
    {
        $admin = User::where('username', 'admin')->first();
        $supervisor = User::where('username', 'supervisor')->first();
        $karyawan = User::where('username', 'karyawan')->first();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isSupervisor());
        $this->assertFalse($admin->isKaryawan());

        $this->assertTrue($supervisor->isSupervisor());
        $this->assertFalse($supervisor->isAdmin());

        $this->assertTrue($karyawan->isKaryawan());
        $this->assertFalse($karyawan->isAdmin());
    }

    public function test_jabatan_karyawan_relationship(): void
    {
        $sekretaris = Jabatan::where('nama_jabatan', 'Sekretaris KPU')->first();
        $this->assertNotNull($sekretaris);
        $this->assertGreaterThan(0, $sekretaris->karyawans()->count());

        $karyawan = Karyawan::where('nip', '197001012000011001')->first();
        $this->assertNotNull($karyawan);
        $this->assertEquals('Sekretaris KPU', $karyawan->jabatan->nama_jabatan);
    }

    public function test_karyawan_hierarchy_scope_and_sort_order(): void
    {
        $this->assertEquals(1, Karyawan::determineSortOrder('Sekretaris KPU'));
        $this->assertEquals(2, Karyawan::determineSortOrder('Kasubag Keuangan'));
        $this->assertEquals(3, Karyawan::determineSortOrder('Penelaah Teknis'));
        $this->assertEquals(4, Karyawan::determineSortOrder('Penyusun Data'));
        $this->assertEquals(5, Karyawan::determineSortOrder('Pengolah Informasi'));
        $this->assertEquals(6, Karyawan::determineSortOrder('Ahli Komputer'));
        $this->assertEquals(7, Karyawan::determineSortOrder('Operator Grafis'));
        $this->assertEquals(8, Karyawan::determineSortOrder('Pengelola Pengadaan'));
        $this->assertEquals(10, Karyawan::determineSortOrder('Staff Umum'));

        $orderedList = Karyawan::hierarchy()->pluck('sort_order')->toArray();
        $sortedCopy = $orderedList;
        sort($sortedCopy);
        $this->assertEquals($sortedCopy, $orderedList);
    }

    public function test_laporan_casts_and_relationships(): void
    {
        $user = User::where('username', 'karyawan')->first();

        $laporan = Laporan::create([
            'user_id' => $user->id,
            'nip' => $user->nip,
            'nama' => $user->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '08:00',
            'jam_keluar' => '16:00',
            'uraian' => 'Menelaah draft pemilu',
            'output' => '1 Berkas resume',
            'status_sinkron_google' => true,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $laporan->tanggal);
        $this->assertTrue($laporan->status_sinkron_google);
        $this->assertEquals($user->id, $laporan->user->id);
        $this->assertEquals($user->nip, $laporan->karyawan->nip);
        $this->assertCount(1, $user->laporans);
    }
}
