<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::controller(LoginController::class)->prefix('login')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/', 'index')->name('login');
        Route::post('/authenticate', 'authenticate')->name('authenticate');
    });

    Route::post('/unauthenticate', 'unauthenticate')->middleware('auth')->name('unauthenticate');
});


Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('index');
    })->name('index');

    Route::controller(ProductionController::class)->prefix('production-panel')->group(function () {
        Route::get('/index/{id}', 'index')->name('production-panel');
        Route::get('/universal', 'universal')->name('production-panel-universal');
        Route::get('/temporary', 'temporary')->name('production-panel-temporary');

        Route::get('/get-po', 'getPo')->name('get-po');
        Route::get('/get-po-size', 'getPoSize')->name('get-po-size');
        Route::get('/get-po-size-qty', 'getPoSizeQty')->name('get-po-size-qty');
    });

    Route::controller(ProfileController::class)->prefix('profile')->group(function () {
        // Route::get('/{id}', 'index');
        Route::put('/update/{id}', 'update')->middleware('auth')->name('update-profile');
    });
});
