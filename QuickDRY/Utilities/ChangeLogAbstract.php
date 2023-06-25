<?php

namespace QuickDRY\Utilities;


/**
 * Class ChangeLog
 */
abstract class ChangeLogAbstract extends strongType
{
    public string $host;
    public ?string $database;
    public ?string $table;
    public string $uuid;
    public string $changes;
    public ?int $user_id;
    public ?string $created_at;
    public string $object_type;
    public bool $is_deleted;

    abstract public function Save();
}