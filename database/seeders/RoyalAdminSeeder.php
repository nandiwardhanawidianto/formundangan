<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class RoyalAdminSeeder extends Seeder
{
    public function run(): void
    {
        $username = env('ROYAL_ADMIN_USERNAME');
        $email = env('ROYAL_ADMIN_EMAIL');
        $password = env('ROYAL_ADMIN_PASSWORD');
        if (!$username || !$email || !$password || strlen($password) < 12) {
            throw new RuntimeException('Atur ROYAL_ADMIN_USERNAME, ROYAL_ADMIN_EMAIL, dan ROYAL_ADMIN_PASSWORD (minimal 12 karakter) di .env sebelum membuat akun admin.');
        }
        User::updateOrCreate(['email' => $email], [
            'name' => 'Admin Royal Wedding',
            'username' => $username,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);
    }
}
