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
        public Model   $model,
        public ?string $type = null,
    )
    {
    }

    /**
     * Get the view / contents that represent the component.
     * first try to find a special view for this model and type
     * drop the model first, then the type, then just use the default
     *
     * @return View
     */
    public function render(): View
    {
        $model = Str::snake(Str::plural( class_basename($this->model)), '-');
        $type = Str::snake($this->type, '-');
        $parts = array_filter(array($model, $type));
        do {
            $parts[count($parts) - 1] = Str::singular($parts[count($parts) - 1]);
            $special_view = "components.excerpts." . implode('.', $parts);
            if (view()->exists($special_view)) {
                return view($special_view);
            }
            array_shift($parts);
        } while (!empty($parts));
        return view('components.excerpts.default');
    }
}
