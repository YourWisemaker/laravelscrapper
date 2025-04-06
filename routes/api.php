<?php

use App\Http\Controllers\OnlyFansProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('onlyfans')->group(function () {
    Route::get('search', [OnlyFansProfileController::class, 'search'])
        ->name('onlyfans.search');

    Route::post('scrape', [OnlyFansProfileController::class, 'scrape'])
        ->name('onlyfans.scrape');

    Route::get('profiles/{username}', [OnlyFansProfileController::class, 'show'])
        ->name('onlyfans.profile.show');
});