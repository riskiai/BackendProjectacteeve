<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactTypeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PurchaseCategoryController;
use App\Http\Controllers\PurchaseStatusController;
use App\Http\Controllers\User\RegisterController;
use App\Http\Controllers\User\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->group(function () {
    Route::post('login', LoginController::class);
});

Route::middleware(['auth:sanctum'])->group(function () {
    // end point user
    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('register', RegisterController::class);
    });

    // end point contact type
    Route::prefix('contact-type')->group(function () {
        Route::get('/', [ContactTypeController::class, 'index']);
        Route::get('/{id}', [ContactTypeController::class, 'show']);
    });

    // end point puchase category
    Route::prefix('purchase-category')->group(function () {
        Route::get('/', [PurchaseCategoryController::class, 'index']);
        Route::get('/{id}', [PurchaseCategoryController::class, 'show']);
    });

    // end point puchase status
    Route::prefix('purchase-status')->group(function () {
        Route::get('/', [PurchaseStatusController::class, 'index']);
        Route::get('/{id}', [PurchaseStatusController::class, 'show']);
    });

    // end point contact resource data
    Route::apiResource('contact', ContactController::class);

    // end point project resource
    Route::apiResource('project', ProjectController::class);
});
