<?php

use Illuminate\Support\Facades\Route;
use Tobidot\ChunkedUpload\Http\Controllers\ChunkedUploadController;

Route::post('/upload', [ChunkedUploadController::class, 'uploadChunk'])->name('chunked-upload.chunk');
Route::post('/cancel', [ChunkedUploadController::class, 'cancelUpload'])->name('chunked-upload.cancel');
