<?php

namespace App\Http\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class Counter extends Component
{
    public int $count = 0;

    public function render() : View
    {
        return view('livewire.counter');
    }

    public function increment() : void {
        $this->count++;
    }
}
