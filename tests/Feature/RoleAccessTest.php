<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_can_access_mahasiswa_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'mahasiswa',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/mahasiswa/dashboard')
            ->assertOk();
    }

    public function test_mahasiswa_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_dosen_cannot_access_superadmin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'dosen',
        ]);

        $this->actingAs($user)
            ->get('/superadmin/dashboard')
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_admin_cannot_access_superadmin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($user)
            ->get('/superadmin/dashboard')
            ->assertForbidden();
    }

    public function test_superadmin_can_access_admin_and_superadmin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
        ]);

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertOk();

        $this->actingAs($user)
            ->get('/superadmin/dashboard')
            ->assertOk();
    }

    public function test_inactive_user_cannot_access_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'mahasiswa',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get('/mahasiswa/dashboard')
            ->assertForbidden();
    }
}
