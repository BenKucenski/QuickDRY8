<?php
declare(strict_types=1);

namespace QuickDRY\Connectors;

/** DO NOT USE THIS CLASS DIRECTLY **/

use DateTime;
use QuickDRY\Utilities\Dates;
use QuickDRY\Utilities\strongType;
use QuickDRY\Utilities\Strings;

const GUID_MSSQL = 'UPPER(SUBSTRING(master.dbo.fn_varbintohexstr(HASHBYTES(\'MD5\',cast(NEWID() as varchar(36)))), 3, 32)) ';

/**
 * Class MSSQL
 */
class MSSQL extends strongType
{
    /**
     * @param $data
     *
     * @return string
     */
    public static function EscapeString($data): string
    {
        if (is_array($data)) {
            Exception($data);
        }
        if (is_numeric($data)) return "'" . $data . "'";

        if ($data instanceof DateTime) {
            $data = Dates::Timestamp($data);
        }

        $non_displayables = [
            // '/%0[0-8bcef]/',            // breaks LIKE '%001'
            // '/%1[0-9a-f]/',
            '/[\x00-\x08]/',            // 00-08
            '/\x0b/',                   // 11
            '/\x0c/',                   // 12
            '/[\x0e-\x1f]/'             // 14-31
        ];

        if ($data) {
            foreach ($non_displayables as $regex) {
                $data = preg_replace($regex, '', $data);
            }
        }

        $data = str_replace("'", "''", $data ?? 'null');
        if (strcasecmp($data, 'null') == 0) {
            return 'null';
        }

        $data = str_replace('{{{', '', $data);
        $data = str_replace('}}}', '', $data);

        return "'" . $data . "'";
    }

    /**
     * @param $sql
     * @param $params
     * @param bool $test
     * @return string
     */
    public static function EscapeQuery($sql, $params, bool $test = false): string
    {
        /**
         * Pattern behavior:
         *  - Eats quoted strings first (single or double) so we don't replace inside them.
         *  - Then matches either:
         *      a) :name or @name  (captures name in group 1)
         *      b) a single bare @  (no capture; ensured by @(?!@))
         *  - Guards against :: and @@ via (?<![:@]) and (?!@)
         */
        $pattern = '/\'(?:\'\'|[^\'])*\'|"(?:""|[^"])*"|(?<![:@])(?:[:@]([A-Za-z_][A-Za-z0-9_]*)|@(?!@))/';

        if ($test) {
            $matches = [];
            preg_match_all($pattern, $sql, $matches);
            Testing($matches);
        }

        $posValues = array_values($params ?? []);
        $posIndex  = 0;

        return preg_replace_callback($pattern, function ($m) use (&$posIndex, $posValues, $params) {
            $tok = $m[0];

            // 1) Quoted string? Return as-is.
            $c0 = $tok[0];
            if ($c0 === "'" || $c0 === '"') {
                return $tok;
            }

            // 2) Named placeholder (@name or :name)
            if (isset($m[1]) && $m[1] !== '') {
                $name = $m[1];

                // Prefer named binding if provided
                if (array_key_exists($name, $params)) {
                    if (Strings::EndsWith($name, '_NQ')) {
                        return $params[$name];
                    }
                    $val = $params[$name];
                    if ($val === null) {
                        return 'null';
                    }
                    return MSSQL::EscapeString($val);
                }

                // Fallback to positional consumption if available
                if (array_key_exists($posIndex, $posValues)) {
                    $val = $posValues[$posIndex++];
                    switch ($name) {
                        case 'nullstring':
                            if ($val === null || $val === 'null' || $val === '') {
                                return 'null';
                            }
                            return MSSQL::EscapeString($val);

                        case 'nullnumeric':
                            if ($val === null || $val === 'null' || $val === '') {
                                return 'null';
                            }
                            return $val * 1.0;

                        case 'nq':
                            return $val;

                        default:
                            return MSSQL::EscapeString($val);
                    }
                }

                // Allow declared SQL variables to pass through if no binding
                return '@' . $name;
            }

            // 3) Bare @ ? consume next positional value
            if (array_key_exists($posIndex, $posValues)) {
                $val = $posValues[$posIndex++];
                // Bare @ doesn't have special name-based modes; just escape/null as needed
                if ($val === null) {
                    return 'null';
                }
                return MSSQL::EscapeString($val);
            }

            // No positional value available; leave a single @ (likely a SQL var)
            return '@';
        }, $sql);
    }
}
