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
    Route::get('auth/complete', [InstallationController::class, 'completeInstallation'])->name('app_install_complete');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
