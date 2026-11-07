<?php

use App\Http\Controllers\CommentsController;
use App\Http\Controllers\LiveSyncController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RawDocumentationController;
use App\Http\Controllers\TceGeneratorController;
use App\Http\Controllers\TcePackageController;
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

/**
 * Old URLs of the removed projects and code releases: permanently to where their content lives now.
 */
Route::permanentRedirect('/projects', '/pages');
Route::permanentRedirect('/code-releases/{any}', '/pages')
    ->where('any', '.*');
Route::get('/projects/{id}/{proxy?}', function (string $id) {
    $targets = [
        1 => '/pages/ant-colony',
        2 => '/pages/baby-steps',
        3 => '/pages/baby-steps',
        4 => '/pages/baby-steps',
        5 => '/pages/baby-steps',
        6 => '/pages/spielwerk/16-flummis',
        7 => '/pages/spielwerk/17-bricks',
        8 => '/pages/baby-steps',
        9 => '/pages/spielwerk/18-rundenkampf',
        10 => '/pages/spielwerk/19-schleim-abwehr',
        11 => '/pages/wolfram-ca',
        12 => '/pages/falling-sand',
        13 => '/pages',
        14 => '/pages/ai-adventure',
        15 => '/tce/tce-yesdevs-shared',
        16 => '/tce/tce-god-weapon',
        17 => '/tce/tce-shape-me',
        18 => '/',
        19 => '/tce/tce-tower-defense-ai',
        20 => '/pages/spielwerk/15-memory',
        21 => '/pages/spielwerk/18-rundenkampf',
        22 => '/pages',
        23 => '/pages/spielwerk/11-flappy-bird',
        24 => '/pages/blockchain',
        25 => '/pages/spielwerk/civilization',
        26 => '/pages/spielwerk/05-lock-picker',
        27 => '/pages/spielwerk/04-mastermind',
        28 => '/pages/spielwerk/01-zahlen-raten',
        29 => '/pages/spielwerk/02-galgenraten',
        30 => '/pages/windows-stars-screensaver',
        31 => '/tce/tce-moonshot',
        32 => '/tce/tce-map-generator',
        33 => '/pages/von-neuman-probes',
        34 => '/pages/spielwerk/03-tic-tac-toe',
    ];

    return redirect($targets[$id] ?? '/pages', 301);
})->where('proxy', 'proxy(/.*)?');

/**
 * Public files a local instance pulled without downloading them come from live (LiveSyncService::liveFile()).
 */
Route::get('/storage/{path}', [LiveSyncController::class, 'storageFile'])
    ->where('path', '.*')
    ->name('live-sync.storage');
