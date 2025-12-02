<?php

declare(strict_types=1);

namespace DataFeedImporter\Tests\Unit\Enum;

use DataFeedImporter\Enum\ValidationError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidationErrorTest extends TestCase
{
    public static function messageProvider(): array
    {
        return [
            [ValidationError::InvalidGtin, 'Invalid GTIN format or checksum'],
            [ValidationError::InvalidPrice, 'Price must be a positive number'],
            [ValidationError::InvalidStock, 'Stock cannot be negative'],
            [ValidationError::InvalidLanguage, 'Invalid language code'],
            [ValidationError::InvalidUrl, 'Invalid URL format'],
            [ValidationError::MissingRequiredField, 'Required field is empty'],
        ];
    }

    #[DataProvider('messageProvider')]
    public function testGetMessage(ValidationError $error, string $expected): void
    {
        self::assertSame($expected, $error->getMessage());
    }
}

