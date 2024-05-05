<?php

namespace App\Http\Controllers;

use App\Enums\PublishState;
use App\Helpers\AppHelper;
use App\Models\Page;
use App\Services\CaptchaService;
use App\Services\Models\ViewService;
use App\Traits\Filterable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class PageController extends Controller
{
    use Filterable;

    public function index(Request $request): View
    {
        AppHelper::resolve(ViewService::class)->associate(Page::class);
        return $this->filterable($request->all());
    }

    /**
     */
    public function show(Page $page): View
    {
        AppHelper::resolve(ViewService::class)->associate($page);
        $relatedItems = $page->projects->merge($page->pages)->sortByDesc('created_at');
        $relatingItems = $page->relatingPages->sortByDesc('created_at');
        return view('pages.show', [
                'page' => $page,
                'relatedItems' => $relatedItems,
                'relatingItems' => $relatingItems,
            ]);
    }

    public function getPerPage(): int
    {
        return 6;
    }

    public function getFilterViewName(): string
    {
        return 'pages.index';
    }

    public function getBaseQuery(): Builder
    {
        return Page::query();
    }
}
