<?php

namespace QuickDRY\Connectors;

use QuickDRY\Utilities\strongType;
use QuickDRYInstance\Common\ChangeLogHandler;

/**
 * Class ChangeLog
 */
class ChangeLog extends strongType
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

    /**
     * @return void
     */
    public function Save(): void
    {
        if (class_exists('QuickDRYInstance\Common\ChangeLogHandler')) {
            ChangeLogHandler::Save($this);
        }
    }
}