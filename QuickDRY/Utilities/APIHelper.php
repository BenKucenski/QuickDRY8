<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;


/**
 *
 */
class APIHelper
{
    // https://stackoverflow.com/questions/9690448/regular-expression-to-remove-comments-from-sql-statement
    /**
     * @param $sqlString
     * @return string
     */
    public static function removeSqlComment($sqlString): string
    {
        $regEx = [
            '~(?:".*?"|\'.*?\')(*SKIP)(*F)|--.*$~m',
            '~(?:".*?"|\'.*?\')(*SKIP)(*F)|/\*.*?\*/~s',
            '~^;?\R~m'
        ];
        return trim(preg_replace($regEx, '', $sqlString));
    }

    /**
     * @param string $filename
     * @param string $placeholder
     * @return string
     */
    public static function getSQL(string $filename, string $placeholder = ':'): string
    {
        return self::removeSqlComment(str_replace('@', $placeholder, file_get_contents($filename)));

    }

    /**
     * @param string $var
     * @param array $data
     * @param string $sql
     * @param array $params
     * @return void
     */
    public static function ApplyArray(string $var, array $data, string &$sql, array &$params): void
    {
        if(!sizeof($data)) {
            return; // leave the placeholder so the query doesn't break on null
        }
        $placeholders = Strings::GetPlaceholders(sizeof($data), '@' . $var, true);
        $sql = str_replace('\'--' . $var . '--\'', $placeholders, $sql);
        foreach($data as $i => $k) {
            $params[$var . $i] = $k;
        }
    }

    /**
     * @param string $var
     * @param array $data
     * @param string $sql
     * @param array $params
     * @return void
     */
    public static function ApplyValues(string $var, array $data, string &$sql, array &$params): void
    {
        $placeholders = Strings::GetValues(sizeof($data), '@' . $var, true);
        $sql = str_replace('\'--' . $var . '--\'', $placeholders, $sql);
        foreach($data as $i => $k) {
            $params[$var . $i] = $k;
        }
    }

    /**
     * @param string $var
     * @param array $data
     * @param string $sql
     * @param array $params
     * @return void
     */
    public static function ApplyValuesArray(string $var, array $data, string &$sql, array &$params): void
    {
        $data2 = [];
        foreach($data as $i => $v) {
            $placeholders = Strings::GetValues(sizeof($v), '@' . $var . $i, true);
            $placeholders = str_replace('),(', ',', $placeholders);
            foreach($v as $j => $k) {
                $params[$var . $i . $j] = $k;
            }
            $data2[] = $placeholders;
        }
        $sql = str_replace('\'--' . $var . '--\'', implode(',', $data2), $sql);
    }
}