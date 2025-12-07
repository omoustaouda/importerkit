<?php

declare(strict_types=1);

namespace ImporterKit\DTO;

use ImporterKit\Enum\ImportStatus;

final readonly class ImportResult
{
    /**
     * @param array<int, array<string>> $errors
     */
    public function __construct(
        public ImportStatus $status,
        public int $processed,
        public int $imported,
        public int $skipped,
        public array $errors = [],
    ) {
    }
}

