<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\ViewsPerDay;
use App\Nova\Metrics\ViewsPerUrl;
use Laravel\Nova\Cards\Help;
use Laravel\Nova\Dashboards\Main as Dashboard;

class Main extends Dashboard
{

    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards() : array
    {
        return [
//            new Help,
            new ViewsPerDay(),
            new ViewsPerUrl(),
        ];
    }
}
