<?php

namespace Tests\Unit;

use App\Constants\TransferConstants;
use App\ValueObjects\Money;
use InvalidArgumentException;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    /**
     * Test creating Money from float.
     */
    public function test_can_create_money_from_float(): void
    {
        $money = Money::fromFloat(100.50);

        $this->assertEquals(10050, $money->getAmountInCents());
        $this->assertEquals(100.50, $money->toFloat());
        $this->assertEquals('BRL', $money->getCurrency());
    }

    /**
     * Test creating Money from cents.
     */
    public function test_can_create_money_from_cents(): void
    {
        $money = Money::fromCents(10050);

        $this->assertEquals(10050, $money->getAmountInCents());
        $this->assertEquals(100.50, $money->toFloat());
    }

    /**
     * Test creating Money with custom currency.
     */
    public function test_can_create_money_with_custom_currency(): void
    {
        $money = Money::fromFloat(100.50, 'USD');

        $this->assertEquals('USD', $money->getCurrency());
    }

    /**
     * Test creating zero Money.
     */
    public function test_can_create_zero_money(): void
    {
        $money = Money::zero();

        $this->assertEquals(0, $money->getAmountInCents());
        $this->assertEquals(0.0, $money->toFloat());
    }

    /**
     * Test that negative amounts throw exception in constructor.
     */
    public function test_throws_exception_for_negative_amount_in_constructor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative');

        new Money(-100);
    }

    /**
     * Test that amounts below minimum throw exception.
     */
    public function test_throws_exception_for_amount_below_minimum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be between');

        Money::fromFloat(TransferConstants::MIN_VALUE - 0.01);
    }

    /**
     * Test that amounts above maximum throw exception.
     */
    public function test_throws_exception_for_amount_above_maximum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be between');

        Money::fromFloat(TransferConstants::MAX_VALUE + 1);
    }

    /**
     * Test that minimum value is accepted.
     */
    public function test_accepts_minimum_value(): void
    {
        $money = Money::fromFloat(TransferConstants::MIN_VALUE);

        $this->assertEquals(TransferConstants::MIN_VALUE, $money->toFloat());
    }

    /**
     * Test that maximum value is accepted.
     */
    public function test_accepts_maximum_value(): void
    {
        $money = Money::fromFloat(TransferConstants::MAX_VALUE);

        $this->assertEquals(TransferConstants::MAX_VALUE, $money->toFloat());
    }

    /**
     * Test floating point precision handling.
     */
    public function test_handles_floating_point_precision_correctly(): void
    {
        // 0.1 + 0.2 should equal 0.3 in Money
        $money1 = Money::fromFloat(0.1);
        $money2 = Money::fromFloat(0.2);
        $result = $money1->add($money2);

        $this->assertEquals(0.3, $result->toFloat());
    }

    /**
     * Test isGreaterThan comparison.
     */
    public function test_is_greater_than(): void
    {
        $money1 = Money::fromFloat(100.50);
        $money2 = Money::fromFloat(50.25);

        $this->assertTrue($money1->isGreaterThan($money2));
        $this->assertFalse($money2->isGreaterThan($money1));
    }

    /**
     * Test isGreaterThanOrEqual comparison.
     */
    public function test_is_greater_than_or_equal(): void
    {
        $money1 = Money::fromFloat(100.50);
        $money2 = Money::fromFloat(100.50);
        $money3 = Money::fromFloat(50.25);

        $this->assertTrue($money1->isGreaterThanOrEqual($money2));
        $this->assertTrue($money1->isGreaterThanOrEqual($money3));
        $this->assertFalse($money3->isGreaterThanOrEqual($money1));
    }

    /**
     * Test isLessThan comparison.
     */
    public function test_is_less_than(): void
    {
        $money1 = Money::fromFloat(50.25);
        $money2 = Money::fromFloat(100.50);

        $this->assertTrue($money1->isLessThan($money2));
        $this->assertFalse($money2->isLessThan($money1));
    }

    /**
     * Test isLessThanOrEqual comparison.
     */
    public function test_is_less_than_or_equal(): void
    {
        $money1 = Money::fromFloat(50.25);
        $money2 = Money::fromFloat(50.25);
        $money3 = Money::fromFloat(100.50);

        $this->assertTrue($money1->isLessThanOrEqual($money2));
        $this->assertTrue($money1->isLessThanOrEqual($money3));
        $this->assertFalse($money3->isLessThanOrEqual($money1));
    }

    /**
     * Test equals comparison.
     */
    public function test_equals(): void
    {
        $money1 = Money::fromFloat(100.50);
        $money2 = Money::fromFloat(100.50);
        $money3 = Money::fromFloat(100.51);

        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
    }

    /**
     * Test equals with different currencies.
     */
    public function test_equals_returns_false_for_different_currencies(): void
    {
        $money1 = Money::fromFloat(100.50, 'BRL');
        $money2 = Money::fromFloat(100.50, 'USD');

        $this->assertFalse($money1->equals($money2));
    }

    /**
     * Test add operation.
     */
    public function test_add(): void
    {
        $money1 = Money::fromFloat(100.50);
        $money2 = Money::fromFloat(50.25);
        $result = $money1->add($money2);

        $this->assertEquals(150.75, $result->toFloat());
        $this->assertEquals(15075, $result->getAmountInCents());
    }

    /**
     * Test subtract operation.
     */
    public function test_subtract(): void
    {
        $money1 = Money::fromFloat(100.50);
        $money2 = Money::fromFloat(50.25);
        $result = $money1->subtract($money2);

        $this->assertEquals(50.25, $result->toFloat());
    }

    /**
     * Test subtract throws exception when result would be negative.
     */
    public function test_subtract_throws_exception_when_result_negative(): void
    {
        $money1 = Money::fromFloat(50.25);
        $money2 = Money::fromFloat(100.50);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subtraction would result in negative amount');

        $money1->subtract($money2);
    }

    /**
     * Test multiply operation.
     */
    public function test_multiply(): void
    {
        $money = Money::fromFloat(100.50);
        $result = $money->multiply(2);

        $this->assertEquals(201.00, $result->toFloat());
    }

    /**
     * Test multiply with float multiplier.
     */
    public function test_multiply_with_float(): void
    {
        $money = Money::fromFloat(100.00);
        $result = $money->multiply(1.5);

        $this->assertEquals(150.00, $result->toFloat());
    }

    /**
     * Test multiply throws exception when result would be negative.
     */
    public function test_multiply_throws_exception_when_result_negative(): void
    {
        $money = Money::fromFloat(100.50);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiplication would result in negative amount');

        $money->multiply(-1);
    }

    /**
     * Test operations with different currencies throw exception.
     */
    public function test_operations_with_different_currencies_throw_exception(): void
    {
        $money1 = Money::fromFloat(100.50, 'BRL');
        $money2 = Money::fromFloat(50.25, 'USD');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot operate on different currencies');

        $money1->add($money2);
    }

    /**
     * Test isGreaterThan with different currencies throws exception.
     */
    public function test_is_greater_than_with_different_currencies_throws_exception(): void
    {
        $money1 = Money::fromFloat(100.50, 'BRL');
        $money2 = Money::fromFloat(50.25, 'USD');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot operate on different currencies');

        $money1->isGreaterThan($money2);
    }

    /**
     * Test string representation.
     */
    public function test_to_string(): void
    {
        $money = Money::fromFloat(100.50);

        $this->assertEquals('BRL 100.50', (string) $money);
    }

    /**
     * Test rounding in fromFloat.
     */
    public function test_from_float_rounds_correctly(): void
    {
        // 0.1 * 3 = 0.3, but in floating point it might be 0.30000000000000004
        $money = Money::fromFloat(0.1 * 3);

        // Should be exactly 30 cents
        $this->assertEquals(30, $money->getAmountInCents());
        $this->assertEquals(0.30, $money->toFloat());
    }

    /**
     * Test edge case: very small amounts.
     */
    public function test_handles_very_small_amounts(): void
    {
        $money = Money::fromFloat(0.01);

        $this->assertEquals(1, $money->getAmountInCents());
        $this->assertEquals(0.01, $money->toFloat());
    }

    /**
     * Test edge case: large amounts.
     */
    public function test_handles_large_amounts(): void
    {
        $money = Money::fromFloat(999999.99);

        $this->assertEquals(99999999, $money->getAmountInCents());
        $this->assertEquals(999999.99, $money->toFloat());
    }
}

