<?php

namespace App\Domain\ClientAccount\Enums;

enum CurrentClientAccountResolutionStatus: string
{
    case NoAccounts = 'no_accounts';
    case Selected = 'selected';
    case SelectionRequired = 'selection_required';
    case InvalidSelection = 'invalid_selection';
}
