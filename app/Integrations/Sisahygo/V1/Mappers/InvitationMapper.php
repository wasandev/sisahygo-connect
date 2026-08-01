<?php

namespace App\Integrations\Sisahygo\V1\Mappers;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\V1\DTO\InvitationActivationData;
use App\Integrations\Sisahygo\V1\DTO\InvitationPreviewData;
use Illuminate\Support\Facades\Log;

class InvitationMapper
{
    /** @param array<string, mixed> $data */
    public function preview(array $data): InvitationPreviewData
    {
        if (! is_string($data['email'] ?? null) || ! is_string($data['company_name'] ?? null) || ! is_string($data['status'] ?? null)) {
            throw new SisahygoUnexpectedResponseException('Invitation response is missing expected fields.');
        }

        $account = is_array($data['client_account'] ?? null) ? $data['client_account'] : [];

        return new InvitationPreviewData(
            status: $data['status'],
            email: $data['email'],
            companyName: $data['company_name'],
            contactName: is_string($data['contact_name'] ?? null) ? $data['contact_name'] : null,
            clientAccountCode: is_string($account['code'] ?? null) ? $account['code'] : null,
            clientAccountName: is_string($account['name'] ?? null) ? $account['name'] : null,
            role: is_string($data['role'] ?? null) ? $data['role'] : 'owner',
            expiresAt: is_string($data['expires_at'] ?? null) ? $data['expires_at'] : null,
            emailVerifiedByInvitation: (bool) ($data['email_verified_by_invitation'] ?? false),
        );
    }

    /** @param array<string, mixed> $data */
    public function activation(array $data): InvitationActivationData
    {
        $missing = $this->missingRequiredActivationFields($data);
        $account = is_array($data['client_account'] ?? null) ? $data['client_account'] : [];
        $user = is_array($data['user'] ?? null) ? $data['user'] : [];
        $mappings = $data['customer_mappings'] ?? null;
        $capabilities = $data['capabilities'] ?? null;

        if ($missing !== []
            || ! is_string($data['activation_status'] ?? null)
            || ! is_string($user['email'] ?? null)
            || ! is_string($account['code'] ?? null)
            || ! is_string($account['name'] ?? null)
            || ! is_array($mappings)
            || ! is_array($capabilities)) {
            $this->logActivationValidationFailure($data, $missing);

            throw new SisahygoUnexpectedResponseException('Invitation activation response is missing expected fields.', null, [
                'missing_fields' => $missing,
                'invitation_reference' => $data['invitation_reference'] ?? null,
                'access_request_reference' => $data['access_request_reference'] ?? null,
                'client_account_external_id' => $account['external_id'] ?? $account['code'] ?? null,
                'activation_status' => $data['activation_status'] ?? null,
            ]);
        }

        return new InvitationActivationData(
            status: $data['activation_status'],
            email: $user['email'],
            companyName: is_string($data['company_name'] ?? null) ? $data['company_name'] : $account['name'],
            contactName: is_string($data['contact_name'] ?? null) ? $data['contact_name'] : null,
            clientAccountCode: $account['code'],
            clientAccountName: $account['name'],
            userRole: is_string($user['role'] ?? null) ? $user['role'] : 'owner',
            emailVerifiedByInvitation: (bool) ($user['email_verified_by_invitation'] ?? false),
            alreadyActivated: ($data['activation_status'] ?? null) === 'already_activated' || (bool) ($data['already_activated'] ?? false),
            customerMappings: array_values($mappings),
            capabilities: array_values(array_filter($capabilities, 'is_string')),
            credential: $data['credential'],
        );
    }

    /** @param array<string, mixed> $data @return array<int, string> */
    private function missingRequiredActivationFields(array $data): array
    {
        return array_values(array_filter([
            $this->hasPath($data, ['invitation_reference']) ? null : 'invitation_reference',
            $this->hasPath($data, ['activation_status']) ? null : 'activation_status',
            $this->hasPath($data, ['user', 'email']) ? null : 'user.email',
            $this->hasPath($data, ['client_account', 'code']) ? null : 'client_account.code',
            $this->hasPath($data, ['client_account', 'name']) ? null : 'client_account.name',
            $this->hasPath($data, ['customer_mappings']) ? null : 'customer_mappings',
            $this->hasPath($data, ['capabilities']) ? null : 'capabilities',
            $this->hasPath($data, ['credential']) ? null : 'credential',
        ]));
    }

    /** @param array<string, mixed> $data @param array<int, string> $path */
    private function hasPath(array $data, array $path): bool
    {
        $cursor = $data;

        foreach ($path as $segment) {
            if (! is_array($cursor) || ! array_key_exists($segment, $cursor)) {
                return false;
            }

            $cursor = $cursor[$segment];
        }

        return true;
    }

    /** @param array<string, mixed> $data @param array<int, string> $missing */
    private function logActivationValidationFailure(array $data, array $missing): void
    {
        $account = is_array($data['client_account'] ?? null) ? $data['client_account'] : [];

        Log::warning('sisahygo.invitation_activation_mapper_failed', [
            'missing_fields' => $missing,
            'invitation_reference' => $data['invitation_reference'] ?? null,
            'access_request_reference' => $data['access_request_reference'] ?? null,
            'client_account_external_id' => $account['external_id'] ?? $account['code'] ?? null,
            'activation_status' => $data['activation_status'] ?? null,
        ]);
    }
}
