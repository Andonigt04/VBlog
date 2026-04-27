<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::factory()->count(50)->create();
        \App\Models\User::create([
            'name' => 'adm01',
            'email' => 'adm01@vblog.local',
            'password' => \Illuminate\Support\Facades\Hash::make('adm01local'),
            'remember_token' => \Illuminate\Support\Str::random(10),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);
    }
}
