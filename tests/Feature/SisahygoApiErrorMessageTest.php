<?php

namespace Tests\Feature;

use App\Application\Integration\SisahygoApiErrorMessage;
use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use Tests\TestCase;

class SisahygoApiErrorMessageTest extends TestCase
{
    public function test_unexpected_message_is_reserved_for_unexpected_exceptions(): void
    {
        $message = app(SisahygoApiErrorMessage::class)->message(
            new SisahygoUnexpectedResponseException('Malformed response.', 200),
            'order_checking'
        );

        $this->assertSame(__('order_checking.errors.malformed'), $message);
        $this->assertNotSame('order_checking.errors.unexpected', $message);
    }
}
