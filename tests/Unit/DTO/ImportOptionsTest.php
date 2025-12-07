<?php

declare(strict_types=1);

namespace ImporterKit\Tests\Unit\DTO;

use ImporterKit\DTO\ImportOptions;
use PHPUnit\Framework\TestCase;

final class ImportOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new ImportOptions();

        self::assertSame(100, $options->batchSize);
        self::assertFalse($options->dryRun);
        self::assertFalse($options->skipGtinValidation);
    }

    public function testCustomValues(): void
    {
        $options = new ImportOptions(batchSize: 250, dryRun: true, skipGtinValidation: true);

        self::assertSame(250, $options->batchSize);
        self::assertTrue($options->dryRun);
        self::assertTrue($options->skipGtinValidation);
    }
}

