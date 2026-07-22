<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->string('postable_type');
            $table->unsignedBigInteger('postable_id');
            $table->enum('platform', ['pinterest', 'facebook', 'x', 'telegram', 'whatsapp']);
            $table->text('caption');
            $table->string('link_url');
            $table->string('image_url')->nullable();
            $table->enum('status', ['draft', 'sent', 'failed'])->default('draft');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['postable_type', 'postable_id']);
            $table->index(['platform', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
