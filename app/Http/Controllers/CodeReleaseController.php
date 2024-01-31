<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\CodeRelease;
use App\Services\Models\ViewService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class CodeReleaseController extends Controller
{
    public function show(CodeRelease $codeRelease): Redirector|Application|RedirectResponse {
        AppHelper::resolve(ViewService::class)->associate($codeRelease);
        $code_attachment = $codeRelease->code();
        $url = "$code_attachment->url/index.html";
        return redirect($url, 302);
    }

}
