<?php

namespace QuickDRY\Connectors;

use models\ChangeLogHistory;
use QuickDRY\Utilities\SimpleExcel;
use QuickDRY\Utilities\SimpleExcel_Column;
use DateTime;
use QuickDRY\Utilities\Dates;
use QuickDRY\Web\ElementID;
use ReflectionException;
use ReflectionObject;

/**
 * Class SQL_Base
 * @property bool HasChanges
 * @property array Changes
 * @property ChangeLogHistory history
 */
class SQL_Base
{
    protected static ?bool $_use_change_log = null;
    protected array $props = [];
    protected static string $table;
    protected static string $database;

    protected array $_change_log = [];
    protected ?ChangeLogHistory $_history = null;
    protected ?int $_from_db = null;

    public ?bool $HasChanges = false;

    public static bool $UseLog = false;
    public static array $Log = [];

    /**
     * @param string $property
     * @return mixed|null
     */
    public static function GetType(string $property): mixed
    {
        return static::$prop_definitions[$property]['type'] ?? null;
    }

    /**
     * @param $database_prefix
     * @param $table
     * @param $lowercase_table
     * @param $database_type_prefix
     * @return string
     */
    public static function TableToClass($database_prefix, $table, $lowercase_table, $database_type_prefix): string
    {
        if ($lowercase_table) {
            $table = strtolower($table);
        }

        $database_prefix = strtolower($database_prefix);
        $t = explode('_', $database_prefix . '_' . $table);

        $type = '';
        foreach ($t as $w) {
            $type .= preg_replace('/[^a-z0-9]/i', '', ucfirst($w));
        }
        $type .= 'Class';
        if (is_numeric($type[0]))
            $type = 'i' . $type;
        return $database_type_prefix . '_' . $type;
    }

    /**
     * @param $database_prefix
     * @param $table
     * @param $lowercase_table
     * @param $database_type_prefix
     * @return string
     */
    public static function StoredProcToClass($database_prefix, $table, $lowercase_table, $database_type_prefix): string
    {
        // note: we need to leave underscores in
        // underscores are valid PHP and a naming choice for developers
        if ($lowercase_table) {
            $table = strtolower($table);
        }

        $database_prefix = strtolower($database_prefix);
        $t = explode('_', $database_prefix . '_' . $table);

        $type = [];
        foreach ($t as $w) {
            $type [] = preg_replace('/[^a-z0-9]/i', '', ucfirst($w));
        }
        $type = implode('_', $type);
        $type .= 'Class';
        if (is_numeric($type[0])) {
            $type = 'i' . $type;
        }
        return $database_type_prefix . '_' . $type;
    }

    /**
     * @param string $table
     * @param bool $lowercase_table
     * @return string
     */
    public static function TableToNiceName(string $table, bool $lowercase_table): string
    {
        if ($lowercase_table) {
            $table = strtolower($table);
        }

        $t = explode('_', $table);

        $type = '';
        foreach ($t as $w) {
            $type .= preg_replace('/[^a-z0-9]/i', '', ucfirst($w));
        }

        if (is_numeric($type[0])) {
            $type = 'i' . $type;
        }
        return $type;
    }

    /**
     *
     */
    public function __construct()
    {
        $this->props = self::GetVars();
    }

    /**
     * @param string $column_name
     * @return string
     */
    private static function ColumnNameToNiceName(string $column_name): string
    {
        return $column_name;
    }

    /**
     * @return bool
     */
    public function HasChangeLog(): bool
    {
        if (defined('DISABLE_CHANGE_LOG') && DISABLE_CHANGE_LOG) {
            return false;
        }
        if (strcasecmp(static::$table, 'change_log') == 0) { // don't change log the change log
            return false;
        }
        if (strcasecmp(static::$table, 'changelog') == 0) { // don't change log the change log
            return false;
        }

        if (!sizeof($this->_change_log)) { // don't log when nothing changed
            return false;
        }

        return !isset(static::$_use_change_log) || static::$_use_change_log;
    }

    /**
     * @param $var
     */
    public function Clear($var): void
    {
        $this->{'_' . $var} = null;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset(static::$prop_definitions[$name]);
    }

    /**
     * @param $c
     * @throws ReflectionException
     */
    public static function GenProps($c): void
    {
        $o = new $c();
        $object = new ReflectionObject($o);
        $method = $object->getMethod('__get');
        $declaringClass = $method->getDeclaringClass();
        $filename = $declaringClass->getFileName();

        $props = [];

        $fp = fopen($filename, 'r');
        $code = fread($fp, filesize($filename));
        fclose($fp);
        $orig_code = $code;
        $pattern = '/@property\s+(.*?)[\r\n]/si';
        $matches = [];
        preg_match_all($pattern, $code, $matches);

        foreach ($matches[1] as $var) {
            $parts = explode(' ', $var);
            $props[$parts[sizeof($parts) - 1]] = trim(str_replace($parts[sizeof($parts) - 1], '', $var));
        }


        $pattern = '/public function __get\(\$name\)(.*?)\n\t}/si';
        $matches = [];
        preg_match($pattern, $code, $matches);
        if (isset($matches[1])) {
            $code = $matches[1];
            $pattern = '/case \'(.*?)\':/si';
            $matches = [];
            preg_match_all($pattern, $code, $matches);
            if (isset($matches[1])) {
                $code = $matches[1];
                foreach ($code as $get) {
                    if (!isset($props[$get]))
                        $props[$get] = 'undefined';
                }
            }
        }
        ksort($props);
        $php = '<?php

/**
 * @author Ben Kucenski
 * QuickDRY Framework ' . date('Y') . '
 *
';
        foreach ($props as $var => $type)
            $php .= ' * @property ' . $type . ' ' . $var . "\r\n";

        $php .= ' */';
        $code = preg_replace('/\<\?php\s+\/\*\*.*?\*\//si', '', $orig_code);
        $code = $php . $code;

        $fp = fopen($filename, 'w');
        fwrite($fp, $code);
        fclose($fp);
    }

    /**
     * @param string $name
     * @return array|mixed|ChangeLogHistory[]|null
     */
    public function __get(string $name)
    {
        return $this->GetProperty($name);
    }

    /**
     * @return array
     */
    public function Changes(): array
    {
        return $this->_change_log;
    }

    /**
     * @return ChangeLogHistory|null
     */
    public function History(): ?ChangeLogHistory
    {
        if (is_null($this->_history)) {
            $this->_history = $this->_history();
        }
        return $this->_history;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function __set(string $name, mixed $value)
    {
        $this->SetProperty($name, $value);
        return $value;
    }

    /**
     * @return ChangeLogHistory[]|null
     */
    private function _history(): ?array
    {
        return ChangeLogHistory::GetHistory($this);
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public static function check_props($key): bool
    {
        return isset(static::$prop_definitions[$key]);
    }

    /**
     * @return array
     */
    public static function GetColumns(): array
    {
        $cols = [];
        foreach (static::$prop_definitions as $name => $def) {
            $cols[] = $name;
        }
        return $cols;
    }

    /**
     * @param array|null $where
     *
     * @return mixed
     */
    public static function Get(?array $where = null): mixed
    {
        return static::_Get($where);
    }

    /**
     * @param array|null $where
     * @param array|null $order_by
     * @param int|null $limit
     *
     * @return array|null
     */
    public static function GetAll(?array $where = null, ?array $order_by = null, ?int $limit = null): ?array
    {
        if (!is_null($order_by) && !is_array($order_by)) {
            Exception('QuickDRY Error: GetAll $order_by must be an assoc array ["col"=>"asc,desc",...]', true);
        }

        if (!is_null($where) && !is_array($where)) {
            Exception('QuickDRY Error: GetAll $where must be an assoc array ["col"=>"val",...]', true);
        }

        if (!is_null($order_by)) {
            foreach ($order_by as $col => $dir) {
                if (!self::check_props(trim($col))) {
                    Exception('QuickDRY Error: ' . $col . ' is not a valid order by column for ' . get_called_class());
                    return null;
                }
            }
        }

        if (is_array($where) && sizeof($where) == 0) {
            $where = null;
        }

        if (!is_null($where)) {
            foreach ($where as $col => $dir) {
                $col = str_replace('+', '', $col);
                if (!self::check_props(trim($col))) {
                    Exception('QuickDRY Error: ' . $col . ' is not a valid where column for ' . get_called_class());
                    return null;
                }
            }
        }

        return static::_GetAll($where, $order_by, $limit);
    }

    /**
     * @param array|null $where
     *
     * @return int
     */
    public static function GetCount(?array $where = null): int
    {
        return static::_GetCount($where);
    }

    /**
     * @param array|null $where
     * @param array|null $order_by
     * @param int $page
     * @param int $per_page
     * @param array|null $left_join
     * @param int|null $limit
     *
     * @return null
     */
    public static function GetAllPaginated(
        ?array $where = null,
        ?array $order_by = null,
        int    $page = 0,
        int    $per_page = 0,
        ?array $left_join = null,
        ?int   $limit = null
    )
    {
        return static::_GetAllPaginated($where, $order_by, $page, $per_page, $left_join, $limit);
    }

    /**
     * @return array
     */
    public static function GetVars(): array
    {
        return array_map(function ($def) {
            return null;
        }, static::$prop_definitions);
    }

    /**
     * @param string|null $name
     * @return mixed|null
     */
    protected function GetProperty(?string $name = null): mixed
    {
        if (array_key_exists($name, $this->props)) {
            return static::StrongType($name, $this->props[$name]);
        }
        Exception($name . ' is not a property of ' . get_class($this) . "\r\n");
        return null;
    }

    /**
     * @return void
     */
    public function ClearProps(): void
    {
        foreach ($this->props as $n => $v) {
            $this->props[$n] = null;
        }
    }

    /**
     * @param $arr
     * @param bool $null_string
     * @param null $prop_definitions
     * @return mixed
     */
    public static function _ToArray($arr, bool $null_string = false, $prop_definitions = null): mixed
    {
        // Cleans up an array of values so that it can ben
        // put into a database object and be saved into the database
        foreach ($arr as $k => $v) {
            if (is_object($v) && get_class($v) === 'DateTime') {
                $arr[$k] = isset($prop_definitions[$k]['type']) && strcasecmp($prop_definitions[$k]['type'], 'date') == 0 ? Dates::Datestamp($v) : Dates::Timestamp($v);
            }
            if ($null_string && is_null($v)) {
                $arr[$k] = 'null';
            }
        }
        return $arr;
    }


    /**
     * @return array
     */
    public function toArray(): array
    {
        return self::_ToArray($this->props, true, static::$prop_definitions);
    }

    /**
     * @return array
     */
    public function ToJSONArray(): array
    {
        return self::_ToArray($this->props, false, static::$prop_definitions);
    }

    /**
     * @param $name
     * @param $value
     * @return mixed|string|null
     */
    protected function SetProperty($name, $value): mixed
    {
        if (!array_key_exists($name, $this->props)) {
            Exception('QuickDRY Error: ' . $name . ' is not a property of ' . get_class($this) . "\r\n");
        }

        if (is_array($value)) {
            Exception(['QuickDRY Error: Value assigned to property cannot be an array.', $value]);
        }

        if (is_object($value)) {
            if ($value instanceof DateTime) {
                $value = Dates::Timestamp($value);
            } else {
                Exception(['QuickDRY Error: Value assigned to property cannot be an object.', $value]);
            }
        }

        if ($value && strcasecmp($value, 'null') == 0) {
            $value = null;
        }

        $old_val = static::StrongType($name, $this->props[$name]);
        $new_val = static::StrongType($name, $value);

        if ($old_val instanceof DateTime) {
            $old_val = Dates::Timestamp($old_val);
        }

        if ($new_val instanceof DateTime) {
            $new_val = Dates::Timestamp($new_val);
        }

        $changed = false;
        $change_reason = '';
        if (is_null($old_val) && !is_null($new_val)) {
            $changed = true;
            $change_reason = 'old = null, new not null';
        } elseif (!is_null($old_val) && is_null($new_val)) {
            $changed = true;
            $change_reason = 'old not null, new null';
        } elseif (!is_null($old_val) && !is_null($new_val) && strlen($old_val) != strlen($new_val)) {
            $changed = true;
            $change_reason = '"' . $new_val . '" "' . $old_val . '" ' . strlen($new_val) . ' ' . strlen($old_val) . ': strcmp = ' . strcmp($new_val, $old_val);
        } elseif (is_numeric($old_val) && is_numeric($new_val)) {

            if (abs($new_val - $old_val) > 0.000000001) {
                /**
                 * [new] => 5270.6709775679 -- PHP thinks these two numbers are different, so we need to compare to a very small number, not equal
                 * [old] => 5270.6709775679
                 * // from PHP's manual "never trust floating number results to the last digit, and do not compare floating point numbers directly for equality" - https://www.php.net/manual/en/language.types.float.php
                 */
                $changed = true;
                $change_reason = 'diff = ' . abs($new_val - $old_val);
            }
        } elseif (!is_null($old_val) && !is_null($new_val) && strcmp($new_val, $old_val) != 0) {
            $changed = true;
            $change_reason = '"' . $new_val . '" "' . $old_val . '" ' . strlen($new_val) . ' ' . strlen($old_val) . ': strcmp = ' . strcmp($new_val, $old_val);
        }


        if ($changed) {
            if (is_null($new_val)) {
                $new_val = 'null';
            }
            if (is_null($old_val)) {
                $old_val = 'null';
            }
            $this->_change_log[$name] = ['new' => $new_val, 'old' => $old_val, 'reason' => $change_reason];
            $this->HasChanges = true;
        }

        $this->props[$name] = $value;
        return $value;
    }

    /**
     * @param string|null $sort_by
     * @param string|null $dir
     * @param bool $modify
     * @param array $add
     * @param array $ignore
     * @param string $add_params
     * @param bool $sortable
     * @param array $column_order
     *
     * @return string
     */
    public static function GetHeader(
        ?string $sort_by = '',
        ?string $dir = '',
        bool    $modify = false,
        array   $add = [],
        array   $ignore = [],
        string  $add_params = '',
        bool    $sortable = true,
        array   $column_order = []): string
    {
        return static::_GetHeader(static::$prop_definitions, $sort_by, $dir, $modify, $add, $ignore, $add_params, $sortable, $column_order);
    }

    /**
     * @param bool $modify
     * @param array $add
     * @param array $ignore
     * @param bool $sortable
     * @param array $column_order
     *
     * @return string
     */
    public static function GetBareHeader(
        bool  $modify = false,
        array $add = [],
        array $ignore = [],
        bool  $sortable = true,
        array $column_order = []): string
    {
        return static::_GetBareHeader(static::$prop_definitions, $modify, $add, $ignore, $sortable, $column_order);
    }

    /**
     * @param array $props
     * @param string|null $sort_by
     * @param string|null $dir
     * @param bool $modify
     * @param array $add
     * @param array $ignore
     * @param string $add_params
     * @param bool $sortable
     * @param array $column_order
     *
     * @return string
     */
    protected static function _GetHeader(
        array   $props,
        ?string $sort_by,
        ?string $dir,
        bool    $modify = false,
        array   $add = [],
        array   $ignore = [],
        string  $add_params = '',
        bool    $sortable = true,
        array   $column_order = []): string
    {
        $not_dir = $dir == 'asc' ? 'desc' : 'asc';
        $arrow = $dir == 'asc' ? '&uarr;' : '&darr;';

        $res = '';

        $columns = [];
        if (!$add) {
            $add = [];
        }
        if (!$ignore) {
            $ignore = [];
        }

        foreach ($props as $name => $info) {
            if (!in_array($name, $ignore)) {
                if ($sortable) {
                    $columns[$name] = '<th><a href="' . CURRENT_PAGE . '?sort_by=' . $name . '&dir=' . (strcasecmp($sort_by, $name) == 0 ? $not_dir : 'asc') . '&per_page=' . PER_PAGE . '&' . $add_params . '">' . static::ColumnNameToNiceName($name) . '</a>' . (strcasecmp($sort_by, $name) == 0 ? ' ' . $arrow : '') . '</th>';
                } else {
                    $columns[$name] = '<th>' . static::ColumnNameToNiceName($name) . '</th>';
                }
            }
        }

        if (sizeof($add) > 0) {
            foreach ($add as $header => $value) {
                if (is_array($value) && $sortable) {
                    $columns[$value['value']] = '<th><a href="' . CURRENT_PAGE . '?sort_by=' . $value['sort_by'] . '&dir=' . ($sort_by == $value['sort_by'] ? $not_dir : 'asc') . '&per_page=' . PER_PAGE . '&' . $add_params . '">' . $header . '</a>' . ($sort_by == $value['sort_by'] ? ' ' . $arrow : '') . '</th>';
                } elseif (is_array($value)) {
                    $columns[$value['value']] = '<th>' . $header . '</th>';
                } else {
                    $columns[$value] = '<th>' . $header . '</th>';
                }
            }
        }

        if (sizeof($column_order) > 0) {
            foreach ($column_order as $order)
                $res .= $columns[$order];
        } else
            $res = '<thead><tr>' . implode('', $columns);

        if ($modify)
            $res .= '<th>Action</th>';

        return $res . '</tr></thead>';
    }

    /**
     * @param array $props
     * @param bool $modify
     * @param array $add
     * @param array $ignore
     * @param bool $sortable
     * @param array $column_order
     *
     * @return string
     */
    protected static function _GetBareHeader(array $props, bool $modify = false, array $add = [], array $ignore = [], bool $sortable = true, array $column_order = []): string
    {
        $res = '';
        $columns = [];

        foreach ($props as $name => $info)
            if (!in_array($name, $ignore))
                if ($sortable)
                    $columns[$name] = '<th>' . $info['display'] . '</th>' . "\r\n";
                else
                    $columns[$name] = '<th>' . $info['display'] . '</th>' . "\r\n";

        if (sizeof($add) > 0)
            foreach ($add as $header => $value) {
                if (is_array($value) && $sortable) {
                    $columns[$value['value']] = '<th>' . $header . '</th>' . "\r\n";
                } elseif (is_array($value)) {
                    $columns[$value['value']] = '<th>' . $header . '</th>' . "\r\n";
                } else {
                    $columns[$value] = '<th>' . $header . '</th>' . "\r\n";
                }

            }

        if (sizeof($column_order) > 0) {
            foreach ($column_order as $order)
                $res .= $columns[$order];
        } else
            $res = '<thead><tr>' . implode('', $columns);

        if ($modify)
            $res .= '<th>Action</th>' . "\r\n";

        return $res . '</tr></thead>';
    }

    /**
     * @param string $column_name
     * @param $value
     * @param bool $force_value
     * @return mixed|null
     */
    public function ValueToNiceValue(string $column_name, $value = null, bool $force_value = false): mixed
    {
        return $value;
    }

    /**
     * @param bool $modify
     * @param array $swap
     * @param array $add
     * @param array $ignore
     * @param string $custom_link
     * @param array $column_order
     *
     * @return string
     */
    public function ToRow(
        bool   $modify = false,
        array  $swap = [],
        array  $add = [],
        array  $ignore = [],
        string $custom_link = '',
        array  $column_order = []): string
    {
        $res = '';

        $columns = [];
        if (is_null($swap)) {
            $swap = [];
        }

        if (is_null($add)) {
            $add = [];
        }

        if (is_null($ignore)) {
            $ignore = [];
        }

        foreach ($this->props as $name => $value) {
            if (in_array($name, $ignore)) {
                continue;
            }


            if (array_key_exists($name, $swap)) {
                if(method_exists($this, $swap[$name])) {
                    $value = $this->{$swap[$name]}();
                } else {
                    $value = $this->{$swap[$name]};
                }
            } else {
                $value = $this->ValueToNiceValue($name, $this->$name);
            }


            if (!is_object($value)) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $columns[$name] = '<td>' . $value . '</td>';
            } elseif ($value instanceof DateTime) {
                $columns[$name] = '<td>' . Dates::Timestamp($value) . '</td>';
            } else {
                $columns[$name] = '<td><i>Object: </i>' . get_class($value) . '</td>';
            }
        }

        if (sizeof($add) > 0)
            foreach ($add as $value) {
                if (is_array($value)) {
                    $name = $value['value'];
                    $value = $this->{$value['value']};
                } else {
                    $name = $value;
                    $value = $this->$value;
                }
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                $columns[$name] = '<td>' . $value . '</td>';
            }
        if (sizeof($column_order) > 0) {
            foreach ($column_order as $name) {
                $res .= $columns[$name];
            }
        } else {
            $res = '<tr>' . implode('', $columns);
        }

        if ($modify) {
            $res .= '
   			<td class="data_text">
   				<a href="#"  onclick="' . get_base_class($this) . '.Load(' . $this->{static::$_primary[0]} . ')"><i class="fa fa-edit"></i></a>
   			</td>
   			';
        }
        if (is_array($custom_link)) {
            $res .= '<td class="data_text"><a href="' . $custom_link['page'] . '?' . static::$_primary[0] . '=' . $this->{static::$_primary[0]} . '">' . $custom_link['title'] . '</a></td>';
        }
        return $res . '</tr>';
    }

    // $trigger_change_log true is for FORM data to pass changes through set_property to trigger change log.
    // when coming from database, don't trigger change log
    // strict will halt when the hash passed in contains columns not in the table definition
    /**
     * @param array $row
     * @param bool $trigger_change_log
     * @param bool $strict
     */
    public function FromRow(
        array $row,
        bool  $trigger_change_log = false,
        bool  $strict = false): void
    {
        global $User;

        if (is_null($trigger_change_log)) {
            $trigger_change_log = false;
        }
        if (is_null($strict)) {
            $strict = false;
        }

        $this->_from_db = true;
        $missing = [];

        foreach ($row as $name => $value) {
            if (property_exists(static::class, $name)) {
                $this->$name = $value;
                continue;
            }

            if (!isset(static::$prop_definitions[$name])) {
                if ($strict) {
                    $missing[$name] = $value;
                }
                continue;
            }
            if (!is_null($User)) {
                if (static::$prop_definitions[$name]['type'] === 'datetime') {
                    if (!$value) {
                        $value = null;
                    } elseif (strtotime($value)) {
                        $value = Dates::Timestamp($value);
                    }
                }
            }

            if ($trigger_change_log) {
                $this->$name = $value;
            } else {
                $this->props[$name] = isset($row[$name]) ? $value : (null);
            }
        }

        if ($strict && sizeof($missing)) {
            Exception([
                'error'   => 'QuickDRY Error: Missing Columns',
                'Object'  => get_class($this),
                'Columns' => $missing,
                'Values'  => $row,
            ]);
        }
    }

    /**
     * @return QueryExecuteResult
     */
    public function Save(): QueryExecuteResult
    {
        return new QueryExecuteResult();
    }

    /**
     * @param array $req
     * @param bool $save
     * @param bool $keep_existing_values
     * @return QueryExecuteResult
     */
    public function FromRequest(array $req, bool $save = true, bool $keep_existing_values = true): QueryExecuteResult
    {
        foreach ($this->props as $name => $value) {
            $this->$name = array_key_exists($name, $req) ? $req[$name] : (!$keep_existing_values ? null : $this->props[$name]);
        }

        if ($save) {
            return $this->Save();
        }
        return new QueryExecuteResult();
    }

    /**
     * @param self $item
     * @return array
     */
    public static function getHeaders(self $item): array
    {
        $cols = array_keys(get_object_vars($item));
        foreach ($cols as $i => $col) {
            if ($col[0] === '_') {
                unset($cols[$i]);
            }
        }
        return $cols;
    }

    /**
     * @param self[] $items
     * @return SimpleExcel|null
     */
    public static function toExcel(array $items): ?SimpleExcel
    {
        if (!sizeof($items)) {
            return null;
        }
        $cols = array_keys($items[0]->toArray());

        $se = new SimpleExcel();
        $se->Report = $items;
        $se->Title = static::class;
        $se->Columns = [];
        foreach ($cols as $col) {
            $se->Columns[$col] = new SimpleExcel_Column($col, $col);
        }

        return $se;
    }

    /**
     * @return string
     */
    public function PrimaryKey(): string
    {
        $key = [];
        foreach (static::$_primary as $col) {
            $key [] = $col . ':' . $this->$col;
        }
        return implode('::', $key);
    }

    // occasionally we end up reusing a selection over and over on the same page
    protected static array $_select_cache = [];

    /**
     * @param        $selected
     * @param ElementID $id
     * @param        $value
     * @param        $order_by
     * @param string $display
     * @param null $where
     * @param bool $show_none
     * @param string $onchange
     * @return string
     */
    protected static function _Select(
        $selected,
        ElementID $id,
        $value,
        $order_by,
        string $display = '',
        $where = null,
        bool $show_none = true,
        string $onchange = ''): string
    {
        if (is_null($show_none)) {
            $show_none = true;
        }

        $type = get_called_class();


        $hash = md5(serialize([$type, $order_by, $where]));
        if (!isset(static::$_select_cache[$hash])) {
            if (!method_exists($type, 'GetAll')) {
                exit($type . '::GetAll');
            }

            $items = $type::GetAll($where, is_array($order_by) ? $order_by : [$order_by => 'asc']);
            static::$_select_cache[$hash] = $items;
        } else
            $items = static::$_select_cache[$hash];

        return self::_SelectItems($items, $selected, $id, $value, $order_by, $display, $show_none, $onchange);
    }

    /**
     * @param        $items
     * @param        $selected
     * @param ElementID $id
     * @param        $value
     * @param        $order_by
     * @param string $display
     * @param bool $show_none
     * @param string $onchange
     * @return string
     */
    protected static function _SelectItems(
        $items,
        $selected,
        ElementID $id,
        $value,
        $order_by,
        string $display = '',
        bool $show_none = true,
        string $onchange = ''): string
    {
        if (is_null($show_none)) {
            $show_none = true;
        }

        $name = $id->name;
        $id = $id->id;

        if ($display == '') {
            if (is_array($order_by)) {
                $display = array_keys($order_by)[0];
            } else {
                $display = $order_by;
            }
        }

        $select = '';

        if (is_array($selected)) {
            $select .= '<select class="form-control" onchange="' . $onchange . '" multiple size="' . (sizeof($items) + 1 <= 10 ? sizeof($items) + 1 : 10) . '" id="' . $id . '" name="' . $name . '[]">';
        } else {
            $select .= '<select class="form-control" onchange="' . $onchange . '"  id="' . $id . '"name="' . $name . '">';
        }

        if ($show_none) {
            $select .= '<option value="null">' . (is_bool($show_none) ? 'None' : $show_none) . '</option>' . "\r\n";
        }

        if (sizeof($items) > 0) {
            if (is_array($selected)) {
                foreach ($items as $item)
                    if (in_array($item->$value, $selected))
                        $select .= '<option selected="selected" value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
                    else
                        $select .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
            } else {
                foreach ($items as $item)
                    if ($selected != $item->$value) // needs to be a loose comparison otherwise it doesn't work with numbers
                        $select .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
                    else
                        $select .= '<option selected="selected" value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
            }
        }
        $select .= '</select>';
        return $select;
    }
}