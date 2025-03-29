<?php

namespace QuickDRY\Web;

use QuickDRY\Utilities\strongType;

/**
 * Class Session
 */
class Session extends strongType
{
    public static function Set(string $name, $value): void
    {
        $_SESSION[$name] = $value;
    }

    public static function Get(string $name)
    {
        return $_SESSION[$name] ?? null;
    }

    public static function isset(string $name): bool
    {
        return isset($_SESSION[$name]);
    }
}

