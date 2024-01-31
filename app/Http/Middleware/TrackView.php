<?php

namespace App\Http\Middleware;

use App\Helpers\AppHelper;
use App\Services\Models\ViewService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackView
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next) : mixed
    {
        // trigger getting the view service will ensure the view is tracked
        AppHelper::resolve(ViewService::class)->get();

        return $next($request);
    }
}
