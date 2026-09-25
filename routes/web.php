<?php

use App\Http\Controllers\MindArController;
use App\Http\Controllers\PlayGroundController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthorityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\ForceMindArHttps;

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
Route::get('/mindar', [MindArController::class, 'index'])->middleware(ForceMindArHttps::class)->name('mind-ar.index');

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
    Route::match(['get', 'post'], '/superadmin/authority', [AuthorityController::class, 'authority'])->name('superadmin.authority');
    Route::get('/playground', [PlayGroundController::class, 'index'])->name('mind-ar.playground');
});
