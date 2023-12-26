<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Shopify\InstallationController;

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
/*/shopify/auth */
Route::get('/', function () {
    return view('welcome');
});

Route::prefix('shopify')->group(function () {
    Route::get('auth', [InstallationController::class, 'startInstallation']);
    Route::get('auth/redirect', [InstallationController::class, 'handleRedirect'])->name('shopify.app_install_redirect');
});
