<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fitur_registrasi_publik_dimatikan(): void
    {
        $this->assertFalse(Features::enabled(Features::registration()));
    }

    public function test_halaman_registrasi_tidak_tersedia(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_registrasi_publik_tidak_membuat_akun(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password-baru-aman',
            'password_confirmation' => 'password-baru-aman',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
