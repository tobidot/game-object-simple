<?php

namespace Tobidot\ChunkedUpload;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Http\Middleware\Authenticate;
use Laravel\Nova\Nova;

class ChunkedUploadServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->routes();

        Nova::serving(function (ServingNova $event) {
            Nova::script('chunked-upload', __DIR__.'/../dist/js/asset.js');
            Nova::style('chunked-upload', __DIR__.'/../dist/css/asset.css');
        });
    }

    /**
     * Register the tool's routes.
     */
    protected function routes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(config('nova.api_middleware', ['web', Authenticate::class]))
            ->prefix('nova-api/chunked-upload')
            ->group(__DIR__.'/../routes/api.php');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ChunkManager::class, function () {
            return new ChunkManager;
        });
    }
}
