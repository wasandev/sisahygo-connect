<?php

namespace App\Application\Settings;

final readonly class ClientAccountSetupState
{
    /**
     * @param  array<int, array{key: string, label: string, status: string}>  $steps
     * @param  array<string, mixed>  $connectivity
     */
    public function __construct(
        public array $steps,
        public array $connectivity,
        public bool $canManageSettings,
        public ?string $clientAccountName,
        public ?string $nextActionKey,
        public ?string $environment,
        public ?string $credentialStatus,
        public ?string $fingerprint,
        public ?string $lastUsedAt,
    ) {}

    public function isReady(): bool
    {
        return collect($this->steps)->every(fn (array $step): bool => $step['status'] === 'complete');
    }

    public function completedSteps(): int
    {
        return collect($this->steps)->where('status', 'complete')->count();
    }

    public function totalSteps(): int
    {
        return count($this->steps);
    }

    public function shouldShowCredentialEntry(): bool
    {
        return $this->canManageSettings && ! $this->isReady();
    }

    public function shouldShowAdministratorGuidance(): bool
    {
        return ! $this->canManageSettings && ! $this->isReady();
    }
}
