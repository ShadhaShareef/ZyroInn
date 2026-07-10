<?php
namespace App\Services;

class Logger
{
    private static ?string $logDir = null;

    public static function init(?string $logDir = null): void
    {
        self::$logDir = $logDir ?? __DIR__ . '/../../storage/logs';
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0775, true);
        }
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        $entry = json_encode([
            'time'    => date('Y-m-d\TH:i:s.vP'),
            'level'   => $level,
            'message' => $message,
            'context' => $context,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $dir = self::$logDir ?? __DIR__ . '/../../storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $dir . '/app.log';
        file_put_contents($file, $entry . "\n", FILE_APPEND | LOCK_EX);
    }
}
