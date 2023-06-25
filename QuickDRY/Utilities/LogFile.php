<?php

namespace QuickDRY\Utilities;

/**
 * Class LogFile
 */
class LogFile
{
    private static array $StartTime;

    public function __construct()
    {
        if (!is_dir(__DIR__ . '/../../logs/')) {
            mkdir(__DIR__ . '/../../logs/');
        }
    }

    /**
     * @param $filename
     * @param $message
     * @param bool $write_to_file
     */
    public function Insert($filename, $message, bool $write_to_file = true): void
    {
        if (is_object($message)) {
            if (method_exists($message, 'GetMessage')) {
                $message = $message->GetMessage();
            }
        }
        if (!isset(self::$StartTime[GUID])) {
            self::$StartTime[GUID] = time();
        }

        $msg = [];
        $msg [] = GUID;
        $msg [] = sprintf('%08.2f', (time() - self::$StartTime[GUID]) / 60);
        $msg [] = Dates::Timestamp();
        $msg [] = getcwd() . '/' . $filename;
        $msg [] = Network::Interfaces();
        $msg [] = is_array($message) || is_object($message) ? json_encode($message) : $message;
        $msg = implode("\t", $msg);


        if ($write_to_file) {
            $f = preg_replace('/[^a-z0-9]/si', '_', $filename) . '.' . Dates::Datestamp();
            $log_path = __DIR__ . '/../../logs/' . $f . '.log';

            $fp = fopen($log_path, 'a');

            if (false === $fp) {
                $error = error_get_last();
                error_log('Unable to log to ' . $log_path . ' -- Please check permissions: ' . $error);
                return;
            }

            fwrite($fp, $msg . PHP_EOL);
            fclose($fp);
        }

        if (SHOW_ERRORS || SHOW_NOTICES) {
            if(!($_SERVER['HTTP_HOST'] ?? null)) {
                echo $msg . PHP_EOL;
            }
        }
    }
}