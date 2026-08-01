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
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class SisahygoApiClient
{
    public function __construct(
        private readonly SisahygoApiConfiguration $configuration,
        private readonly SisahygoApiCredentialService $credentials,
        private readonly SisahygoApiLogger $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(SisahygoIntegrationContext $context, string $endpoint, array $query = []): array
    {
        return $this->send('GET', $context, $endpoint, $query, allowRetry: true);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  callable(array<string, mixed>): mixed  $mapper
     * @param  array<string, mixed>  $mappingContext
     */
    public function getMapped(SisahygoIntegrationContext $context, string $endpoint, array $query, callable $mapper, array $mappingContext = []): mixed
    {
        return $this->send('GET', $context, $endpoint, $query, allowRetry: true, mapper: $mapper, mappingContext: $mappingContext);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(SisahygoIntegrationContext $context, string $endpoint, array $payload = []): array
    {
        return $this->send('POST', $context, $endpoint, $payload, allowRetry: false);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function getPublic(string $endpoint, array $query = [], ?string $correlationId = null, ?string $logEndpoint = null): array
    {
        return $this->sendPublic('GET', $endpoint, $query, $correlationId ?? (string) Str::uuid(), allowRetry: true, logEndpoint: $logEndpoint);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function postPublic(string $endpoint, array $payload = [], ?string $correlationId = null, ?string $logEndpoint = null): array
    {
        return $this->sendPublic('POST', $endpoint, $payload, $correlationId ?? (string) Str::uuid(), allowRetry: false, logEndpoint: $logEndpoint);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function send(string $method, SisahygoIntegrationContext $context, string $endpoint, array $data, bool $allowRetry, ?callable $mapper = null, array $mappingContext = []): mixed
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

                $payload = $this->decode($response, $context->safeLogContext(), $endpoint);
                $this->throwForStatus($response, $payload, $context->safeLogContext(), $endpoint, $context->correlationId, $data);

                $result = $payload;

                if ($mapper !== null) {
                    try {
                        $result = $mapper($payload);
                    } catch (SisahygoUnexpectedResponseException $exception) {
                        throw $this->mappingException($exception, $response, $payload, $context->safeLogContext(), $endpoint, $context->correlationId, $data, $mappingContext);
                    }
                }

                $this->credentials->markLastUsed($credential);
                $this->log($context, $endpoint, $method, $response->status(), $startedAt, $retryCount, true, extra: array_merge(
                    $this->requestJsonContext($data),
                    $this->operationLogContext(true, $mapper !== null ? true : null, true),
                ));

                return $result;
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

        $failureStatus = $lastException instanceof SisahygoApiException ? $lastException->status : null;

        $this->log($context, $endpoint, $method, $failureStatus, $startedAt, $retryCount, false, $lastException, $this->operationLogContext(
            $failureStatus !== null,
            $lastException instanceof SisahygoUnexpectedResponseException ? ($lastException->safeContext()['mapping_success'] ?? null) : null,
            false,
        ));

        throw $lastException;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sendPublic(string $method, string $endpoint, array $data, string $correlationId, bool $allowRetry, ?string $logEndpoint = null): array
    {
        $logEndpoint ??= $endpoint;
        $safeContext = $this->publicLogContext($correlationId);
        $attempts = $allowRetry ? $this->configuration->retryTimes + 1 : 1;
        $retryCount = 0;
        $startedAt = microtime(true);
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->publicRequest($correlationId)
                    ->send($method, $this->publicEndpoint($endpoint), $method === 'GET' ? ['query' => $data] : ['json' => $data]);

                if ($this->shouldRetryResponse($method, $response) && $attempt < $attempts) {
                    $retryCount++;
                    usleep($this->configuration->retrySleepMs * 1000);

                    continue;
                }

                $payload = $this->decode($response, $safeContext, $logEndpoint);
                $this->throwForStatus($response, $payload, $safeContext, $logEndpoint, $correlationId, $data);
                $this->logSafe($safeContext, $logEndpoint, $method, $response->status(), $startedAt, $retryCount, true, extra: array_merge(
                    $this->requestJsonContext($data),
                    $this->operationLogContext(true, null, true),
                ));

                return $payload;
            } catch (ConnectionException $exception) {
                $lastException = new SisahygoConnectionException('Unable to connect to Sisahygo API.', null, $safeContext, $exception);

                if ($allowRetry && $attempt < $attempts) {
                    $retryCount++;
                    usleep($this->configuration->retrySleepMs * 1000);

                    continue;
                }
            } catch (SisahygoApiException $exception) {
                $lastException = $exception;
                break;
            } catch (Throwable $exception) {
                $lastException = new SisahygoUnexpectedResponseException('Unexpected Sisahygo API client failure.', null, $safeContext, $exception);
                break;
            }
        }

        $failureStatus = $lastException instanceof SisahygoApiException ? $lastException->status : null;

        $this->logSafe($safeContext, $logEndpoint, $method, $failureStatus, $startedAt, $retryCount, false, $lastException, $this->operationLogContext(
            $failureStatus !== null,
            null,
            false,
        ));

        throw $lastException;
    }


    private function publicEndpoint(string $endpoint): string
    {
        if (str_starts_with($endpoint, '/connect-onboarding')) {
            return preg_replace('#/client$#', '', $this->configuration->baseUrl).'/'.ltrim($endpoint, '/');
        }

        return ltrim($endpoint, '/');
    }

    private function request(SisahygoApiCredential $credential, SisahygoIntegrationContext $context): PendingRequest
    {
        return $this->baseRequest()
            ->withHeaders([
                'X-Api-Key' => $credential->apiKey(),
                'X-Correlation-ID' => $context->correlationId,
            ]);
    }

    private function publicRequest(string $correlationId): PendingRequest
    {
        return $this->baseRequest()
            ->withHeaders([
                'X-Correlation-ID' => $correlationId,
            ]);
    }

    private function baseRequest(): PendingRequest
    {
        return Http::baseUrl($this->configuration->baseUrl)
            ->acceptJson()
            ->asJson()
            ->connectTimeout($this->configuration->connectTimeout)
            ->timeout($this->configuration->timeout)
            ->withUserAgent($this->configuration->userAgent);
    }

    /** @return array<string, mixed> */
    private function decode(Response $response, array $safeContext, string $endpoint): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new SisahygoUnexpectedResponseException('Sisahygo API returned malformed JSON.', $response->status(), array_merge($safeContext, [
                'endpoint' => $endpoint,
            ]));
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private function throwForStatus(Response $response, array $payload, array $safeContext, string $endpoint, string $correlationId, array $requestData): void
    {
        if ($response->successful()) {
            return;
        }

        $error = is_array($payload['error'] ?? null) ? $payload['error'] : $payload;

        $safe = array_merge($safeContext, [
            'endpoint' => $endpoint,
            'api_error_code' => $error['code'] ?? null,
            'api_error_message' => $error['message'] ?? null,
            'validation_errors' => $error['details'] ?? $payload['errors'] ?? null,
            'correlation_id' => $error['correlation_id'] ?? $correlationId,
        ], $this->requestJsonContext($requestData), $this->responseJsonContext($payload));

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


    /** @param array<string, mixed> $payload */
    private function mappingException(SisahygoUnexpectedResponseException $exception, Response $response, array $payload, array $safeContext, string $endpoint, string $correlationId, array $requestData, array $mappingContext): SisahygoUnexpectedResponseException
    {
        return new SisahygoUnexpectedResponseException($exception->getMessage(), $response->status(), array_merge(
            $safeContext,
            $mappingContext,
            $exception->safeContext(),
            [
                'endpoint' => $endpoint,
                'correlation_id' => $correlationId,
                'transport_success' => true,
                'mapping_success' => false,
                'operation_success' => false,
            ],
            $this->requestJsonContext($requestData),
            $this->responseJsonContext($payload),
        ), $exception);
    }

    /** @return array<string, bool|null> */
    private function operationLogContext(bool $transportSuccess, ?bool $mappingSuccess, bool $operationSuccess): array
    {
        return [
            'transport_success' => $transportSuccess,
            'mapping_success' => $mappingSuccess,
            'operation_success' => $operationSuccess,
        ];
    }

    private function shouldRetryResponse(string $method, Response $response): bool
    {
        return $method === 'GET'
            && ($response->status() === 429 || in_array($response->status(), [500, 502, 503, 504], true));
    }

    /** @return array<string, mixed> */
    private function publicLogContext(string $correlationId): array
    {
        return [
            'user_id' => null,
            'client_account_id' => null,
            'credential_id' => null,
            'credential_fingerprint' => null,
            'environment' => $this->configuration->environment->value,
            'required_capability' => 'public_access_request.submit',
            'correlation_id' => $correlationId,
        ];
    }

    /** @param array<string, mixed> $safeContext */
    private function logSafe(array $safeContext, string $endpoint, string $method, ?int $status, float $startedAt, int $retryCount, bool $success, ?Throwable $exception = null, array $extra = []): void
    {
        $this->logger->recordSafe(
            context: $safeContext,
            endpoint: $endpoint,
            method: $method,
            status: $status,
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
            retryCount: $retryCount,
            success: $success,
            exception: $exception,
            extra: array_merge($extra, $this->exceptionJsonContext($exception)),
        );
    }

    private function log(SisahygoIntegrationContext $context, string $endpoint, string $method, ?int $status, float $startedAt, int $retryCount, bool $success, ?Throwable $exception = null, array $extra = []): void
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
            extra: array_merge($extra, $this->exceptionJsonContext($exception)),
        );
    }

    /** @param array<string, mixed> $payload */
    private function requestJsonContext(array $payload): array
    {
        return ['request_json' => $this->toSanitizedJson($payload)];
    }

    /** @param array<string, mixed> $payload */
    private function responseJsonContext(array $payload): array
    {
        return ['response_json' => $this->toSanitizedJson($payload)];
    }

    /** @return array<string, string> */
    private function exceptionJsonContext(?Throwable $exception): array
    {
        if (! $exception instanceof SisahygoApiException) {
            return [];
        }

        return array_filter([
            'request_json' => $exception->safeContext()['request_json'] ?? null,
            'response_json' => $exception->safeContext()['response_json'] ?? null,
        ], is_string(...));
    }

    /** @param array<string, mixed> $payload */
    private function toSanitizedJson(array $payload): string
    {
        try {
            return json_encode($this->sanitizePayload($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '{}';
        }
    }

    private function sanitizePayload(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $sanitized = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            $sanitized[$key] = $this->sanitizePayload($item);
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key): bool
    {
        return in_array(strtolower($key), [
            'api_key',
            'apikey',
            'x-api-key',
            'authorization',
            'password',
            'secret',
            'token',
            'access_token',
            'refresh_token',
            'encrypted_api_key',
            'credential',
            'credentials',
        ], true);
    }
}
