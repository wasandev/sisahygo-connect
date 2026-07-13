<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_account_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_account_id')->constrained('client_accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('viewer');
            $table->boolean('is_active')->default(true);
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['client_account_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
            $table->index(['client_account_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_account_users');
    }
};