<?php

namespace QuickDRY\Web;

use QuickDRY\Utilities\HTTP;

/**
 * Class Request
 */
class Request
{

    private array $_vars = [];

    /**
     * @return array
     */
    public function ToArray(): array
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
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $value = !is_array($value) ? trim($value) : $value;
        $_POST[$name] = $value;
        $_GET[$name] = $value;
        $this->_vars[$name] = $value;
    }

    /**
     * @param string $name
     * @return array|string|null
     */
    public function Get(string $name): array|string|null
    {
        return $this->$name;
    }

    /**
     * @param $name
     * @param $value
     * @return void
     */
    public function Set($name, $value): void
    {
        $this->$name = $value;
    }

    /**
     * @param string $name
     *
     * @return array|string|null
     */
    public function __get(string $name)
    {
        if (isset($_POST[$name])) {
            return is_array($_POST[$name]) ? $_POST[$name] : trim($_POST[$name]);
        }

        if (isset($_GET[$name])) {
            return is_array($_GET[$name]) ? $_GET[$name] : trim($_GET[$name]);
        }

        if (isset($this->_vars[$name])) {
            return is_array($this->_vars[$name]) ? $this->_vars[$name] : trim($this->_vars[$name]);
        }

        return null;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($_GET[$name]) || isset($_POST[$name]) || isset($this->_vars[$name]);
    }

    /**
     * @param $serialized
     */
    public function FromSerialized($serialized): void
    {
        if (!$serialized) {
            return;
        }

        $post = HTTP::PostFromSerialized($serialized);
        foreach ($post as $k => $v) {
            $this->$k = $v;
        }
    }
}
