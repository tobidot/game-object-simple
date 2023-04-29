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
        return view('pages.default', [
            'page' => $page
        ]);
    }


}
