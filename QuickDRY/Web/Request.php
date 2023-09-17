<?php

namespace QuickDRY\Web;

use QuickDRY\Utilities\strongType;

class Request extends strongType
{
    public static function Get(string $name)
    {
        return $_REQUEST[$name] ?? null;
    }

    public static function isset(string $name): bool
    {
        return isset($_REQUEST[$name]);
    }
}