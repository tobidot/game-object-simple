<?php

namespace App\Providers;

use App\Nova\Dashboards\Main;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() : void
    {
        parent::boot();
        Nova::script('trix-adjust', resource_path('js/nova-trix-adjust.js'));
        Nova::style('trix-adjust', resource_path('css/nova-trix-adjust.css'));
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes() : void
    {
        Nova::routes()
                ->withAuthenticationRoutes()
                ->withPasswordResetRoutes()
                ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate() : void
    {
        Gate::define('viewNova', function ($user) {
            return $user->email == 'object.name@live.de';
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array
     */
    protected function dashboards(): array
    {
        return [
            new Main,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools() : array
    {
        return [
            \Laravel\Nova\LogViewer\LogViewer::make(),
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() : void
    {
        //
    }
}
