<?php

namespace App\Domain\ClientAccount\Models;

use App\Domain\ClientAccount\Enums\ClientCapability;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientAccountCapability extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_account_id',
        'capability',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'capability' => ClientCapability::class,
            'is_enabled' => 'boolean',
        ];
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }
}