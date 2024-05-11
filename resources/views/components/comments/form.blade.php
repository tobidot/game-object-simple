@props([
    'commentable'
])

@php
    /**
    * @var Model $commentable
    */

@endphp

{{--
    The form for creating a new comment.
    Contains a hidden input for the commentable type and id.
    Has title, author and content fields.
    Optional email field (make sure user knows it is provided at his own risk).
    Custom Captcha Check.
--}}

<form id="comment-form" action="{{ route('comments.store') }}#comment-form" method="post" {{$attributes->merge([
    'class' => 'comment-form'
])}}>
    @csrf
    <input type="hidden" name="commentable_type" value="{{ get_class($commentable) }}">
    <input type="hidden" name="commentable_id" value="{{ $commentable->id }}">
    <div class="comment-form__row">
        <div class="comment-form__field full">
            <label for="comment-title" class="comment-form__label">Title</label>
            <input type="text" name="title" id="comment-title" class="comment-form__input" required value="{{old('title')}}">
        </div>
    </div>
    <div class="comment-form__row">
        <div class="comment-form__field">
            <label for="comment-author" class="comment-form__label">Author</label>
            <input type="text" name="author" id="comment-author" class="comment-form__input" required value="{{old('author')}}">
        </div>
        <div class="comment-form__field">
            <label for="comment-email" class="comment-form__label">Email (optional)</label>
            <input type="email" name="email" id="comment-email" class="comment-form__input" value="{{old('email')}}">
        </div>
    </div>
    <div class="comment-form__row">
        <div class="comment-form__field full">
            <label for="comment-content" class="comment-form__label">Content</label>
            <textarea name="content" id="comment-content" class="comment-form__input" required>{{old('content')}}</textarea>
        </div>
    </div>
    @isset($captcha)
        <div class="comment-form__row">
            <div class="comment-form__field">
                <label for="comment-captcha" class="comment-form__label narrow wrap">
                    Captcha
                </label>
                <figure class="comment-form__captcha-image">
                    <img src="{{ $captcha['image'] }}" alt="captcha"  onclick="this.parentElement.parentElement.parentElement.requestFullscreen()">
{{--                    <a href="#comment-form" class="comment-form__captcha-refresh">Refresh</a>--}}
                </figure>
            </div>
            <div class="comment-form__field column">
                <label for="comment-captcha" class="comment-form__label wrap">
                    {{ $captcha['prompt'] }}
                </label>
                <input type="text" name="captcha[solution]" id="comment-captcha" class="comment-form__input" required>
                <input hidden type="hidden" name="captcha[token]" required value="{{$captcha['token']}}">
            </div>
        </div>
    @endif
    <div class="comment-form__row">
        <div class="comment-form__field full">
            <button type="submit" class="comment-form__submit">Submit</button>
        </div>
    </div>
    @if($errors->any())
        <div class="comment-form__row">
            <div class="comment-form__field full">
                <ul class="comment-form__errors">
                    @foreach($errors->all() as $error)
                        <li class="comment-form__error">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
    @if(session('comment-form-message'))
        <div class="comment-form__row">
            <div class="comment-form__field full">
                <div class="comment-form__success">
                    {{ session('comment-form-message') }}
                </div>
            </div>
        </div>
    @endif
</form>
