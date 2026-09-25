<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playgrounds', function (Blueprint $table) {
            $table->string('qr_url', 2048)->nullable()->after('qr_token');
            $table->string('mind_target_path')->nullable()->after('qr_url');
            $table->dateTime('target_compiled_at')->nullable()->after('mind_target_path');
        });
    }

    public function down(): void
    {
        Schema::table('playgrounds', function (Blueprint $table) {
            $table->dropColumn([
                'qr_url',
                'mind_target_path',
                'target_compiled_at',
            ]);
        });
    }
};
