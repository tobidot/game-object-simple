<?php

namespace App\Http\Livewire;

use App\Exceptions\DummyException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Component;

class Pagination extends Component
{
    public string $class = '';

    public int $page = 1;

    public string $order_by = 'id';

    public function mount(string $class = '', int $page = 1, string $order_by = 'id'): void
    {
        $this->class = $class;
        $this->page = $page > 0 ? $page : 1;
        $this->order_by = in_array($order_by, $this->getAllowedOrderByColumns()) ? $order_by : 'id';
    }

    public function getAllowedOrderByColumns(): array
    {
        return ['id', 'created_at', 'created'];
    }

    public function getPaginator(): LengthAwarePaginator
    {
        if (! is_a($this->class, Model::class, true)) {
            throw new \LogicException("Invalid Class $this->class");
        }
        $query = $this->class::query();
        if (! ($query instanceof Builder)) {
            throw new DummyException('Not a query');
        }
        $query
            ->when(
                in_array($this->order_by, $this->getAllowedOrderByColumns()),
                function (Builder $query) {
                    return $query->orderBy($this->order_by);
                }
            );

        return $query->paginate(3, ['*'], 'page', $this->page);
    }

    public function next()
    {
        $this->page++;
    }

    public function previous()
    {
        if ($this->page <= 1) {
            return;
        }
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
