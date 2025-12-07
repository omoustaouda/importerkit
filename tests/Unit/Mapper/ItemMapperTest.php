<?php

declare(strict_types=1);

namespace ImporterKit\Tests\Unit\Mapper;

use ImporterKit\Domain\Item;
use ImporterKit\Exception\MappingException;
use ImporterKit\Mapper\ItemMapper;
use PHPUnit\Framework\TestCase;

final class ItemMapperTest extends TestCase
{
    private ItemMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ItemMapper();
    }

    public function testMapsValidData(): void
    {
        $item = $this->mapper->map($this->validRow());

        self::assertInstanceOf(Item::class, $item);
        self::assertSame('4006381333931', $item->gtin);
        self::assertSame('en', $item->language);
        self::assertSame('Valid product', $item->title);
        self::assertSame('http://example.com/img.jpg', $item->picture);
        self::assertSame('Description', $item->description);
        self::assertSame('19.99', $item->price);
        self::assertSame(10, $item->stock);
    }

    public function testTrimsValues(): void
    {
        $item = $this->mapper->map($this->validRow([
            'title' => '  Fancy Product  ',
        ]));

        self::assertSame('Fancy Product', $item->title);
    }

    public function testNormalizesPrice(): void
    {
        $item = $this->mapper->map($this->validRow([
            'price' => '738.7',
        ]));

        self::assertSame('738.70', $item->price);
    }

    public function testLeavesNonNumericPriceAsIs(): void
    {
        $item = $this->mapper->map($this->validRow([
            'price' => 'not-a-number',
        ]));

        self::assertSame('not-a-number', $item->price);
    }

    public function testSupportsIndicatesRequiredColumnsPresence(): void
    {
        self::assertTrue($this->mapper->supports($this->validRow()));
        self::assertFalse($this->mapper->supports(['gtin' => '123']));
    }

    public function testThrowsWhenColumnsMissing(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Missing required columns');

        $this->mapper->map(['gtin' => '123']);
    }

    /**
     * @param array<string, string> $overrides
     * @return array<string, string>
     */
    private function validRow(array $overrides = []): array
    {
        $row = [
            'gtin' => '4006381333931',
            'language' => 'en',
            'title' => 'Valid product',
            'picture' => 'http://example.com/img.jpg',
            'description' => 'Description',
            'price' => '19.99',
            'stock' => '10',
        ];

        return array_merge($row, $overrides);
    }
}

