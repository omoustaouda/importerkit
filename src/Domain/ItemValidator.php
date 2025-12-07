<?php

declare(strict_types=1);

namespace ImporterKit\Domain;

use ImporterKit\Enum\ValidationError;

final class ItemValidator
{
    private const array VALID_LANGUAGES = ['en', 'de', 'fr', 'it', 'es', 'nl', 'pl'];

    public function validate(Item $item): ValidationResult
    {
        $errors = [];

        if (!$this->isValidGtin($item->gtin)) {
            $errors[] = ValidationError::InvalidGtin;
        }

        if (!$this->isValidPrice($item->price)) {
            $errors[] = ValidationError::InvalidPrice;
        }

        if (!$this->isValidStock($item->stock)) {
            $errors[] = ValidationError::InvalidStock;
        }

        if (!$this->isValidLanguage($item->language)) {
            $errors[] = ValidationError::InvalidLanguage;
        }

        if (!$this->isValidUrl($item->picture)) {
            $errors[] = ValidationError::InvalidUrl;
        }

        if ($item->title === '') {
            $errors[] = ValidationError::MissingRequiredField;
        }

        return new ValidationResult(
            isValid: $errors === [],
            errors: $errors,
        );
    }

    private function isValidGtin(string $gtin): bool
    {
        if (!preg_match('/^\d{8,14}$/', $gtin)) {
            return false;
        }

        if (strlen($gtin) === 13) {
            return $this->isValidGtin13Checksum($gtin);
        }

        return true;
    }

    private function isValidGtin13Checksum(string $gtin): bool
    {
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $gtin[$i];
            $sum += ($i % 2 === 0) ? $digit : 3 * $digit;
        }

        $expected = (10 - ($sum % 10)) % 10;

        return $expected === (int) $gtin[12];
    }

    private function isValidPrice(string $price): bool
    {
        if (!is_numeric($price)) {
            return false;
        }

        $numeric = (float) $price;

        return $numeric > 0 && $numeric < 10_000_000_000_000;
    }

    private function isValidStock(int $stock): bool
    {
        return $stock >= 0;
    }

    private function isValidLanguage(string $language): bool
    {
        return in_array(strtolower($language), self::VALID_LANGUAGES, true);
    }

    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

