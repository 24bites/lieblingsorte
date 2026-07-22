<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('rejected_at');
            $table->timestamp('content_completed_at')->nullable()->after('approved_at');
        });

        // Existing regions already have real, curated (or previously
        // approved/published) content - never sweep them into the new
        // auto-completion pipeline. Only regions still awaiting KI-Vorschläge
        // review stay NULL here, so once approved they correctly flow into
        // the completion cron.
        DB::table('regions')
            ->where('is_published', true)
            ->orWhere('ai_generated', false)
            ->update(['content_completed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'content_completed_at']);
        });
    }
};
