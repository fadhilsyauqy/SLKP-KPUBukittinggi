<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('SLKP KPU BUKITTINGGI');
    }

    public function test_admin_can_login_and_is_redirected_to_monitoring(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/monitoring');
    }

    public function test_supervisor_can_login_and_is_redirected_to_monitoring(): void
    {
        $response = $this->post('/login', [
            'username' => 'supervisor',
            'password' => 'supervisor123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/monitoring');
    }

    public function test_karyawan_can_login_and_is_redirected_to_riwayat(): void
    {
        $response = $this->post('/login', [
            'username' => 'karyawan',
            'password' => 'karyawan123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/laporan/riwayat');
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $response = $this->from('/login')->post('/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_login_rate_limiting_triggers_after_multiple_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'username' => 'admin',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertStringContainsString('Terlalu banyak upaya login', session('errors')->first('username'));
    }

    public function test_user_can_logout_and_session_is_invalidated(): void
    {
        $user = User::where('username', 'admin')->first();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
