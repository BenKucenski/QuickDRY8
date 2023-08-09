<?php

namespace QuickDRY\Web;

use QuickDRY\Utilities\strongType;

class Request extends strongType
{
    public static function Get(string $name)
    {
        return $_REQUEST[$name] ?? null;
    }
}