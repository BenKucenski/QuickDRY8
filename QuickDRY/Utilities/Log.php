<?php

namespace QuickDRY\Utilities;

/**
 * Class Log
 */
class Log extends strongType
{
    private static ?LogFile $_log_file = null;
    private static ?array $StartTime = null;

    /**
     *
     */
    private static function _init()
    {
        if (is_null(self::$_log_file)) {
            self::$_log_file = new LogFile();
        }
    }

    /**
     * @param $message
     * @param bool $write_to_file
     */
    public static function Insert($message, bool $write_to_file = true)
    {
        self::_init();
        if (!defined('GUID')) {
            return;
        }

        self::$_log_file->Insert($_SERVER['SCRIPT_FILENAME'], $message, $write_to_file);
    }

    public static function Print($message)
    {
        self::Insert($message, true, false);
    }

    public static function File($message)
    {
        self::Insert($message);
    }
}