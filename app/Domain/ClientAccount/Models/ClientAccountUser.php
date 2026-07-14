<?php

namespace App\Domain\ClientAccount\Models;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientAccountUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_account_id',
        'user_id',
        'role',
        'is_active',
        'invited_by',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => ClientAccountRole::class,
            'is_active' => 'boolean',
            'joined_at' => 'datetime',
        ];
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}