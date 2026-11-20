<?php

declare(strict_types=1);

namespace App\Helpers;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;

final class LoggerFactory
{
    public static function create(string $channel = 'app'): Logger
    {
        $logger = new Logger($channel);
        $logger->pushHandler(new RotatingFileHandler(BASE_PATH . '/storage/logs/app.log', 14, Level::Info));
        return $logger;
    }
}
