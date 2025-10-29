<?php
declare(strict_types=1);

namespace QuickDRY\Interfaces;

interface IAPIUser
{
    public ?string $client_id = null {
        get;
        set;
    }
    public ?string $email_address = null {
        get;
        set;
    }

    public static function GetForClientId(string $client_id): self;

    public function validate(string $client_secret): bool;
}