<?php

namespace App\Domain\Onboarding\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'province',
        'website',
        'number_of_branches',
        'additional_notes',
        'status',
        'invitation_token',
        'submitted_at',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'number_of_branches' => 'integer',
            'submitted_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }
}
