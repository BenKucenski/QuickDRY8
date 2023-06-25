<?php

namespace QuickDRY\Connectors\mysql;

/**
 * Class MySQLBase
 */
class MySQL_C extends MySQL_Core
{
    protected static ?MySQL_Connection $connection = null;

    protected static function _connect()
    {
        if (!defined('MYSQLC_HOST')) {
            exit('MYSQLC_HOST');
        }

        if (!defined('MYSQLC_USER')) {
            exit('MYSQLC_USER');
        }

        if (!defined('MYSQLC_PASS')) {
            exit('MYSQLC_PASS');
        }

        if (!defined('MYSQLC_PORT')) {
            exit('MYSQLC_PORT');
        }


        if (is_null(static::$connection)) {
            static::$DB_HOST = MYSQLC_HOST;
            static::$connection = new MySQL_Connection(MYSQLC_HOST, MYSQLC_USER, MYSQLC_PASS, MYSQLC_PORT);
        }
    }
}