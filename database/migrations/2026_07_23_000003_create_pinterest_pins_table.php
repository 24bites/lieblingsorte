<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pinterest_pins', function (Blueprint $table) {
            $table->id();
            $table->string('featurable_type');
            $table->unsignedBigInteger('featurable_id');
            $table->foreignId('board_id')->constrained('pinterest_boards')->restrictOnDelete();
            $table->string('variant_label');
            $table->string('overlay_headline')->nullable();
            $table->string('overlay_subline')->nullable();
            $table->string('generated_image_path')->nullable();
            $table->string('pin_title')->nullable();
            $table->text('pin_description')->nullable();
            $table->enum('status', ['draft', 'approved', 'scheduled', 'posted', 'failed'])->default('draft');
            $table->date('scheduled_for')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->string('pinterest_pin_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['featurable_type', 'featurable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pinterest_pins');
    }
};
