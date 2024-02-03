<?php

namespace App\Nova\Metrics;

use App\Models\CodeRelease;
use App\Models\Page;
use App\Models\Project;
use App\Models\View;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

/**
 * Metric that shows the top visited urls.
 */
class ViewsPerUrl extends Partition
{
    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->width('1/2');
        $this->height(300);
        $this->fixedHeight();
    }

    /**
     * Calculate the value of the metric.
     *
     * @param NovaRequest $request
     * @return mixed
     */
    public function calculate(NovaRequest $request): PartitionResult
    {

//        $views = \App\Models\View::query()->
//        select('viewable_id', 'viewable_type', 'url')->
//        addSelect(DB::raw('COUNT(*) as count'))->
//        groupBy('viewable_id', 'viewable_type', 'url')->
//        orderBy('count', 'desc')->
//        with('viewable')->
//        get();
//
//
//        $median = $views->median('count');
//
//        $better_views = $views->
//        sortBy('count', SORT_REGULAR, true)->
//        where('count', '>', $median);
//
//        $groups = $better_views->mapWithKeys(function (\App\Models\View $view) {
//            if ($view->viewable !== null) {
//                $name = $view->viewable->title ?? 'Untitled';
//            } else {
//                $name = parse_url($view->url)['path'] ?? '/';
//            }
//            if (strlen($name) > 32) {
//                $name = substr($name, 0, 32) . '...';
//            }
//            $count = $view->count ?? 0;
//            return [
//                $name => $count
//            ];
//        });
//
//        $p = new \Laravel\Nova\Metrics\PartitionResult($groups->toArray());
//        // create hue colors for each group
//        $count = $groups->count();
//        $colors = $groups->values()->map(
//            fn($_,$index)=> "hsl(" . (360 / $count * $index) . ", 100%, 50%)"
//        )->toArray();


        $views = View::query()
            ->select('viewable_id', 'viewable_type', 'url')
            ->addSelect(DB::raw('COUNT(*) as count'))
            ->groupBy('viewable_id', 'viewable_type', 'url')
            ->orderBy('count', 'desc')
            ->with('viewable')
            ->get();

        $median = $views->median('count');

        $better_views = $views
            ->sortBy('count', SORT_REGULAR, true)
            ->where('count', '>', $median);

        $groups = $better_views->mapWithKeys(function (View $view) {
            if ($view->viewable !== null) {
                $name = $view->viewable->title ?? 'Untitled';
            } else {
                $name = parse_url($view->url)['path'] ?? '/';
            }
            if (strlen($name) > 32) {
                $name = substr($name, 0, 32) . '...';
            }
            $count = $view->count ?? 0;
            return [
                $name => $count
            ];
        });


        $count = $groups->count();
        $colors = $groups->values()->map(
            fn($_,$index)=> "hsl(" . (360 / $count * $index) . ", 100%, 50%)"
        )->toArray();

        return (new PartitionResult($groups->toArray()))
            ->colors($colors);
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     *
     * @return Carbon
     */
    public function cacheFor(): Carbon
    {
        return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey(): string
    {
        return 'views-per-url';
    }
}
