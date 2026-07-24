<?php

namespace App\Livewire\Onboarding;

use App\Application\Integration\SisahygoApiErrorMessage;
use App\Application\Onboarding\SubmitAccessRequest;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use App\Integrations\Sisahygo\Exceptions\SisahygoConnectionException;
use App\Integrations\Sisahygo\Exceptions\SisahygoValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class RequestAccess extends Component
{
    public string $company_name = '';

    public string $contact_name = '';

    public string $email = '';

    public string $phone = '';

    public string $province = '';

    public string $website = '';

    public ?int $number_of_branches = null;

    public string $additional_notes = '';

    public string $connectReference = '';

    public string $state = 'editing';

    public bool $isSubmitting = false;

    public bool $hasSubmitted = false;

    public ?string $pageError = null;

    /** @var array<string, mixed>|null */
    public ?array $successResult = null;

    public ?string $submittedEmail = null;

    public function mount(SubmitAccessRequest $service): void
    {
        $this->connectReference = (string) session('onboarding.access_request.connect_reference', '');

        if ($this->connectReference === '') {
            $this->connectReference = $service->generateConnectReference(session()->getId().'|'.now()->toIso8601String());
            session(['onboarding.access_request.connect_reference' => $this->connectReference]);
        }
    }

    public function submit(SubmitAccessRequest $service, SisahygoApiErrorMessage $messages): void
    {
        if ($this->isSubmitting || $this->hasSubmitted) {
            return;
        }

        $this->isSubmitting = true;
        $this->pageError = null;
        $this->resetErrorBag();

        try {
            $result = $service->submit($this->formData(), $this->connectReference);

            $this->successResult = $result->toArray();
            $this->submittedEmail = $this->email;
            $this->state = 'success';
            $this->hasSubmitted = true;
            session()->forget('onboarding.access_request.connect_reference');
        } catch (ValidationException $exception) {
            $this->state = 'editing';
            $this->mapValidationErrors($exception->errors());
        } catch (SisahygoValidationException $exception) {
            $this->state = 'editing';
            $this->mapValidationErrors($this->coreValidationErrors($exception));
            $this->pageError = __('onboarding.errors.validation');
        } catch (SisahygoConnectionException $exception) {
            report($exception);
            $this->state = 'editing';
            $this->pageError = __('onboarding.errors.connection');
        } catch (SisahygoApiException $exception) {
            report($exception);
            $this->state = 'editing';
            $this->pageError = $messages->message($exception, 'onboarding');
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function render(): View
    {
        return view('livewire.onboarding.request-access');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'company_name' => $this->company_name,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'province' => $this->province,
            'website' => $this->website,
            'number_of_branches' => $this->number_of_branches,
            'additional_notes' => $this->additional_notes,
        ];
    }

    /** @param array<string, array<int, string>|string> $errors */
    private function mapValidationErrors(array $errors): void
    {
        foreach ($errors as $field => $messages) {
            $this->addError($field, is_array($messages) ? (string) ($messages[0] ?? __('onboarding.errors.validation')) : (string) $messages);
        }
    }

    /** @return array<string, array<int, string>> */
    private function coreValidationErrors(SisahygoValidationException $exception): array
    {
        $errors = $exception->safeContext()['validation_errors'] ?? [];

        if (! is_array($errors)) {
            return ['page' => [__('onboarding.errors.validation')]];
        }

        $mapped = [];
        foreach ($errors as $field => $messages) {
            $target = match ($field) {
                'branch_count' => 'number_of_branches',
                'notes' => 'additional_notes',
                'connect_reference' => 'page',
                default => (string) $field,
            };
            $mapped[$target] = is_array($messages) ? $messages : [(string) $messages];
        }

        return $mapped;
    }
}
