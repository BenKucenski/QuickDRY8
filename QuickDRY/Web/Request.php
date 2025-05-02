<?php

namespace QuickDRY\Web;

use QuickDRY\Utilities\HTTP;
use QuickDRY\Utilities\strongType;

/**
 *
 */
class Request
{
    /**
     * @return array
     */
    public static function toArray(): array
    {
        $vals = [];
        foreach ($_POST as $k => $v) {
            $vals[$k] = $v;
        }
        foreach ($_GET as $k => $v) {
            $vals[$k] = $v;
        }

        return $vals;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public static function Get(string $name): mixed
    {
        return $_REQUEST[$name] ?? null;
    }

    /**
     * @param string $name
     * @param $value
     * @return void
     */
    public static function Set(string $name, $value): void
    {
        $_REQUEST[$name] = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isset(string $name): bool
    {
        return isset($_REQUEST[$name]);
    }

    /**
     * @param $serialized
     */
    public static function FromSerialized($serialized): void
    {
        if (!$serialized) {
            return;
        }

        $post = HTTP::PostFromSerialized($serialized);
        foreach ($post as $k => $v) {
            self::Set($k, $v);
        }
    }
}