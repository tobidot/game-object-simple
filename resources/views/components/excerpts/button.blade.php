@php
    use App\Helpers\ViewHelper;
    use Illuminate\Database\Eloquent\Model;
@endphp
@props([
    'model',
])
<?php
/**
 * @var Model $model
 */
?>

<x-link :href="ViewHelper::modelUrl($model)">
    <div class="excerpt excerpt--button">
        <div class="excerpt__image">
            @isset($model->thumbnail)
                {!! ViewHelper::mediaImageHtml($model->thumbnail) !!}
            @endif
        </div>
        <div class="excerpt__title">
            @isset($model->title)
                <h3>
                    {{$model->title}}
                </h3>
            @endisset
        </div>
    </div>
</x-link>
