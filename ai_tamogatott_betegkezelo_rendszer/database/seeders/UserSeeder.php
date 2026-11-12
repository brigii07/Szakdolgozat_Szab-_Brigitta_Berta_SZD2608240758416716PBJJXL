<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $doctor = User::firstOrCreate(
            ['email' => 'peterkovacs@korhaz.hu'],
            [
                'name' => 'Dr. Kovács Péter',
                'password_hash' => Hash::make('password'),
                'role' => 'doctor',
                'doctor_id' => null,
            ]
        );

        User::firstOrCreate(
            ['email' => 'annaszabo@korhaz.hu'],
            [
                'name' => 'Dr. Szabó Anna',
                'password_hash' => Hash::make('password'),
                'role' => 'doctor',
                'doctor_id' => null,
            ]
        );

        User::firstOrCreate(
            ['email' => 'bencetoth@korhaz.hu'],
            [
                'name' => 'Tóth Bence',
                'password_hash' => Hash::make('password'),
                'role' => 'assistant',
                'doctor_id' => $doctor->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'eszternagy@korhaz.hu'],
            [
                'name' => 'Nagy Eszter',
                'password_hash' => Hash::make('password'),
                'role' => 'assistant',
                'doctor_id' => $doctor->id,
            ]
        );
    }
}
