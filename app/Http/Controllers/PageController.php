<?php

namespace App\Http\Controllers;

use App\Enums\PublishState;
use App\Helpers\AppHelper;
use App\Models\Page;
use App\Services\Models\ViewService;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{

    public function index(): View
    {
        AppHelper::resolve(ViewService::class)->associate(Page::class);
        $pages = Page::query()
            ->orderByDesc('created_at')
            ->limit(24)
            ->get();
        return view('pages.index', [
            'pages' => $pages,
        ]);
    }

    public function show(Page $page) : View {
        AppHelper::resolve(ViewService::class)->associate($page);
        $relatedItems = $page->projects->merge($page->pages)->sortByDesc('created_at');
        $relatingItems = $page->relatingPages->sortByDesc('created_at');
        return view('pages.show', [
            'page' => $page,
            'relatedItems' => $relatedItems,
            'relatingItems' => $relatingItems,
        ]);
    }


}
