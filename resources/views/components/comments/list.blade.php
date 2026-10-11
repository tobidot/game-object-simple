@props([
    'comments',
])
@php
    use App\Helpers\ViewHelper;
    use Illuminate\Database\Eloquent\Collection;
    use App\Models\Comment;
    /**
    * @var Collection<Comment> $model
     */
@endphp

<ul class="comment-list">
    @foreach($comments as $comment)
        @if($comment->visible)
            <li class="comment-list__item comment-entry">
                <x-comments.entry :comment="$comment"></x-comments.entry>
            </li>
        @endif
    @endforeach
</ul>
