<?php

namespace App\Http\Controllers;

use App\Mail\NewComment;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Project;
use App\Rules\CustomCaptcha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class CommentsController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // Validate the request...
        $params = $request->validate([
            'author' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:128'],
            'email' => ['nullable', 'email'],
            'content' => ['required', 'string', 'max:2048'],
            'commentable_type' => ['required', 'string', Rule::in([Page::class, Project::class])],
            'commentable_id' => ['required', 'integer'],
            'captcha' => [new CustomCaptcha()],
        ]);

        $commentable = $params['commentable_type']::find($params['commentable_id']);
        //

        // Store the comment...
        $comment = new Comment();
        $comment->author = $params['author'];
        $comment->title = $params['title'];
        $comment->email = $params['email'];
        $comment->content = $params['content'];
        $comment->visible = false;
        $comment->commentable()->associate($commentable);
        $comment->save();

        Mail::to('object.name@live.de')->send(new NewComment($comment));

        session()->flash('comment-form-message', 'Your comment has been submitted and is awaiting approval.');

        return match ($params['commentable_type']) {
            Page::class => response()->redirectToRoute('pages.show', ['page' => $commentable, 'comment' => true]),
            Project::class => response()->redirectToRoute('projects.show', ['project' => $commentable, 'comment' => true]),
            default => response()->json([
                'message' => 'Weird, bad comment type!',
                'comment' => $comment,
            ]),
        };

    }
}
