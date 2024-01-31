<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\Page;
use App\Services\Models\ViewService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function home(): View
    {
        $page = Page::query()->where('uri', '=', 'home')->first();
        AppHelper::resolve(ViewService::class)->associate($page);
        if($page) {
            return view('pages.show', ['page' => $page]);
        }
        return view('statics.home');
    }

    public function imprint(): View
    {
        return view('statics.imprint');
    }


}
