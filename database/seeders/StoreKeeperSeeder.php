<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StoreKeeperSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Store Keeper',
            'email' => 'storekeeper@example.com',
            'password' => Hash::make('password'),
            'role' => Role::STORE_KEEPER,
        ]);

        User::factory()->count(2)->create([
            'role' => Role::STORE_KEEPER,
        ]);
    }
}

