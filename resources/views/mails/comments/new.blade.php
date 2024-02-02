@php
/**
* @var Comment $comment
 */
@endphp

<h1>
    New Comment added from {{ $comment->author ?? 'unknown' }} ({{$comment->email ?? 'no-mail'}}).
</h1>

<p>
    On {{ $comment->commentable?->title }}:
</p>

<br>
<h2>
    Comment:
</h2>
<hr>

<h3>
    {{ $comment->title}})
</h3>
<br>

<p>
    {{ $comment->content }}
</p>
