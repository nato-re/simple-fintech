<?php

namespace Tests\Unit;

use App\Models\Transfer;
use App\Repositories\TransferRepository;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class TransferRepositoryTest extends TestCase
{
    private TransferRepository $repository;
    private $mockModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockModel = Mockery::mock(Transfer::class);
        $this->repository = new TransferRepository($this->mockModel);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test create returns created transfer.
     */
    public function test_create_returns_created_transfer(): void
    {
        $data = [
            'payer_wallet_id' => 1,
            'payee_wallet_id' => 2,
            'value' => 100.00,
        ];

        $transfer = new Transfer($data);
        $transfer->id = 1;

        $this->mockModel->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($transfer);

        $result = $this->repository->create($data);

        $this->assertInstanceOf(Transfer::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals(1, $result->payer_wallet_id);
        $this->assertEquals(2, $result->payee_wallet_id);
        $this->assertEquals(100.00, $result->value);
    }

    /**
     * Test findById returns transfer when found.
     */
    public function test_find_by_id_returns_transfer_when_found(): void
    {
        $transfer = new Transfer();
        $transfer->id = 1;
        $transfer->payer_wallet_id = 1;
        $transfer->payee_wallet_id = 2;
        $transfer->value = 100.00;

        $this->mockModel->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($transfer);

        $result = $this->repository->findById(1);

        $this->assertInstanceOf(Transfer::class, $result);
        $this->assertEquals(1, $result->id);
    }

    /**
     * Test findById returns null when transfer not found.
     */
    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $this->mockModel->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    /**
     * Test getByPayerWalletId returns collection of transfers.
     */
    public function test_get_by_payer_wallet_id_returns_collection(): void
    {
        $transfer1 = new Transfer();
        $transfer1->id = 1;
        $transfer1->payer_wallet_id = 1;
        $transfer1->payee_wallet_id = 2;
        $transfer1->value = 100.00;
        
        $transfer2 = new Transfer();
        $transfer2->id = 2;
        $transfer2->payer_wallet_id = 1;
        $transfer2->payee_wallet_id = 3;
        $transfer2->value = 200.00;
        
        $collection = new Collection([$transfer1, $transfer2]);

        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('get')
            ->once()
            ->andReturn($collection);

        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('payer_wallet_id', 1)
            ->andReturn($queryMock);

        $result = $this->repository->getByPayerWalletId(1);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result->first()->id);
    }

    /**
     * Test getByPayeeWalletId returns collection of transfers.
     */
    public function test_get_by_payee_wallet_id_returns_collection(): void
    {
        $transfer1 = new Transfer();
        $transfer1->id = 1;
        $transfer1->payer_wallet_id = 2;
        $transfer1->payee_wallet_id = 1;
        $transfer1->value = 100.00;
        
        $transfer2 = new Transfer();
        $transfer2->id = 2;
        $transfer2->payer_wallet_id = 3;
        $transfer2->payee_wallet_id = 1;
        $transfer2->value = 200.00;
        
        $collection = new Collection([$transfer1, $transfer2]);

        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('get')
            ->once()
            ->andReturn($collection);

        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('payee_wallet_id', 1)
            ->andReturn($queryMock);

        $result = $this->repository->getByPayeeWalletId(1);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result->first()->id);
    }

    /**
     * Test getByPayerWalletId returns empty collection when no transfers found.
     */
    public function test_get_by_payer_wallet_id_returns_empty_collection(): void
    {
        $collection = new Collection([]);

        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('get')
            ->once()
            ->andReturn($collection);

        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('payer_wallet_id', 999)
            ->andReturn($queryMock);

        $result = $this->repository->getByPayerWalletId(999);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }
}

