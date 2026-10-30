<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        config([
            'boost.browser_logs_watcher' => false,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (file_exists(public_path('build/manifest.json'))) {
            \Illuminate\Support\Facades\Vite::useHotFile(storage_path('app/vite.hot.disabled'));
        }

        if (! $this->app->runningInConsole()) {
            $request = request();
            if ($request->getHost() && ! in_array($request->getHost(), ['localhost', '127.0.0.1'], true)) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
                \Illuminate\Support\Facades\URL::forceRootUrl('https://' . $request->getHttpHost());
            } elseif ($request->header('x-forwarded-proto') === 'https' || $request->secure()) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
        }

        // Auto-migrate & seed when running on Vercel Serverless with ephemeral SQLite
        if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || getenv('VERCEL')) {
            try {
                if (config('database.default') === 'sqlite' && !Schema::hasTable('users')) {
                    Artisan::call('migrate', [
                        '--force' => true,
                        '--seed' => true,
                    ]);
                }
            } catch (\Throwable $e) {
                // Ignore during build phase
            }
        }
    }
}
