<?php

namespace App\View\Components;

use App\Exceptions\TypeException;
use App\Types\View\LinkType;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Navigation extends Component
{

    /**
     * Create a new component instance.
     *
     * @return void
     * @throws TypeException
     */
    public function __construct(
        public ?array $links = null,
    )
    {
        if ($this->links === null) {
            $this->links = [
                new LinkType([
                    'label' => 'Home',
                    'href' => route('home'),
                ]),
                new LinkType([
                    'label' => 'Pages',
                    'href' => route('pages'),
                ]),
                new LinkType([
                    'label' => 'Projects',
                    'href' => route('projects'),
                ]),
                new LinkType([
                    'label' => 'Imprint',
                    'href' => route('imprint'),
                ]),
            ];
        }

    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View
     */
    public function render() : View
    {
        return view('components.navigation');
    }
}
