<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('travel_reports', function (Blueprint $table) {
            $table->string('og_description', 500)->nullable()->after('seo_description');
            $table->json('faq')->nullable()->after('og_description');
        });

        // Content used to be stored as plain text with a "## Heading" convention
        // (parsed at render time by the now-removed TravelReport::contentBlocks()).
        // The admin editor now produces real HTML directly, so existing rows are
        // converted once here to keep every report on the same storage format.
        DB::table('travel_reports')->select('id', 'content')->orderBy('id')->each(function ($report) {
            if (str_contains($report->content, '<p') || str_contains($report->content, '<h2')) {
                return;
            }

            $html = collect(preg_split('/\n\s*\n/', trim($report->content)) ?: [])
                ->map(fn ($block) => trim($block))
                ->filter(fn ($block) => $block !== '')
                ->map(function ($block) {
                    if (str_starts_with($block, '## ')) {
                        return '<h2>'.e(trim(substr($block, 3))).'</h2>';
                    }

                    return '<p>'.e($block).'</p>';
                })
                ->implode("\n");

            DB::table('travel_reports')->where('id', $report->id)->update(['content' => $html]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_reports', function (Blueprint $table) {
            $table->dropColumn(['og_description', 'faq']);
        });
    }
};
