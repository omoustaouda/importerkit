<?php

declare(strict_types=1);

namespace DataFeedImporter\Tests\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    private const string MIGRATIONS_DIR = __DIR__ . '/../../database/migrations';

    private static ?Connection $connection = null;

    protected static function connection(): Connection
    {
        if (self::$connection === null) {
            self::$connection = DriverManager::getConnection([
                'driver' => 'pdo_mysql',
                'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
                'dbname' => $_ENV['DB_DATABASE'] ?? 'data_feed_test',
                'user' => $_ENV['DB_USERNAME'] ?? 'app',
                'password' => $_ENV['DB_PASSWORD'] ?? 'secret',
                'charset' => 'utf8mb4',
            ]);
        }

        return self::$connection;
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::applyMigrations();
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::truncateTables();
    }

    private static function applyMigrations(): void
    {
        $files = glob(self::MIGRATIONS_DIR . '/*.sql');
        sort($files);

        foreach ($files as $file) {
            self::runSqlFile($file);
        }
    }

    private static function runSqlFile(string $path): void
    {
        $sql = file_get_contents($path);

        if ($sql === false) {
            throw new \RuntimeException(sprintf('Failed to read migration file: %s', $path));
        }

        foreach (self::splitSqlStatements($sql) as $statement) {
            if ($statement !== '') {
                self::connection()->executeStatement($statement);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private static function splitSqlStatements(string $sql): array
    {
        $statements = array_map('trim', explode(';', $sql));

        return array_values(array_filter($statements, static fn(string $statement): bool => $statement !== ''));
    }

    private static function truncateTables(): void
    {
        try {
            self::connection()->executeStatement('TRUNCATE TABLE items');
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Unable to truncate items table. Did migrations run?', 0, $exception);
        }
    }
}

