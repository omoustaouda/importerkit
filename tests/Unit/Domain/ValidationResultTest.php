<?php

declare(strict_types=1);

namespace ImporterKit\Tests\Unit\Domain;

use ImporterKit\Domain\ValidationResult;
use ImporterKit\Enum\ValidationError;
use PHPUnit\Framework\TestCase;

final class ValidationResultTest extends TestCase
{
    public function testStoresValidityAndErrors(): void
    {
        $result = new ValidationResult(
            isValid: false,
            errors: [
                ValidationError::InvalidPrice,
                ValidationError::InvalidStock,
            ],
        );

        self::assertFalse($result->isValid);
        self::assertSame(
            [
                ValidationError::InvalidPrice,
                ValidationError::InvalidStock,
            ],
            $result->errors,
        );
    }

    public function testMapsErrorMessages(): void
    {
        $result = new ValidationResult(
            isValid: false,
            errors: [
                ValidationError::InvalidPrice,
                ValidationError::InvalidUrl,
            ],
        );

        self::assertSame(
            [
                'Price must be a positive number',
                'Invalid URL format',
            ],
            $result->getErrorMessages(),
        );
    }
}

