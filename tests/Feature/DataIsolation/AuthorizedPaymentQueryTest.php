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
            $table->unsignedTinyInteger('payment_status')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();
        });
    }

    public function test_sender_can_view_payment_types_h_t_and_f(): void
    {
        $account = ClientAccount::create(['name' => 'Sender A', 'code' => 'SENDER-A']);
        ClientAccountCustomer::create([
            'client_account_id' => $account->id,
            'customer_id' => 10,
            'can_send' => true,
            'can_view_payment' => true,
            'is_active' => true,
        ]);

        DB::table('order_headers')->insert([
            ['id' => 1, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'H', 'payment_status' => 0, 'reference' => 'Sender H'],
            ['id' => 2, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'T', 'payment_status' => 0, 'reference' => 'Sender T'],
            ['id' => 3, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'F', 'payment_status' => 1, 'reference' => 'Sender F'],
            ['id' => 4, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'E', 'payment_status' => 0, 'reference' => 'Receiver E hidden'],
            ['id' => 5, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'L', 'payment_status' => 0, 'reference' => 'Receiver L hidden'],
            ['id' => 6, 'customer_id' => 20, 'customer_rec_id' => 50, 'paymenttype' => 'F', 'payment_status' => 0, 'reference' => 'Other sender receivable'],
        ]);

        $query = app(AuthorizedPaymentQuery::class);
        $orders = $query->forClientAccount($account)->orderBy('id')->pluck('id')->all();

        $this->assertSame([1, 2, 3], $orders);
        $this->assertNotNull($query->findAuthorized($account, 1));
        $this->assertNotNull($query->findAuthorized($account, 2));
        $this->assertNotNull($query->findAuthorized($account, 3));
        $this->assertNull($query->findAuthorized($account, 4));
        $this->assertNull($query->findAuthorized($account, 5));
        $this->assertNull($query->findAuthorized($account, 6));
    }

    public function test_receiver_can_view_payment_types_e_and_l(): void
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
            ['id' => 1, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'E', 'payment_status' => 0, 'reference' => 'Receiver E'],
            ['id' => 2, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'L', 'payment_status' => 1, 'reference' => 'Receiver L'],
            ['id' => 3, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'H', 'payment_status' => 0, 'reference' => 'Sender H hidden'],
            ['id' => 4, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'T', 'payment_status' => 0, 'reference' => 'Sender T hidden'],
            ['id' => 5, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'F', 'payment_status' => 0, 'reference' => 'Sender F hidden'],
            ['id' => 6, 'customer_id' => 10, 'customer_rec_id' => 60, 'paymenttype' => 'L', 'payment_status' => 0, 'reference' => 'Other receiver receivable'],
        ]);

        $query = app(AuthorizedPaymentQuery::class);
        $orders = $query->forClientAccount($account)->orderBy('id')->pluck('id')->all();

        $this->assertSame([1, 2], $orders);
        $this->assertNotNull($query->findAuthorized($account, 1));
        $this->assertNotNull($query->findAuthorized($account, 2));
        $this->assertNull($query->findAuthorized($account, 3));
        $this->assertNull($query->findAuthorized($account, 4));
        $this->assertNull($query->findAuthorized($account, 5));
        $this->assertNull($query->findAuthorized($account, 6));
    }

    public function test_payment_visibility_requires_customer_payment_permission(): void
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
            ['id' => 1, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'E', 'payment_status' => 0, 'reference' => 'Hidden without permission'],
        ]);

        $this->assertSame([], app(AuthorizedPaymentQuery::class)->forClientAccount($account)->pluck('id')->all());
    }
}