<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PosterController;
use App\Http\Controllers\Api\ViewerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FlutterwaveBotController;

Route::middleware(['auth:sanctum', 'throttle:6,1'])->group(function(){
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::prefix('category')->group(function () {
        Route::post('/viewer', [CategoryController::class, 'viewer']);
        Route::post('/poster', [CategoryController::class, 'poster']);
    });
    Route::prefix('payment')->group(function () {
        Route::get('paymentmethod', [FlutterwaveBotController::class, 'paymentMethod'])->name('payment.callback');
        Route::post('createpayment', [FlutterwaveBotController::class, 'createPayment'])->name('payment.create');
    });


    Route::apiResource('posters', PosterController::class);
    Route::apiResource('viewers', ViewerController::class);
}); 


require __DIR__.'/auth.php';