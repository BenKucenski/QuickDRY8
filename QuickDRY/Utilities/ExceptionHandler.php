<?php

namespace QuickDRY\Utilities;

use Exception;

/**
 * Class ExceptionHandler
 */
class ExceptionHandler
{
    /**
     * @param string $err
     */
    public static function Exception(string $err)
    {
        if (defined('SHOW_ERRORS') && SHOW_ERRORS) {
            Debug($err);
        }
        self::LogError(-1, $err, '', '');
    }

    public static function LogError(string $errno, string $errstr, string $errfile, string $errline)
    {
        Log::Insert([$errno, $errstr, $errfile, $errline]);
    }

    /**
     * @param string $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     * @return bool
     * @throws Exception
     */
    public static function Error(string $errno, string $errstr, string $errfile, string $errline): bool
    {
        if (defined('SHOW_ERRORS') && SHOW_ERRORS) {
            if ($errno != 8 || (defined('SHOW_NOTICES') && SHOW_NOTICES)) { // don't show notice errors on the page unless explicitly told to
                self::LogError($errno, $errstr, $errfile, $errline);
                throw new Exception(json_encode([$errno, $errstr, $errfile, $errline]));
            }
        }
        self::LogError($errno, $errstr, $errfile, $errline);
        return false;
    }

    /**
     *
     */
    public static function Fatal()
    {
        $error = error_get_last();
        if (isset($error['type'])) {
            if ($error['type'] == E_ERROR) {
                try {
                    self::Error($error['type'], $error['message'], $error['file'], $error['line']);
                } catch (Exception $e) {
                    Debug($e);
                }
            }
        }
    }

    public static function Init()
    {
        register_shutdown_function(['QuickDRY\Utilities\ExceptionHandler', 'Fatal']);

        set_exception_handler(['QuickDRY\Utilities\ExceptionHandler', 'Exception']);
        set_error_handler(['QuickDRY\Utilities\ExceptionHandler', 'Error']);

    }
}