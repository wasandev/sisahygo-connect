<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Contracts\RecordsClientAccountActivity;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ClientAccountActivityLogger implements RecordsClientAccountActivity
{
    public function record(ClientAccount $clientAccount, string $event, ?User $user = null, ?Model $subject = null, array $metadata = []): void
    {
        $clientAccount->activityLogs()->create([
            'user_id' => $user?->id,
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}