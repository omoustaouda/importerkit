<?php

declare(strict_types=1);

namespace DataFeedImporter\DTO;

final readonly class ImportOptions
{
    public function __construct(
        public int $batchSize = 100,
        public bool $dryRun = false,
    ) {
    }
}

