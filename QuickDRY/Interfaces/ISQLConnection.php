<?php
declare(strict_types=1);

namespace QuickDRY\Interfaces;

use QuickDRY\Connectors\QueryExecuteResult;

interface ISQLConnection
{
    public function __construct(string $host, string $user, string $pass);

    public function TableToClass(string $database, string $table): string;

    public function CheckDatabase(mixed $db_base): bool;

    public function SetDatabase(mixed $db_base): void;

    /**
     * Runs a query and returns either:
     *  - array like ['error' => ..., 'data' => ..., ...] when $map_function is null
     *  - or mapped rows (mixed) when $map_function is provided
     */
    public function Query(mixed $sql, ?array $params = null, mixed $map_function = null): mixed;

    public function Execute(string $sql, ?array $params = null, bool $large = false): QueryExecuteResult;

    public function ExecuteWindows(string $query, bool $large = false): ?QueryExecuteResult;

    public function LastID(?int $LastID = null): ?int;

    public function GetDatabases(): array;

    public function GetTables(): array;

    public function GetTableColumns(string $table_name): array;

    public function GetTableIndexes(string $table_name): array;

    public function GetIndexes(string $table_name): mixed;

    public function GetUniqueKeys(string $table_name): mixed;

    public function GetForeignKeys(string $table_name): mixed;

    public function GetLinkedTables(string $table_name): mixed;

    public function GetPrimaryKey(string $table_name): array;

    public function GetTriggers(): array;

    public function GetDefinitions(): array;

    public function GetStoredProcs(): array;

    public function GetStoredProcParams(string $stored_proc): array;
}
