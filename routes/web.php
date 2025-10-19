<?php

use Illuminate\Support\Facades\Route;

// SPA catch-all route - serves the React app for all tenant paths
Route::get('/{tenant}/{any?}', function () {
    return view('app');
})->where('any', '.*');
