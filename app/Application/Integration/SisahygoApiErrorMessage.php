<?php

namespace App\Application\Integration;

use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthenticationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthorizationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoConnectionException;
use App\Integrations\Sisahygo\Exceptions\SisahygoNotFoundException;
use App\Integrations\Sisahygo\Exceptions\SisahygoRateLimitException;
use App\Integrations\Sisahygo\Exceptions\SisahygoServerException;
use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Exceptions\SisahygoValidationException;

class SisahygoApiErrorMessage
{
    public function message(SisahygoApiException $exception, string $translationPrefix): string
    {
        $key = match (true) {
            $exception instanceof SisahygoAuthenticationException => 'authentication',
            $exception instanceof SisahygoAuthorizationException => 'authorization',
            $exception instanceof SisahygoNotFoundException => 'not_found',
            $exception instanceof SisahygoConnectionException => $translationPrefix === 'order_checking' ? 'core_unavailable' : 'connection',
            $exception instanceof SisahygoValidationException => 'validation',
            $exception instanceof SisahygoRateLimitException => 'rate_limited',
            $exception instanceof SisahygoServerException => $translationPrefix === 'order_checking' ? 'core_unavailable' : 'server',
            $exception instanceof SisahygoUnexpectedResponseException => $this->malformedKey($exception),
            default => 'unexpected',
        };

        $translationKey = "{$translationPrefix}.errors.{$key}";

        return __($translationKey) === $translationKey
            ? __("{$translationPrefix}.errors.unexpected")
            : __($translationKey);
    }

    private function malformedKey(SisahygoUnexpectedResponseException $exception): string
    {
        return match ($exception->safeContext()['response_domain'] ?? null) {
            'receiver' => 'receiver_malformed',
            'reference_data' => 'reference_data_unavailable',
            default => 'malformed',
        };
    }
}
