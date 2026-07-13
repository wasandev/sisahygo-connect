<?php

namespace App\Domain\ClientAccount\Enums;

enum ClientAccountRole: string
{
    case Owner = 'owner';
    case Administrator = 'administrator';
    case Operator = 'operator';
    case Viewer = 'viewer';
    case Accounting = 'accounting';

    public function canManageAccount(): bool
    {
        return in_array($this, [self::Owner, self::Administrator], true);
    }
}