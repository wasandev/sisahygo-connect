<?php

namespace App\Domain\ClientAccount\Enums;

enum ClientAccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';
}