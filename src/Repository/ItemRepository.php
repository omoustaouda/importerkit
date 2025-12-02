<?php

declare(strict_types=1);

namespace DataFeedImporter\Repository;

use DataFeedImporter\Domain\Item;
use DataFeedImporter\Exception\RepositoryException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * Persistence layer for Item entities.
 *
 * The repository intentionally stays thin: Doctrine DBAL handles SQL while
 * we provide an idempotent upsert API (`INSERT ... ON DUPLICATE KEY UPDATE`)
 * so repeated imports keep data in sync without duplicates. Transactions wrap
 * every batch to keep the DB consistent even when an error bubbles up.
 */
final class ItemRepository
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Idempotent insert/update for a single item.
     */
    public function upsert(Item $item): void
    {
        $this->upsertBatch([$item]);
    }

    /**
     * @param array<Item> $items
     */
    public function upsertBatch(array $items): void
    {
        if ($items === []) {
            return;
        }

        $sql = <<<SQL
            INSERT INTO items (gtin, language, title, picture, description, price, stock)
            VALUES (:gtin, :language, :title, :picture, :description, :price, :stock)
            ON DUPLICATE KEY UPDATE
                language = VALUES(language),
                title = VALUES(title),
                picture = VALUES(picture),
                description = VALUES(description),
                price = VALUES(price),
                stock = VALUES(stock),
                updated_at = CURRENT_TIMESTAMP
        SQL;

        $this->connection->beginTransaction();

        try {
            foreach ($items as $item) {
                $this->connection->executeStatement($sql, [
                    'gtin' => $item->gtin,
                    'language' => $item->language,
                    'title' => $item->title,
                    'picture' => $item->picture,
                    'description' => $item->description,
                    'price' => $item->price,
                    'stock' => $item->stock,
                ]);
            }

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw new RepositoryException('Failed to upsert items', 0, $exception);
        }
    }

    /**
     * Lightweight lookup for integration tests and potential future reads.
     */
    public function findByGtin(string $gtin): ?Item
    {
        try {
            $row = $this->connection->fetchAssociative(
                'SELECT * FROM items WHERE gtin = :gtin',
                ['gtin' => $gtin],
            );
        } catch (Exception $exception) {
            throw new RepositoryException('Failed to fetch item', 0, $exception);
        }

        if ($row === false) {
            return null;
        }

        return new Item(
            gtin: $row['gtin'],
            language: $row['language'],
            title: $row['title'],
            picture: $row['picture'],
            description: $row['description'],
            price: $row['price'],
            stock: (int) $row['stock'],
        );
    }
}

