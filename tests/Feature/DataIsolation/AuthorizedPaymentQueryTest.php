<?php

namespace Tests\Feature\DataIsolation;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\Payment\Queries\AuthorizedPaymentQuery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthorizedPaymentQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('order_headers');
        Schema::create('order_headers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('customer_rec_id');
            $table->string('paymenttype')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();
        });
    }

    public function test_receiver_can_view_payment_only_for_payment_types_e_or_l(): void
    {
        $account = ClientAccount::create(['name' => 'Receiver X', 'code' => 'RECEIVER-X']);
        ClientAccountCustomer::create([
            'client_account_id' => $account->id,
            'customer_id' => 50,
            'can_receive' => true,
            'can_view_payment' => true,
            'is_active' => true,
        ]);

        DB::table('order_headers')->insert([
            ['id' => 1, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'E', 'reference' => 'Visible E'],
            ['id' => 2, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'L', 'reference' => 'Visible L'],
            ['id' => 3, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'X', 'reference' => 'Hidden X'],
            ['id' => 4, 'customer_id' => 10, 'customer_rec_id' => 60, 'paymenttype' => 'E', 'reference' => 'Other receiver'],
        ]);

        $orders = app(AuthorizedPaymentQuery::class)->forClientAccount($account)->pluck('id')->all();

        $this->assertSame([1, 2], $orders);
        $this->assertNotNull(app(AuthorizedPaymentQuery::class)->findAuthorized($account, 1));
        $this->assertNull(app(AuthorizedPaymentQuery::class)->findAuthorized($account, 3));
        $this->assertNull(app(AuthorizedPaymentQuery::class)->findAuthorized($account, 4));
    }

    public function test_receiver_payment_visibility_requires_customer_payment_permission(): void
    {
        $account = ClientAccount::create(['name' => 'Receiver X', 'code' => 'RECEIVER-X']);
        ClientAccountCustomer::create([
            'client_account_id' => $account->id,
            'customer_id' => 50,
            'can_receive' => true,
            'can_view_payment' => false,
            'is_active' => true,
        ]);

        DB::table('order_headers')->insert([
            ['id' => 1, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'E', 'reference' => 'Hidden without permission'],
        ]);

        $this->assertSame([], app(AuthorizedPaymentQuery::class)->forClientAccount($account)->pluck('id')->all());
    }
}