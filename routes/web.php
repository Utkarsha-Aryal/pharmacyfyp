<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\frontend\HomeController;
use App\Http\Controllers\frontend\ContactsController;
use App\Http\Controllers\frontend\CartsController;
use App\Http\Controllers\frontend\CheckoutController;
use App\Http\Controllers\frontend\description;
use App\Http\Controllers\frontend\AdvancedController;
use App\Http\Controllers\frontend\AccountController;


// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/contacts', [ContactsController::class, 'index'])->name('contacts');
Route::get('/carts', [CartsController::class, 'index'])->name('carts');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::get('/description', [description::class, 'index'])->name('description');
Route::get('/Advanced', [AdvancedController::class, 'index'])->name('advanced');

Route::get('/login', [AccountController::class, 'login'])->name('login');
Route::get('/register', [AccountController::class, 'register'])->name('register');




