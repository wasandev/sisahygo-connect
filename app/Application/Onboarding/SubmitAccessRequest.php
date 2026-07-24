<?php

namespace App\Application\Onboarding;

use App\Integrations\Sisahygo\V1\DTO\AccessRequestSubmissionRequest;
use App\Integrations\Sisahygo\V1\DTO\AccessRequestSubmissionResult;
use App\Integrations\Sisahygo\V1\Endpoints\AccessRequestsEndpoint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SubmitAccessRequest
{
    public function __construct(private readonly AccessRequestsEndpoint $accessRequests) {}

    /**
     * @param array<string, mixed> $input
     */
    public function submit(array $input, string $connectReference): AccessRequestSubmissionResult
    {
        $validated = $this->validate($input);
        $request = $this->map($validated, $connectReference);

        return $this->accessRequests->create($request, (string) Str::uuid());
    }

    public function generateConnectReference(?string $seed = null): string
    {
        $seed ??= (string) Str::uuid();

        return 'CONNECT-REQ-'.now()->format('Ymd').'-'.strtoupper(substr(hash('sha256', $seed), 0, 16));
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function validate(array $input): array
    {
        return Validator::make($input, [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:60'],
            'province' => ['required', 'string', 'max:120'],
            'website' => ['nullable', 'url', 'max:255'],
            'number_of_branches' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'additional_notes' => ['nullable', 'string', 'max:2000'],
        ], [], __('onboarding.validation.attributes'))->validate();
    }

    /** @param array<string, mixed> $validated */
    public function map(array $validated, string $connectReference): AccessRequestSubmissionRequest
    {
        return new AccessRequestSubmissionRequest(
            connectReference: $connectReference,
            companyName: trim((string) $validated['company_name']),
            contactName: trim((string) $validated['contact_name']),
            email: Str::lower(trim((string) $validated['email'])),
            phone: trim((string) $validated['phone']),
            province: trim((string) $validated['province']),
            website: filled($validated['website'] ?? null) ? trim((string) $validated['website']) : null,
            branchCount: Arr::has($validated, 'number_of_branches') && $validated['number_of_branches'] !== null ? (int) $validated['number_of_branches'] : null,
            notes: filled($validated['additional_notes'] ?? null) ? trim((string) $validated['additional_notes']) : null,
            submittedAt: now()->toIso8601String(),
        );
    }
}
