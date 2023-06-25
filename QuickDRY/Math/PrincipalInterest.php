<?php

namespace QuickDRY\Math;

use QuickDRY\Utilities\strongType;

/**
 * Class PrincipalInterest
 */
class PrincipalInterest extends strongType
{
    public ?int $month = null;
    public ?float $principal = null;
    public ?float $interest = null;
    public ?float $principal_payment = null;
    public ?float $interest_payment = null;
    public ?float $rollover = null;

    public ?array $table = null;
}