<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playground_objects', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('playground_id');
            $table->foreign('playground_id')
                ->references('id')
                ->on('playgrounds')
                ->onDelete('cascade');

            $table->unsignedBigInteger('model_asset_id');
            $table->foreign('model_asset_id')
                ->references('id')
                ->on('model_assets')
                ->onDelete('cascade');

            $table->string('object_uuid', 100);
            $table->string('name', 100);
            $table->double('position_x')->default(0);
            $table->double('position_y')->default(0);
            $table->double('position_z')->default(0);
            $table->double('rotation_x')->default(0);
            $table->double('rotation_y')->default(0);
            $table->double('rotation_z')->default(0);
            $table->double('scale_x')->default(1);
            $table->double('scale_y')->default(1);
            $table->double('scale_z')->default(1);
            $table->boolean('visible')->default(true);
            $table->boolean('locked')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->unique(['playground_id', 'object_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playground_objects');
    }
};
