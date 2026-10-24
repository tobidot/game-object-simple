<?php

use App\Enums\LiveSyncSection;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\LiveSyncController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TceGeneratorController;
use App\Http\Controllers\TcePackageController;
use App\Http\Controllers\TobidotElementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'prefix' => 'v1',
], function () {
    Route::group([
        'middleware' => 'auth:sanctum',
    ], function () {
        Route::post('/projects/{project}/releases', [
            ProjectController::class,
            'release',
        ])->name('projects.release')
            ->whereNumber('project');

        Route::post('/attachments', [
            AttachmentController::class,
            'upload',
        ])->name('attachments.upload');

        Route::post('/tobidot-elements', [
            TobidotElementController::class,
            'upload',
        ])->name('tobidot-elements.upload');

        Route::post('/tce-packages', [
            TcePackageController::class,
            'upload',
        ])->name('tce-packages.upload');

        Route::get('/live-sync/{section}', [
            LiveSyncController::class,
            'export',
        ])->name('live-sync.export')
            ->whereIn('section', array_column(LiveSyncSection::cases(), 'value'));

        Route::get(
            '/validate-token',
            function () {
                echo 'Token is valid';

                return \request()->user()->id;
            }
        );
    });
});

Route::group([
    'prefix' => 'public',
], function () {
    Route::get('/tobidot-elements', [
        TobidotElementController::class,
        'index',
    ])->name('tobidot-elements.index');

    Route::get('/tce-packages', [
        TcePackageController::class,
        'index',
    ])->name('tce-packages.index');

    Route::post('/tce-generator', [
        TceGeneratorController::class,
        'apiGenerate',
    ])->name('api.public.tce-generator');
});
