<?php

declare(strict_types=1);

/**
 * PHP-DI Container Configuration
 *
 * This file defines all service definitions for dependency injection.
 * Keep it simple: one entry per service, clear dependencies.
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
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
        $logger = new Logger('data-feed-importer');

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

];

