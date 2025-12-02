<?php

declare(strict_types=1);

namespace DataFeedImporter\DataReader;

use DataFeedImporter\Exception\DataReaderException;

/**
 * Generic interface for reading data from various sources.
 *
 * IMPORTANT: This interface has NO knowledge of domain entities.
 * It returns raw associative arrays. Domain mapping is handled
 * by dedicated Mapper classes.
 *
 * Current implementation: CSV (via league/csv)
 * Future possibilities: JSON, XML, API, Database
 */
interface DataReaderInterface
{
    /**
     * Read data from the source.
     *
     * @return iterable<int, array<string, string>> Generator yielding:
     *         - Key: line number (for error reporting)
     *         - Value: associative array of column => value
     *
     * @throws DataReaderException When source cannot be read
     */
    public function read(): iterable;

    /**
     * Get total count of records (if known).
     * Returns null if count cannot be determined without reading entire source.
     */
    public function getEstimatedCount(): ?int;
}
