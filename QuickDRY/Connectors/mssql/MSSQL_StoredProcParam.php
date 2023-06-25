<?php

namespace QuickDRY\Connectors\mssql;

use QuickDRY\Utilities\strongType;

class MSSQL_StoredProcParam extends strongType
{
    public string $StoredProc;
    public string $Parameter_name;
    public string $Type;
    public string $Length;
    public string $Prec;
    public string $Scale;
    public string $Param_order;
    public string $Collation;

    public function __construct($row = null)
    {
        if ($row) {
            $this->FromRow($row);
        }
    }
}