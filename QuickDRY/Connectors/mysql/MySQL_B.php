<?php

namespace QuickDRY\Connectors\mysql;

/**
 * Class MySQLBase
 */
class MySQL_B extends MySQL_Core
{
    protected static ?MySQL_Connection $connection = null;

    protected static function _connect()
    {
        if (!defined('MYSQLB_HOST')) {
            exit('MYSQLB_HOST');
        }

        if (!defined('MYSQLB_USER')) {
            exit('MYSQLB_USER');
        }

        if (!defined('MYSQLB_PASS')) {
            exit('MYSQLB_PASS');
        }

        if (!defined('MYSQLB_PORT')) {
            exit('MYSQLB_PORT');
        }

        if (is_null(static::$connection)) {
            static::$DB_HOST = MYSQLB_HOST;
            static::$connection = new MySQL_Connection(MYSQLB_HOST, MYSQLB_USER, MYSQLB_PASS, MYSQLB_PORT);
        }
    }
}