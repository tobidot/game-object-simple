<?php

use App\Http\Controllers\CodeReleaseController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProjectController;
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

Route::get('/', [Controller::class, 'home'])
    ->name('home');
Route::get('/imprint', [PageController::class, 'imprint'])
    ->name('imprint');
//
Route::get('/pages', [PageController::class, 'index'])
    ->name('pages');
Route::get('/pages/{page}', [PageController::class, 'show'])
    ->whereNumber('page')
    ->name('page');
//
Route::get('/projects', [ProjectController::class, 'index'])
    ->name('projects');
Route::get('/project/{project}', [ProjectController::class, 'show'])
    ->whereNumber('project')
    ->name('project');
//
Route::get('/code_release/{code_release}', [CodeReleaseController::class, 'show'])
    ->whereNumber('code_release')
    ->name('code_release');

//
