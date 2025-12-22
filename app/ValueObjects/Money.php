<?php

namespace App\ValueObjects;

use App\Constants\TransferConstants;
use InvalidArgumentException;

/**
 * Value Object representing a monetary amount.
 *
 * This class ensures precision by storing amounts in cents (integers)
 * instead of floats, preventing floating-point arithmetic errors.
 */
class Money
{
    /**
     * Create a new Money instance from cents.
     *
     * @param  int  $amountInCents Amount in cents (must be non-negative)
     * @param  string  $currency Currency code (default: BRL)
     */
    public function __construct(
        private readonly int $amountInCents,
        private readonly string $currency = 'BRL'
    ) {
        if ($amountInCents < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
    }

    /**
     * Create a Money instance from a float value.
     *
     * @param  float  $amount Amount in currency units (e.g., 100.50 for R$ 100.50)
     * @param  string  $currency Currency code (default: BRL)
     * @return self
     * @throws InvalidArgumentException If amount is out of valid range
     */
    public static function fromFloat(float $amount, string $currency = 'BRL'): self
    {
        if ($amount < TransferConstants::MIN_VALUE || $amount > TransferConstants::MAX_VALUE) {
            throw new InvalidArgumentException(
                sprintf(
                    'Amount must be between %s and %s, got %s',
                    TransferConstants::MIN_VALUE,
                    TransferConstants::MAX_VALUE,
                    $amount
                )
            );
        }

        // Convert to cents and round to avoid floating-point errors
        $amountInCents = (int) round($amount * 100);

        return new self($amountInCents, $currency);
    }

    /**
     * Create a Money instance from cents.
     *
     * @param  int  $amountInCents Amount in cents
     * @param  string  $currency Currency code (default: BRL)
     * @return self
     */
    public static function fromCents(int $amountInCents, string $currency = 'BRL'): self
    {
        return new self($amountInCents, $currency);
    }

    /**
     * Get the amount as a float (for database storage and API responses).
     *
     * @return float
     */
    public function toFloat(): float
    {
        return $this->amountInCents / 100.0;
    }

    /**
     * Get the amount in cents.
     *
     * @return int
     */
    public function getAmountInCents(): int
    {
        return $this->amountInCents;
    }

    /**
     * Get the currency code.
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Check if this amount is greater than another.
     *
     * @param  Money  $other
     * @return bool
     * @throws InvalidArgumentException If currencies don't match
     */
    public function isGreaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amountInCents > $other->amountInCents;
    }

    /**
     * Check if this amount is greater than or equal to another.
     *
     * @param  Money  $other
     * @return bool
     * @throws InvalidArgumentException If currencies don't match
     */
    public function isGreaterThanOrEqual(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amountInCents >= $other->amountInCents;
    }

    /**
     * Check if this amount is less than another.
     *
     * @param  Money  $other
     * @return bool
     * @throws InvalidArgumentException If currencies don't match
     */
    public function isLessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amountInCents < $other->amountInCents;
    }

    /**
     * Check if this amount is less than or equal to another.
     *
     * @param  Money  $other
     * @return bool
     * @throws InvalidArgumentException If currencies don't match
     */
    public function isLessThanOrEqual(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amountInCents <= $other->amountInCents;
    }

    /**
     * Check if this amount equals another.
     *
     * @param  Money  $other
     * @return bool
     */
    public function equals(Money $other): bool
    {
        return $this->amountInCents === $other->amountInCents &&
               $this->currency === $other->currency;
    }

    /**
     * Add another Money amount to this one.
     *
     * @param  Money  $other
     * @return self New Money instance with the sum
     * @throws InvalidArgumentException If currencies don't match
     */
    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    /**
     * Subtract another Money amount from this one.
     *
     * @param  Money  $other
     * @return self New Money instance with the difference
     * @throws InvalidArgumentException If currencies don't match or result would be negative
     */
    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        $result = $this->amountInCents - $other->amountInCents;

        if ($result < 0) {
            throw new InvalidArgumentException('Subtraction would result in negative amount');
        }

        return new self($result, $this->currency);
    }

    /**
     * Multiply this amount by a scalar.
     *
     * @param  float|int  $multiplier
     * @return self
     */
    public function multiply(float|int $multiplier): self
    {
        $result = (int) round($this->amountInCents * $multiplier);

        if ($result < 0) {
            throw new InvalidArgumentException('Multiplication would result in negative amount');
        }

        return new self($result, $this->currency);
    }

    /**
     * Get a zero Money instance.
     *
     * @param  string  $currency Currency code (default: BRL)
     * @return self
     */
    public static function zero(string $currency = 'BRL'): self
    {
        return new self(0, $currency);
    }

    /**
     * Assert that two Money instances have the same currency.
     *
     * @param  Money  $other
     * @return void
     * @throws InvalidArgumentException If currencies don't match
     */
    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot operate on different currencies: %s and %s',
                    $this->currency,
                    $other->currency
                )
            );
        }
    }

    /**
     * Get string representation of the Money object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('%s %.2f', $this->currency, $this->toFloat());
    }
}

