<?php

namespace App\Application\Settings;

final readonly class SisahygoApiCredentialVerificationResult
{
    private function __construct(
        public bool $verified,
        public ?string $reason = null,
    ) {}

    public static function verified(): self
    {
        return new self(true);
    }

    public static function failed(string $reason): self
    {
        return new self(false, $reason);
    }
}
