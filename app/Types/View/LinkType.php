<?php

namespace App\Types\View;

use App\Types\Type;

class LinkType extends Type
{
    public string $label;
    public string $href;

    public function isActive() : bool{
        return request()->is($this->href);
    }

}
