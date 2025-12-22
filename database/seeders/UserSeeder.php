<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->count(5)->customerWithWallet(1000.00)->create();
        User::factory()->count(2)->storeKeeperWithWallet(1000.00)->create();
    }
}
