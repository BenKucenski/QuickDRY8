<?php

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
     * @return string
     */
    public static function getSQL(string $filename): string
    {
        return self::removeSqlComment(str_replace('@', ':', file_get_contents($filename)));

    }

    public static function ApplyArray(string $var, array $data, string &$sql, array &$params): void
    {
        $placeholders = Strings::GetPlaceholders(sizeof($data), '@' . $var, true);
        $sql = str_replace('\'--' . $var . '--\'', $placeholders, $sql);
        foreach($data as $i => $k) {
            $params[$var . $i] = $k;
        }
    }

    public static function ApplyValues(string $var, array $data, string &$sql, array &$params): void
    {
        $placeholders = Strings::GetValues(sizeof($data), '@' . $var, true);
        $sql = str_replace('\'--' . $var . '--\'', $placeholders, $sql);
        foreach($data as $i => $k) {
            $params[$var . $i] = $k;
        }
    }
}