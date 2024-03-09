<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactTypeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PurchaseCategoryController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseStatusController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\User\RegisterController;
use App\Http\Controllers\User\UserController;
use App\Http\Resources\UserCollection;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('auth')->group(function () {
    Route::post('login', LoginController::class);

    Route::post('logout', LogoutController::class)
        ->middleware('auth:sanctum');
});

Route::middleware(['auth:sanctum'])->group(function () {
    // end point user
    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('me', function (Request $request) {
            return $request->user();
        });
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/reset-password/{id}', [UserController::class, 'resetPassword']);
        Route::put('/update-password', [UserController::class, 'updatePassword']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
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
    Route::get('contact', [ContactController::class, 'showByContactType']);
    Route::apiResource('contact', ContactController::class);


    // end point project resource
    Route::get('project/counting', [ProjectController::class, 'counting']); // buat kotak besaar
    Route::get('project/invoice/{id}', [ProjectController::class, 'invoice']); // Buat Transaction
    Route::put('project/accept/{id}', [ProjectController::class, 'accept']); // Buat Transaction
    Route::put('project/reject/{id}', [ProjectController::class, 'reject']); // Buat Transaction
    Route::apiResource('project', ProjectController::class);

    // end point tax resource
    Route::get('tax/ppn/report', [TaxController::class, 'reportPpn']);
    Route::apiResource('tax', TaxController::class);

    // end point puchase resource
    Route::get('purchase/counting', [PurchaseController::class, 'counting']); // buat kotak besar
    Route::put('purchase/activate/{id}', [PurchaseController::class, 'activate']);
    Route::put('purchase/undo/{id}', [PurchaseController::class, 'undo']);
    Route::put('purchase/accept/{id}', [PurchaseController::class, 'accept']);
    Route::put('purchase/reject/{id}', [PurchaseController::class, 'reject']);
    Route::put('purchase/request/{id}', [PurchaseController::class, 'request']);
    Route::put('purchase/payment/{id}', [PurchaseController::class, 'payment']);
    Route::delete('purchase/delete-document/{id}', [PurchaseController::class, 'deleteDocument']);
    Route::apiResource('purchase', PurchaseController::class);
});
