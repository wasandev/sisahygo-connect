<?php

namespace Tests\Unit\Domain\Payment;

use App\Domain\Payment\Enums\PaymentType;
use Tests\TestCase;

class PaymentTypeTest extends TestCase
{
    public function test_payment_type_is_single_source_for_visibility_groups(): void
    {
        $this->assertSame(['H', 'T', 'F'], PaymentType::senderVisibleValues());
        $this->assertSame(['E', 'L'], PaymentType::receiverVisibleValues());
    }

    public function test_payment_type_is_single_source_for_timing_groups(): void
    {
        $this->assertSame(['H', 'T', 'E'], PaymentType::immediateValues());
        $this->assertSame(['F', 'L'], PaymentType::creditValues());
    }
}
