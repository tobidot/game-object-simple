<?php

namespace App\Console\Commands;

use App\Models\CodeRelease;
use App\Models\Page;
use App\Models\Project;
use App\Models\View;
use Illuminate\Console\Command;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $views = View::query()
            ->select('viewable_id', 'viewable_type', 'url')
            ->addSelect(DB::raw('COUNT(*) as count'))
            ->groupBy('viewable_id', 'viewable_type', 'url')
            ->orderBy('count', 'desc')
            ->limit(25)
            ->with('viewable')
            ->get();

        $groups = $views->mapWithKeys(function (View $view) {
            if ($view->viewable !== null) {
                $name = $view->viewable->title;
            } else {
                $name = parse_url($view->url)['path'] ?? '/';
            }
            if (strlen($name) > 32) {
                $name = substr($name, 0, 32) . '...';
            }
            $count = $view->count;
            return [
                $name => $count
            ];
        });

//
//        $views->leftJoin('pages', fn(JoinClause $join) => $join
//            ->on('viewable_id', '=', 'pages.id')
//            ->where('viewable_type', '=', Page::class));
//        $views->leftJoin('projects', fn(JoinClause $join) => $join
//            ->on('viewable_id', '=', 'projects.id')
//            ->where('viewable_type', '=', Project::class));
//        $views->leftJoin('code_releases', fn(JoinClause $join) => $join
//            ->on('viewable_id', '=', 'code_releases.id')
//            ->where('viewable_type', '=', CodeRelease::class));

//        dump($views->toRawSql());
//        dd($views->map(fn($view) => $view->attributesToArray()));

        dd($groups->toArray());
        return Command::SUCCESS;
    }
}
