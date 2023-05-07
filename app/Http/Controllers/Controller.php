<?php

namespace App\Http\Controllers;

use App\Models\Page;
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
        $page = Page::query()->where('slug', 'home')->first();
        if($page) {
            return view('pages.default', ['page' => $page]);
        }
        return view('statics.home');
    }

    public function imprint(): View
    {
        return view('statics.imprint');
    }


}
