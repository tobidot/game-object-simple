<?php

namespace App\Http\Livewire;

use App\Exceptions\DummyException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

class Pagination extends Component
{

    public int $page = 1;
    public string $order_by = '';

    public function __construct(
        public string $class
    )
    {
        parent::__construct();
    }

    public function mount(): void
    {

    }

    public function getAllowedOrderByColumns(): array
    {
        return ['id', 'created'];
    }

    public function getPaginator(): LengthAwarePaginator
    {
        if (!is_a($this->class, Model::class, true)) {
            throw new \LogicException("Invalid Class $this->class");
        }
        $query = $this->class::query();
        if (!($query instanceof Builder)) {
            throw new DummyException("Not a query");
        }
            $query
            ->when(
                in_array($this->order_by, $this->getAllowedOrderByColumns()),
                function (Builder $query) {
                    return $query->orderBy($this->order_by);
                })
            ->orderBy($this->order_by)
            ->forPage($this->page);
        Log::info('', [
            'query' => $query->toSql(),
            'page' => $this->page,
        ]);
        return $query->paginate(3, ['*'], 'page', $this->page);
    }

    public function next()
    {
        $this->page++;
    }

    public function previous()
    {
        if ($this->page <= 1) return;
        $this->page--;
    }

    public function render(): View
    {
        $paginator = $this->getPaginator();
        return view('livewire.pagination', [
            'items' => $paginator->items(),
            'has_more_pages' => $paginator->hasMorePages(),
        ]);
    }
}
