<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'admin',
            'role' => 'admin' ,
            'login' => 'admin',
            'password' => '1234567',
        ]);
    }
}
