<?php

use App\Http\Controllers\ScoreboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScoreboardController::class, 'index']);
Route::post('/score/update', [ScoreboardController::class, 'ajaxUpdate'])->name('score.ajax.update');
Route::post('/score/reset', [ScoreboardController::class, 'ajaxReset'])->name('score.ajax.reset');
