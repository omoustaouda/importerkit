<?php

declare(strict_types=1);

namespace DataFeedImporter\Enum;

enum ValidationError: string
{
    case InvalidGtin = 'invalid_gtin';
    case InvalidPrice = 'invalid_price';
    case InvalidStock = 'invalid_stock';
    case InvalidLanguage = 'invalid_language';
    case InvalidUrl = 'invalid_url';
    case MissingRequiredField = 'missing_required_field';

    public function getMessage(): string
    {
        return match ($this) {
            self::InvalidGtin => 'Invalid GTIN format or checksum',
            self::InvalidPrice => 'Price must be a positive number',
            self::InvalidStock => 'Stock cannot be negative',
            self::InvalidLanguage => 'Invalid language code',
            self::InvalidUrl => 'Invalid URL format',
            self::MissingRequiredField => 'Required field is empty',
        };
    }
}

