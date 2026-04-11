<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DropdownOptionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InventoryBatchController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PartyTypeController;
use App\Http\Controllers\ProductBatchesController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierTypeController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthController::class, 'login'])->name('login');
    Route::post('/admin/login', [AuthController::class, 'authenticate'])->name('login.submit');
});

Route::post('/admin/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::redirect('/admin', '/admin/dashboard');

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::post('/update', [ProfileController::class, 'update'])->name('update');
    });

    Route::group(['prefix' => 'company', 'middleware' => 'permission:inventory.company'], function () {
        Route::get('/', [CompanyController::class, 'index'])->name('company');
        Route::post('/save', [CompanyController::class, 'save'])->name('company.save');
        Route::post('/list', [CompanyController::class, 'list'])->name('company.list');
        Route::post('/delete', [CompanyController::class, 'delete'])->name('company.delete');
        Route::post('/restore', [CompanyController::class, 'restore'])->name('company.restore');
    });

    Route::group(['prefix' => 'product', 'middleware' => 'permission:inventory.product'], function () {
        Route::get('/', [ProductController::class, 'index'])->name('product');
        Route::post('/save', [ProductController::class, 'save'])->name('product.save');
        Route::post('/quick-save', [ProductController::class, 'quickStore'])->name('product.quick-save');
        Route::post('/list', [ProductController::class, 'list'])->name('product.list');
        Route::post('/view', [ProductController::class, 'view'])->name('product.view');
        Route::post('/delete', [ProductController::class, 'delete'])->name('product.delete');
        Route::post('/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('product.toggle-active');
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
        Route::post('/adjustments/{stockAdjustment}/delete', [StockAdjustmentController::class, 'delete'])->middleware('permission:inventory.adjustment')->name('adjustments.delete');

        Route::get('/movements', [InventoryMovementController::class, 'index'])->middleware('permission:inventory.view')->name('movements.index');
        Route::post('/movements/list', [InventoryMovementController::class, 'list'])->middleware('permission:inventory.view')->name('movements.list');
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
        Route::get('/product-info', [PurchaseController::class, 'productInfo'])->name('purchase.product-info');
        Route::post('/save', [PurchaseController::class, 'save'])->name('purchase.save');
    });

    Route::prefix('purchase/returns')->name('purchase-returns.')->group(function () {
        Route::get('/', [PurchaseReturnController::class, 'index'])->name('index');
        Route::post('/list', [PurchaseReturnController::class, 'list'])->name('list');
        Route::get('/create', [PurchaseReturnController::class, 'create'])->name('create');
        Route::post('/store', [PurchaseReturnController::class, 'store'])->name('store');
        Route::get('/{purchaseReturn}/edit', [PurchaseReturnController::class, 'edit'])->name('edit');
        Route::post('/{purchaseReturn}/update', [PurchaseReturnController::class, 'update'])->name('update');
        Route::post('/{purchaseReturn}/delete', [PurchaseReturnController::class, 'destroy'])->name('delete');
        Route::get('/get-purchases', [PurchaseReturnController::class, 'getPurchases'])->name('get-purchases');
        Route::get('/get-items', [PurchaseReturnController::class, 'getItems'])->name('get-items');
        Route::get('/get-batches', [PurchaseReturnController::class, 'getSupplierBatches'])->name('get-batches');
        Route::get('/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('show');
        Route::get('/{purchaseReturn}/print', [PurchaseReturnController::class, 'print'])->name('print');
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

    Route::prefix('customers')->name('customers.')->middleware('permission:party.manage')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::post('/list', [CustomerController::class, 'list'])->name('list');
        Route::post('/save', [CustomerController::class, 'save'])->name('save');
        Route::get('/options', [CustomerController::class, 'options'])->name('options');
        Route::get('/{customer}/ledger', [CustomerController::class, 'ledger'])->name('ledger');
        Route::get('/{customer}/ledger/print', [CustomerController::class, 'ledgerPdf'])->name('ledger.print');
        Route::post('/{customer}/toggle-active', [CustomerController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{customer}/delete', [CustomerController::class, 'delete'])->name('delete');
    });

    Route::prefix('sales')->name('sales.')->group(function () {
        Route::middleware('permission:sales.return')->group(function () {
            Route::get('/returns', [SalesInvoiceController::class, 'returnsIndex'])->name('returns.index');
            Route::post('/returns/list', [SalesInvoiceController::class, 'returnsList'])->name('returns.list');
        });

        Route::middleware('permission:sales.invoice')->group(function () {
            Route::get('/invoices', [SalesInvoiceController::class, 'index'])->name('index');
            Route::post('/invoices/list', [SalesInvoiceController::class, 'list'])->name('list');
            Route::get('/invoices/create', [SalesInvoiceController::class, 'create'])->name('create');
            Route::post('/invoices/store', [SalesInvoiceController::class, 'store'])->name('store');
            Route::get('/invoices/{salesInvoice}', [SalesInvoiceController::class, 'show'])->name('show');
            Route::get('/invoices/{salesInvoice}/print', [SalesInvoiceController::class, 'printView'])->name('print');
            Route::get('/invoices/{salesInvoice}/pdf', [SalesInvoiceController::class, 'pdf'])->name('pdf');
            Route::post('/invoices/{salesInvoice}/payment', [SalesInvoiceController::class, 'updatePayment'])->name('payment');
            Route::post('/invoices/{salesInvoice}/returns', [SalesInvoiceController::class, 'returnStore'])->name('return.store');
            Route::get('/customer-options', [SalesInvoiceController::class, 'customerOptions'])->name('customer-options');
            Route::get('/product-options', [SalesInvoiceController::class, 'productOptions'])->name('product-options');
            Route::get('/product-info', [SalesInvoiceController::class, 'productInfo'])->name('product-info');
        });
    });

    Route::prefix('expenses')->name('expenses.')->middleware('permission:expense.manage')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::post('/list', [ExpenseController::class, 'list'])->name('list');
        Route::post('/save', [ExpenseController::class, 'save'])->name('save');
        Route::post('/{expense}/delete', [ExpenseController::class, 'delete'])->name('delete');
    });

    Route::prefix('payments')->name('payments.')->middleware('permission:accounting.ledger')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/in/create', [PaymentController::class, 'createIn'])->name('in.create');
        Route::post('/in', [PaymentController::class, 'storeIn'])->name('in.store');
        Route::get('/out/create', [PaymentController::class, 'createOut'])->name('out.create');
        Route::post('/out', [PaymentController::class, 'storeOut'])->name('out.store');
        Route::get('/{payment}/edit', [PaymentController::class, 'edit'])->name('edit');
        Route::post('/{payment}/update', [PaymentController::class, 'update'])->name('update');
        Route::get('/outstanding-bills', [PaymentController::class, 'outstandingBills'])->name('outstanding-bills');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
        Route::get('/{payment}/print', [PaymentController::class, 'print'])->name('print');
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/ledger', [FinanceController::class, 'ledger'])->middleware('permission:accounting.ledger')->name('ledger');
        Route::get('/day-book', [FinanceController::class, 'dayBook'])->middleware('permission:accounting.ledger')->name('day-book');
        Route::post('/day-book/list', [FinanceController::class, 'dayBookList'])->middleware('permission:accounting.ledger')->name('day-book.list');
        Route::get('/account-tree', [FinanceController::class, 'accountTree'])->middleware('permission:accounting.ledger')->name('account-tree');
        Route::get('/trial-balance', [FinanceController::class, 'trialBalance'])->middleware('permission:accounting.trial_balance')->name('trial-balance');
        Route::get('/cash-book', [FinanceController::class, 'cashBook'])->middleware('permission:accounting.cash_book')->name('cash-book');
        Route::get('/bank-book', [FinanceController::class, 'bankBook'])->middleware('permission:accounting.bank_book')->name('bank-book');
    });

    Route::group(['prefix' => 'report'], function () {
        Route::get('/low-stock', [ReportController::class, 'lowStock'])->middleware('permission:report.low_stock')->name('report.lowstock');
        Route::get('/expiry-alert', [ReportController::class, 'expiryAlert'])->middleware('permission:report.expiry')->name('report.expiry');
        Route::get('/expiry-alert/print', [ReportController::class, 'expiryAlertPrint'])->middleware('permission:report.expiry')->name('reports.expiry-alert.print');
        Route::get('/purchases', [ReportController::class, 'purchaseHistory'])->middleware('permission:report.purchases')->name('report.purchases');
        Route::get('/sales', [ReportController::class, 'salesReport'])->middleware('permission:report.sales')->name('report.sales');
        Route::get('/supplier-performance', [ReportController::class, 'supplierPerformance'])->middleware('permission:report.suppliers')->name('report.suppliers');
    });

    Route::get('/sales-invoices/{salesInvoice}/print', [SalesInvoiceController::class, 'printPdf'])->middleware('permission:sales.invoice')->name('sales-invoices.print');
    Route::get('/purchases/{purchase}/print', [PurchaseController::class, 'print'])->middleware('permission:purchase.entry')->name('purchases.print');
    Route::get('/sales-invoices/{salesInvoice}/pdf', [SalesInvoiceController::class, 'pdf'])->middleware('permission:sales.invoice')->name('sales-invoices.pdf');

    Route::middleware(['role:admin'])->group(function () {
        Route::prefix('users')->name('user.')->middleware('permission:user.manage')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::post('/list', [UserManagementController::class, 'list'])->name('list');
            Route::get('/create', [UserManagementController::class, 'create'])->name('create');
            Route::post('/store', [UserManagementController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
            Route::post('/{user}/update', [UserManagementController::class, 'update'])->name('update');
            Route::post('/{user}/delete', [UserManagementController::class, 'delete'])->name('delete');
            Route::post('/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{user}/update-role', [UserManagementController::class, 'updateRole'])->name('update-role');
            Route::post('/{user}/update-status', [UserManagementController::class, 'updateStatus'])->name('update-status');
        });

        Route::prefix('role-permission')->name('role-permission.')->middleware('permission:role.manage')->group(function () {
            Route::get('/', [RolePermissionController::class, 'index'])->name('index');
            Route::post('/list', [RolePermissionController::class, 'list'])->name('list');
            Route::get('/create', [RolePermissionController::class, 'create'])->name('create');
            Route::post('/store', [RolePermissionController::class, 'store'])->name('store');
            Route::get('/{role}/edit', [RolePermissionController::class, 'edit'])->name('edit');
            Route::post('/{role}/update', [RolePermissionController::class, 'update'])->name('update');
            Route::post('/{role}/delete', [RolePermissionController::class, 'delete'])->name('delete');
        });

        Route::prefix('settings')->name('settings.')->middleware('permission:settings.manage')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::post('/update', [SettingsController::class, 'update'])->name('update');
            Route::post('/test-mail', [SettingsController::class, 'testMail'])->name('test-mail');
            Route::get('/options', [DropdownOptionController::class, 'index'])->name('options.index');
            Route::post('/options', [DropdownOptionController::class, 'store'])->name('options.store');
            Route::put('/options/{dropdownOption}', [DropdownOptionController::class, 'update'])->name('options.update');
            Route::delete('/options/{dropdownOption}', [DropdownOptionController::class, 'destroy'])->name('options.destroy');
            Route::redirect('/dropdown-options', '/admin/settings/options');
        });

        Route::prefix('party-types')->name('party-types.')->middleware('permission:settings.manage')->group(function () {
            Route::get('/', [PartyTypeController::class, 'index'])->name('index');
            Route::post('/store', [PartyTypeController::class, 'store'])->name('store');
            Route::post('/{partyType}/update', [PartyTypeController::class, 'update'])->name('update');
            Route::post('/{partyType}/delete', [PartyTypeController::class, 'destroy'])->name('delete');
        });

        Route::prefix('supplier-types')->name('supplier-types.')->middleware('permission:settings.manage')->group(function () {
            Route::get('/', [SupplierTypeController::class, 'index'])->name('index');
            Route::post('/store', [SupplierTypeController::class, 'store'])->name('store');
            Route::post('/{supplierType}/update', [SupplierTypeController::class, 'update'])->name('update');
            Route::post('/{supplierType}/delete', [SupplierTypeController::class, 'destroy'])->name('delete');
        });

        Route::prefix('imports')->name('imports.')->group(function () {
            Route::get('/sample/companies', [ImportController::class, 'sampleCompanies'])->name('sample.companies');
            Route::get('/sample/units', [ImportController::class, 'sampleUnits'])->name('sample.units');
            Route::get('/sample/products', [ImportController::class, 'sampleProducts'])->name('sample.products');
            Route::get('/sample/customers', [ImportController::class, 'sampleCustomers'])->name('sample.customers');
            Route::get('/sample/suppliers', [ImportController::class, 'sampleSuppliers'])->name('sample.suppliers');
            Route::post('/companies', [ImportController::class, 'importCompanies'])->name('companies');
            Route::post('/units', [ImportController::class, 'importUnits'])->name('units');
            Route::post('/products', [ImportController::class, 'importProducts'])->name('products');
            Route::post('/customers', [ImportController::class, 'importCustomers'])->name('customers');
            Route::post('/suppliers', [ImportController::class, 'importSuppliers'])->name('suppliers');
        });

        Route::prefix('ocr')->name('ocr.')->middleware('permission:purchase.entry')->group(function () {
            Route::get('/', [OcrController::class, 'index'])->name('index');
            Route::post('/extract', [OcrController::class, 'extract'])->name('extract');
            Route::post('/draft-purchase', [OcrController::class, 'draftPurchase'])->name('draft-purchase');
        });

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/company', [ExportController::class, 'companies'])->name('company');
            Route::get('/company/pdf', [ExportController::class, 'companiesPdf'])->name('company-pdf');
            Route::get('/unit', [ExportController::class, 'units'])->name('unit');
            Route::get('/unit/pdf', [ExportController::class, 'unitsPdf'])->name('unit-pdf');
            Route::get('/supplier', [ExportController::class, 'suppliers'])->name('supplier');
            Route::get('/supplier/pdf', [ExportController::class, 'suppliersPdf'])->name('supplier-pdf');
            Route::get('/product', [ExportController::class, 'products'])->name('product');
            Route::get('/product/pdf', [ExportController::class, 'productsPdf'])->name('product-pdf');
            Route::get('/customers', [ExportController::class, 'customers'])->name('customers');
            Route::get('/customers/pdf', [ExportController::class, 'customersPdf'])->name('customers-pdf');
            Route::get('/sales-invoices', [ExportController::class, 'salesInvoices'])->name('sales-invoices');
            Route::get('/expenses', [ExportController::class, 'expenses'])->name('expenses');
            Route::get('/ledger', [ExportController::class, 'ledger'])->name('ledger');
            Route::get('/ledger/pdf', [ExportController::class, 'ledgerPdf'])->name('ledger-pdf');
            Route::get('/trial-balance', [ExportController::class, 'trialBalance'])->name('trial-balance');
            Route::get('/trial-balance/pdf', [ExportController::class, 'trialBalancePdf'])->name('trial-balance-pdf');
            Route::get('/cash-book', [ExportController::class, 'cashBook'])->name('cash-book');
            Route::get('/cash-book/pdf', [ExportController::class, 'cashBookPdf'])->name('cash-book-pdf');
            Route::get('/bank-book', [ExportController::class, 'bankBook'])->name('bank-book');
            Route::get('/bank-book/pdf', [ExportController::class, 'bankBookPdf'])->name('bank-book-pdf');
            Route::get('/account-tree/pdf', [ExportController::class, 'accountTreePdf'])->name('account-tree-pdf');
            Route::get('/purchase', [ExportController::class, 'purchases'])->name('purchase');
            Route::get('/purchase-supplier-summary', [ExportController::class, 'purchaseSupplierSummary'])->name('purchase-supplier-summary');
            Route::get('/purchase-orders', [ExportController::class, 'purchaseOrders'])->name('purchase-orders');
            Route::get('/purchase-history', [ExportController::class, 'purchaseHistory'])->name('purchase-history');
            Route::get('/purchase-history/pdf', [ExportController::class, 'purchaseHistoryPdf'])->name('purchase-history-pdf');
            Route::get('/supplier-performance', [ExportController::class, 'supplierPerformance'])->name('supplier-performance');
            Route::get('/supplier-performance/pdf', [ExportController::class, 'supplierPerformancePdf'])->name('supplier-performance-pdf');
            Route::get('/inventory-products', [ExportController::class, 'inventoryProducts'])->name('inventory-products');
            Route::get('/inventory-products/pdf', [ExportController::class, 'inventoryProductsPdf'])->name('inventory-products-pdf');
            Route::get('/inventory-batches', [ExportController::class, 'inventoryBatches'])->name('inventory-batches');
            Route::get('/inventory-batches/pdf', [ExportController::class, 'inventoryBatchesPdf'])->name('inventory-batches-pdf');
            Route::get('/user', [ExportController::class, 'users'])->name('user');
            Route::get('/user/pdf', [ExportController::class, 'usersPdf'])->name('user-pdf');
            Route::get('/low-stock', [ExportController::class, 'lowStock'])->name('low-stock');
            Route::get('/low-stock/pdf', [ExportController::class, 'lowStockPdf'])->name('low-stock-pdf');
            Route::get('/expiry-alert', [ExportController::class, 'expiryAlert'])->name('expiry-alert');
            Route::get('/expiry-alert/pdf', [ExportController::class, 'expiryAlertPdf'])->name('expiry-alert-pdf');
            Route::get('/sales-invoices/pdf', [ExportController::class, 'salesInvoicesPdf'])->name('sales-invoices-pdf');
            Route::get('/batch/{slug}', [ExportController::class, 'batches'])->name('batch');
        });
    });
});
