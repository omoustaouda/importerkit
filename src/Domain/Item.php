<?php

declare(strict_types=1);

namespace ImporterKit\Domain;

final readonly class Item
{
    public function __construct(
        public string $gtin,
        public string $language,
        public string $title,
        public string $picture,
        public string $description,
        public string $price,
        public int $stock,
    ) {
    }
}

