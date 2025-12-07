<?php

declare(strict_types=1);

namespace ImporterKit\Domain;

use ImporterKit\Enum\ValidationError;

final readonly class ValidationResult
{
    /**
     * @param array<ValidationError> $errors
     */
    public function __construct(
        public bool $isValid,
        public array $errors = [],
    ) {
    }

    /**
     * @return array<string>
     */
    public function getErrorMessages(): array
    {
        return array_map(
            static fn(ValidationError $error): string => $error->getMessage(),
            $this->errors,
        );
    }
}

