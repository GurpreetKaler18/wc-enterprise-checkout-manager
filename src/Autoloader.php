<?php

declare(strict_types=1);

namespace SCE;

final class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    private static function autoload(string $class): void
    {
        if (strpos($class, __NAMESPACE__ . '\\') !== 0) {
            return;
        }

        $relativeClass = substr($class, strlen(__NAMESPACE__) + 1);
        $path = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($path)) {
            require_once $path;
        }
    }
}
