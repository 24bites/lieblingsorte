<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pinterest_feed_features', function (Blueprint $table) {
            $table->id();
            $table->string('featurable_type');
            $table->unsignedBigInteger('featurable_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['featurable_type', 'featurable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pinterest_feed_features');
    }
};
