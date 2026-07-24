<?php

namespace App\Integrations\Sisahygo\Logging;

use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use Illuminate\Support\Facades\Log;
use Throwable;

class SisahygoApiLogger
{
    /**
     * @param array<string, mixed> $extra
     */
    public function record(SisahygoIntegrationContext $context, string $endpoint, string $method, ?int $status, int $durationMs, int $retryCount, bool $success, ?Throwable $exception = null, array $extra = []): void
    {
        $this->recordSafe($context->safeLogContext(), $endpoint, $method, $status, $durationMs, $retryCount, $success, $exception, $extra);
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     */
    public function recordSafe(array $context, string $endpoint, string $method, ?int $status, int $durationMs, int $retryCount, bool $success, ?Throwable $exception = null, array $extra = []): void
    {
        Log::channel(config('logging.default'))->info('sisahygo.api.request', array_merge($context, [
            'endpoint' => $endpoint,
            'method' => $method,
            'http_status' => $status,
            'duration_ms' => $durationMs,
            'retry_count' => $retryCount,
            'success' => $success,
            'exception_category' => $exception ? $exception::class : null,
        ], $this->redact($extra)));
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function redact(array $context): array
    {
        unset($context['X-Api-Key'], $context['x-api-key'], $context['api_key'], $context['encrypted_api_key'], $context['request_payload'], $context['response_payload']);

        return $context;
    }
}