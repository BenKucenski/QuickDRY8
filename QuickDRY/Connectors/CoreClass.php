<?php

namespace QuickDRY\Connectors;

/**
 * Class CoreClass
 */
abstract class CoreClass
{
    /**
     * @param $req
     * @param bool $save
     * @param bool $keep_existing_values
     * @return bool|array
     */
    abstract public function FromRequest(&$req, bool $save = true, bool $keep_existing_values = true);


    /**
     * @return mixed
     */
    abstract public function IsReferenced();

    /**
     * @param $user
     * @return mixed
     */
    abstract public function VisibleTo(&$user);

    /**
     * @param $user
     * @return mixed
     */
    abstract public function CanDelete(&$user);

    /**
     * @param $column_name
     * @return mixed
     */
    abstract public function ColumnNameToNiceName($column_name);

    /**
     * @param $column_name
     * @param $value
     * @return mixed
     */
    abstract public function ValueToNiceValue($column_name, $value);

    /**
     * @param $column_name
     * @return mixed
     */
    abstract public function IgnoreColumn($column_name);
}