<?php

namespace QuickDRY\Web;

/**
 * Class Server
 *
 * @property string PHP_SELF
 * @property string argv
 * @property string argc
 * @property string GATEWAY_INTERFACE
 * @property string SERVER_ADDR
 * @property string SERVER_NAME
 * @property string SERVER_SOFTWARE
 * @property string SERVER_PROTOCOL
 * @property string REQUEST_METHOD
 * @property string REQUEST_TIME
 * @property string REQUEST_TIME_FLOAT
 * @property string QUERY_STRING
 * @property string DOCUMENT_ROOT
 * @property string HTTP_ACCEPT
 * @property string HTTP_ACCEPT_CHARSET
 * @property string HTTP_ACCEPT_ENCODING
 * @property string HTTP_ACCEPT_LANGUAGE
 * @property string HTTP_CONNECTION
 * @property string HTTP_HOST
 * @property string HTTP_REFERER
 * @property string HTTP_USER_AGENT
 * @property string HTTPS
 * @property string REMOTE_ADDR
 * @property string REMOTE_HOST
 * @property string REMOTE_PORT
 * @property string REMOTE_USER
 * @property string REDIRECT_REMOTE_USER
 * @property string SCRIPT_FILENAME
 * @property string SERVER_ADMIN
 * @property string SERVER_PORT
 * @property string SERVER_SIGNATURE
 * @property string PATH_TRANSLATED
 * @property string SCRIPT_NAME
 * @property string REQUEST_URI
 * @property string PHP_AUTH_DIGEST
 * @property string PHP_AUTH_USER
 * @property string PHP_AUTH_PW
 * @property string AUTH_TYPE
 * @property string PATH_INFO
 * @property string ORIG_PATH_INFO
 *
 */
class Server
{
    private ?array $_VALS;

    /**
     * Server constructor.
     * @param array|null $vals
     */
    public function __construct(array $vals = null)
    {
        $this->_VALS = $vals;
    }

    /**
     * @param string $prefix
     * @return array
     */
    public function ToArray(string $prefix = ''): array
    {
        $res = [];
        foreach ($this->_VALS as $k => $v) {
            if (!$prefix || str_starts_with($k, $prefix)) {
                $res[$k] = $this->$k;
            }
        }
        foreach ($_SERVER as $k => $v) {
            if (!$prefix || str_starts_with($k, $prefix)) {
                $res[$k] = $this->$k;
            }
        }

        return $res;
    }

    /**
     * @param string $name
     *
     * @return mixed|string
     */
    public function __get(string $name)
    {
        if (isset($_SERVER[$name]) && $_SERVER[$name]) {
            return $_SERVER[$name];
        }

        if (isset($this->_VALS[$name])) {
            return unserialize($this->_VALS[$name]);
        }

        return '';
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        if (isset($_SERVER[$name])) {
            unset($_SERVER[$name]);
        }
        if (isset($this->_VALS[$name])) {
            unset($this->_VALS[$name]);
        }
    }

    /**
     * @param string $name
     * @param $value
     */
    public function __set(string $name, $value)
    {
        $this->_VALS[$name] = serialize($value);
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($_SERVER[$name]) || isset($this->_VALS[$name]);
    }
}
