<?php

declare(strict_types=1);

namespace DataFeedImporter\Tests\Unit\Enum;

use DataFeedImporter\Enum\ImportStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ImportStatusTest extends TestCase
{
    public static function exitCodeProvider(): array
    {
        return [
            [ImportStatus::Success, 0],
            [ImportStatus::PartialFailure, 1],
            [ImportStatus::Failed, 2],
        ];
    }

    #[DataProvider('exitCodeProvider')]
    public function testGetExitCode(ImportStatus $status, int $expected): void
    {
        self::assertSame($expected, $status->getExitCode());
    }
}

