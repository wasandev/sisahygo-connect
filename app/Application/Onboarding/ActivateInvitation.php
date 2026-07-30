<?php

namespace App\Application\Onboarding;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Integrations\Sisahygo\V1\DTO\InvitationActivationData;
use App\Integrations\Sisahygo\V1\DTO\InvitationPreviewData;
use App\Integrations\Sisahygo\V1\Endpoints\InvitationsEndpoint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ActivateInvitation
{
    public function __construct(private readonly InvitationsEndpoint $invitations) {}

    public function preview(string $token): InvitationPreviewData
    {
        $preview = $this->invitations->show($token);

        if (! $preview->isUsable()) {
            throw ValidationException::withMessages([
                'invitation' => [__('onboarding.invitation.errors.'.$this->statusKey($preview->status))],
            ]);
        }

        return $preview;
    }

    /** @param array<string, mixed> $input */
    public function activate(string $token, array $input): InvitationActivationResult
    {
        $preview = $this->preview($token);

        Validator::make($input, [
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], __('onboarding.validation.attributes'))->validate();

        if (isset($input['email']) && strcasecmp((string) $input['email'], $preview->email) !== 0) {
            throw ValidationException::withMessages([
                'email' => [__('onboarding.invitation.errors.email_locked')],
            ]);
        }

        $activation = $this->invitations->activate($token, [
            'email' => $preview->email,
        ]);

        return DB::transaction(function () use ($activation, $input): InvitationActivationResult {
            $account = $this->upsertClientAccount($activation);
            $user = $this->upsertUser($activation, (string) $input['password']);

            ClientAccountUser::query()->firstOrCreate([
                'client_account_id' => $account->id,
                'user_id' => $user->id,
            ], [
                'role' => $this->role($activation->userRole)->value,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            $this->upsertCustomerMappings($account, $activation);
            $this->upsertCapabilities($account, $activation);

            return new InvitationActivationResult($user, $account);
        });
    }

    private function upsertClientAccount(InvitationActivationData $activation): ClientAccount
    {
        $account = ClientAccount::query()->firstOrNew(['code' => $activation->clientAccountCode]);
        $account->fill([
            'name' => $activation->clientAccountName,
            'status' => ClientAccountStatus::Active->value,
        ]);
        $account->save();

        return $account;
    }

    private function upsertUser(InvitationActivationData $activation, string $password): User
    {
        $user = User::query()->firstOrNew(['email' => $activation->email]);

        if ($user->exists) {
            return $user;
        }

        $user->fill([
            'name' => $activation->contactName ?: $activation->companyName,
            'password' => Hash::make($password),
        ]);

        if ($activation->emailVerifiedByInvitation) {
            $user->email_verified_at = now();
        }

        $user->save();

        return $user;
    }

    private function upsertCustomerMappings(ClientAccount $account, InvitationActivationData $activation): void
    {
        foreach ($activation->customerMappings as $mapping) {
            if (! is_array($mapping)) {
                continue;
            }

            $customerId = $this->mappingCustomerId($mapping);

            if ($customerId === null) {
                continue;
            }

            $role = is_string($mapping['role'] ?? null) ? $mapping['role'] : 'both';

            ClientAccountCustomer::query()->updateOrCreate([
                'client_account_id' => $account->id,
                'customer_id' => $customerId,
            ], [
                'can_send' => in_array($role, ['sender', 'both'], true),
                'can_receive' => in_array($role, ['receiver', 'both'], true),
                'can_view_payment' => false,
                'is_default_sender' => in_array($role, ['sender', 'both'], true),
                'is_default_receiver' => in_array($role, ['receiver', 'both'], true),
                'is_active' => true,
            ]);
        }
    }

    private function upsertCapabilities(ClientAccount $account, InvitationActivationData $activation): void
    {
        foreach ($activation->capabilities as $capability) {
            if (! ClientCapability::tryFrom($capability)) {
                continue;
            }

            ClientAccountCapability::query()->updateOrCreate([
                'client_account_id' => $account->id,
                'capability' => $capability,
            ], [
                'is_enabled' => true,
            ]);
        }
    }

    /** @param array<string, mixed> $mapping */
    private function mappingCustomerId(array $mapping): ?int
    {
        foreach (['core_customer_id', 'customer_id', 'customer_external_id'] as $key) {
            $value = $mapping[$key] ?? null;

            if (is_int($value)) {
                return $value;
            }

            if (is_string($value) && ctype_digit($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function role(string $role): ClientAccountRole
    {
        return ClientAccountRole::tryFrom($role) ?? ClientAccountRole::Owner;
    }

    private function statusKey(string $status): string
    {
        return match ($status) {
            'expired' => 'expired',
            'revoked' => 'revoked',
            'used', 'activated', 'already_activated' => 'used',
            default => 'invalid',
        };
    }
}
