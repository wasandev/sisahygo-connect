<?php

namespace Database\Seeders;

use Database\Seeders\Development\ClientAccountDemoSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if ($this->command?->getLaravel()->environment('local')) {
            $this->call(ClientAccountDemoSeeder::class);
        }
    }
}