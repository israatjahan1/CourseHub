<?php
declare(strict_types=1);

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

function createLogger(): LoggerInterface
{
    $logDirectory = __DIR__ . '/../logs';
    if (!is_dir($logDirectory)) mkdir($logDirectory, 0755, true);
    $logger    = new Logger('coursehub');
    $handler   = new StreamHandler($logDirectory . '/app.log', Logger::DEBUG);
    $formatter = new LineFormatter(null, null, true, true);
    $handler->setFormatter($formatter);
    $logger->pushHandler($handler);
    return $logger;
}

function getDatabase(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dbPath = __DIR__ . '/../database/coursehub.sqlite';
        $isNew  = !file_exists($dbPath);
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        if ($isNew) {
            $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
            $pdo->exec($schema);
        }
    }
    return $pdo;
}
