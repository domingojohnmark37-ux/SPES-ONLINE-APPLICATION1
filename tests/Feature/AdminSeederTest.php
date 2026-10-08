<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seeder_updates_the_configured_admin_credentials(): void
    {
        $admin = User::factory()->create([
            'email' => 'lgulallo@gmail.com',
            'username' => 'pesoadmin',
            'role' => 'user',
            'password' => 'OldPassword123!',
        ]);
        config([
            'admin.email' => 'sheriffelsuarez@gmail.com',
            'admin.password' => 'TestAdminPassword123!',
        ]);

        $this->seed(AdminSeeder::class);

        $admin->refresh();
        $this->assertSame('sheriffelsuarez@gmail.com', $admin->email);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('TestAdminPassword123!', $admin->password));
    }

    public function test_admin_seeder_requires_a_password_before_creating_an_account(): void
    {
        config([
            'admin.email' => 'sheriffelsuarez@gmail.com',
            'admin.password' => null,
        ]);

        try {
            $this->seed(AdminSeeder::class);
            $this->fail('Expected the seeder to require ADMIN_PASSWORD.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Set ADMIN_PASSWORD before creating the admin account.', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeded_admin_can_sign_in_to_the_existing_admin_portal(): void
    {
        config([
            'admin.email' => 'sheriffelsuarez@gmail.com',
            'admin.password' => 'TestAdminPassword123!',
        ]);
        $this->seed(AdminSeeder::class);

        $this->post(route('login'), [
            'email' => 'sheriffelsuarez@gmail.com',
            'password' => 'TestAdminPassword123!',
        ])->assertRedirect(route('admin.dashboard', absolute: false));
    }
}
