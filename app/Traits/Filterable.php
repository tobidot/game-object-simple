<?php

namespace App\Traits;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    public function filterable(
        array $params
    ): View {
        // build the paginator
        $search = $this->getSearchInput();
        $paginator = $this->getBaseQuery()
            ->orderByDesc('created_at', 'DESC')
            ->when($search !== null, function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%');
            })
            ->paginate($this->getPerPage())
            ->withPath($this->getBaseUrl());
        $paginator->onEachSide = 2;

        return $this->buildFilterView($paginator);
    }

    abstract public function getBaseQuery(): Builder;

    public function getPerPage(): int
    {
        return 9;
    }

    public function getFilterViewName(): string
    {
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
        $search = $this->getSearchInput();
        if ($search !== null) {
            $base_url_path .= '?search='.$search;
        }

        return $base_url_path;
    }

    public function getSearchInput(): ?string
    {
        $search = request()->query('search', null);
        if (empty($search)) {
            return null;
        }
        if (preg_match('/[^a-zA-Z0-9\s]/', $search)) {
            return null;
        }
        if (strlen($search) > 255) {
            return substr($search, 0, 255);
        }

        return $search;
    }
}
