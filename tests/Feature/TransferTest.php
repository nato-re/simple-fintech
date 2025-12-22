<?php

namespace Tests\Feature;

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
     * Mock HTTP responses for authorize and notify endpoints.
     */
    private function mockHttpResponses(int $authorizeStatus = 200, int $notifyStatus = 200): void
    {
        Http::fake([
            'util.devi.tools/api/v2/authorize*' => Http::response(['message' => $authorizeStatus === 200 ? 'Authorized' : 'Not Authorized'], $authorizeStatus),
            'util.devi.tools/api/v2/notify*' => Http::response(['message' => $notifyStatus === 200 ? 'Notification successful' : 'Notification failed'], $notifyStatus),
        ]);
    }

    /**
     * Make a transfer request.
     */
    private function makeTransferRequest(int $payerWalletId, int $payeeWalletId, float $value): \Illuminate\Testing\TestResponse
    {
        return $this->post('/api/transfer', [
            'payer' => $payerWalletId,
            'payee' => $payeeWalletId,
            'value' => $value,
        ]);
    }

    /**
     * Assert wallet balance.
     */
    private function assertWalletBalance(int $walletId, float $expectedBalance): void
    {
        $this->assertDatabaseHas('wallets', [
            'id' => $walletId,
            'balance' => $expectedBalance,
        ]);
    }

    /**
     * Assert that an authorize request was sent.
     */
    private function assertAuthorizeRequestSent(): void
    {
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'util.devi.tools/api/v2/authorize') &&
                   $request->method() == 'GET';
        });
    }

    /**
     * Assert that a notify request was sent.
     */
    private function assertNotifyRequestSent(): void
    {
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'util.devi.tools/api/v2/notify') &&
                   $request->method() == 'POST';
        });
    }

    /**
     * A basic test example.
     */
    public function test_successful_transfer_funds_between_users(): void
    {
        $this->mockHttpResponses();

        $customerWallet = User::factory()->customerWithWallet(1000.00)->create()->wallets()->first();
        $storeKeeperWallet = User::factory()->storeKeeperWithWallet(1000.00)->create()->wallets()->first();

        $response = $this->makeTransferRequest($customerWallet->id, $storeKeeperWallet->id, 100.00);

        $response->assertStatus(200);

        $this->assertWalletBalance($customerWallet->id, 900.00);
        $this->assertWalletBalance($storeKeeperWallet->id, 1100.00);
    }

    public function test_unsuccessful_transfer_funds_between_users_because_of_insufficient_balance(): void
    {
        $this->mockHttpResponses();

        $customerWallet = User::factory()->customerWithWallet(1000.00)->create()->wallets()->first();
        $storeKeeperWallet = User::factory()->storeKeeperWithWallet(1000.00)->create()->wallets()->first();

        $response = $this->makeTransferRequest($customerWallet->id, $storeKeeperWallet->id, 2000.00);

        $response->assertStatus(400);

        $this->assertWalletBalance($customerWallet->id, 1000.00);
        $this->assertWalletBalance($storeKeeperWallet->id, 1000.00);
    }

    public function test_unsuccessful_transfer_funds_between_users_because_of_invalid_payer(): void
    {
        $customerWallet = User::factory()->customerWithWallet(1000.00)->create()->wallets()->first();

        $response = $this->post('/api/transfer', [
            'payer' => 'invalid-payer',
            'payee' => $customerWallet->id,
            'value' => 100.00,
        ]);

        $response->assertStatus(422);

        $this->assertWalletBalance($customerWallet->id, 1000.00);
    }

    public function test_unsuccessful_transfer_funds_between_users_because_of_invalid_payee(): void
    {
        $customerWallet = User::factory()->customerWithWallet(1000.00)->create()->wallets()->first();

        $response = $this->post('/api/transfer', [
            'payer' => $customerWallet->id,
            'payee' => 'invalid-payee',
            'value' => 100.00,
        ]);

        $response->assertStatus(422);

        $this->assertWalletBalance($customerWallet->id, 1000.00);
    }

    public function test_unsuccessful_transfer_funds_between_users_because_of_invalid_value(): void
    {
        $customerWallet = User::factory()->customerWithWallet(1000.00)->create()->wallets()->first();
        $storeKeeperWallet = User::factory()->storeKeeperWithWallet(1000.00)->create()->wallets()->first();

        $response = $this->post('/api/transfer', [
            'payer' => $customerWallet->id,
            'payee' => $customerWallet->id,
            'value' => 'invalid-value',
        ]);

        $response->assertStatus(422);

        $this->assertWalletBalance($customerWallet->id, 1000.00);
        $this->assertWalletBalance($storeKeeperWallet->id, 1000.00);
    }

    public function test_unsuccessful_transfer_funds_store_keeper_should_not_be_able_to_transfer_funds(): void
    {
        $this->mockHttpResponses();

        $storeKeeperWallet = User::factory()->storeKeeperWithWallet(1000.00)->create()->wallets()->first();
        $customerWallet = User::factory()->customerWithWallet(1000.00)->create()->wallets()->first();

        $response = $this->makeTransferRequest($storeKeeperWallet->id, $customerWallet->id, 100.00);

        $response->assertStatus(400);

        $this->assertWalletBalance($storeKeeperWallet->id, 1000.00);
    }

    public function test_verify_unsuccessful_authorization_of_third_party_transfer(): void
    {
        Http::fake([
            'util.devi.tools/api/v2/authorize*' => Http::response(['message' => 'Not Authorized'], 403),
        ]);

        $customerWallet = User::factory()->customerWithWallet(1000.00)->create()->wallets()->first();
        $storeKeeperWallet = User::factory()->storeKeeperWithWallet(1000.00)->create()->wallets()->first();

        $response = $this->makeTransferRequest($customerWallet->id, $storeKeeperWallet->id, 100.00);

        $this->assertAuthorizeRequestSent();

        $response->assertStatus(403);

        $this->assertWalletBalance($customerWallet->id, 1000.00);
        $this->assertWalletBalance($storeKeeperWallet->id, 1000.00);
    }

    public function test_verify_successful_authorization_of_third_party_transfer_and_notification(): void
    {
        $this->mockHttpResponses();

        $customerWallet = User::factory()->customerWithWallet(1000.00)->create()->wallets()->first();
        $storeKeeperWallet = User::factory()->storeKeeperWithWallet(1000.00)->create()->wallets()->first();

        $response = $this->makeTransferRequest($customerWallet->id, $storeKeeperWallet->id, 100.00);

        $this->assertAuthorizeRequestSent();
        $this->assertNotifyRequestSent();

        $response->assertStatus(200);

        $this->assertWalletBalance($customerWallet->id, 900.00);
        $this->assertWalletBalance($storeKeeperWallet->id, 1100.00);
    }

    public function test_verify_unsuccessful_notification_of_third_party_transfer(): void
    {
        $this->mockHttpResponses(200, 400);

        $customerWallet = User::factory()->customerWithWallet(1000.00)->create()->wallets()->first();
        $storeKeeperWallet = User::factory()->storeKeeperWithWallet(1000.00)->create()->wallets()->first();

        $response = $this->makeTransferRequest($customerWallet->id, $storeKeeperWallet->id, 100.00);

        $this->assertAuthorizeRequestSent();
        $this->assertNotifyRequestSent();

        $response->assertStatus(400);

        $this->assertWalletBalance($customerWallet->id, 1000.00);
        $this->assertWalletBalance($storeKeeperWallet->id, 1000.00);
    }
}
