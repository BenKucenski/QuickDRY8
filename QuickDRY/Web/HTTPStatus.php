<?php

namespace QuickDRY\Web;

use const QuickDRY\Utilities\HTTP_STATUS_BAD_GATEWAY;
use const QuickDRY\Utilities\HTTP_STATUS_BAD_REQUEST;
use const QuickDRY\Utilities\HTTP_STATUS_CALM_DOWN;
use const QuickDRY\Utilities\HTTP_STATUS_FORBIDDEN;
use const QuickDRY\Utilities\HTTP_STATUS_GATEWAY_TIMEOUT;
use const QuickDRY\Utilities\HTTP_STATUS_GONE;
use const QuickDRY\Utilities\HTTP_STATUS_INTERNAL_SERVER_ERROR;
use const QuickDRY\Utilities\HTTP_STATUS_NOT_ACCEPTABLE;
use const QuickDRY\Utilities\HTTP_STATUS_NOT_FOUND;
use const QuickDRY\Utilities\HTTP_STATUS_NOT_MODIFIED;
use const QuickDRY\Utilities\HTTP_STATUS_OK;
use const QuickDRY\Utilities\HTTP_STATUS_SERVICE_UNAVAILABLE;
use const QuickDRY\Utilities\HTTP_STATUS_TOO_MANY_REQUESTS;
use const QuickDRY\Utilities\HTTP_STATUS_UNAUTHORIZED;
use const QuickDRY\Utilities\HTTP_STATUS_UNPROCESSABLE_ENTITY;

/**
 * Class HTTPStatus
 */
class HTTPStatus
{
    /**
     * @param $http_status_code
     * @return null|string
     */
    public static function GetDescription($http_status_code): ?string
    {
        if (!is_numeric($http_status_code)) {
            Debug('QuickDRY Error: Invalid status code: ' . $http_status_code);
        }

        switch ($http_status_code) {
            case HTTP_STATUS_OK:
                return 'OK';
            case HTTP_STATUS_NOT_MODIFIED:
                return 'Not Modified';
            case HTTP_STATUS_BAD_REQUEST:
                return 'Bad Request';
            case HTTP_STATUS_UNAUTHORIZED:
                return 'Unauthorized';
            case HTTP_STATUS_FORBIDDEN:
                return 'Forbidden';
            case HTTP_STATUS_NOT_FOUND:
                return 'Not Found';
            case HTTP_STATUS_NOT_ACCEPTABLE:
                return 'Not Acceptable';
            case HTTP_STATUS_GONE:
                return 'Gone';
            case HTTP_STATUS_CALM_DOWN:
                return 'Calm Your Scripts';
            case HTTP_STATUS_UNPROCESSABLE_ENTITY:
                return 'Unprocessable Entity';
            case HTTP_STATUS_TOO_MANY_REQUESTS:
                return 'Too Many Requests';
            case HTTP_STATUS_INTERNAL_SERVER_ERROR:
                return 'Internal Server Error';
            case HTTP_STATUS_BAD_GATEWAY:
                return 'Bad Gateway';
            case HTTP_STATUS_SERVICE_UNAVAILABLE:
                return 'Service Unavailable';
            case HTTP_STATUS_GATEWAY_TIMEOUT:
                return 'Gateway timeout';

        }

        Debug('QuickDRY Error: Invalid status code: ' . $http_status_code);
        return null;
    }
}