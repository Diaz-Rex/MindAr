<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zoho_tokens', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('authorized_by')->nullable();
            $table->foreign('authorized_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->string('accounts_url');
            $table->string('api_domain')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->dateTime('expires_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoho_tokens');
    }
};
