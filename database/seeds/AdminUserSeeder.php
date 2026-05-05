<?php

declare(strict_types=1);

namespace Database\Seeds;

use Admin\Auth\User;

class AdminUserSeeder
{
    public function run(): void
    {
        $exists = User::where('email', 'admin@admin.com')->first();
        if (!$exists) {
            $user = new User();
            $user->name = 'Admin';
            $user->email = 'admin@admin.com';
            $user->password = 'admin123';
            $user->role = 'admin';
            $user->save();
        }
    }
}
