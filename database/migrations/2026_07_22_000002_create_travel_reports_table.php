<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt');
            $table->longText('content');
            $table->string('author_name');
            $table->string('author_bio')->nullable();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('ai_generated')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_reports');
    }
};
