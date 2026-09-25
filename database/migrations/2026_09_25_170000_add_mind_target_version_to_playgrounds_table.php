<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playgrounds', function (Blueprint $table) {
            $table->unsignedTinyInteger('mind_target_version')->nullable()->after('mind_target_path');
        });
    }

    public function down(): void
    {
        Schema::table('playgrounds', function (Blueprint $table) {
            $table->dropColumn('mind_target_version');
        });
    }
};
