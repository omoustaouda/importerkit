<?php

declare(strict_types=1);

namespace DataFeedImporter\Tests\Integration\Database;

use DataFeedImporter\Tests\Integration\DatabaseTestCase;

final class DatabaseSetupTest extends DatabaseTestCase
{
    public function testItemsTableStartsEmpty(): void
    {
        $count = (int) self::connection()->fetchOne('SELECT COUNT(*) FROM items');

        self::assertSame(0, $count);
    }

    public function testCanInsertRow(): void
    {
        self::connection()->insert('items', [
            'gtin' => '0000000000000',
            'language' => 'en',
            'title' => 'Integration Test Item',
            'picture' => 'https://example.com/item.jpg',
            'description' => 'Test description',
            'price' => '9.99',
            'stock' => 5,
        ]);

        $count = (int) self::connection()->fetchOne('SELECT COUNT(*) FROM items');

        self::assertSame(1, $count);
    }
}

