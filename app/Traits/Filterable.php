<?php

namespace App\Traits;

use App\Helpers\AppHelper;
use App\Models\Project;
use App\Services\Models\ViewService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

trait Filterable
{
    public function filterable(
        array   $params
    ): View {
        // validate the input
        $params = Validator::make(
            $params, [
            'search' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s]*$/'],
        ])->validate();
        // build the paginator
        $paginator = $this->getBaseQuery()
            ->orderByDesc('created_at', 'DESC')
            ->when(isset($params['search']), function ($query) use ($params) {
                $query->where('title', 'like', '%' . $params['search'] . '%');
            })
            ->paginate($this->getPerPage())
            ->withPath($this->getBaseUrl());
        $paginator->onEachSide = 2;
        return $this->buildFilterView($paginator);
    }

    public abstract function getBaseQuery(): Builder;

    public function getPerPage(): int
    {
        return 9;
    }

    public function getFilterViewName() : string {
        return 'components.pagination.section';
    }

    public function getOnEachSide(): int
    {
        return 2;
    }



    public function buildFilterView($paginator): View
    {
        return view($this->getFilterViewName(), [
            'paginator' => $paginator,
        ]);
    }

    public function getBaseUrl(): string
    {
        $base_url_path = request()->getBaseUrl();
        if (isset($params['search'])) {
            $base_url_path .= '?search=' . $params['search'];
        }
        return $base_url_path;
    }
}
