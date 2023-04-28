<?php

namespace Tobidot\LookupEnum;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metable;

class LookupEnum extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'lookup-enum';

    protected string $table = '';

    /**
     * The lookup table
     */
    public function table(string $table): LookupEnum
    {
        return $this->withMeta([
            'table' => $this->table = $table,
            'options' => DB::table($table)->get(['id', 'label'])
                ->map(fn($row) => [
                    "value" => $row->id,
                    "label" => $row->label,
                ])
        ]);
    }

    public function displayUsingLabels(): LookupEnum
    {
        return $this->displayUsing(function ($value) {
            foreach ($this->meta['options'] as $option) {
                if ($option['value'] === $value) {
                    return $option['label'];
                }
            }
            return "-";
        });
    }

    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute): void
    {
        if ($request->exists($requestAttribute)) {
            tap(
                $request[$requestAttribute],
                function ($value) use ($model, $attribute) {
                    $value = $this->isValidNullValue($value) ? null : $value;

                    if (!is_numeric($value)) {
                        $name = Str::slug($value, '_');
                        $label = Str::headline($value);
                        $id = DB::table($this->table)->insertGetId([
                            'name' => $name,
                            'label' => $label,
                        ]);
                        $value = $id;
                    }
                    Str::contains($attribute, '.')
                        ? data_set($model, $attribute, $value)
                        : $model->{$attribute} = $value;
                }
            );
        }
    }
}
