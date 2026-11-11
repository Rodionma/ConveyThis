<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FileController::class, 'create'])->name('files.create');

Route::prefix('files')->name('files.')->group(function () {
    Route::get('/', [FileController::class, 'index'])->name('index');
    Route::get('/list', [FileController::class, 'list'])->name('list');
    Route::post('/', [FileController::class, 'store'])->name('store');
    Route::get('/{file}/download', [FileController::class, 'download'])->name('download');
    Route::delete('/{file}', [FileController::class, 'destroy'])->name('destroy');
});
