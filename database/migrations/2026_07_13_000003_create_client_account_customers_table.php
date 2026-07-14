<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_account_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_account_id')->constrained('client_accounts')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id');
            $table->boolean('can_send')->default(false);
            $table->boolean('can_receive')->default(false);
            $table->boolean('can_view_payment')->default(false);
            $table->boolean('is_default_sender')->default(false);
            $table->boolean('is_default_receiver')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['client_account_id', 'customer_id']);
            $table->index(['client_account_id', 'is_active']);
            $table->index(['customer_id', 'can_send']);
            $table->index(['customer_id', 'can_receive']);
            $table->index(['customer_id', 'can_view_payment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_account_customers');
    }
};
