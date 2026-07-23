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
            $exception instanceof SisahygoConnectionException => 'connection',
            $exception instanceof SisahygoValidationException => 'validation',
            $exception instanceof SisahygoRateLimitException => 'rate_limited',
            $exception instanceof SisahygoServerException => 'server',
            $exception instanceof SisahygoUnexpectedResponseException => 'malformed',
            default => 'unexpected',
        };

        $translationKey = "{$translationPrefix}.errors.{$key}";

        return __($translationKey) === $translationKey
            ? __("{$translationPrefix}.errors.unexpected")
            : __($translationKey);
    }
}
