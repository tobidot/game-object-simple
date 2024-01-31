<?php

namespace App\Services\Models;

use App\Models\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ViewService
{
    protected ?View $current_view;

    public function __construct()
    {
        $this->current_view = null;
    }

    public function clear(): void
    {
        $this->current_view = null;
    }

    public function get(): View
    {
        if ($this->current_view === null) {
            $this->current_view = new View();
            $this->current_view->url = request()->url();
            $this->current_view->save();
        }
        return $this->current_view;
    }

    public function associate(Model|string $viewable): void
    {
        $view = $this->get();
        if ($view->viewable_id !== null || $view->viewable_type !== null) {
            Log::error('ViewService::attach() called when a resource is already attached.');
        }
        if (is_string($viewable)) {
            $view->viewable_type = $viewable;
        } else {
            $view->viewable()->associate($viewable);
        }
        $view->save();
    }
}
