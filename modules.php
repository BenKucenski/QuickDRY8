<?php
// require modules.php in command line scripts

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_DEPRECATED);
date_default_timezone_set('GMT');

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/env.php';

/**
 * @param ...$args
 */
function Debug(...$args): void
{
    $code = time() . '.' . rand(0, 1000000);
    if(!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs');
    }

    $file = __DIR__ . '/logs/' . $code . '.txt';
    file_put_contents($file, json_encode(['data' => $args, 'backtrace' => debug_backtrace()], JSON_PRETTY_PRINT));

    if(!defined('CONST_OUTPUT_ERRORS') || !CONST_OUTPUT_ERRORS) {
        exit('<p>An Error Occurred: ' . $code . '</p>');
    }

    if(defined('HALT_ON_DEBUG') && HALT_ON_DEBUG) {
        dd(['data' => $args, 'backtrace' => debug_backtrace()]);
    }
}

/**
 * @return string
 */
function GUID(): string
{
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(16384, 20479),
        mt_rand(32768, 49151),
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(0, 65535)
    );
}

define('GUID', GUID());

spl_autoload_register(function ($class_name) {
    $class_name = str_replace('\\', '/', $class_name);
    $file = __DIR__ . '/' . $class_name . '.php';
    try {
        if (!file_exists($file)) {
            return;
        }
        require_once $file;
    } catch (Exception $e) {
        dd($e);
    }
});

