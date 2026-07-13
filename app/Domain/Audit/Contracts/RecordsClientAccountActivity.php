<?php

namespace App\Domain\Audit\Contracts;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface RecordsClientAccountActivity
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(ClientAccount $clientAccount, string $event, ?User $user = null, ?Model $subject = null, array $metadata = []): void;
}