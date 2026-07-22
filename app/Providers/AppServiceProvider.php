<?php

namespace App\Providers;

use App\Support\ResendConfig;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Config is normally frozen by `config:cache`; this lets an
        // admin-configured Resend key (Settings) override the cached
        // RESEND_API_KEY env value without a redeploy. Must happen here
        // (before anything touches the Mail facade) rather than at the
        // point of sending, since queued mail is sent from a separate
        // worker process/boot where a controller-level override wouldn't
        // exist. Guarded for the pre-migration window (fresh installs,
        // `migrate` itself) when the settings table doesn't exist yet.
        // Note: a long-running `queue:work` worker only re-reads this at
        // its own boot, so a newly entered key needs a worker restart.
        if (Schema::hasTable('settings')) {
            config(['services.resend.key' => ResendConfig::apiKey()]);
        }
    }
}
