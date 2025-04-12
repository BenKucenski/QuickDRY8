<?php

namespace QuickDRY\Web;


/**
 *
 */
class Server
{
    /**
     * @param array|null $remove
     * @return string
     */
    public static function GetQueryString(?array $remove = null): string
    {
        $params = [];
        parse_str($_SERVER['QUERY_STRING'] ?? '', $params);
        if ($remove) {
            foreach ($remove as $key) {
                if (isset($params[$key])) {
                    unset($params[$key]);
                }
            }
        }
        return http_build_query($params);
    }

    /**
     * @return string|null
     */
    public static function REMOTE_ADDR(): ?string
    {
        return ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? null) ? explode(':', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : ($_SERVER['REMOTE_ADDR'] ?? 'script');
    }

    /**
     * @return string|null
     */
    public static function REQUEST_URI(): ?string
    {
        return explode('?', $_SERVER['REQUEST_URI'] ?? ($_SERVER['HTTP_X_ORIGINAL_URL'] ?? null))[0];
    }


    /**
     * @return string|null
     */
    public static function REQUEST_METHOD(): ?string
    {
        return $_SERVER['REQUEST_METHOD'] ?? null;
    }

    /**
     * @return string|null
     */
    public static function HTTP_USER_AGENT(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * @return string|null
     */
    public static function QUERY_STRING(): ?string
    {
        return $_SERVER['QUERY_STRING'] ?? null;
    }
}
