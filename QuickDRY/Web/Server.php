<?php

namespace QuickDRY\Web;

use QuickDRY\Utilities\strongType;

/**
 *
 */
class Server extends strongType
{
    public static function RemoteADDR()
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
    }

    /**
     * @param array|null $remove
     * @return string
     */
    public static function GetQueryString(?array $remove): string
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
}