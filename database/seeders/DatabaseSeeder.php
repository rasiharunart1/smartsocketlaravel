<?php

namespace Database\Seeders;

use App\Actions\CreateUserDevice;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@smartsocket.com'],
            [
                'name' => 'Admin Smart Socket',
                'password' => Hash::make('password'),
            ],
        );

        if (! $user->devices()->exists()) {
            app(CreateUserDevice::class)->handle($user);
        }
    }
}
