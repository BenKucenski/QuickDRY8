<?php


namespace QuickDRY\Utilities;


/**
 *
 */
abstract class ChangeLogClass
{
    public array $changes_list;
    public string $created_at;

    /**
     * @return mixed
     */
    abstract public function GetUser(): mixed;
}