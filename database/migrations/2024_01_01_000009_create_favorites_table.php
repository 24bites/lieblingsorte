<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->foreignId('travel_tip_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['session_id']);
            $table->unique(['user_id', 'travel_tip_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
