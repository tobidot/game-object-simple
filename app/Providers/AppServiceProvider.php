<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() : void
    {
        // get the called view name inside the layout
        View::composer('*', function (\Illuminate\View\View $view) {
            if (!empty(View::shared('viewName'))) return ;
            View::share('viewName', $view->getName());
        });
    }
}
