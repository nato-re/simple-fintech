<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Wallet;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class WalletRepositoryTest extends TestCase
{

    private WalletRepository $repository;
    private $mockModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockModel = Mockery::mock(Wallet::class);
        $this->repository = new WalletRepository($this->mockModel);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test findById returns wallet from cache.
     */
    public function test_find_by_id_returns_wallet_from_cache(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('wallet.1', \Mockery::any(), \Mockery::type('Closure'))
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $wallet = new Wallet();
        $wallet->id = 1;
        $wallet->balance = 1000.00;
        
        $this->mockModel->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($wallet);

        $result = $this->repository->findById(1);

        $this->assertInstanceOf(Wallet::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals(1000.00, $result->balance);
    }

    /**
     * Test findById returns null when wallet not found.
     */
    public function test_find_by_id_returns_null_when_not_found(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('wallet.999', \Mockery::any(), \Mockery::type('Closure'))
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->mockModel->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    /**
     * Test findByIdWithUser returns wallet with user relationship.
     */
    public function test_find_by_id_with_user_returns_wallet_with_user(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('wallet.1.with.user', \Mockery::any(), \Mockery::type('Closure'))
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $user = new User();
        $user->id = 1;
        $user->name = 'Test User';
        
        $wallet = new Wallet();
        $wallet->id = 1;
        $wallet->balance = 1000.00;
        $wallet->setRelation('user', $user);

        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($wallet);

        $this->mockModel->shouldReceive('with')
            ->once()
            ->with('user')
            ->andReturn($queryMock);

        $result = $this->repository->findByIdWithUser(1);

        $this->assertInstanceOf(Wallet::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertNotNull($result->user);
    }

    /**
     * Test decrementBalance decreases wallet balance.
     */
    public function test_decrement_balance_decreases_balance(): void
    {
        Cache::shouldReceive('forget')
            ->twice()
            ->with(\Mockery::pattern('/^wallet\.1/'))
            ->andReturn(true);

        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('decrement')
            ->once()
            ->with('balance', 100.00)
            ->andReturn(1);

        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('id', 1)
            ->andReturn($queryMock);

        $this->repository->decrementBalance(1, 100.00);

        // If we get here without exception, the method worked
        $this->assertTrue(true);
    }

    /**
     * Test incrementBalance increases wallet balance.
     */
    public function test_increment_balance_increases_balance(): void
    {
        Cache::shouldReceive('forget')
            ->twice()
            ->with(\Mockery::pattern('/^wallet\.1/'))
            ->andReturn(true);

        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('increment')
            ->once()
            ->with('balance', 100.00)
            ->andReturn(1);

        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('id', 1)
            ->andReturn($queryMock);

        $this->repository->incrementBalance(1, 100.00);

        // If we get here without exception, the method worked
        $this->assertTrue(true);
    }

    /**
     * Test updateBalance updates wallet balance.
     */
    public function test_update_balance_updates_balance(): void
    {
        Cache::shouldReceive('forget')
            ->twice()
            ->with(\Mockery::pattern('/^wallet\.1/'))
            ->andReturn(true);

        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('update')
            ->once()
            ->with(['balance' => 500.00])
            ->andReturn(1);

        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('id', 1)
            ->andReturn($queryMock);

        $this->repository->updateBalance(1, 500.00);

        // If we get here without exception, the method worked
        $this->assertTrue(true);
    }

    /**
     * Test hasSufficientBalance returns true when balance is sufficient.
     */
    public function test_has_sufficient_balance_returns_true_when_sufficient(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('wallet.1', \Mockery::any(), \Mockery::type('Closure'))
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $wallet = new Wallet();
        $wallet->id = 1;
        $wallet->balance = 1000.00;
        
        $this->mockModel->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($wallet);

        $result = $this->repository->hasSufficientBalance(1, 500.00);

        $this->assertTrue($result);
    }

    /**
     * Test hasSufficientBalance returns false when balance is insufficient.
     */
    public function test_has_sufficient_balance_returns_false_when_insufficient(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('wallet.1', \Mockery::any(), \Mockery::type('Closure'))
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $wallet = new Wallet();
        $wallet->id = 1;
        $wallet->balance = 100.00;
        
        $this->mockModel->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($wallet);

        $result = $this->repository->hasSufficientBalance(1, 500.00);

        $this->assertFalse($result);
    }

    /**
     * Test hasSufficientBalance returns false when wallet not found.
     */
    public function test_has_sufficient_balance_returns_false_when_wallet_not_found(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('wallet.999', \Mockery::any(), \Mockery::type('Closure'))
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->mockModel->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $result = $this->repository->hasSufficientBalance(999, 500.00);

        $this->assertFalse($result);
    }

    /**
     * Test lockForUpdate locks wallet for update.
     */
    public function test_lock_for_update_locks_wallet(): void
    {
        $wallet = new Wallet();
        $wallet->id = 1;
        $wallet->balance = 1000.00;

        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('first')
            ->once()
            ->andReturn($wallet);

        $lockMock = Mockery::mock();
        $lockMock->shouldReceive('lockForUpdate')
            ->once()
            ->andReturn($queryMock);

        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('id', 1)
            ->andReturn($lockMock);

        $result = $this->repository->lockForUpdate(1);

        $this->assertInstanceOf(Wallet::class, $result);
        $this->assertEquals(1, $result->id);
    }

    /**
     * Test lockForUpdate returns null when wallet not found.
     */
    public function test_lock_for_update_returns_null_when_not_found(): void
    {
        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('first')
            ->once()
            ->andReturn(null);

        $lockMock = Mockery::mock();
        $lockMock->shouldReceive('lockForUpdate')
            ->once()
            ->andReturn($queryMock);

        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('id', 999)
            ->andReturn($lockMock);

        $result = $this->repository->lockForUpdate(999);

        $this->assertNull($result);
    }
}

