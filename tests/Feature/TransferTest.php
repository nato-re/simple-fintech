<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

define('AUTHORIZE_URL', 'https://util.devi.tools/api/v2/authorize');
define('NOTIFY_URL', 'https://util.devi.tools/api/v2/notify');

class TransferTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     */
    public function test_successful_transfer_funds_between_users(): void
    {
        $customer = User::factory()->create([
            'role' => Role::CUSTOMER,
        ]);

        $storeKeeper = User::factory()->create([
            'role' => Role::STORE_KEEPER,
        ]);

        $storeKeeperWallet = $storeKeeper->wallets()->create([
            'balance' => 1000.00,
        ]);

        $customerWallet = $customer->wallets()->create([
            'balance' => 1000.00,
        ]);
        
        $response = $this->post('/transfer', [
            'payer' => $customerWallet->id,
            'payee' => $storeKeeperWallet->id,
            'value' => 100.00,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $customerWallet->id,
            'balance' => 900.00,
        ]);
        $this->assertDatabaseHas('wallets', [
            'user_id' => $storeKeeperWallet->id,
            'balance' => 1100.00,
        ]);
    }

    public function test_unsuccessful_transfer_funds_between_users_because_of_insufficient_balance(): void
    {
        $customer = User::factory()->create([
            'role' => Role::CUSTOMER,
        ]);

        $storeKeeper = User::factory()->create([
            'role' => Role::STORE_KEEPER,
        ]);

        $storeKeeperWallet = $storeKeeper->wallets()->create([
            'balance' => 1000.00,
        ]);

        $customerWallet = $customer->wallets()->create([
            'balance' => 1000.00,
        ]);

        $response = $this->post('/transfer', [
            'payer' => $customerWallet->id,
            'payee' => $storeKeeperWallet->id,
            'value' => 2000.00,
        ]);

        $response->assertStatus(400);

        $this->assertDatabaseHas('wallets', [
            'id' => $customerWallet->id,
            'balance' => 1000.00,
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $storeKeeperWallet->id,
            'balance' => 1000.00,
        ]);
    }

    public function test_unsuccessful_transfer_funds_between_users_because_of_invalid_payer(): void
    {
        $customer = User::factory()->create([
            'role' => Role::CUSTOMER,
        ]);

        $customerWallet = $customer->wallets()->create([
            'balance' => 1000.00,
        ]);

        $response = $this->post('/transfer', [
            'payer' => 'invalid-payer',
            'payee' => $customerWallet->id,
            'value' => 100.00,
        ]);

        $response->assertStatus(400);

        $this->assertDatabaseHas('wallets', [
            'id' => $customerWallet->id,
            'balance' => 1000.00,
        ]);
    }

    public function test_unsuccessful_transfer_funds_between_users_because_of_invalid_payee(): void
    {
        $customer = User::factory()->create([
            'role' => Role::CUSTOMER,
        ]);

        $customerWallet = $customer->wallets()->create([
            'balance' => 1000.00,
        ]);
    
        $response = $this->post('/transfer', [
            'payer' => $customerWallet->id,
            'payee' => 'invalid-payee',
            'value' => 100.00,
        ]);

        $response->assertStatus(400);

        $this->assertDatabaseHas('wallets', [
            'id' => $customerWallet->id,
            'balance' => 1000.00,
        ]);
    }

    public function test_unsuccessful_transfer_funds_between_users_because_of_invalid_value(): void
    {
        $customer = User::factory()->create([
            'role' => Role::CUSTOMER,
        ]);
        $storeKeeper = User::factory()->create([
            'role' => Role::STORE_KEEPER,
        ]);

        $storeKeeperWallet = $storeKeeper->wallets()->create([
            'balance' => 1000.00,
        ]);

        $customerWallet = $customer->wallets()->create([
            'balance' => 1000.00,
        ]);
    
        $response = $this->post('/transfer', [
            'payer' => $customerWallet->id,
            'payee' => $customerWallet->id,
            'value' => 'invalid-value',
        ]);

        $response->assertStatus(400);

        $this->assertDatabaseHas('wallets', [
            'id' => $customerWallet->id,
            'balance' => 1000.00,
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $storeKeeperWallet->id,
            'balance' => 1000.00,
        ]);
    }

    public function test_unsuccessful_transfer_funds_store_keeper_should_not_be_able_to_transfer_funds(): void
    {
        $storeKeeper = User::factory()->create([
            'role' => Role::STORE_KEEPER,
        ]);
        $customer = User::factory()->create([
            'role' => Role::CUSTOMER,
        ]);
        $storeKeeperWallet = $storeKeeper->wallets()->create([
            'balance' => 1000.00,
        ]);
  
        $customerWallet = $customer->wallets()->create([
            'balance' => 1000.00,
        ]);

        $response = $this->post('/transfer', [
            'payer' => $storeKeeperWallet->id,
            'payee' => $customerWallet->id,
            'value' => 100.00,
        ]);

        $response->assertStatus(400);

        $this->assertDatabaseHas('wallets', [
            'id' => $storeKeeperWallet->id,
            'balance' => 1000.00,
        ]);
    }

    public function test_verify_unsuccessful_authorization_of_third_party_transfer(): void {

        Http::fake([
            AUTHORIZE_URL => Http::response(['message' => 'Not Authorized'], 403),
        ]);
        $customer = User::factory()->create([
            'role' => Role::CUSTOMER,
        ]);
        $customerWallet = $customer->wallets()->create([
            'balance' => 1000.00,
        ]);
        $storeKeeper = User::factory()->create([
            'role' => Role::STORE_KEEPER,
        ]);
        $storeKeeperWallet = $storeKeeper->wallets()->create([
            'balance' => 1000.00,
        ]);

        $response = $this->post('/transfer', [
            'payer' => $customerWallet->id,
            'payee' => $storeKeeperWallet->id,
            'value' => 100.00,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == AUTHORIZE_URL &&
                   $request->method() == 'GET';
                   
        });

        $response->assertStatus(403);

        $this->assertDatabaseHas('wallets', [
            'id' => $customerWallet->id,
            'balance' => 1000.00,
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $storeKeeperWallet->id,
            'balance' => 1000.00,
        ]);
    }

    public function test_verify_successful_authorization_of_third_party_transfer_and_notification(): void {
        Http::fake([
            AUTHORIZE_URL => Http::response(['message' => 'Authorized'], 200),
            NOTIFY_URL => Http::response(['message' => 'Notification successful'], 200),
        ]);
        $customer = User::factory()->create([
            'role' => Role::CUSTOMER,
        ]);
        $customerWallet = $customer->wallets()->create([
            'balance' => 1000.00,
        ]);
        $storeKeeper = User::factory()->create([
            'role' => Role::STORE_KEEPER,
        ]);
        $storeKeeperWallet = $storeKeeper->wallets()->create([
            'balance' => 1000.00,
        ]);

        $response = $this->post('/transfer', [
            'payer' => $customerWallet->id,
            'payee' => $storeKeeperWallet->id,
            'value' => 100.00,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == AUTHORIZE_URL &&
                   $request->method() == 'GET';
        });
        Http::assertSent(function ($request) {
            return $request->url() == NOTIFY_URL &&
                   $request->method() == 'POST';
        });

        $response->assertStatus(200);

        $this->assertDatabaseHas('wallets', [
            'id' => $customerWallet->id,
            'balance' => 900.00,
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $storeKeeperWallet->id,
            'balance' => 1100.00,
        ]);
    }

    public function test_verify_unsuccessful_notification_of_third_party_transfer(): void {
        Http::fake([
            AUTHORIZE_URL => Http::response(['message' => 'Authorized'], 200),
            NOTIFY_URL => Http::response(['message' => 'Notification failed'], 400),
        ]);
        $customer = User::factory()->create([
            'role' => Role::CUSTOMER,
        ]);
        $customerWallet = $customer->wallets()->create([
            'balance' => 1000.00,
        ]);
        $storeKeeper = User::factory()->create([
            'role' => Role::STORE_KEEPER,
        ]);
        $storeKeeperWallet = $storeKeeper->wallets()->create([
            'balance' => 1000.00,
        ]);

        $response = $this->post('/transfer', [
            'payer' => $customerWallet->id,
            'payee' => $storeKeeperWallet->id,
            'value' => 100.00,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == AUTHORIZE_URL &&
                   $request->method() == 'GET';
        });
        Http::assertSent(function ($request) {
            return $request->url() == NOTIFY_URL &&
                   $request->method() == 'POST';
        });

        $response->assertStatus(200);

        $this->assertDatabaseHas('wallets', [
            'id' => $customerWallet->id,
            'balance' => 900.00,
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $storeKeeperWallet->id,
            'balance' => 1100.00,
        ]);
    }
}
