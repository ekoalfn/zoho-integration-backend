<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZohoController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('zoho.api')->group(function () {
    Route::get('/user', [ZohoController::class, 'getUser']);
    Route::get('/zoho/chart-of-accounts', [ZohoController::class, 'getChartOfAccounts']);
    Route::post('/zoho/sync/chart-of-accounts', [ZohoController::class, 'syncChartOfAccounts']);
    Route::get('/zoho/contacts', [ZohoController::class, 'getContacts']);
    Route::post('/zoho/sync/contacts', [ZohoController::class, 'syncContacts']);
    Route::post('/zoho/sync/receipts', [ZohoController::class, 'syncReceipts']);
    Route::post('/zoho/expenses', [ZohoController::class, 'createExpense']);
});
