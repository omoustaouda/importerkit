<?php

declare(strict_types=1);

namespace DataFeedImporter\Tests\Unit\DTO;

use DataFeedImporter\DTO\ImportOptions;
use PHPUnit\Framework\TestCase;

final class ImportOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new ImportOptions();

        self::assertSame(100, $options->batchSize);
        self::assertFalse($options->dryRun);
    }

    public function testCustomValues(): void
    {
        $options = new ImportOptions(batchSize: 250, dryRun: true);

        self::assertSame(250, $options->batchSize);
        self::assertTrue($options->dryRun);
    }
}

