<?php

use App\Http\Controllers\BackPanel\CategoryController;
use App\Http\Controllers\BackPanel\DashboardController;
use App\Http\Controllers\BackPanel\ExportController;
use App\Http\Controllers\BackPanel\InventoryBatchController;
use App\Http\Controllers\BackPanel\ProductBatchesController;
use App\Http\Controllers\BackPanel\ProductController;
use App\Http\Controllers\BackPanel\ProfileController;
use App\Http\Controllers\BackPanel\PurchaseOrderController;
use App\Http\Controllers\BackPanel\PurchaseController;
use App\Http\Controllers\BackPanel\ReportController;
use App\Http\Controllers\BackPanel\RolePermissionController;
use App\Http\Controllers\BackPanel\SettingsController;
use App\Http\Controllers\BackPanel\StockAdjustmentController;
use App\Http\Controllers\BackPanel\SupplierController;
use App\Http\Controllers\BackPanel\UnitController;
use App\Http\Controllers\BackPanel\UserManagementController;
use App\Http\Controllers\frontend\AccountController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AccountController::class, 'login'])->name('login');
    Route::post('/admin/login', [AccountController::class, 'authenticate'])->name('login.submit');
});

Route::post('/admin/logout', [AccountController::class, 'logout'])->middleware('auth')->name('logout');

Route::redirect('/admin', '/admin/dashboard');

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::post('/update', [ProfileController::class, 'update'])->name('update');
    });

    Route::group(['prefix' => 'category', 'middleware' => 'permission:inventory.category'], function () {
        Route::get('/', [CategoryController::class, 'index'])->name('category');
        Route::post('/save', [CategoryController::class, 'save'])->name('category.save');
        Route::post('/list', [CategoryController::class, 'list'])->name('category.list');
        Route::post('/delete', [CategoryController::class, 'delete'])->name('category.delete');
        Route::post('/restore', [CategoryController::class, 'restore'])->name('category.restore');
    });

    Route::group(['prefix' => 'product', 'middleware' => 'permission:inventory.product'], function () {
        Route::get('/', [ProductController::class, 'index'])->name('product');
        Route::post('/save', [ProductController::class, 'save'])->name('product.save');
        Route::post('/list', [ProductController::class, 'list'])->name('product.list');
        Route::post('/view', [ProductController::class, 'view'])->name('product.view');
        Route::post('/delete', [ProductController::class, 'delete'])->name('product.delete');
        Route::any('/form', [ProductController::class, 'form'])->name('product.form');
        Route::post('/restore', [ProductController::class, 'restore'])->name('product.restore');
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/products', [ProductController::class, 'inventoryIndex'])->middleware('permission:inventory.product')->name('products.index');
        Route::post('/products/list', [ProductController::class, 'list'])->middleware('permission:inventory.product')->name('products.list');

        Route::get('/batches', [InventoryBatchController::class, 'index'])->middleware('permission:inventory.batch')->name('batches.index');
        Route::post('/batches', [InventoryBatchController::class, 'store'])->middleware('permission:inventory.batch')->name('batches.store');
        Route::post('/batches/{batch}/delete', [InventoryBatchController::class, 'destroy'])->middleware('permission:inventory.batch')->name('batches.delete');

        Route::get('/adjustments', [StockAdjustmentController::class, 'index'])->middleware('permission:inventory.adjustment')->name('adjustments.index');
        Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->middleware('permission:inventory.adjustment')->name('adjustments.store');
    });

    Route::group(['prefix' => 'batch', 'middleware' => 'permission:inventory.batch'], function () {
        Route::get('/{slug}', [ProductBatchesController::class, 'index'])->name('batch');
        Route::post('/list', [ProductBatchesController::class, 'list'])->name('batch.list');
    });

    Route::group(['prefix' => 'supplier', 'middleware' => 'permission:purchase.supplier'], function () {
        Route::get('/', [SupplierController::class, 'index'])->name('supplier');
        Route::post('/save', [SupplierController::class, 'save'])->name('supplier.save');
        Route::post('/list', [SupplierController::class, 'list'])->name('supplier.list');
        Route::post('/delete', [SupplierController::class, 'delete'])->name('supplier.delete');
        Route::post('/restore', [SupplierController::class, 'restore'])->name('supplier.restore');
    });

    Route::group(['prefix' => 'unit', 'middleware' => 'permission:inventory.unit'], function () {
        Route::get('/', [UnitController::class, 'index'])->name('unit');
        Route::post('/save', [UnitController::class, 'save'])->name('unit.save');
        Route::post('/list', [UnitController::class, 'list'])->name('unit.list');
        Route::post('/delete', [UnitController::class, 'delete'])->name('unit.delete');
        Route::post('/restore', [UnitController::class, 'restore'])->name('unit.restore');
    });

    Route::group(['prefix' => 'purchase', 'middleware' => 'permission:purchase.entry'], function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('purchase');
        Route::post('/list', [PurchaseController::class, 'list'])->name('purchase.list');
        Route::get('/create', [PurchaseController::class, 'addpurchase'])->name('purchase.create');
        Route::get('/addpurchase', [PurchaseController::class, 'addpurchase'])->name('purchase.addpurchase');
        Route::get('/supplier-options', [PurchaseController::class, 'supplierOptions'])->name('purchase.supplier-options');
        Route::get('/product-options', [PurchaseController::class, 'productOptions'])->name('purchase.product-options');
        Route::post('/save', [PurchaseController::class, 'save'])->name('purchase.save');
    });

    Route::prefix('purchase/orders')->name('purchase-orders.')->middleware('permission:purchase.orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
        Route::post('/store', [PurchaseOrderController::class, 'store'])->name('store');
        Route::get('/supplier-options', [PurchaseOrderController::class, 'supplierOptions'])->name('supplier-options');
        Route::get('/product-options', [PurchaseOrderController::class, 'productOptions'])->name('product-options');
        Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('show');
        Route::post('/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->middleware('permission:purchase.orders')->name('approve');
        Route::get('/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->middleware('permission:purchase.receive')->name('receive');
        Route::post('/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveStore'])->middleware('permission:purchase.receive')->name('receive.store');
        Route::post('/{purchaseOrder}/payment', [PurchaseOrderController::class, 'updatePayment'])->middleware('permission:purchase.payment')->name('payment');
    });

    Route::group(['prefix' => 'report'], function () {
        Route::get('/low-stock', [ReportController::class, 'lowStock'])->middleware('permission:report.low_stock')->name('report.lowstock');
        Route::get('/expiry-alert', [ReportController::class, 'expiryAlert'])->middleware('permission:report.expiry')->name('report.expiry');
        Route::get('/purchases', [ReportController::class, 'purchaseHistory'])->middleware('permission:report.purchases')->name('report.purchases');
        Route::get('/supplier-performance', [ReportController::class, 'supplierPerformance'])->middleware('permission:report.suppliers')->name('report.suppliers');
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::prefix('users')->name('user.')->middleware('permission:user.manage')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::get('/create', [UserManagementController::class, 'create'])->name('create');
            Route::post('/store', [UserManagementController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
            Route::post('/{user}/update', [UserManagementController::class, 'update'])->name('update');
            Route::post('/{user}/delete', [UserManagementController::class, 'delete'])->name('delete');
            Route::post('/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('toggle-active');
        });

        Route::prefix('role-permission')->name('role-permission.')->middleware('permission:role.manage')->group(function () {
            Route::get('/', [RolePermissionController::class, 'index'])->name('index');
            Route::get('/create', [RolePermissionController::class, 'create'])->name('create');
            Route::post('/store', [RolePermissionController::class, 'store'])->name('store');
            Route::get('/{role}/edit', [RolePermissionController::class, 'edit'])->name('edit');
            Route::post('/{role}/update', [RolePermissionController::class, 'update'])->name('update');
            Route::post('/{role}/delete', [RolePermissionController::class, 'delete'])->name('delete');
        });

        Route::prefix('settings')->name('settings.')->middleware('permission:settings.manage')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::post('/update', [SettingsController::class, 'update'])->name('update');
        });

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/category', [ExportController::class, 'categories'])->name('category');
            Route::get('/unit', [ExportController::class, 'units'])->name('unit');
            Route::get('/supplier', [ExportController::class, 'suppliers'])->name('supplier');
            Route::get('/product', [ExportController::class, 'products'])->name('product');
            Route::get('/purchase', [ExportController::class, 'purchases'])->name('purchase');
            Route::get('/purchase-supplier-summary', [ExportController::class, 'purchaseSupplierSummary'])->name('purchase-supplier-summary');
            Route::get('/purchase-orders', [ExportController::class, 'purchaseOrders'])->name('purchase-orders');
            Route::get('/purchase-history', [ExportController::class, 'purchaseHistory'])->name('purchase-history');
            Route::get('/supplier-performance', [ExportController::class, 'supplierPerformance'])->name('supplier-performance');
            Route::get('/inventory-products', [ExportController::class, 'inventoryProducts'])->name('inventory-products');
            Route::get('/inventory-batches', [ExportController::class, 'inventoryBatches'])->name('inventory-batches');
            Route::get('/user', [ExportController::class, 'users'])->name('user');
            Route::get('/low-stock', [ExportController::class, 'lowStock'])->name('low-stock');
            Route::get('/expiry-alert', [ExportController::class, 'expiryAlert'])->name('expiry-alert');
            Route::get('/batch/{slug}', [ExportController::class, 'batches'])->name('batch');
        });
    });
});
