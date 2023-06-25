<?php

namespace QuickDRY\Utilities;


class APIHelper
{
    // https://stackoverflow.com/questions/9690448/regular-expression-to-remove-comments-from-sql-statement
    public static function removeSqlComment($sqlString): string
    {
        $regEx = [
            '~(?:".*?"|\'.*?\')(*SKIP)(*F)|--.*$~m',
            '~(?:".*?"|\'.*?\')(*SKIP)(*F)|/\*.*?\*/~s',
            '~^;?\R~m'
        ];
        return trim(preg_replace($regEx, '', $sqlString));
    }

    public static function getSQL(string $filename): string
    {
        return self::removeSqlComment(str_replace('@', ':', file_get_contents($filename)));

    }
}