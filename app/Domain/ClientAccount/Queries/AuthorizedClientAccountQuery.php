<?php

namespace App\Domain\ClientAccount\Queries;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AuthorizedClientAccountQuery
{
    public function forUser(User $user): Builder
    {
        return ClientAccount::query()
            ->where('status', 'active')
            ->whereHas('memberships', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('is_active', true)
            );
    }
}