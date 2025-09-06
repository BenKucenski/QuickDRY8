<?php
declare(strict_types=1);

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
    public static function Exception(string $err): void
    {
        if (defined('SHOW_ERRORS') && SHOW_ERRORS) {
            Exception($err);
        }
        self::LogError(-1, $err, '', 0);
    }

    /**
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return void
     */
    public static function LogError(int $errno, string $errstr, string $errfile, int $errline): void
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
    public static function Error(int $errno, string $errstr, string $errfile, int $errline): bool
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
    public static function Fatal(): void
    {
        $error = error_get_last();
        if (isset($error['type'])) {
            if ($error['type'] == E_ERROR) {
                try {
                    self::Error((int)$error['type'], (string)$error['message'], (string)$error['file'], (int)$error['line']);
                } catch (Exception $e) {
                    Exception($e);
                }
            }
        }
    }

    /**
     * @return void
     */
    public static function Init(): void
    {
        register_shutdown_function(['QuickDRY\Utilities\ExceptionHandler', 'Fatal']);
        set_exception_handler(['QuickDRY\Utilities\ExceptionHandler', 'Exception']);
        set_error_handler(['QuickDRY\Utilities\ExceptionHandler', 'Error']);

    }
}