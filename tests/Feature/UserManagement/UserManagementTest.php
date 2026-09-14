<?php

namespace Tests\Feature\UserManagement;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_create_a_user_with_multiple_roles(): void
    {
        $admin = $this->karyawanUser('admin');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Dedi Serba Bisa',
            'email' => 'dedi@pabrik.test',
            'password' => 'password123',
            'roles' => ['hrd', 'manager'],
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $user = User::query()->where('email', 'dedi@pabrik.test')->firstOrFail();
        $this->assertTrue($user->hasRole('hrd'));
        $this->assertTrue($user->hasRole('manager'));
        $this->assertCount(2, $user->roles);
    }

    public function test_admin_can_update_a_user_to_have_multiple_roles(): void
    {
        $admin = $this->karyawanUser('admin');
        $user = $this->karyawanUser('karyawan');

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'roles' => ['kepala_bagian', 'koordinator_shift'],
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $user->refresh();
        $this->assertTrue($user->hasRole('kepala_bagian'));
        $this->assertTrue($user->hasRole('koordinator_shift'));
        $this->assertFalse($user->hasRole('karyawan'));
    }

    public function test_roles_is_required_and_must_be_a_known_role(): void
    {
        $admin = $this->karyawanUser('admin');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Tanpa Role',
            'email' => 'tanpa.role@pabrik.test',
            'password' => 'password123',
            'roles' => [],
        ]);
        $response->assertSessionHasErrors('roles');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Role Ngaco',
            'email' => 'role.ngaco@pabrik.test',
            'password' => 'password123',
            'roles' => ['role_yang_tidak_ada'],
        ]);
        $response->assertSessionHasErrors('roles.0');
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->post(route('users.store'), [
            'name' => 'Dedi',
            'email' => 'dedi2@pabrik.test',
            'password' => 'password123',
            'roles' => ['hrd'],
        ]);

        $response->assertForbidden();
    }
}
