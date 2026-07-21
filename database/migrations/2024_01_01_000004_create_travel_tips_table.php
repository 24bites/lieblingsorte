<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('short_description');
            $table->text('description');
            $table->string('location_name')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('duration')->nullable();
            $table->enum('difficulty', ['leicht', 'mittel', 'anspruchsvoll'])->nullable();
            $table->string('price_information')->nullable();
            $table->string('opening_hours')->nullable();
            $table->string('parking_information')->nullable();
            $table->text('arrival_information')->nullable();
            $table->string('website_url')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->boolean('family_friendly')->default(false);
            $table->boolean('stroller_friendly')->default(false);
            $table->boolean('dog_friendly')->default(false);
            $table->boolean('indoor')->default(false);
            $table->boolean('free_entry')->default(false);
            $table->boolean('featured')->default(false);
            $table->string('best_season')->nullable();
            $table->json('highlights')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['region_id', 'slug']);
            $table->index(['is_published', 'sort_order']);
            $table->index('featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_tips');
    }
};
