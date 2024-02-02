@props([
    'comment',
])
@php
    use App\Helpers\ViewHelper;
    use Illuminate\Database\Eloquent\Model;
    use App\Models\Comment;
    /**
     * @var Comment $comment
     */
@endphp

<div class="comment">
    <h3 class="comment__title">
        {{$comment->title}}
    </h3>
    <div class="comment__meta">
        @if($comment->author)
            <span class="comment__author">
                {{$comment->author}}
            </span>
        @endif
        @if($comment->created_at)
            <span class="comment__date">
                {{$comment->created_at->setTimezone(new DateTimeZone("Europe/Berlin"))->format('Y-m-d')}}
            </span>
        @endif
    </div>
    <p class="comment__content">
        {{$comment->content}}
    </p>
</div>
