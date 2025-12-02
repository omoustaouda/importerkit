<?php

declare(strict_types=1);

namespace DataFeedImporter\Tests\Integration\Repository;

use DataFeedImporter\Domain\Item;
use DataFeedImporter\Repository\ItemRepository;
use DataFeedImporter\Tests\Integration\DatabaseTestCase;
use Doctrine\DBAL\Connection;

final class ItemRepositoryTest extends DatabaseTestCase
{
    private ItemRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ItemRepository(self::connection());
    }

    public function testUpsertInsertsNewItem(): void
    {
        $item = $this->item(gtin: '4006381333931');

        $this->repository->upsert($item);

        $fetched = $this->repository->findByGtin('4006381333931');

        self::assertNotNull($fetched);
        self::assertSame('Valid title', $fetched->title);
        self::assertSame('29.99', $fetched->price);
    }

    public function testUpsertUpdatesExistingItem(): void
    {
        $item = $this->item(gtin: '4006381333931', price: '19.99');
        $updated = $this->item(gtin: '4006381333931', price: '15.00', stock: 3, title: 'Updated');

        $this->repository->upsert($item);
        $this->repository->upsert($updated);

        $fetched = $this->repository->findByGtin('4006381333931');

        self::assertNotNull($fetched);
        self::assertSame('15.00', $fetched->price);
        self::assertSame(3, $fetched->stock);
        self::assertSame('Updated', $fetched->title);
    }

    public function testUpsertBatchPersistsMultipleItems(): void
    {
        $this->repository->upsertBatch([
            $this->item(gtin: '1'),
            $this->item(gtin: '2'),
        ]);

        $connection = self::connection();
        $count = (int) $connection->fetchOne('SELECT COUNT(*) FROM items');

        self::assertSame(2, $count);
    }

    private function item(
        string $gtin = '4006381333931',
        string $language = 'en',
        string $title = 'Valid title',
        string $picture = 'https://example.com/img.jpg',
        string $description = 'Description',
        string $price = '29.99',
        int $stock = 5,
    ): Item {
        return new Item(
            gtin: $gtin,
            language: $language,
            title: $title,
            picture: $picture,
            description: $description,
            price: $price,
            stock: $stock,
        );
    }
}

