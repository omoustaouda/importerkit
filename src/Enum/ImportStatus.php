<?php

declare(strict_types=1);

namespace DataFeedImporter\Enum;

enum ImportStatus: string
{
    case Success = 'success';
    case PartialFailure = 'partial_failure';
    case Failed = 'failed';

    public function getExitCode(): int
    {
        return match ($this) {
            self::Success => 0,
            self::PartialFailure => 1,
            self::Failed => 2,
        };
    }
}

