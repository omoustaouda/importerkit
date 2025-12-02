<?php

declare(strict_types=1);

namespace DataFeedImporter\Command;

use DataFeedImporter\DataReader\CsvDataReader;
use DataFeedImporter\DTO\ImportOptions;
use DataFeedImporter\Enum\ImportStatus;
use DataFeedImporter\Service\DataImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'import:feed',
    description: 'Import data from a CSV feed file into the database',
)]
final class ImportCommand extends Command
{
    public function __construct(
        private readonly DataImporter $importer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'Path to the CSV file to import',
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_REQUIRED,
                'Number of records to process per batch',
                100,
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Validate data without persisting it',
            )
            ->addOption(
                'skip-gtin-validation',
                null,
                InputOption::VALUE_NONE,
                'Treat GTIN checksum errors as warnings (useful for demo feeds)',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = (string) $input->getArgument('file');

        if (!is_file($filePath)) {
            $io->error(sprintf('File not found: %s', $filePath));
            return Command::FAILURE;
        }

        if (!is_readable($filePath)) {
            $io->error(sprintf('File is not readable: %s', $filePath));
            return Command::FAILURE;
        }

        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $dryRun = (bool) $input->getOption('dry-run');
        $skipGtinValidation = (bool) $input->getOption('skip-gtin-validation');

        $io->title('Data Feed Importer');
        $io->text([
            sprintf('File: %s', $filePath),
            sprintf('Batch size: %d', $batchSize),
            sprintf('Mode: %s', $dryRun ? 'DRY RUN' : 'LIVE'),
            sprintf('GTIN validation: %s', $skipGtinValidation ? 'LENIENT' : 'STRICT'),
        ]);

        $reader = new CsvDataReader($filePath);
        $options = new ImportOptions(
            batchSize: $batchSize,
            dryRun: $dryRun,
            skipGtinValidation: $skipGtinValidation,
        );
        $estimatedCount = $reader->getEstimatedCount();

        $progressBar = null;
        if ($output->isVerbose() && $estimatedCount !== null) {
            $progressBar = new ProgressBar($output, $estimatedCount);
            $progressBar->start();
        }

        $progressCallback = $progressBar
            ? function (int $processed, ?int $total) use ($progressBar): void {
                $progressBar->setProgress($processed);
            }
            : null;

        $result = $this->importer->import($reader, $options, $progressCallback);

        $progressBar?->finish();
        if ($progressBar !== null) {
            $io->newLine(2);
        }

        $io->section('Import Results');
        $io->definitionList(
            ['Status' => $result->status->value],
            ['Processed' => $result->processed],
            ['Imported' => $result->imported],
            ['Skipped' => $result->skipped],
        );

        if (!empty($result->errors) && $output->isVerbose()) {
            $io->section('Validation Errors (first 10)');
            foreach (array_slice($result->errors, 0, 10, true) as $line => $errors) {
                $io->text(sprintf('Line %d: %s', $line, implode(', ', $errors)));
            }

            $remaining = count($result->errors) - 10;
            if ($remaining > 0) {
                $io->text(sprintf('... and %d more errors', $remaining));
            }
        }

        return match ($result->status) {
            ImportStatus::Success => Command::SUCCESS,
            ImportStatus::PartialFailure => 1,
            ImportStatus::Failed => Command::FAILURE,
        };
    }
}

