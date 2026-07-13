<?php

namespace App\Domain\ClientAccount\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientAccountCustomer extends Model
{
    protected $fillable = [
        'client_account_id',
        'customer_id',
        'can_send',
        'can_receive',
        'can_view_payment',
        'is_default_sender',
        'is_default_receiver',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'can_send' => 'boolean',
            'can_receive' => 'boolean',
            'can_view_payment' => 'boolean',
            'is_default_sender' => 'boolean',
            'is_default_receiver' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }
}