<?php

namespace App\Providers;

use App\Helpers\AppHelper;
use App\Services\CaptchaService;
use App\Services\Models\ViewService;
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
        $this->app->singleton(ViewService::class);
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
            if (empty(View::shared('viewName'))) {
                View::share('viewName', $view->getName());
            }
            $captcha_service = AppHelper::resolve(CaptchaService::class);
            if ($captcha_service->isCaptchaRequired()) {
                View::share('captcha', $captcha_service->generateCaptcha());
            };
        });
    }
}
