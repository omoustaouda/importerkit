<?php

declare(strict_types=1);

namespace ImporterKit\Tests\Unit\Domain;

use ImporterKit\Domain\Item;
use ImporterKit\Domain\ItemValidator;
use ImporterKit\Enum\ValidationError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ItemValidatorTest extends TestCase
{
    private ItemValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ItemValidator();
    }

    public function testValidItemPassesValidation(): void
    {
        $result = $this->validator->validate($this->createItem());

        self::assertTrue($result->isValid);
        self::assertSame([], $result->errors);
    }

    #[DataProvider('invalidGtinProvider')]
    public function testInvalidGtin(string $gtin): void
    {
        $result = $this->validator->validate($this->createItem(gtin: $gtin));

        self::assertFalse($result->isValid);
        self::assertContains(ValidationError::InvalidGtin, $result->errors);
    }

    public static function invalidGtinProvider(): array
    {
        return [
            'too short' => ['1234567'],
            'too long' => ['123456789012345'],
            'non numeric' => ['400638133393X'],
            'bad checksum' => ['4006381333932'],
        ];
    }

    public function testNegativePriceFails(): void
    {
        $result = $this->validator->validate($this->createItem(price: '-1.00'));

        self::assertFalse($result->isValid);
        self::assertContains(ValidationError::InvalidPrice, $result->errors);
    }

    public function testNonNumericPriceFails(): void
    {
        $result = $this->validator->validate($this->createItem(price: 'abc'));

        self::assertFalse($result->isValid);
        self::assertContains(ValidationError::InvalidPrice, $result->errors);
    }

    public function testNegativeStockFails(): void
    {
        $result = $this->validator->validate($this->createItem(stock: -5));

        self::assertFalse($result->isValid);
        self::assertContains(ValidationError::InvalidStock, $result->errors);
    }

    public function testInvalidLanguageFails(): void
    {
        $result = $this->validator->validate($this->createItem(language: 'xx'));

        self::assertFalse($result->isValid);
        self::assertContains(ValidationError::InvalidLanguage, $result->errors);
    }

    public function testInvalidUrlFails(): void
    {
        $result = $this->validator->validate($this->createItem(picture: 'not-a-url'));

        self::assertFalse($result->isValid);
        self::assertContains(ValidationError::InvalidUrl, $result->errors);
    }

    public function testEmptyTitleFails(): void
    {
        $result = $this->validator->validate($this->createItem(title: ''));

        self::assertFalse($result->isValid);
        self::assertContains(ValidationError::MissingRequiredField, $result->errors);
    }

    public function testLanguageCheckIgnoresCase(): void
    {
        $result = $this->validator->validate($this->createItem(language: 'DE'));

        self::assertTrue($result->isValid);
    }

    private function createItem(
        string $gtin = '4006381333931',
        string $language = 'en',
        string $title = 'Valid title',
        string $picture = 'https://example.com/img.jpg',
        string $description = 'Description',
        string $price = '19.99',
        int $stock = 10,
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

