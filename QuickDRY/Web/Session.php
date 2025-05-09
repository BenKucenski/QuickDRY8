<?php

namespace QuickDRY\Web;

use QuickDRY\Utilities\strongType;

/**
 * Class Session
 */
class Session extends strongType
{
    /**
     * @param string $name
     * @param $value
     * @return void
     */
    /**
     * @param string $name
     * @param $value
     * @return void
     */
    public static function Set(string $name, $value): void
    {
        $_SESSION[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    /**
     * @param string $name
     * @return mixed|null
     */
    public static function Get(string $name)
    {
        return $_SESSION[$name] ?? null;
    }

    public static function isset(string $name): bool
    {
        return isset($_SESSION[$name]);
    }
}

