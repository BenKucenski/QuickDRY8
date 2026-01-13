<?php
declare(strict_types=1);

namespace QuickDRY\Interfaces;

use QuickDRY\Connectors\QueryExecuteResult;
use QuickDRY\Connectors\SQL_Query;
use QuickDRY\Web\ElementID;

interface ISQLCore
{
    // ---- UI / display helpers ----
    public static function Select(?string $selected = null, ?ElementID $id = null): string;

    public static function ColumnNameToNiceName(string $column_name): string;

    public function ValueToNiceValue(string $column_name, mixed $value = null, bool $force_value = false): mixed;

    public static function IgnoreColumn(string $column_name): bool;

    // ---- persistence (instance) ----
    public function Insert(bool $return_query = false): SQL_Query|QueryExecuteResult|null;

    public function Update(bool $return_query = false): SQL_Query|QueryExecuteResult|null;

    public function Remove(): QueryExecuteResult;

    public function CanDelete(): bool;

    // ---- misc business helper ----
    public static function Suggest(mixed $search, ICurrentUser $user): array;

    // ---- schema / metadata ----
    public static function GetTables(): array;

    public static function GetDatabases(): array;

    public static function SetDatabase(string $db_base): void;

    public static function GetTableColumns(string $table_name): array;

    public static function GetTableIndexes(string $table_name): array;

    public static function GetUniqueKeys(string $table_name): mixed;

    public static function GetIndexes(string $table_name): mixed;

    public static function GetForeignKeys(string $table_name): mixed;

    public static function GetLinkedTables(string $table_name): mixed;

    public static function GetTriggers(): array;

    public static function GetStoredProcs(): array;

    public static function GetDefinitions(): array;

    public static function GetStoredProcParams(mixed $stored_proc): array;

    public static function GetPrimaryKey(string $table_name): array;

    // ---- query execution ----
    public static function Execute(string $sql, ?array $params = null, bool $large = false): ?QueryExecuteResult;

    public static function QueryMap(string $sql, ?array $params, callable $map_function): mixed;

    public static function Query(string $sql, ?array $params = null, bool $objects_only = false, ?callable $map_function = null): array;

    public static function GUID(): string;

    public static function getConnection(): ?ISQLConnection;

    public static function LastID(?int $LastID = null): int|string|null;
}
