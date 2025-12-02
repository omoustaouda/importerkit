<?php

declare(strict_types=1);

namespace DataFeedImporter\Mapper;

use DataFeedImporter\Domain\Item;
use DataFeedImporter\Exception\MappingException;

final class ItemMapper
{
    /**
     * @var array<int, string>
     */
    private const REQUIRED_COLUMNS = [
        'gtin',
        'language',
        'title',
        'picture',
        'description',
        'price',
        'stock',
    ];

    /**
     * @param array<string, string> $data
     */
    public function map(array $data): Item
    {
        $this->assertRequiredColumns($data);

        return new Item(
            gtin: $this->trim($data['gtin']),
            language: $this->trim($data['language']),
            title: $this->trim($data['title']),
            picture: $this->trim($data['picture']),
            description: $this->trim($data['description']),
            price: $this->normalizePrice($data['price']),
            stock: (int) $data['stock'],
        );
    }

    /**
     * @param array<string, string> $data
     */
    public function supports(array $data): bool
    {
        foreach (self::REQUIRED_COLUMNS as $column) {
            if (!array_key_exists($column, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    public function getRequiredColumns(): array
    {
        return self::REQUIRED_COLUMNS;
    }

    /**
     * @param array<string, string> $data
     */
    private function assertRequiredColumns(array $data): void
    {
        $missing = [];

        foreach (self::REQUIRED_COLUMNS as $column) {
            if (!array_key_exists($column, $data)) {
                $missing[] = $column;
            }
        }

        if ($missing !== []) {
            throw new MappingException(
                sprintf('Missing required columns: %s', implode(', ', $missing)),
            );
        }
    }

    private function normalizePrice(string $price): string
    {
        $value = $this->trim($price);

        if (!is_numeric($value)) {
            return $value;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function trim(string $value): string
    {
        return trim($value);
    }
}

