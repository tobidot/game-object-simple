<?php

use App\Http\Controllers\CodeReleaseController;
use App\Http\Controllers\CommentsController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProjectController;
use App\Http\Middleware\TrackView;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


/**
 * Content
 */
Route::middleware([
    TrackView::class,
])->group(function() {
    Route::get('/', [Controller::class, 'home'])
        ->name('home');
    Route::get('/imprint', [PageController::class, 'imprint'])
        ->name('imprint');
    //
    Route::get('/pages', [PageController::class, 'index'])
        ->name('pages');
    Route::get('/pages/{page}', [PageController::class, 'show'])
        ->where('page', '.*')
        ->name('pages.show');
    //
    Route::get('/projects', [ProjectController::class, 'index'])
        ->name('projects');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])
        ->whereNumber('project')
        ->name('projects.show');
    //
    Route::get('/code-releases/{codeRelease}', [CodeReleaseController::class, 'show'])
        ->whereNumber('codeRelease')
        ->name('code-releases.show');

    // Comments
    Route::post('/comments', [CommentsController::class, 'store'])
        ->name('comments.store');
});
