<?php

declare(strict_types=1);

namespace DataFeedImporter\Tests\Integration\Command;

use DataFeedImporter\Command\ImportCommand;
use DataFeedImporter\DataReader\CsvDataReader;
use DataFeedImporter\Domain\ItemValidator;
use DataFeedImporter\Mapper\ItemMapper;
use DataFeedImporter\Repository\ItemRepository;
use DataFeedImporter\Service\DataImporter;
use DataFeedImporter\Tests\Integration\DatabaseTestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ImportCommandTest extends DatabaseTestCase
{
    public function testSuccessfulImport(): void
    {
        $tester = new CommandTester($this->createCommand());
        $statusCode = $tester->execute([
            'file' => $this->fixture('valid-feed.csv'),
        ]);

        self::assertSame(Command::SUCCESS, $statusCode);
        self::assertStringContainsString('Status', $tester->getDisplay());
        self::assertSame(2, $this->countItems());
    }

    public function testDryRunDoesNotPersist(): void
    {
        $tester = new CommandTester($this->createCommand());
        $statusCode = $tester->execute([
            'file' => $this->fixture('valid-feed.csv'),
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $statusCode);
        self::assertSame(0, $this->countItems());
    }

    public function testSkipGtinValidationOptionImportsRows(): void
    {
        $tester = new CommandTester($this->createCommand());
        $statusCode = $tester->execute([
            'file' => $this->fixture('invalid-gtin-only.csv'),
            '--skip-gtin-validation' => true,
        ]);

        self::assertSame(Command::SUCCESS, $statusCode);
        self::assertSame(2, $this->countItems());
    }

    public function testMissingFileFails(): void
    {
        $tester = new CommandTester($this->createCommand());

        $statusCode = $tester->execute([
            'file' => __DIR__ . '/missing.csv',
        ]);

        self::assertSame(Command::FAILURE, $statusCode);
        self::assertStringContainsString('File not found', $tester->getDisplay());
    }

    private function createCommand(): ImportCommand
    {
        $repository = new ItemRepository(self::connection());
        $importer = new DataImporter(
            mapper: new ItemMapper(),
            validator: new ItemValidator(),
            repository: $repository,
            logger: new NullLogger(),
        );

        return new ImportCommand($importer);
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

