<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->text('credit_author')->nullable()->after('source');
            $table->string('credit_license')->nullable()->after('credit_author');
            $table->string('credit_source_title')->nullable()->after('credit_license');
            $table->string('credit_source_url')->nullable()->after('credit_source_title');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['credit_author', 'credit_license', 'credit_source_title', 'credit_source_url']);
        });
    }
};
