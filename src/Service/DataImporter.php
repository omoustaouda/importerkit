<?php

declare(strict_types=1);

namespace DataFeedImporter\Service;

use DataFeedImporter\DataReader\DataReaderInterface;
use DataFeedImporter\Domain\Item;
use DataFeedImporter\Domain\ItemValidator;
use DataFeedImporter\DTO\ImportOptions;
use DataFeedImporter\DTO\ImportResult;
use DataFeedImporter\Enum\ImportStatus;
use DataFeedImporter\Enum\ValidationError;
use DataFeedImporter\Exception\MappingException;
use DataFeedImporter\Mapper\ItemMapper;
use DataFeedImporter\Repository\ItemRepository;
use Psr\Log\LoggerInterface;

/**
 * Orchestrates the full import flow:
 * Reader → Mapper → Validator → Repository
 *
 * Single responsibility for coordinating the pipeline, reporting statistics,
 * and keeping the CLI lightweight.
 */
final class DataImporter
{
    public function __construct(
        private readonly ItemMapper $mapper,
        private readonly ItemValidator $validator,
        private readonly ItemRepository $repository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param callable(int $processed, ?int $total): void|null $progressCallback
     */
    public function import(
        DataReaderInterface $reader,
        ImportOptions $options,
        ?callable $progressCallback = null,
    ): ImportResult {
        $stats = [
            'processed' => 0,
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $batch = [];
        $estimatedTotal = $reader->getEstimatedCount();
        $gtinSkipCount = 0;
        $gtinSkipSamples = [];

        foreach ($reader->read() as $lineNumber => $row) {
            $stats['processed']++;

            try {
                $item = $this->mapper->map($row);
            } catch (MappingException $exception) {
                $this->recordError($stats, $lineNumber, [$exception->getMessage()]);
                continue;
            }

            $validation = $this->validator->validate($item);
            $errors = $validation->errors;
            $isValid = $validation->isValid;

            if ($options->skipGtinValidation && $errors !== []) {
                $filteredErrors = array_values(array_filter(
                    $errors,
                    static fn (ValidationError $error): bool => $error !== ValidationError::InvalidGtin,
                ));

                if (count($filteredErrors) !== count($errors)) {
                    $gtinSkipCount++;
                    if (count($gtinSkipSamples) < 5) {
                        $gtinSkipSamples[] = [
                            'line' => $lineNumber,
                            'gtin' => $item->gtin,
                        ];
                    }
                }

                $errors = $filteredErrors;
                $isValid = $errors === [];
            }

            if (!$isValid) {
                $this->recordError($stats, $lineNumber, $this->formatValidationMessages($errors));
                continue;
            }

            $batch[] = $item;

            if (count($batch) >= $options->batchSize) {
                $stats['imported'] += $this->processBatch($batch, $options->dryRun);
                $batch = [];

                if ($progressCallback !== null) {
                    $progressCallback($stats['processed'], $estimatedTotal);
                }
            }
        }

        if ($batch !== []) {
            $stats['imported'] += $this->processBatch($batch, $options->dryRun);
        }

        if ($options->skipGtinValidation && $gtinSkipCount > 0) {
            $this->logger->notice('GTIN validation skipped for some rows', [
                'rows' => $gtinSkipCount,
                'samples' => $gtinSkipSamples,
            ]);
        }

        $status = $this->determineStatus($stats);

        return new ImportResult(
            status: $status,
            processed: $stats['processed'],
            imported: $stats['imported'],
            skipped: $stats['skipped'],
            errors: $stats['errors'],
        );
    }

    /**
     * @param array<Item> $items
     */
    private function processBatch(array $items, bool $dryRun): int
    {
        if ($dryRun) {
            $this->logger->debug('Dry run: skipped persisting batch', ['count' => count($items)]);
            return count($items);
        }

        $this->repository->upsertBatch($items);

        return count($items);
    }

    private function determineStatus(array $stats): ImportStatus
    {
        if ($stats['processed'] === 0) {
            return ImportStatus::Failed;
        }

        if ($stats['skipped'] === 0) {
            return ImportStatus::Success;
        }

        if ($stats['imported'] === 0) {
            return ImportStatus::Failed;
        }

        return ImportStatus::PartialFailure;
    }

    /**
     * @param array<string> $messages
     */
    private function recordError(array &$stats, int $lineNumber, array $messages): void
    {
        $stats['skipped']++;
        $stats['errors'][$lineNumber] = $messages;

        $this->logger->warning('Import skipped row', [
            'line' => $lineNumber,
            'errors' => $messages,
        ]);
    }

    /**
     * @param array<ValidationError> $errors
     * @return array<string>
     */
    private function formatValidationMessages(array $errors): array
    {
        return array_map(
            static fn (ValidationError $error): string => $error->getMessage(),
            $errors,
        );
    }
}

