<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone');
            $table->string('province');
            $table->string('website')->nullable();
            $table->unsignedInteger('number_of_branches')->nullable();
            $table->text('additional_notes')->nullable();
            $table->string('status')->default('pending');
            $table->string('invitation_token')->unique();
            $table->timestamp('submitted_at');
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_requests');
    }
};
