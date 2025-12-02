<?php

declare(strict_types=1);

namespace DataFeedImporter\Tests\Unit\Service;

use DataFeedImporter\DataReader\DataReaderInterface;
use DataFeedImporter\Domain\Item;
use DataFeedImporter\Domain\ItemValidator;
use DataFeedImporter\DTO\ImportOptions;
use DataFeedImporter\Enum\ImportStatus;
use DataFeedImporter\Mapper\ItemMapper;
use DataFeedImporter\Repository\ItemRepository;
use DataFeedImporter\Service\DataImporter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class DataImporterTest extends TestCase
{
    public function testImportWithEmptyReaderFails(): void
    {
        $this->markTestSkipped('Covered in integration tests.');
    }
}

