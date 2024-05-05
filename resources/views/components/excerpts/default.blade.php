@php use Illuminate\Database\Eloquent\Model; @endphp
@props([
    'model',
])
<?php
/**
 * @var Model $model
 */
?>


<div class="excerpt">
    <div class="excerpt__title">
        <h3>
            {{$model->title}}
        </h3>
    </div>
    @isset($model->thumbnail)
        <div class="excerpt__thumbnail">
            {!! ViewHelper::mediaImageHtml($model->thumbnail, 'cover') !!}
        </div>
    @endif
    <div class="excerpt__meta">
        @isset($model->created_at)
            <span>
                {{ $model->created_at->setTimezone(new DateTimeZone("Europe/Berlin"))->format('Y-m-d') }}
            </span>
        @endisset
    </div>
    <div class="excerpt__content">
        <p>
            {{ substr( strip_tags( html_entity_decode( str_replace("<br>"," ", $model->content) ) ) , 0, 255) }}
        </p>
    </div>
</div>
