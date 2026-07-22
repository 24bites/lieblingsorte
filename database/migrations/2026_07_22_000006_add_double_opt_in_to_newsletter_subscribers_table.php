<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->string('confirmation_token')->nullable()->unique()->after('email');
            $table->timestamp('confirmed_at')->nullable()->after('subscribed_at');
            $table->string('unsubscribe_token')->nullable()->unique()->after('confirmed_at');
            $table->timestamp('unsubscribed_at')->nullable()->after('unsubscribe_token');
            $table->string('consent_ip')->nullable()->after('unsubscribed_at');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropColumn(['confirmation_token', 'confirmed_at', 'unsubscribe_token', 'unsubscribed_at', 'consent_ip']);
        });
    }
};
