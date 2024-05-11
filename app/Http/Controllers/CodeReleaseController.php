<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\CodeRelease;
use App\Services\Models\ViewService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CodeReleaseController extends Controller
{
    public function show(CodeRelease $codeRelease): Redirector|Application|RedirectResponse
    {
        AppHelper::resolve(ViewService::class)->associate($codeRelease);
        $code_attachment = $codeRelease->code();
        $url = "$code_attachment->url/index.html";
        return redirect($url, 302);
    }

    public function proxy(CodeRelease $codeRelease, string $path): Redirector|Application|RedirectResponse|BinaryFileResponse
    {
        if ($path === 'index.html') {
            AppHelper::resolve(ViewService::class)->associate($codeRelease);
        }
        $code_attachment = $codeRelease->code();
        $ending = ltrim($path, '/');
        $ending = explode('.', $ending);
        $ending = end($ending);
        $content_type = match ( $ending ) {
            'html' => 'text/html',
            'js' => 'application/javascript',
            'css' => 'text/css',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'txt' => 'text/plain',
            'json' => 'application/json',
            default => 'text/plain',
        };
        return response()->file(public_path("$code_attachment->path/$path"), [
            'Content-Type' => $content_type
        ]);
    }

    public function proxyIndex(CodeRelease $codeRelease): Redirector|Application|RedirectResponse
    {
        return redirect(
            route('code-releases.proxy', [
                'codeRelease' => $codeRelease->id,
                'path' => 'index.html'
            ]),
            302
        );
    }

}
