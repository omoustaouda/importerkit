<?php

declare(strict_types=1);

/**
 * PHP-DI Container Configuration
 *
 * This file defines all service definitions for dependency injection.
 * Keep it simple: one entry per service, clear dependencies.
 */

use ImporterKit\Command\ImportCommand;
use ImporterKit\Domain\ItemValidator;
use ImporterKit\Mapper\ItemMapper;
use ImporterKit\Repository\ItemRepository;
use ImporterKit\Service\DataImporter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function DI\factory;

return [
    // Database Connection
    Connection::class => factory(function () {
        return DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => $_ENV['DB_HOST'] ?? 'db',
            'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
            'dbname' => $_ENV['DB_DATABASE'] ?? 'data_feed',
            'user' => $_ENV['DB_USERNAME'] ?? 'app',
            'password' => $_ENV['DB_PASSWORD'] ?? 'secret',
            'charset' => 'utf8mb4',
        ]);
    }),

    // Logger
    LoggerInterface::class => factory(function () {
        $logger = new Logger('importerkit');

        $logLevel = match ($_ENV['LOG_LEVEL'] ?? 'info') {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'warning' => Level::Warning,
            'error' => Level::Error,
            default => Level::Info,
        };

        $logger->pushHandler(new StreamHandler('php://stderr', $logLevel));

        return $logger;
    }),

    ItemMapper::class => factory(fn () => new ItemMapper()),

    ItemValidator::class => factory(fn () => new ItemValidator()),

    ItemRepository::class => factory(
        fn (ContainerInterface $container): ItemRepository => new ItemRepository(
            $container->get(Connection::class),
        )
    ),

    DataImporter::class => factory(
        fn (ContainerInterface $container): DataImporter => new DataImporter(
            mapper: $container->get(ItemMapper::class),
            validator: $container->get(ItemValidator::class),
            repository: $container->get(ItemRepository::class),
            logger: $container->get(LoggerInterface::class),
        )
    ),

    ImportCommand::class => factory(
        fn (ContainerInterface $container): ImportCommand => new ImportCommand(
            $container->get(DataImporter::class),
        )
    ),
];

