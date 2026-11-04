<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
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

    public function test_supervisor_sees_pending_absence_count_in_notification_badge(): void
    {
        // Bersihkan laporan pending awal bila ada
        Laporan::tidakHadir()->pending()->delete();

        // Buat 3 permohonan izin berstatus pending
        Laporan::factory()->count(3)->tidakHadir()->pending()->create();

        $response = $this->actingAs($this->supervisor)->get('/monitoring');

        $response->assertOk();
        $response->assertViewHas('notificationCount', 3);
        $response->assertViewHas('notificationRoleType', 'supervisor');
        $response->assertSee('id="notificationNavbarBadge"', false);
        $response->assertSee('3', false);
        $response->assertSee('Permohonan Izin Baru');
    }

    public function test_approving_request_decreases_supervisor_notification_count(): void
    {
        Laporan::tidakHadir()->pending()->delete();

        $laporan1 = Laporan::factory()->tidakHadir()->pending()->create();
        $laporan2 = Laporan::factory()->tidakHadir()->pending()->create();

        // Cek awal 2 pending
        $resBefore = $this->actingAs($this->supervisor)->get('/approval');
        $resBefore->assertViewHas('notificationCount', 2);

        // Supervisor menyetujui 1 laporan
        $this->actingAs($this->supervisor)->post("/approval/{$laporan1->id}/approve", [
            'catatan_approval' => 'Disetujui.',
        ]);

        // Cek setelah approve berkurang menjadi 1
        $resAfter = $this->actingAs($this->supervisor)->get('/approval');
        $resAfter->assertViewHas('notificationCount', 1);
    }

    public function test_karyawan_sees_unread_badge_when_leave_request_is_decided(): void
    {
        // Buat permohonan milik karyawan yang masih pending (badge karyawan harus 0)
        $laporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'keterangan' => 'Cuti Tahunan',
        ]);

        $resPending = $this->actingAs($this->karyawan)->get('/laporan/riwayat');
        $resPending->assertOk();
        $resPending->assertViewHas('notificationCount', 0);
        $resPending->assertViewHas('notificationRoleType', 'karyawan');

        // Supervisor menyetujui permohonan
        $this->actingAs($this->supervisor)->post("/approval/{$laporan->id}/approve", [
            'catatan_approval' => 'Disetujui untuk cuti tahunan.',
        ]);

        // Karyawan sekarang memiliki 1 notifikasi belum dibaca
        $resApproved = $this->actingAs($this->karyawan)->get('/laporan/riwayat');
        $resApproved->assertOk();
        $resApproved->assertViewHas('notificationCount', 1);
        $resApproved->assertSee('Permohonan Disetujui');
        $resApproved->assertSee('Pengajuan Cuti Tahunan Anda telah disetujui atasan.');
    }

    public function test_karyawan_sees_rejection_notification_with_reason(): void
    {
        $laporan = Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->name,
            'keterangan' => 'Izin',
        ]);

        $this->actingAs($this->supervisor)->post("/approval/{$laporan->id}/reject", [
            'catatan_approval' => 'Lampiran surat dinas tidak lengkap.',
        ]);

        $response = $this->actingAs($this->karyawan)->get('/laporan/riwayat');
        $response->assertOk();
        $response->assertViewHas('notificationCount', 1);
        $response->assertSee('Permohonan Ditolak');
        $response->assertSee('Lampiran surat dinas tidak lengkap.');
    }

    public function test_karyawan_can_mark_all_notifications_as_read(): void
    {
        // Buat 2 laporan yang sudah disetujui tetapi belum dibaca
        Laporan::factory()->count(2)->tidakHadir()->approved()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'user_read_at' => null,
        ]);

        $resBefore = $this->actingAs($this->karyawan)->get('/laporan/riwayat');
        $resBefore->assertViewHas('notificationCount', 2);

        // Panggil endpoint mark-all-read
        $response = $this->actingAs($this->karyawan)->postJson('/notifications/mark-all-read');
        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'count' => 0,
        ]);

        // Verifikasi di database user_read_at sudah terisi
        $unreadCount = Laporan::where('user_id', $this->karyawan->id)->whereNull('user_read_at')->count();
        $this->assertEquals(0, $unreadCount);

        // Verifikasi view setelahnya memiliki count 0
        $resAfter = $this->actingAs($this->karyawan)->get('/laporan/riwayat');
        $resAfter->assertViewHas('notificationCount', 0);
    }

    public function test_karyawan_can_mark_single_notification_as_read(): void
    {
        $laporan1 = Laporan::factory()->tidakHadir()->approved()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'user_read_at' => null,
        ]);
        $laporan2 = Laporan::factory()->tidakHadir()->approved()->create([
            'user_id' => $this->karyawan->id,
            'nip' => $this->karyawan->nip,
            'user_read_at' => null,
        ]);

        $response = $this->actingAs($this->karyawan)->postJson("/notifications/{$laporan1->id}/mark-read");
        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'count' => 1,
        ]);

        $this->assertNotNull($laporan1->fresh()->user_read_at);
        $this->assertNull($laporan2->fresh()->user_read_at);
    }

    public function test_karyawan_cannot_mark_other_employee_notification(): void
    {
        $otherUser = User::factory()->create([
            'role' => 'karyawan',
            'username' => 'otherkaryawan',
            'nip' => '199901012022011009',
        ]);

        $otherLaporan = Laporan::factory()->tidakHadir()->approved()->create([
            'user_id' => $otherUser->id,
            'nip' => $otherUser->nip,
            'user_read_at' => null,
        ]);

        $response = $this->actingAs($this->karyawan)->postJson("/notifications/{$otherLaporan->id}/mark-read");
        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
        ]);

        $this->assertNull($otherLaporan->fresh()->user_read_at);
    }

    public function test_notifications_json_endpoint_returns_valid_structure(): void
    {
        Laporan::tidakHadir()->pending()->delete();
        Laporan::factory()->count(2)->tidakHadir()->pending()->create();

        $response = $this->actingAs($this->supervisor)->getJson('/notifications');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'count',
            'items' => [
                '*' => [
                    'id',
                    'laporan_id',
                    'title',
                    'message',
                    'keterangan',
                    'time',
                    'time_ago',
                    'icon',
                    'badge_class',
                    'badge_text',
                    'url',
                    'is_unread',
                    'status_approval',
                ],
            ],
            'role_type',
        ]);
        $response->assertJson([
            'status' => 'success',
            'count' => 2,
            'role_type' => 'supervisor',
        ]);
    }

    public function test_guest_cannot_access_notification_endpoints(): void
    {
        $this->getJson('/notifications')->assertUnauthorized();
        $this->postJson('/notifications/mark-all-read')->assertUnauthorized();
        $this->postJson('/notifications/1/mark-read')->assertUnauthorized();
    }
}
