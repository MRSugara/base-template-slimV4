<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Predis\Client as RedisClient;
use App\Application\Middleware\RateLimiterMiddleware;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
       // Database
        'db' => function () {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();

            $db = new PDO(
                "{$_ENV['DB_DRIVER']}:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
                $_ENV['DB_USER'],
                $_ENV['DB_PASS']
            );

            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $db;
        },

        // Logger
        LoggerInterface::class => function ($c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');

            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler(
                $loggerSettings['path'],
                $loggerSettings['level']
            );

            $logger->pushHandler($handler);

            return $logger;
        },

        RedisClient::class => function () {
            return new RedisClient([
                'scheme' => 'tcp',
                'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                'port' => $_ENV['REDIS_PORT'] ?? 6379,
            ]);
        },
        RateLimiterMiddleware::class => function ($c) {
        return new RateLimiterMiddleware(
            $c->get(\Predis\Client::class),
            maxRequests: 60,        // 60 request
            decaySeconds: 60        // per 1 menit
        );
    },
    ]);
};
