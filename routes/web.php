<?php

use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Report\AttachmentController;
use App\Http\Controllers\Report\PurchaseController;
use App\Http\Controllers\Report\TaxController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('http://acteeveallthingsfinance.id');
});

Route::get('auth/verification', VerificationController::class);

Route::prefix('admin/export')->group(function () {
    Route::get('tax/ppn', [TaxController::class, 'ppn']);
    Route::get('tax/pph', [TaxController::class, 'pph']);
    Route::get('attachment', AttachmentController::class);
    Route::get('purchase/event', [PurchaseController::class, 'event']);
    Route::get('purchase/operational', [PurchaseController::class, 'operational']);
});
