<?php

namespace App\Http\Resources;

use App\Models\TobidotElement;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TobidotElementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray(\Illuminate\Http\Request $request): array
    {
        /** @var TobidotElement $element */
        $element = $this;
        return [
            'name' => $element->name,
            'major' => $element->major,
            'minor' => $element->minor,
            'patch' => $element->patch,
            'icon' =>  asset(Storage::url("media/{$element->icon}")),
            'content' => asset(Storage::url("tobidot-elements/$element->content"   )),
        ];
    }
}
