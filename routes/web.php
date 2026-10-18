<?php

use App\Http\Controllers\CodeReleaseController;
use App\Http\Controllers\CommentsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RawDocumentationController;
use App\Http\Controllers\TceGeneratorController;
use App\Http\Controllers\TcePackageController;
use App\Http\Controllers\TobidotElementController;
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
])->group(function () {
    Route::get('/', [TcePackageController::class, 'home'])
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
    Route::get('/projects/{project}/proxy', [ProjectController::class, 'proxyIndex'])
        ->whereNumber('project');
    Route::get('/projects/{project}/proxy/{path}', [ProjectController::class, 'proxy'])
        ->whereNumber('project')
        ->where('path', '.*')
        ->name('projects.proxy');
    //
    Route::get('/code-releases/{codeRelease}', [CodeReleaseController::class, 'show'])
        ->whereNumber('codeRelease')
        ->name('code-releases.show');
    Route::get('/code-releases/{codeRelease}/proxy', [CodeReleaseController::class, 'proxyIndex'])
        ->whereNumber('codeRelease');
    Route::get('/code-releases/{codeRelease}/proxy/{path}', [CodeReleaseController::class, 'proxy'])
        ->whereNumber('codeRelease')
        ->where('path', '.*')
        ->name('code-releases.proxy');

    // Comments
    Route::post('/comments', [CommentsController::class, 'store'])
        ->name('comments.store');

    // TCE Generator
    Route::get('/tce-generator', [TceGeneratorController::class, 'index'])
        ->name('tce-generator.index');
    Route::post('/tce-generator', [TceGeneratorController::class, 'download'])
        ->name('tce-generator.download');

    // Stable alias for the v3 desktop — now also served at '/' (route 'home').
    Route::get('/v3', [TcePackageController::class, 'desktop'])
        ->name('v3.desktop');
});

Route::get('/documentation/{path?}', [RawDocumentationController::class, 'show'])
    ->where('path', '.*')
    ->name('documentation.raw');

/**
 * Tobidot elements (legacy — kept read-only/reference, untouched by the v3 migration)
 */
Route::get('/tobidot-elements/{tobidotElement}/view', [TobidotElementController::class, 'view'])
    ->name('tobidot-elements.view');

/**
 * Tce packages (v3)
 */
Route::get('/tce/{name}', [TcePackageController::class, 'play'])
    ->where('name', '[a-z][a-z0-9]*(-[a-z0-9]+)+')
    ->name('tce-packages.play');
Route::get('/tce-packages/{tcePackage}/view', [TcePackageController::class, 'view'])
    ->name('tce-packages.view');
Route::get('/tce-elements/{namespace}/{name}', [TcePackageController::class, 'embedScript'])
    ->where('namespace', '[0-9a-zA-Z\-_]+')
    ->where('name', '[0-9a-zA-Z\-_@.]+')
    ->name('tce-elements.embed');
Route::get('/tce-elements/{name}', [TcePackageController::class, 'embedScript'])
    ->where('name', '[0-9a-zA-Z\-_@.]+')
    ->name('tce-elements.embed.default');
