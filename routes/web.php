<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\frontend\HomeController;
use App\Http\Controllers\frontend\ContactsController;
use App\Http\Controllers\frontend\CartsController;
use App\Http\Controllers\frontend\CheckoutController;
use App\Http\Controllers\frontend\description;
use App\Http\Controllers\frontend\AdvancedController;
use App\Http\Controllers\BackPanel\DashboardController;
use App\Http\Controllers\BackPanel\CategoryController;
use App\Http\Controllers\BackPanel\ProductController;
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



// backend

Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');

// category start
Route::group(['prefix'=>'category'],function(){
    Route::get('/',[CategoryController::class,'index'])->name('category');
    Route::post('/save',[CategoryController::class,'save'])->name('category.save');
    Route::post('/list',[CategoryController::class,'list'])->name('category.list');
    Route::post('/delete',[CategoryController::class,'delete'])->name('category.delete');
    Route::post('/restore',[CategoryController::class,'restore'])->name('category.restore');
});

// Product start
Route::group(['prefix'=>'product'],function(){
    Route::get('/', [ProductController::class, 'index'])->name('product');
    Route::post('/save', [ProductController::class, 'save'])->name('product.save');
    Route::post('/list', [ProductController::class, 'list'])->name('product.list');
    Route::post('/view', [ProductController::class, 'view'])->name('product.view');
    Route::post('/delete', [ProductController::class, 'delete'])->name('product.delete');
    Route::any('/form', [ProductController::class, 'form'])->name('product.form');
    Route::post('/restore', [ProductController::class, 'restore'])->name('product.restore');
    Route::post('/view', [ProductController::class, 'view'])->name('product.view');
});
