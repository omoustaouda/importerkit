<?php

declare(strict_types=1);

namespace DataFeedImporter\Tests\Integration\Service;

use DataFeedImporter\DataReader\CsvDataReader;
use DataFeedImporter\Domain\ItemValidator;
use DataFeedImporter\DTO\ImportOptions;
use DataFeedImporter\Enum\ImportStatus;
use DataFeedImporter\Mapper\ItemMapper;
use DataFeedImporter\Repository\ItemRepository;
use DataFeedImporter\Service\DataImporter;
use DataFeedImporter\Tests\Integration\DatabaseTestCase;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;

final class DataImporterTest extends DatabaseTestCase
{
    private DataImporter $importer;
    private ItemRepository $repository;
    private TestHandler $logHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ItemRepository(self::connection());
        $this->logHandler = new TestHandler();

        $logger = new Logger('test');
        $logger->pushHandler($this->logHandler);

        $this->importer = new DataImporter(
            mapper: new ItemMapper(),
            validator: new ItemValidator(),
            repository: $this->repository,
            logger: $logger,
        );
    }

    public function testSuccessfulImport(): void
    {
        $reader = new CsvDataReader($this->fixture('valid-feed.csv'));
        $options = new ImportOptions(batchSize: 1);

        $result = $this->importer->import($reader, $options);

        self::assertSame(ImportStatus::Success, $result->status);
        self::assertSame(2, $result->processed);
        self::assertSame(2, $result->imported);
        self::assertSame(0, $result->skipped);
        self::assertEmpty($result->errors);
    }

    public function testDryRunDoesNotPersist(): void
    {
        $reader = new CsvDataReader($this->fixture('valid-feed.csv'));
        $options = new ImportOptions(batchSize: 2, dryRun: true);

        $result = $this->importer->import($reader, $options);

        self::assertSame(ImportStatus::Success, $result->status);
        self::assertSame(2, $result->imported);
        self::assertSame(0, $this->countItems());
        self::assertTrue($this->logHandler->hasRecordThatContains('Dry run', Level::Debug));
    }

    public function testPartialFailure(): void
    {
        $reader = new CsvDataReader($this->fixture('invalid-feed.csv'));
        $options = new ImportOptions(batchSize: 2);

        $result = $this->importer->import($reader, $options);

        self::assertSame(ImportStatus::PartialFailure, $result->status);
        self::assertSame(3, $result->processed);
        self::assertSame(1, $result->imported);
        self::assertSame(2, $result->skipped);
        self::assertNotEmpty($result->errors);
    }

    private function fixture(string $file): string
    {
        return __DIR__ . '/../../Fixtures/' . $file;
    }

    private function countItems(): int
    {
        return (int) self::connection()->fetchOne('SELECT COUNT(*) FROM items');
    }
}

