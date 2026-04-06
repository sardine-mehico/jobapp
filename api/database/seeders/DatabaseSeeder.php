<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@officepc.online');
        $adminPassword = env('ADMIN_PASSWORD', 'changeme123');
        $adminName = env('ADMIN_NAME', 'Office PC Admin');

        // This app uses a single admin login defined by environment variables.
        User::query()
            ->where('email', '!=', $adminEmail)
            ->delete();

        User::query()->updateOrCreate([
            'email' => $adminEmail,
        ], [
            'name' => $adminName,
            'password' => Hash::make($adminPassword),
        ]);
    }
}
