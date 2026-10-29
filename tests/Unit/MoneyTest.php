<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public static function validInputs(): array
    {
        return [
            ['12.3', 1230],
            ['12.30', 1230],
            [' 7 ', 700],
            ['1,000', 100000],
            ['0.05', 5],
        ];
    }

    #[DataProvider('validInputs')]
    public function test_parses_valid_amounts_to_cents(string $input, int $cents): void
    {
        $this->assertSame($cents, Money::toCents($input));
    }

    public static function invalidInputs(): array
    {
        return [['12.345'], ['0'], ['0.00'], ['-5'], ['abc'], [''], ['1.2.3']];
    }

    #[DataProvider('invalidInputs')]
    public function test_rejects_invalid_amounts(string $input): void
    {
        $this->assertNull(Money::toCents($input));
    }

    public function test_formats_cents_with_currency(): void
    {
        $this->assertSame('PHP 1,234.56', Money::format(123456, 'PHP'));
        $this->assertSame('USD 0.05', Money::format(5, 'USD'));
        $this->assertSame('PHP -12.00', Money::format(-1200, 'PHP'));
    }

    public function test_converts_cents_to_form_input(): void
    {
        $this->assertSame('1234.56', Money::toInput(123456));
        $this->assertSame('0.05', Money::toInput(5));
        $this->assertSame('100.00', Money::toInput(10000));
    }
}
