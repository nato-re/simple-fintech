<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class StoreKeeperWalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $storeKeepers = User::where('role', Role::STORE_KEEPER)->get();

        foreach ($storeKeepers as $storeKeeper) {
            // Check if wallet already exists
            if (!$storeKeeper->wallets->isEmpty()) {
                continue;
            }

            Wallet::create([
                'user_id' => $storeKeeper->id,
                'balance' => 0.00, // Store keepers start with zero balance
            ]);
        }
    }
}
