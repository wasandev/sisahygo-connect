<?php

namespace Tests\Feature\DataIsolation;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\Shipment\Queries\AuthorizedOrderQuery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthorizedOrderQueryTest extends TestCase
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

    public function test_sender_can_only_see_orders_from_authorized_sender_customers(): void
    {
        $account = ClientAccount::create(['name' => 'Sender A', 'code' => 'SENDER-A']);
        ClientAccountCustomer::create([
            'client_account_id' => $account->id,
            'customer_id' => 10,
            'can_send' => true,
            'is_active' => true,
        ]);

        DB::table('order_headers')->insert([
            ['id' => 1, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'X', 'reference' => 'Sender A to Receiver X'],
            ['id' => 2, 'customer_id' => 20, 'customer_rec_id' => 50, 'paymenttype' => 'X', 'reference' => 'Sender B to Receiver X'],
        ]);

        $orders = app(AuthorizedOrderQuery::class)->forClientAccount($account)->pluck('id')->all();

        $this->assertSame([1], $orders);
        $this->assertNotNull(app(AuthorizedOrderQuery::class)->findAuthorized($account, 1));
        $this->assertNull(app(AuthorizedOrderQuery::class)->findAuthorized($account, 2));
    }

    public function test_receiver_can_only_see_orders_for_authorized_receiver_customers(): void
    {
        $account = ClientAccount::create(['name' => 'Receiver X', 'code' => 'RECEIVER-X']);
        ClientAccountCustomer::create([
            'client_account_id' => $account->id,
            'customer_id' => 50,
            'can_receive' => true,
            'is_active' => true,
        ]);

        DB::table('order_headers')->insert([
            ['id' => 1, 'customer_id' => 10, 'customer_rec_id' => 50, 'paymenttype' => 'X', 'reference' => 'Sender A to Receiver X'],
            ['id' => 2, 'customer_id' => 10, 'customer_rec_id' => 60, 'paymenttype' => 'X', 'reference' => 'Sender A to Receiver Y'],
        ]);

        $orders = app(AuthorizedOrderQuery::class)->forClientAccount($account)->pluck('id')->all();

        $this->assertSame([1], $orders);
        $this->assertNotNull(app(AuthorizedOrderQuery::class)->findAuthorized($account, 1));
        $this->assertNull(app(AuthorizedOrderQuery::class)->findAuthorized($account, 2));
    }
}