<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZohoController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('zoho/auth', [ZohoController::class, 'redirectToZoho']);
Route::get('/zoho/callback', [ZohoController::class, 'handleZohoCallback']);
Route::get('/logout', [ZohoController::class, 'logout']);
