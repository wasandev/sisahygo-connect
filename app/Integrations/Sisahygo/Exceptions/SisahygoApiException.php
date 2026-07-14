<?php

namespace App\Integrations\Sisahygo\Exceptions;

use RuntimeException;
use Throwable;

class SisahygoApiException extends RuntimeException
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(string $message, public readonly ?int $status = null, public readonly array $context = [], ?Throwable $previous = null)
    {
        parent::__construct($message, $status ?? 0, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function safeContext(): array
    {
        return $this->context;
    }
}