<?php

namespace App\Http\Controllers;

use App\Http\Resources\TobidotElementResource;
use App\Models\TobidotElement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class TobidotElementController extends Controller
{


    public function index(): Collection|\Illuminate\Support\Collection
    {
        return collect( [
            'version' => 1,
            'packages' => TobidotElement::query()
                ->get()
                ->groupBy('name')
                ->map(
                    function (Collection $items) {
                        return $items->map(fn(TobidotElement $element) => new TobidotElementResource($element));
                    }
                ),
        ]);
    }

}
