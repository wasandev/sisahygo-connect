<?php

namespace App\Domain\Sisahygo\Models;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Enums\SisahygoCredentialStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SisahygoApiCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_account_id',
        'environment',
        'name',
        'encrypted_api_key',
        'key_fingerprint',
        'status',
        'active_slot',
        'last_used_at',
        'revoked_at',
        'rotated_from_id',
        'created_by',
    ];

    protected $hidden = [
        'encrypted_api_key',
    ];

    protected function casts(): array
    {
        return [
            'environment' => SisahygoApiEnvironment::class,
            'status' => SisahygoCredentialStatus::class,
            'encrypted_api_key' => 'encrypted',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rotatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rotated_from_id');
    }

    public function isActive(): bool
    {
        return $this->status === SisahygoCredentialStatus::Active
            && $this->revoked_at === null;
    }

    public function apiKey(): string
    {
        return (string) $this->encrypted_api_key;
    }

    public static function fingerprint(string $apiKey): string
    {
        return hash('sha256', $apiKey);
    }
}