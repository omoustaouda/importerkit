<?php

declare(strict_types=1);

namespace ImporterKit\Tests\Unit\DTO;

use ImporterKit\DTO\ImportResult;
use ImporterKit\Enum\ImportStatus;
use PHPUnit\Framework\TestCase;

final class ImportResultTest extends TestCase
{
    public function testStoresProvidedData(): void
    {
        $errors = [
            5 => ['Invalid GTIN'],
            12 => ['Invalid price', 'Invalid stock'],
        ];

        $result = new ImportResult(
            status: ImportStatus::PartialFailure,
            processed: 20,
            imported: 18,
            skipped: 2,
            errors: $errors,
        );

        self::assertSame(ImportStatus::PartialFailure, $result->status);
        self::assertSame(20, $result->processed);
        self::assertSame(18, $result->imported);
        self::assertSame(2, $result->skipped);
        self::assertSame($errors, $result->errors);
    }
}

