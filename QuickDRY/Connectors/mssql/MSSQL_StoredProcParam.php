<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mssql;

use QuickDRY\Utilities\strongType;

/**
 *
 */
class MSSQL_StoredProcParam extends strongType
{
    public ?string $StoredProc = null;
    public ?string $Parameter_name = null;
    public ?string $Type = null;
    public ?string $Length = null;
    public ?string $Prec = null;
    public ?string $Scale = null;
    public ?string $Param_order = null;
    public ?string $Collation = null;

    /**
     * @param $row
     */
    public function __construct($row = null)
    {
        if ($row) {
            $this->fromData($row);
        }
    }
}