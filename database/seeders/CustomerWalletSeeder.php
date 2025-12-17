<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class CustomerWalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = User::where('role', Role::CUSTOMER)->get();

        foreach ($customers as $customer) {
            // Check if wallet already exists
            if (!$customer->wallets->isEmpty()) {
                continue;
            }

            Wallet::create([
                'user_id' => $customer->id,
                'balance' => 1000.00, 
            ]);
        }
    }
}

