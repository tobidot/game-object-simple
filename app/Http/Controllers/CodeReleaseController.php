<?php

namespace App\Http\Controllers;

use App\Models\CodeRelease;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class CodeReleaseController extends Controller
{
    public function show(CodeRelease $codeRelease): Redirector|Application|RedirectResponse {
        $code_attachment = $codeRelease->code();
        $url = "$code_attachment->url/index.html";
        return redirect($url, 302);
    }

}
