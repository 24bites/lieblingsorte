<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pinterest_boards', function (Blueprint $table) {
            $table->id();
            $table->string('pinterest_board_id')->nullable();
            $table->string('name');
            $table->enum('type', ['region', 'topic']);
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pinterest_boards');
    }
};
