<?php

namespace Tests\Feature\Localization;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\PaymentType;
use Tests\TestCase;

class PaymentLocalizationTest extends TestCase
{
    public function test_thai_is_the_default_locale(): void
    {
        $this->assertSame('th', config('app.locale'));
    }

    public function test_payment_type_translations_are_available(): void
    {
        $this->assertSame('เงินสดต้นทาง', __(PaymentType::SenderCashAtOrigin->translationKey()));
        $this->assertSame('เงินโอนต้นทาง', __(PaymentType::SenderTransferAtOrigin->translationKey()));
        $this->assertSame('เก็บเงินปลายทาง', __(PaymentType::ReceiverCollectAfterDelivery->translationKey()));
        $this->assertSame('วางบิลต้นทาง', __(PaymentType::OriginBilling->translationKey()));
        $this->assertSame('วางบิลปลายทาง', __(PaymentType::DestinationBilling->translationKey()));
    }

    public function test_payment_status_translations_are_available(): void
    {
        $this->assertSame('ค้างชำระ', __(PaymentStatus::Outstanding->translationKey()));
        $this->assertSame('ชำระแล้ว', __(PaymentStatus::Paid->translationKey()));
    }
}