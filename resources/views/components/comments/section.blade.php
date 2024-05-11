@props([
    'comments' => [],
    'commentable',
])
@php
    use App\Models\Comment;
    /**
     * @var \Illuminate\Database\Eloquent\Collection<Comment> $comments
     * @var $commentable
     */
    $open_comments = !empty(session('comment-form-message')) || !empty($errors->any());
@endphp

<div class="comment-section js-active">
    <h2>
        Comments
    </h2>
    <button
            id="write-comment"
            class="{{ $open_comments ? "js-hidden" : "" }}"
    >Write a comment
    </button>
    <x-comments.form
            :commentable="$commentable"
            :class='$open_comments ? "" : "js-hidden"'
    ></x-comments.form>
    <x-comments.list :comments="$comments"></x-comments.list>
</div>
