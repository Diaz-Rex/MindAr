<?php

use App\Http\Controllers\AuthorityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MindArController;
use App\Http\Controllers\PlayGroundController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\ZohoMainController;
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

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/

Route::get('/sign-in', [SessionsController::class, 'create'])->middleware('guest')->name('login');
Route::post('/sign-in', [SessionsController::class, 'store'])->middleware('guest');
Route::get('/mindar', [MindArController::class, 'index'])->name('mind-ar.index');
Route::get('/mindar/{playground:qr_token}/target.png', [MindArController::class, 'index'])
    ->defaults('type', 'targetImage')
    ->name('mind-ar.playground.target-image');
Route::get('/mindar/{playground:qr_token}', [MindArController::class, 'index'])->name('mind-ar.playground.viewer');

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/sign-out', [SessionsController::class, 'destroy'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/superadmin/authority', [AuthorityController::class, 'authority'])->name('superadmin.authority');
    Route::post('/superadmin/authority', [AuthorityController::class, 'authority']);
    Route::get('/playground', [PlayGroundController::class, 'lobby'])->name('mind-ar.playground');
    Route::post('/playground', [PlayGroundController::class, 'lobby']);
    Route::get('/playground/{playground:qr_token}/build', [PlayGroundController::class, 'index'])->name('mind-ar.playground.build');
    Route::post('/playground/{playground:qr_token}/build', [PlayGroundController::class, 'index']);
    Route::get('/zoho/auth', [ZohoMainController::class, 'redirectToZoho'])->name('zoho.auth');
    Route::get('/zoho/callback', [ZohoMainController::class, 'handleCallback'])->name('zoho.callback');
});
