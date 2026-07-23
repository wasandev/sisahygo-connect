<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_page_returns_ok_with_current_customer_messaging(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('พื้นที่ลูกค้าสำหรับจัดการงานขนส่งกับ Sisahygo')
            ->assertSee('สร้างรายการรับส่งสินค้า')
            ->assertSee('ติดตามสถานะการขนส่ง')
            ->assertSee('ตรวจสอบประวัติรายการ')
            ->assertSee('ตรวจสอบสถานะการชำระเงิน')
            ->assertSee('บัญชีลูกค้า')
            ->assertSee('พร้อมใช้งาน')
            ->assertSee('สร้างและติดตามรายการ')
            ->assertSee('พร้อมทดสอบ')
            ->assertSee('Sisahygo Core API')
            ->assertSee('เชื่อมต่อ Sandbox');
    }

    public function test_welcome_page_removes_obsolete_sprint_copy(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Client Account Foundation')
            ->assertDontSee('รอ Sprint ถัดไป')
            ->assertDontSee('เตรียมพร้อมสำหรับการเชื่อมต่อข้อมูลคำสั่งซื้อ');
    }

    public function test_welcome_page_keeps_login_and_request_access_links(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Login')
            ->assertSee('Request Access')
            ->assertSee('ขอใช้งาน')
            ->assertSee(route('login'), false)
            ->assertSee(route('request-access'), false)
            ->assertDontSee(route('register'), false);
    }

    public function test_welcome_page_does_not_render_credentials_or_internal_endpoints(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('secret-api-key')
            ->assertDontSee('X-Api-Key')
            ->assertDontSee('Authorization')
            ->assertDontSee('sandbox-api.sisahygo.online')
            ->assertDontSee('api.sisahygo.online')
            ->assertDontSee('DB_PASSWORD');
    }
}
