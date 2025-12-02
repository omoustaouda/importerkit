<?php

declare(strict_types=1);

namespace DataFeedImporter\Tests\Unit\Domain;

use DataFeedImporter\Domain\Item;
use PHPUnit\Framework\TestCase;

final class ItemTest extends TestCase
{
    public function testConstructsReadonlyData(): void
    {
        $item = new Item(
            gtin: '4006381333931',
            language: 'en',
            title: 'Test Product',
            picture: 'http://example.com/image.jpg',
            description: 'Description',
            price: '19.99',
            stock: 25,
        );

        self::assertSame('4006381333931', $item->gtin);
        self::assertSame('en', $item->language);
        self::assertSame('Test Product', $item->title);
        self::assertSame('http://example.com/image.jpg', $item->picture);
        self::assertSame('Description', $item->description);
        self::assertSame('19.99', $item->price);
        self::assertSame(25, $item->stock);
    }
}

