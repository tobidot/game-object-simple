@php
    use App\Helpers\ViewHelper;
    use Illuminate\Database\Eloquent\Model;
@endphp
@props([
    'model',
])
@php
    /** @var Model $model */
    $thumbnail = $model->thumbnail ?? null;
@endphp

<div class="excerpt">
    <div class="excerpt__title">
        <h3 title="{{ $model->title ?? 'Untitled' }}">
            {{ $model->title ?? 'Untitled' }}
        </h3>
    </div>
    @if(!empty($thumbnail))
        <div class="excerpt__thumbnail">
            {!! ViewHelper::mediaImageHtml($thumbnail, $model->title ?? 'Cover') !!}
        </div>
    @else
        <div class="excerpt__thumbnail excerpt__thumbnail--placeholder" aria-label="No thumbnail">
            <div class="excerpt__placeholder"></div>
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
        {{ substr( strip_tags( html_entity_decode( str_replace("<br>"," ", $model->content ?? $model->description ?? '') ) ) , 0, 255) }}
    </div>
</div>
