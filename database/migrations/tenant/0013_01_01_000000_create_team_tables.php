<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamp('logged_out_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'logged_in_at']);
        });

        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->string('role');
            $table->json('permissions')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('login_histories');
    }
};
