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
            ->where('publish_state_id', PublishState::PUBLISHED)
            ->limit(6)
            ->get();
        return view('pages.index', [
            'pages' => $pages,
        ]);
    }

    public function show(Page $page) : View {
        if ($page->publishState->id !== PublishState::PUBLISHED->value) {
            abort(404);
        }
        $relatedItems = $page->projects->merge($page->pages)->sortByDesc('created_at');
        $relatingItems = $page->relatingPages->sortByDesc('created_at');
        return view('pages.show', [
            'page' => $page,
            'relatedItems' => $relatedItems,
            'relatingItems' => $relatingItems,
        ]);
    }


}
