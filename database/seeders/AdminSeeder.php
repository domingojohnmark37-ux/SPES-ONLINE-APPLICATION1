<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = config('admin.email');
        $adminPassword = config('admin.password');
        $admin = User::where('email', $adminEmail)->first()
            ?? User::where('username', 'pesoadmin')->first();

        User::where('username', 'pesoadmin')
            ->where('email', '!=', $adminEmail)
            ->when($admin, fn ($query) => $query->whereKeyNot($admin->id))
            ->update(['role' => 'user']);

        if ($admin) {
            $admin->email = $adminEmail;
            $admin->role = 'admin';
            $admin->username = $admin->username ?: 'pesoadmin';
            if (filled($adminPassword)) {
                $admin->password = Hash::make($adminPassword);
            }
            $admin->save();

            return;
        }

        if (! filled($adminPassword)) {
            throw new \RuntimeException('Set ADMIN_PASSWORD before creating the admin account.');
        }

        User::create([
            'name'     => 'PESO Admin',
            'username' => 'pesoadmin',
            'email'    => $adminEmail,
            'password' => Hash::make($adminPassword),
            'role'     => 'admin',
        ]);
    }
}
