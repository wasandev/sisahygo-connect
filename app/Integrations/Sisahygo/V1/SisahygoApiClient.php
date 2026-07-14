<?php

namespace App\Integrations\Sisahygo\V1;

use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthenticationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthorizationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoConnectionException;
use App\Integrations\Sisahygo\Exceptions\SisahygoNotFoundException;
use App\Integrations\Sisahygo\Exceptions\SisahygoRateLimitException;
use App\Integrations\Sisahygo\Exceptions\SisahygoServerException;
use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Exceptions\SisahygoValidationException;
use App\Integrations\Sisahygo\Logging\SisahygoApiLogger;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class SisahygoApiClient
{
    public function __construct(
        private readonly SisahygoApiConfiguration $configuration,
        private readonly SisahygoApiCredentialService $credentials,
        private readonly SisahygoApiLogger $logger,
    ) {}

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(SisahygoIntegrationContext $context, string $endpoint, array $query = []): array
    {
        return $this->send('GET', $context, $endpoint, $query, allowRetry: true);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function post(SisahygoIntegrationContext $context, string $endpoint, array $payload = []): array
    {
        return $this->send('POST', $context, $endpoint, $payload, allowRetry: false);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function send(string $method, SisahygoIntegrationContext $context, string $endpoint, array $data, bool $allowRetry): array
    {
        $credential = SisahygoApiCredential::query()
            ->whereKey($context->credentialId)
            ->where('client_account_id', $context->clientAccountId)
            ->firstOrFail();

        $attempts = $allowRetry ? $this->configuration->retryTimes + 1 : 1;
        $retryCount = 0;
        $startedAt = microtime(true);
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->request($credential, $context)
                    ->send($method, ltrim($endpoint, '/'), $method === 'GET' ? ['query' => $data] : ['json' => $data]);

                if ($this->shouldRetryResponse($method, $response) && $attempt < $attempts) {
                    $retryCount++;
                    usleep($this->configuration->retrySleepMs * 1000);
                    continue;
                }

                $payload = $this->decode($response, $context, $endpoint);
                $this->throwForStatus($response, $payload, $context, $endpoint);
                $this->credentials->markLastUsed($credential);
                $this->log($context, $endpoint, $method, $response->status(), $startedAt, $retryCount, true);

                return $payload;
            } catch (ConnectionException $exception) {
                $lastException = new SisahygoConnectionException('Unable to connect to Sisahygo API.', null, $context->safeLogContext(), $exception);

                if ($allowRetry && $attempt < $attempts) {
                    $retryCount++;
                    usleep($this->configuration->retrySleepMs * 1000);
                    continue;
                }
            } catch (SisahygoApiException $exception) {
                $lastException = $exception;
                break;
            } catch (Throwable $exception) {
                $lastException = new SisahygoUnexpectedResponseException('Unexpected Sisahygo API client failure.', null, $context->safeLogContext(), $exception);
                break;
            }
        }

        $this->log($context, $endpoint, $method, null, $startedAt, $retryCount, false, $lastException);

        throw $lastException;
    }

    private function request(SisahygoApiCredential $credential, SisahygoIntegrationContext $context): PendingRequest
    {
        return Http::baseUrl($this->configuration->baseUrl)
            ->acceptJson()
            ->asJson()
            ->connectTimeout($this->configuration->connectTimeout)
            ->timeout($this->configuration->timeout)
            ->withUserAgent($this->configuration->userAgent)
            ->withHeaders([
                'X-Api-Key' => $credential->apiKey(),
                'X-Correlation-ID' => $context->correlationId,
            ]);
    }

    /** @return array<string, mixed> */
    private function decode(Response $response, SisahygoIntegrationContext $context, string $endpoint): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new SisahygoUnexpectedResponseException('Sisahygo API returned malformed JSON.', $response->status(), array_merge($context->safeLogContext(), [
                'endpoint' => $endpoint,
            ]));
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private function throwForStatus(Response $response, array $payload, SisahygoIntegrationContext $context, string $endpoint): void
    {
        if ($response->successful()) {
            return;
        }

        $safe = array_merge($context->safeLogContext(), [
            'endpoint' => $endpoint,
            'api_error_code' => $payload['code'] ?? null,
            'api_error_message' => $payload['message'] ?? null,
            'validation_errors' => $payload['errors'] ?? null,
        ]);

        throw match ($response->status()) {
            401 => new SisahygoAuthenticationException('Sisahygo API authentication failed.', 401, $safe),
            403 => new SisahygoAuthorizationException('Sisahygo API authorization failed.', 403, $safe),
            404 => new SisahygoNotFoundException('Sisahygo API resource was not found.', 404, $safe),
            422 => new SisahygoValidationException('Sisahygo API validation failed.', 422, $safe),
            429 => new SisahygoRateLimitException('Sisahygo API rate limit exceeded.', 429, $safe),
            default => $response->serverError()
                ? new SisahygoServerException('Sisahygo API server error.', $response->status(), $safe)
                : new SisahygoUnexpectedResponseException('Sisahygo API returned an unexpected status.', $response->status(), $safe),
        };
    }

    private function shouldRetryResponse(string $method, Response $response): bool
    {
        return $method === 'GET'
            && ($response->status() === 429 || in_array($response->status(), [500, 502, 503, 504], true));
    }

    private function log(SisahygoIntegrationContext $context, string $endpoint, string $method, ?int $status, float $startedAt, int $retryCount, bool $success, ?Throwable $exception = null): void
    {
        $this->logger->record(
            context: $context,
            endpoint: $endpoint,
            method: $method,
            status: $status,
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
            retryCount: $retryCount,
            success: $success,
            exception: $exception,
        );
    }
}