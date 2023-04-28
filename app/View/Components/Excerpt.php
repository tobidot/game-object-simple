<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Excerpt extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        public Model $model
    )
    {
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View
     */
    public function render() : View
    {
        $special = Str::slug( class_basename($this->model),'-' );
        $special_view = "components.excerpts.$special";
        if (view()->exists($special_view)) {
            return view($special_view);
        }
        return view('components.excerpts.default');
    }
}
