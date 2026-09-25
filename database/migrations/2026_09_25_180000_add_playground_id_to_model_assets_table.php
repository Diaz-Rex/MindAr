<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_assets', function (Blueprint $table) {
            $table->unsignedBigInteger('playground_id')->nullable()->after('user_id');
            $table->foreign('playground_id')
                ->references('id')
                ->on('playgrounds')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('model_assets', function (Blueprint $table) {
            $table->dropForeign(['playground_id']);
            $table->dropColumn('playground_id');
        });
    }
};
