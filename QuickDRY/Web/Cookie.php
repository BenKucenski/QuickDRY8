<?php

namespace QuickDRY\Web;

/**
 * Class Cookie
 */
class Cookie
{
    private static array $_VALS = [];

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        setcookie($name, $value, 0, '/', HTTP_HOST);
        Cookie::$_VALS[$name] = $value;
        $_COOKIE[$name] = $value;
    }

    /**
     * @param string $name
     * @param string $value
     * @param float $expires
     */
    public static function Set(string $name, string $value, float $expires = 24): void
    {
        if ($expires) {
            setcookie($name, $value, time() + $expires * 60 * 60, '/', HTTP_HOST);
        } else {
            setcookie($name, $value, 0, '/', HTTP_HOST);
        }
        Cookie::$_VALS[$name] = $value;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function Get(string $name): ?string
    {
        return $this->$name;
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function __get(string $name)
    {
        if (isset(Cookie::$_VALS[$name]))
            return Cookie::$_VALS[$name];
        if (isset($_COOKIE[$name]))
            return $_COOKIE[$name];
        return NULL;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        if (!isset($_COOKIE[$name]))
            return;

        // http://petersnotes.blogspot.com/2011/01/iphone-cookie-hell.html
        setcookie($name, ''); // for iPhone
        setcookie($name, '', time() - 1, HTTP_HOST);
        unset(Cookie::$_VALS[$name]);
        unset($_COOKIE[$name]);
    }
}
