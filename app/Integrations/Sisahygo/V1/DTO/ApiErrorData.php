<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class ApiErrorData
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(public string $message, public ?string $code = null, public array $details = []) {}
}
