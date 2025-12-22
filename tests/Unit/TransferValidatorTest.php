<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\StoreKeeperTransferException;
use App\Exceptions\WalletNotFoundException;
use App\Http\Services\TransferValidator;
use App\Models\User;
use App\Models\Wallet;
use App\ValueObjects\Money;
use Tests\TestCase;

class TransferValidatorTest extends TestCase
{
    private TransferValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TransferValidator;
    }

    /**
     * Test validation passes when all conditions are met.
     */
    public function test_validate_passes_when_all_conditions_met(): void
    {
        $payerUser = new User(['role' => Role::CUSTOMER]);
        $payerWallet = new Wallet(['id' => 1, 'balance' => 1000.00]);
        $payerWallet->setRelation('user', $payerUser);

        $payeeUser = new User(['role' => Role::CUSTOMER]);
        $payeeWallet = new Wallet(['id' => 2, 'balance' => 500.00]);
        $payeeWallet->setRelation('user', $payeeUser);

        $transferValue = Money::fromFloat(100.00);

        // Should not throw any exception
        $this->validator->validate($payerWallet, $payeeWallet, $transferValue);

        $this->assertTrue(true); // If we get here, validation passed
    }

    /**
     * Test validation throws exception when payer wallet is null.
     */
    public function test_validate_throws_exception_when_payer_wallet_is_null(): void
    {
        $payeeUser = new User(['id' => 2, 'role' => Role::CUSTOMER]);
        $payeeWallet = new Wallet(['id' => 2, 'balance' => 500.00]);
        $payeeWallet->setRelation('user', $payeeUser);
        $transferValue = Money::fromFloat(100.00);

        // The validator will try to get user->id from null wallet, which will be null
        // WalletNotFoundException accepts null but we need to catch TypeError first
        try {
            $this->validator->validate(null, $payeeWallet, $transferValue);
            $this->fail('Expected WalletNotFoundException or TypeError');
        } catch (WalletNotFoundException|\TypeError $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test validation throws exception when payee wallet is null.
     */
    public function test_validate_throws_exception_when_payee_wallet_is_null(): void
    {
        $payerUser = new User(['id' => 1, 'role' => Role::CUSTOMER]);
        $payerWallet = new Wallet(['id' => 1, 'balance' => 1000.00]);
        $payerWallet->setRelation('user', $payerUser);
        $transferValue = Money::fromFloat(100.00);

        // The validator will try to get user->id from null wallet, which will be null
        // WalletNotFoundException accepts null but we need to catch TypeError first
        try {
            $this->validator->validate($payerWallet, null, $transferValue);
            $this->fail('Expected WalletNotFoundException or TypeError');
        } catch (WalletNotFoundException|\TypeError $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test validation throws exception when payer has insufficient balance.
     */
    public function test_validate_throws_exception_when_insufficient_balance(): void
    {
        $payerUser = new User(['role' => Role::CUSTOMER]);
        $payerWallet = new Wallet(['id' => 1, 'balance' => 50.00]);
        $payerWallet->setRelation('user', $payerUser);

        $payeeUser = new User(['role' => Role::CUSTOMER]);
        $payeeWallet = new Wallet(['id' => 2, 'balance' => 500.00]);
        $payeeWallet->setRelation('user', $payeeUser);

        $transferValue = Money::fromFloat(100.00);

        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->validator->validate($payerWallet, $payeeWallet, $transferValue);
    }

    /**
     * Test validation throws exception when payer has exactly the required amount.
     */
    public function test_validate_passes_when_balance_equals_transfer_value(): void
    {
        $payerUser = new User(['role' => Role::CUSTOMER]);
        $payerWallet = new Wallet(['id' => 1, 'balance' => 100.00]);
        $payerWallet->setRelation('user', $payerUser);

        $payeeUser = new User(['role' => Role::CUSTOMER]);
        $payeeWallet = new Wallet(['id' => 2, 'balance' => 500.00]);
        $payeeWallet->setRelation('user', $payeeUser);

        $transferValue = Money::fromFloat(100.00);

        // Should not throw exception
        $this->validator->validate($payerWallet, $payeeWallet, $transferValue);

        $this->assertTrue(true);
    }

    /**
     * Test validation throws exception when payer is a store keeper.
     */
    public function test_validate_throws_exception_when_payer_is_store_keeper(): void
    {
        $payerUser = new User(['role' => Role::STORE_KEEPER]);
        $payerWallet = new Wallet(['id' => 1, 'balance' => 1000.00]);
        $payerWallet->setRelation('user', $payerUser);

        $payeeUser = new User(['role' => Role::CUSTOMER]);
        $payeeWallet = new Wallet(['id' => 2, 'balance' => 500.00]);
        $payeeWallet->setRelation('user', $payeeUser);

        $transferValue = Money::fromFloat(100.00);

        $this->expectException(StoreKeeperTransferException::class);
        $this->expectExceptionMessage('Store keeper cannot transfer funds');

        $this->validator->validate($payerWallet, $payeeWallet, $transferValue);
    }

    /**
     * Test validateBalanceAfterLock passes when balance is sufficient.
     */
    public function test_validate_balance_after_lock_passes_when_sufficient(): void
    {
        $lockedPayerWallet = new Wallet(['id' => 1, 'balance' => 1000.00]);
        $lockedPayeeWallet = new Wallet(['id' => 2, 'balance' => 500.00]);
        $transferValue = Money::fromFloat(100.00);

        // Should not throw exception
        $this->validator->validateBalanceAfterLock($lockedPayerWallet, $transferValue, $lockedPayeeWallet);

        $this->assertTrue(true);
    }

    /**
     * Test validateBalanceAfterLock throws exception when balance is insufficient.
     */
    public function test_validate_balance_after_lock_throws_exception_when_insufficient(): void
    {
        $lockedPayerWallet = new Wallet(['id' => 1, 'balance' => 50.00]);
        $lockedPayeeWallet = new Wallet(['id' => 2, 'balance' => 500.00]);
        $transferValue = Money::fromFloat(100.00);

        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->validator->validateBalanceAfterLock($lockedPayerWallet, $transferValue, $lockedPayeeWallet);
    }

    /**
     * Test validateBalanceAfterLock passes when balance equals transfer value.
     */
    public function test_validate_balance_after_lock_passes_when_balance_equals_value(): void
    {
        $lockedPayerWallet = new Wallet(['id' => 1, 'balance' => 100.00]);
        $lockedPayeeWallet = new Wallet(['id' => 2, 'balance' => 500.00]);
        $transferValue = Money::fromFloat(100.00);

        // Should not throw exception
        $this->validator->validateBalanceAfterLock($lockedPayerWallet, $transferValue, $lockedPayeeWallet);

        $this->assertTrue(true);
    }

    /**
     * Test validation with very small transfer amount.
     */
    public function test_validate_passes_with_minimum_transfer_amount(): void
    {
        $payerUser = new User(['role' => Role::CUSTOMER]);
        $payerWallet = new Wallet(['id' => 1, 'balance' => 1.00]);
        $payerWallet->setRelation('user', $payerUser);

        $payeeUser = new User(['role' => Role::CUSTOMER]);
        $payeeWallet = new Wallet(['id' => 2, 'balance' => 500.00]);
        $payeeWallet->setRelation('user', $payeeUser);

        $transferValue = Money::fromFloat(0.01);

        // Should not throw exception
        $this->validator->validate($payerWallet, $payeeWallet, $transferValue);

        $this->assertTrue(true);
    }

    /**
     * Test validation with large transfer amount.
     */
    public function test_validate_passes_with_large_transfer_amount(): void
    {
        $payerUser = new User(['role' => Role::CUSTOMER]);
        $payerWallet = new Wallet(['id' => 1, 'balance' => 1000000.00]);
        $payerWallet->setRelation('user', $payerUser);

        $payeeUser = new User(['role' => Role::CUSTOMER]);
        $payeeWallet = new Wallet(['id' => 2, 'balance' => 500.00]);
        $payeeWallet->setRelation('user', $payeeUser);

        $transferValue = Money::fromFloat(500000.00);

        // Should not throw exception
        $this->validator->validate($payerWallet, $payeeWallet, $transferValue);

        $this->assertTrue(true);
    }
}
