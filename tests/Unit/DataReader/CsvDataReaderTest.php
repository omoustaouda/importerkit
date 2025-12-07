<?php

declare(strict_types=1);

namespace ImporterKit\Tests\Unit\DataReader;

use ImporterKit\DataReader\CsvDataReader;
use ImporterKit\Exception\DataReaderException;
use PHPUnit\Framework\TestCase;

final class CsvDataReaderTest extends TestCase
{
    public function testReadsCsvRecordsWithLineNumbers(): void
    {
        $reader = new CsvDataReader($this->fixture('valid-feed.csv'));

        $records = iterator_to_array($reader->read(), preserve_keys: true);

        self::assertCount(2, $records);
        self::assertSame(2, array_key_first($records));
        self::assertSame('4006381333931', $records[2]['gtin']);
        self::assertSame('12345670', $records[3]['gtin']);
    }

    public function testEstimatedCount(): void
    {
        $reader = new CsvDataReader($this->fixture('valid-feed.csv'));

        self::assertSame(2, $reader->getEstimatedCount());
    }

    public function testThrowsWhenFileMissing(): void
    {
        $reader = new CsvDataReader('/tmp/not-found.csv');

        $this->expectException(DataReaderException::class);
        $this->expectExceptionMessage('Failed to read CSV file');

        iterator_to_array($reader->read());
    }

    private function fixture(string $file): string
    {
        return __DIR__ . '/../../Fixtures/' . $file;
    }
}

