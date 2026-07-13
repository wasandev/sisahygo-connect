<?php

namespace App\Domain\ClientAccount\Models;

use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ClientAccountStatus::class,
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ClientAccountUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_account_users')
            ->withPivot(['role', 'is_active', 'invited_by', 'joined_at'])
            ->withTimestamps();
    }

    public function customerLinks(): HasMany
    {
        return $this->hasMany(ClientAccountCustomer::class);
    }

    public function capabilities(): HasMany
    {
        return $this->hasMany(ClientAccountCapability::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ClientAccountActivityLog::class);
    }
}