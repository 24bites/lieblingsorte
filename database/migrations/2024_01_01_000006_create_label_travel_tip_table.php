<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label_travel_tip', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->foreignId('travel_tip_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['label_id', 'travel_tip_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_travel_tip');
    }
};
