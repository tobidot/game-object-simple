<?php

namespace App\Http\Controllers;

use App\Models\CodeRelease;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class CodeReleaseController extends Controller
{
    public function show(CodeRelease $code_release): Redirector|Application|RedirectResponse {
        $code_attachment = $code_release->code();
        $url = "$code_attachment->url/index.html";
        return redirect($url, 302);
    }

}
