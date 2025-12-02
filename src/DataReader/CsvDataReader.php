<?php

declare(strict_types=1);

namespace DataFeedImporter\DataReader;

use DataFeedImporter\Exception\DataReaderException;
use League\Csv\Reader;

/**
 * CSV implementation of DataReaderInterface.
 *
 * The reader stays intentionally generic: it knows nothing about Items,
 * only how to yield associative arrays for each CSV row. Domain mapping
 * and validation are handled in dedicated layers (ItemMapper + ItemValidator).
 *
 * League\Csv gives us robust CSV parsing (BOM handling, encoding quirks,
 * streaming iterators) without reinventing parser edge cases.
 */
final class CsvDataReader implements DataReaderInterface
{
    public function __construct(
        private readonly string $filePath,
        private readonly string $delimiter = ',',
        private readonly string $enclosure = '"',
    ) {
    }

    /**
     * @return iterable<int, array<string, string>> 2-based line numbers → row data
     */
    public function read(): iterable
    {
        $csv = $this->buildReader();
        $lineNumber = 2;

        foreach ($csv->getRecords() as $record) {
            yield $lineNumber++ => $record;
        }
    }

    /**
     * League\Csv can count rows cheaply when the file is readable upfront.
     * We return null when we fail so callers can degrade gracefully.
     */
    public function getEstimatedCount(): ?int
    {
        try {
            return $this->buildReader()->count();
        } catch (DataReaderException) {
            return null;
        }
    }

    private function buildReader(): Reader
    {
        try {
            $csv = Reader::from($this->filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter($this->delimiter);
            $csv->setEnclosure($this->enclosure);
            $csv->setEscape('');

            return $csv;
        } catch (\Throwable $exception) {
            throw new DataReaderException(
                sprintf('Failed to read CSV file: %s', $exception->getMessage()),
                previous: $exception,
            );
        }
    }
}

