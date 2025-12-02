<?php

declare(strict_types=1);

namespace DataFeedImporter\Tests\Integration;

use Doctrine\DBAL\Exception;

final class DatabaseTestCaseTest extends DatabaseTestCase
{
    /**
     * @throws Exception
     */
    public function testInsertsAndReadsItem(): void
    {
        $connection = self::connection();

        $connection->insert('items', [
            'gtin' => '12345678',
            'language' => 'en',
            'title' => 'Integration Product',
            'picture' => 'https://example.com/img.jpg',
            'description' => 'Description',
            'price' => '9.99',
            'stock' => 5,
        ]);

        $count = (int) $connection->fetchOne('SELECT COUNT(*) FROM items');

        self::assertSame(1, $count);
    }
}


