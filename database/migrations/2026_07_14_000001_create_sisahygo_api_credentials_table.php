<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sisahygo_api_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_account_id')->constrained('client_accounts')->cascadeOnDelete();
            $table->string('environment');
            $table->string('name');
            $table->text('encrypted_api_key');
            $table->string('key_fingerprint', 64);
            $table->string('status')->default('active');
            $table->string('active_slot')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('rotated_from_id')->nullable()->constrained('sisahygo_api_credentials')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['client_account_id', 'environment', 'active_slot'], 'sac_active_unique');
            $table->unique(['client_account_id', 'environment', 'key_fingerprint'], 'sac_fingerprint_unique');
            $table->index(['client_account_id', 'environment', 'status'], 'sac_account_env_status_idx');
            $table->index(['key_fingerprint'], 'sac_fingerprint_idx');
            $table->index(['created_by', 'created_at'], 'sac_created_by_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sisahygo_api_credentials');
    }
};