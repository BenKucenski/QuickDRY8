<?php

namespace QuickDRY\Web;

/**
 * Class Session
 */
class Session
{
    static array $_VALS = [];

    /**
     * @return array
     */
    public function ToArray(): array
    {
        $vals = [];
        foreach ($_SESSION as $k => $v) {
            $vals[$k] = $v;
        }
        foreach (self::$_VALS as $k => $v) {
            $vals[$k] = $v;
        }

        return $vals;
    }

    /**
     * @return void
     */
    public static function ClearAll(): void
    {
        foreach ($_SESSION as $n => $v) {
            unset(static::$_VALS[$n]);
            unset($_SESSION[$n]);
        }
    }

    /**
     * @param string $name
     *
     * @return mixed|string
     */
    public function Get(string $name): mixed
    {
        if (isset($_SESSION[$name])) {
            return unserialize($_SESSION[$name]);
        }

        if (isset(static::$_VALS[$name]))
            return unserialize(static::$_VALS[$name]);

        return '';
    }

    /**
     * @param string $name
     *
     * @return mixed|string
     */
    public function __get(string $name)
    {
        return $this->Get($name);
    }

    /**
     * @param $name
     */
    public static function Clear($name = null): void
    {
        if (is_null($name)) {
            session_destroy();
        }
        if (isset($_SESSION[$name])) {
            unset(static::$_VALS[$name]);
            unset($_SESSION[$name]);
        }
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        static::Clear($name);
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function Set($name, $value): mixed
    {
        $_SESSION[$name] = serialize($value);
        static::$_VALS[$name] = $_SESSION[$name];
        return $value;
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        return $this->Set($name, $value);
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public static function Check($name): bool
    {
        return isset($_SESSION[$name]);
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return static::Check($name);
    }
}


