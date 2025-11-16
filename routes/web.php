<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\frontend\HomeController;
use App\Http\Controllers\frontend\ContactsController;
use App\Http\Controllers\frontend\CartsController;
use App\Http\Controllers\frontend\CheckoutController;
use App\Http\Controllers\frontend\description;
use App\Http\Controllers\frontend\ProductCOntroller as Fproductcontroller;
use App\Http\Controllers\frontend\AdvancedController;
use App\Http\Controllers\frontend\AccountController;
use App\Http\Controllers\BackPanel\DashboardController;
use App\Http\Controllers\BackPanel\CategoryController;
use App\Http\Controllers\BackPanel\ProductController;
use App\Http\Controllers\BackPanel\ProductBatchesController;
use App\Http\Controllers\BackPanel\SupplierController;
use App\Http\Controllers\BackPanel\PurchaseController;






// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/contacts', [ContactsController::class, 'index'])->name('contacts');
Route::get('/carts', [CartsController::class, 'index'])->name('carts');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::get('/login', [AccountController::class, 'login'])->name('login');
Route::get('/register', [AccountController::class, 'register'])->name('register');

Route::group(['prefix'=>'advanced'],function(){
    Route::get('/',[AdvancedController::class,'index'])->name('advanced');
    Route::post('/selection',[AdvancedController::class,'selection'])->name('advanced.selection');
});

Route::get('/description', [description::class, 'index'])->name('description');

Route::group(['prefix'=>'product'],function(){
    Route::get('/{slug}',[Fproductcontroller::class,'index'])->name('product');
});



// backend
Route::prefix('admin')->name('admin.')->group(function(){

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
    Route::post('/delete', [ProductController::class, 'delete'])->name('product.delete')
    ;
    Route::any('/form', [ProductController::class, 'form'])->name('product.form');
    Route::post('/restore', [ProductController::class, 'restore'])->name('product.restore');
    Route::post('/view', [ProductController::class, 'view'])->name('product.view');
});
Route::post('/global-search', [ProductController::class, 'globalSearch'])->name('global.search');

 Route::group(['prefix'=>'batch'],function(){
        Route::get('/{slug}', [ProductBatchesController::class, 'index'])->name('batch');
        Route::post('/save', [ProductBatchesController::class, 'save'])->name('batch.save');
        Route::post('/list', [ProductBatchesController::class, 'list'])->name('batch.list');
        Route::post('/delete', [ProductBatchesController::class, 'delete'])->name('batch.delete');
        Route::post('/restore', [ProductBatchesController::class, 'restore'])->name('batch.restore');

    });
// supplier
Route::group(['prefix'=>'supplier'],function(){
    Route::get('/',[SupplierController::class,'index'])->name('supplier');
    Route::post('/save',[SupplierController::class,'save'])->name('supplier.save');
    Route::post('/list',[SupplierController::class,'list'])->name('supplier.list');
    Route::post('/delete',[SupplierController::class,'delete'])->name('supplier.delete');
    Route::post('/restore',[SupplierController::class,'restore'])->name('supplier.restore');
});

Route::group(['prefix'=>'unit'], function() {
    Route::get('/', [\App\Http\Controllers\BackPanel\UnitController::class, 'index'])->name('unit');
    Route::post('/save', [\App\Http\Controllers\BackPanel\UnitController::class, 'save'])->name('unit.save');
    Route::post('/list', [\App\Http\Controllers\BackPanel\UnitController::class, 'list'])->name('unit.list');
    Route::post('/delete', [\App\Http\Controllers\BackPanel\UnitController::class, 'delete'])->name('unit.delete');
    Route::post('/restore', [\App\Http\Controllers\BackPanel\UnitController::class, 'restore'])->name('unit.restore');
});

Route::group(['prefix'=>'purchase'],function(){
    Route::get('/', [PurchaseController::class, 'index'])->name('purchase');
    Route::post('/list',[PurchaseController::class,'list'])->name('purchase.list');
    Route::get('/addpurchase',[PurchaseController::class,'addpurchase'])->name('purchase.addpurchase');
});


});


