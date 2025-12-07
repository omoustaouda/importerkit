<?php

declare(strict_types=1);

namespace ImporterKit\DTO;

final readonly class ImportOptions
{
    public function __construct(
        public int $batchSize = 100,
        public bool $dryRun = false,
        public bool $skipGtinValidation = false,
    ) {
    }
}

