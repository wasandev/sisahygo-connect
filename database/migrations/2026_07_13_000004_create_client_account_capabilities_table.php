<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_account_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_account_id')->constrained('client_accounts')->cascadeOnDelete();
            $table->string('capability');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['client_account_id', 'capability']);
            $table->index(['capability', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_account_capabilities');
    }
};