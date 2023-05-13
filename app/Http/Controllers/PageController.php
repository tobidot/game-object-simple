<?php

namespace App\Http\Controllers;

use App\Enums\PublishState;
use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{

    public function index(): View
    {
        $pages = Page::query()
            ->orderByDesc('created_at')
            ->limit(24)
            ->get();
        return view('pages.index', [
            'pages' => $pages,
        ]);
    }

    public function show(Page $page) : View {
        $relatedItems = $page->projects->merge($page->pages)->sortByDesc('created_at');
        $relatingItems = $page->relatingPages->sortByDesc('created_at');
        return view('pages.show', [
            'page' => $page,
            'relatedItems' => $relatedItems,
            'relatingItems' => $relatingItems,
        ]);
    }


}
