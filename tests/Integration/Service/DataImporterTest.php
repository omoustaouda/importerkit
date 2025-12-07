<?php

declare(strict_types=1);

namespace ImporterKit\Tests\Integration\Service;

use ImporterKit\DataReader\CsvDataReader;
use ImporterKit\Domain\ItemValidator;
use ImporterKit\DTO\ImportOptions;
use ImporterKit\Enum\ImportStatus;
use ImporterKit\Mapper\ItemMapper;
use ImporterKit\Repository\ItemRepository;
use ImporterKit\Service\DataImporter;
use ImporterKit\Tests\Integration\DatabaseTestCase;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;

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

    public function testSkipGtinValidationImportsOtherwiseValidRows(): void
    {
        $reader = new CsvDataReader($this->fixture('invalid-gtin-only.csv'));
        $options = new ImportOptions(batchSize: 2, skipGtinValidation: true);

        $result = $this->importer->import($reader, $options);

        self::assertSame(ImportStatus::Success, $result->status);
        self::assertSame(2, $result->processed);
        self::assertSame(2, $result->imported);
        self::assertSame(0, $result->skipped);
        self::assertTrue($this->logHandler->hasRecordThatContains('GTIN validation skipped for some rows', Level::Notice));

        $noticeRecords = array_values(array_filter(
            $this->logHandler->getRecords(),
            static fn (LogRecord $record): bool => $record->level === Level::Notice,
        ));
        self::assertCount(1, $noticeRecords);
        self::assertSame(2, $noticeRecords[0]->context['rows'] ?? null);
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

