<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\ProductController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\CustomerController;
use App\Http\Controllers\Dashboard\ExternalCustomerController;
use App\Http\Controllers\Dashboard\EmployeeController;
use App\Http\Controllers\Dashboard\SupplierController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\PaySalaryController;
use App\Http\Controllers\Dashboard\AttendenceController;
use App\Http\Controllers\Dashboard\AdvanceSalaryController;
use App\Http\Controllers\Dashboard\DatabaseBackupController;
use App\Http\Controllers\Dashboard\OrderController;
use App\Http\Controllers\Dashboard\PosController;
use App\Http\Controllers\Dashboard\RoleController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\RepairsController;
use App\Http\Controllers\Dashboard\TandaController;
use App\Http\Controllers\Dashboard\TandaPeriodController;
use App\Http\Controllers\Dashboard\CashController;
use App\Http\Controllers\Dashboard\BranchController;
use App\Http\Controllers\Dashboard\FinancierasController;
use App\Http\Controllers\Dashboard\FinSaleController;
use App\Http\Controllers\Dashboard\FinDeviceController;
use App\Http\Controllers\Dashboard\FinWarrantyController;
use App\Http\Controllers\Dashboard\FinTheftReportController;
use App\Http\Controllers\Dashboard\FinCatalogController;
use App\Http\Controllers\Dashboard\LoanController;
use App\Http\Controllers\Dashboard\UnifiedPosController;
use App\Http\Controllers\Dashboard\GiftController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// DEFAULT DASHBOARD & PROFILE
Route::middleware('auth')->group(function () {
    Route::middleware(['permission:dashboard.menu|dashboard.read'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/financieras/export-pdf', [DashboardController::class, 'exportFinancieraPaymentsPdf'])
            ->middleware('permission:dashboard.export|financieras.dashboard_payments|financieras.export')
            ->name('dashboard.financieras.pdf');
        Route::get('/dashboard/financieras/export-excel', [DashboardController::class, 'exportFinancieraPaymentsExcel'])
            ->middleware('permission:dashboard.export|financieras.dashboard_payments|financieras.export')
            ->name('dashboard.financieras.excel');
        Route::post('/dashboard/transactions/{transaction}/cancel', [DashboardController::class, 'cancelTransaction'])
            ->middleware('permission:dashboard.cancel_transaction|cobros.cancel_transaction')
            ->name('dashboard.transactions.cancel');
    });

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');
});

// ====== MÓDULO CENTRALIZADO DE COBROS Y VENTAS (4 EN 1) ======
Route::middleware(['auth', 'permission:cobros.menu|cobros.read|pos.menu|pos.read'])->prefix('central-pos')->name('central-pos.')->group(function () {
    Route::get('/', [UnifiedPosController::class, 'index'])->name('index');
    Route::get('/lookup-financiera', [UnifiedPosController::class, 'lookupFinanciera'])->name('lookup-financiera');
    Route::get('/lookup-loans', [UnifiedPosController::class, 'lookupCustomerLoans'])->name('lookup-loans');
    Route::get('/lookup-pending-notes', [UnifiedPosController::class, 'lookupCustomerPendingNotes'])->name('lookup-pending-notes');
    Route::get('/lookup-tanda', [UnifiedPosController::class, 'lookupTanda'])->name('lookup-tanda');
    Route::get('/lookup-products', [UnifiedPosController::class, 'lookupProducts'])->name('lookup-products');
    Route::get('/lookup-gifts', [UnifiedPosController::class, 'lookupGifts'])->name('lookup-gifts');
    Route::get('/external-customers', [UnifiedPosController::class, 'lookupExternalCustomers'])->name('external-customers.lookup');
    Route::post('/external-customers', [UnifiedPosController::class, 'storeExternalCustomer'])->name('external-customers.store');
    Route::get('/recent-transactions', [UnifiedPosController::class, 'recentTransactions'])->name('recent-transactions');
    Route::post('/checkout', [UnifiedPosController::class, 'checkout'])->name('checkout');
    Route::get('/receipt/{transaction}', [UnifiedPosController::class, 'printReceipt'])->name('receipt');
    Route::get('/open-drawer', [UnifiedPosController::class, 'openCashDrawer'])->name('open-drawer');
    Route::get('/open-drawer-view', [UnifiedPosController::class, 'openCashDrawer'])->name('open-drawer.view');
});

// ====== MÓDULO DE PRÉSTAMOS ======
Route::middleware(['auth', 'permission:prestamos.menu|prestamos.read'])->prefix('loans')->name('loans.')->group(function () {
    Route::get('/', [LoanController::class, 'index'])->name('index');
    Route::get('/create', [LoanController::class, 'create'])->name('create');
    Route::post('/', [LoanController::class, 'store'])->name('store');
    Route::get('/check-customer-alerts', [LoanController::class, 'checkCustomerAlerts'])->name('check-customer-alerts');

    // Catálogo CRUD Completo de Tipos de Préstamos
    Route::get('/types', [LoanController::class, 'loanTypesIndex'])->name('types.index');
    Route::post('/types', [LoanController::class, 'storeLoanType'])->name('types.store');
    Route::put('/types/{loanType}', [LoanController::class, 'updateLoanType'])->name('types.update');
    Route::patch('/types/{loanType}/toggle', [LoanController::class, 'toggleLoanType'])->name('types.toggle');
    Route::delete('/types/{loanType}', [LoanController::class, 'destroyLoanType'])->name('types.destroy');

    Route::get('/ticket/{payment}', [LoanController::class, 'printTicket'])->name('ticket');

    Route::get('/{loan}', [LoanController::class, 'show'])->name('show');
    Route::post('/{loan}/payments', [LoanController::class, 'registerPayment'])->name('payments.store');
    Route::post('/{loan}/close-manual', [LoanController::class, 'manualClose'])->name('close-manual');
    Route::post('/{loan}/cancel', [LoanController::class, 'cancel'])->name('cancel');
    Route::get('/{loan}/pagare/print', [LoanController::class, 'printPromissoryNote'])->name('pagare.print');
    Route::get('/{loan}/recibo-entrega/print', [LoanController::class, 'printDeliveryReceipt'])->name('delivery-receipt.print');
    Route::post('/{loan}/pagare/upload', [LoanController::class, 'uploadPromissoryNote'])->name('pagare.upload');
    Route::get('/{loan}/pagare/download', [LoanController::class, 'downloadPromissoryNote'])->name('pagare.download');

    // Video de evidencia exclusivo para SuperAdmin
    Route::get('/{loan}/video/stream', [LoanController::class, 'streamVideo'])->name('video.stream');
    Route::post('/{loan}/video', [LoanController::class, 'updateVideo'])->name('video.update');
    Route::delete('/{loan}/video', [LoanController::class, 'deleteVideo'])->name('video.delete');
});

// ====== MÓDULO DE VENTA A CRÉDITO DE ARTÍCULOS ======
Route::middleware(['auth', 'permission:prestamos.menu|prestamos.read'])->prefix('article-credits')->name('article-credits.')->group(function () {
    Route::get('/', [LoanController::class, 'articleCreditsIndex'])->name('index');
    Route::get('/create', [LoanController::class, 'articleCreditsCreate'])->name('create');
    Route::post('/', [LoanController::class, 'store'])->name('store');
});

// ====== MÓDULO DE GIFTS Y PROMOCIONALES POR PROVEEDOR ======
Route::middleware(['auth', 'permission:gifts.menu|gifts.read'])->prefix('gifts')->name('gifts.')->group(function () {
    Route::get('/', [GiftController::class, 'index'])->name('index');
    Route::post('/', [GiftController::class, 'store'])->name('store');
    Route::post('/entry', [GiftController::class, 'registerEntry'])->name('entry');
    Route::post('/deliver', [GiftController::class, 'registerDelivery'])->name('deliver');
    Route::put('/{gift}', [GiftController::class, 'update'])->name('update');
    Route::delete('/{gift}', [GiftController::class, 'destroy'])->name('destroy');
});

// ====== USERS ======
Route::middleware(['permission:user.menu|user.read'])->group(function () {
    Route::resource('/users', UserController::class)->except(['show']);
});

// ====== BRANCHES ======
Route::middleware(['permission:branch.menu|branch.read'])->group(function () {
    Route::resource('branches', BranchController::class)->names('branches');
});

// ====== CUSTOMERS ======
Route::middleware(['permission:customer.menu|customer.read'])->group(function () {
    Route::get('/customers/check-phone', [CustomerController::class, 'checkPhone'])->name('customers.check-phone');
    Route::get('/customers/check-name', [CustomerController::class, 'checkName'])->name('customers.check-name');
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::get('/customers/export', [CustomerController::class, 'exportData'])->name('customers.exportData');
    Route::resource('/customers', CustomerController::class);
});

// ====== EXTERNAL CUSTOMERS (CLIENTES EXTERNOS) ======
Route::middleware(['permission:external_customer.menu|external_customer.read|external_customer.create|external_customer.edit|external_customer.delete|external_customer.convert'])->group(function () {
    Route::post('/external-customers/{externalCustomer}/convert', [ExternalCustomerController::class, 'convert'])->name('external-customers.convert');
    Route::resource('/external-customers', ExternalCustomerController::class);
});

// ====== SUPPLIERS ======
Route::middleware(['permission:supplier.menu|supplier.read'])->group(function () {
    Route::resource('/suppliers', SupplierController::class);
});

// ====== EMPLOYEES ======
Route::middleware(['permission:employee.menu|employee.read'])->group(function () {
    Route::resource('/employees', EmployeeController::class);
});

// ====== EMPLOYEE ATTENDENCE ======
Route::middleware(['permission:attendence.menu|attendence.read|attendence.create'])->group(function () {
    Route::resource('/employee/attendence', AttendenceController::class)->except(['show', 'update', 'destroy']);
});

// ====== SALARY EMPLOYEE ======
Route::middleware(['permission:salary.menu|salary.read|salary.create'])->group(function () {
    // PaySalary
    Route::resource('/pay-salary', PaySalaryController::class)->except(['show', 'create', 'edit', 'update']);
    Route::get('/pay-salary/history', [PaySalaryController::class, 'payHistory'])->name('pay-salary.payHistory');
    Route::get('/pay-salary/history/{id}', [PaySalaryController::class, 'payHistoryDetail'])->name('pay-salary.payHistoryDetail');
    Route::get('/pay-salary/{id}', [PaySalaryController::class, 'paySalary'])->name('pay-salary.paySalary');

    // Advance Salary
    Route::resource('/advance-salary', AdvanceSalaryController::class)->except(['show']);
});

// ====== PRODUCTS ======
Route::middleware(['permission:product.menu|product.read|product.create'])->group(function () {
    Route::get('/products/import', [ProductController::class, 'importView'])->name('products.importView');
    Route::post('/products/import', [ProductController::class, 'importStore'])->name('products.importStore');
    Route::get('/products/export', [ProductController::class, 'exportData'])->name('products.exportData');
    Route::resource('/products', ProductController::class);
});

// ====== CATEGORY PRODUCTS ======
Route::middleware(['permission:category.menu|category.read'])->group(function () {
    Route::resource('/categories', CategoryController::class);
});

// ====== POS (Redirección automática al Módulo Centralizado) ======
Route::middleware(['permission:pos.menu|pos.read'])->group(function () {
    Route::get('/pos', function () {
        return redirect()->route('central-pos.index');
    })->name('pos.index');
    Route::post('/pos/add', [PosController::class, 'addCart'])->name('pos.addCart');
    Route::post('/pos/add-dynamic', [PosController::class, 'addDynamicProduct'])->name('pos.addDynamicProduct');
    Route::post('/pos/update/{rowId}', [PosController::class, 'updateCart'])->name('pos.updateCart');
    Route::get('/pos/delete/{rowId}', [PosController::class, 'deleteCart'])->name('pos.deleteCart');
    Route::post('/pos/invoice/create', [PosController::class, 'createInvoice'])->name('pos.createInvoice');
    Route::get('/pos/invoice/show', [PosController::class, 'showInvoice'])->name('pos.showInvoice');
    Route::post('/pos/invoice/print', [PosController::class, 'printInvoice'])->name('pos.printInvoice');
    Route::get('/pos/customers/search', [CustomerController::class, 'search'])->name('pos.customers.search');

    // Create Order
    Route::post('/pos/order', [OrderController::class, 'storeOrder'])->name('pos.storeOrder');
    Route::post('/pos/scan-barcode', [PosController::class, 'scanBarcode'])->name('pos.scanBarcode');

});

Route::middleware(['permission:repairs.menu|repairs.read'])->group(function () {
    Route::resource('repairs', RepairsController::class);
    Route::get('/buscar-opciones', [RepairsController::class, 'buscarOpciones'])->name('buscar.opciones');
});

Route::middleware(['permission:tandas.menu|tandas.read'])->group(function () {
    Route::resource('tandas', TandaController::class);
    Route::patch('/tanda-periods/{period}', [TandaPeriodController::class, 'updatePayment'])->name('tanda-periods.updatePayment');
    Route::post('/tandas/{tanda}/payments', [TandaController::class, 'updatePayments'])->name('tandas.payments.update');
});

Route::middleware(['permission:cash.menu|cash.read'])->group(function () {
    Route::get('/cash', [CashController::class, 'index'])->name('cash.index');
    Route::get('/cash/daily-cut', [CashController::class, 'dailyCut'])->name('cash.dailyCut');
    Route::post('/cash', [CashController::class, 'store'])->name('cash.store');
    Route::post('/cash/pre-cut', [CashController::class, 'applyPreCut'])->name('cash.preCut');
    Route::post('/cash/apply-cut', [CashController::class, 'applyCut'])->name('cash.applyCut');
    Route::post('/cash/auto-cut-config', [CashController::class, 'updateAutoCutConfig'])->name('cash.autoCutConfig');
    Route::get('/cash/filter-by-date', [CashController::class, 'filterByDate'])->name('cash.filterByDate');
    Route::get('/cash/print', [CashController::class, 'printCut'])->name('cash.printCut');
    Route::get('/cash/export-pdf', [CashController::class, 'exportCutsPdf'])->name('cash.exportPdf');
    Route::get('/cash/export-excel', [CashController::class, 'exportCutsExcel'])->name('cash.exportExcel');
    Route::get('/cash/expenses', [CashController::class, 'expensesReport'])->name('cash.expenses');
    Route::get('/cash/expenses/pdf', [CashController::class, 'exportExpensesPdf'])->name('cash.expenses.pdf');
    Route::get('/cash/expenses/excel', [CashController::class, 'exportExpensesExcel'])->name('cash.expenses.excel');
    Route::get('/cash/expense-types', [CashController::class, 'expenseTypesIndex'])->name('cash.expenseTypes.index');
    Route::post('/cash/expense-types', [CashController::class, 'storeExpenseType'])->name('cash.expenseTypes.store');
    Route::put('/cash/expense-types/{expenseType}', [CashController::class, 'updateExpenseType'])->name('cash.expenseTypes.update');
    Route::patch('/cash/expense-types/{expenseType}/toggle', [CashController::class, 'toggleExpenseType'])->name('cash.expenseTypes.toggle');
    Route::delete('/cash/expense-types/{expenseType}', [CashController::class, 'destroyExpenseType'])->name('cash.expenseTypes.destroy');
});

// ====== ORDERS ======
Route::middleware(['permission:orders.menu|orders.read'])->group(function () {
    Route::get('/orders/pending', [OrderController::class, 'pendingOrders'])->name('order.pendingOrders');
    Route::get('/orders/complete', [OrderController::class, 'completeOrders'])->name('order.completeOrders');
    Route::get('/orders/details/{order_id}', [OrderController::class, 'orderDetails'])->name('order.orderDetails');
    Route::put('/orders/update/status', [OrderController::class, 'updateStatus'])->name('order.updateStatus');
    Route::get('/orders/invoice/download/{order_id}', [OrderController::class, 'invoiceDownload'])->name('order.invoiceDownload');
    Route::delete('order/{id}', [OrderController::class, 'destroy'])->name('order.delete');
    Route::post('/order/updateAmount', [OrderController::class, 'updateAmount'])->name('order.updateAmount');
    Route::post('/video-upload', [OrderController::class, 'uploadVideo'])->name('video.upload');
    Route::get('/orders/{id}/contract', [OrderController::class, 'contract'])->name('order.contract');

    // Pending Due / Notas de Venta con Saldo Pendiente
    Route::get('/pending/due', [OrderController::class, 'pendingDue'])->name('order.pendingDue');
    Route::get('/order/due/{id}', [OrderController::class, 'orderDueAjax'])->name('order.orderDueAjax');
    Route::post('/update/due', [OrderController::class, 'updateDue'])->name('order.updateDue');
    Route::get('/orders/{order}/ticket-abono/{historyId}', [OrderController::class, 'printAbonoTicket'])->name('order.abonoTicket');
    Route::get('/orders/{order}/abonos-history', [OrderController::class, 'abonosHistoryAjax'])->name('order.abonosHistoryAjax');

    // Stock Management
    Route::get('/stock', [OrderController::class, 'stockManage'])->name('order.stockManage');
    Route::post('/order/update-device-id', [OrderController::class, 'updateDeviceId'])->name('order.updateDeviceId');
});

// ====== DATABASE BACKUP ======
Route::middleware(['permission:database.menu|database.read'])->group(function () {
    Route::get('/database/backup', [DatabaseBackupController::class, 'index'])->name('backup.index');
    Route::get('/database/backup/now', [DatabaseBackupController::class, 'create'])->name('backup.create');
    Route::get('/database/backup/download/{getFileName}', [DatabaseBackupController::class, 'download'])->name('backup.download');
    Route::get('/database/backup/delete/{getFileName}', [DatabaseBackupController::class, 'delete'])->name('backup.delete');
});

// ====== ROLE CONTROLLER ======
Route::middleware(['permission:roles.menu|roles.read|roles.edit|permissions.menu|permissions.read'])->group(function () {
    // Permissions
    Route::get('/permission', [RoleController::class, 'permissionIndex'])->name('permission.index');
    Route::get('/permission/create', [RoleController::class, 'permissionCreate'])->name('permission.create');
    Route::post('/permission', [RoleController::class, 'permissionStore'])->name('permission.store');
    Route::get('/permission/edit/{id}', [RoleController::class, 'permissionEdit'])->name('permission.edit');
    Route::put('/permission/{id}', [RoleController::class, 'permissionUpdate'])->name('permission.update');
    Route::delete('/permission/{id}', [RoleController::class, 'permissionDestroy'])->name('permission.destroy');

    // Roles
    Route::get('/role', [RoleController::class, 'roleIndex'])->name('role.index');
    Route::get('/role/create', [RoleController::class, 'roleCreate'])->name('role.create');
    Route::post('/role', [RoleController::class, 'roleStore'])->name('role.store');
    Route::get('/role/edit/{id}', [RoleController::class, 'roleEdit'])->name('role.edit');
    Route::put('/role/{id}', [RoleController::class, 'roleUpdate'])->name('role.update');
    Route::delete('/role/{id}', [RoleController::class, 'roleDestroy'])->name('role.destroy');

    // Role Permissions
    Route::get('/role/permission', [RoleController::class, 'rolePermissionIndex'])->name('rolePermission.index');
    Route::get('/role/permission/create', [RoleController::class, 'rolePermissionCreate'])->name('rolePermission.create');
    Route::post('/role/permission', [RoleController::class, 'rolePermissionStore'])->name('rolePermission.store');
    Route::get('/role/permission/{id}', [RoleController::class, 'rolePermissionEdit'])->name('rolePermission.edit');
    Route::put('/role/permission/{id}', [RoleController::class, 'rolePermissionUpdate'])->name('rolePermission.update');
    Route::delete('/role/permission/{id}', [RoleController::class, 'rolePermissionDestroy'])->name('rolePermission.destroy');
});

// ====== FINANCIERAS ======
Route::middleware(['auth', 'permission:financieras.menu|financieras.read|financieras.ventas.menu|financieras.inventario.menu|financieras.garantias.menu|financieras.robos.menu|financieras.catalogos.menu'])->prefix('financieras')->name('financieras.')->group(function () {
    Route::get('/', [FinancierasController::class, 'index'])->name('index');
    Route::get('/brands/autocomplete', [FinancierasController::class, 'autocompleteBrands'])->name('brands.autocomplete');
    Route::get('/devices/autocomplete-imei', [FinancierasController::class, 'autocompleteImeis'])->name('devices.autocomplete-imei');
    Route::get('/devices/lookup-imei', [FinancierasController::class, 'lookupImei'])->name('devices.lookup-imei');

    // Section 1: Ventas
    Route::prefix('ventas')->name('ventas.')->group(function () {
        Route::get('/', [FinSaleController::class, 'index'])->name('index');
        Route::get('/create', [FinSaleController::class, 'create'])->name('create');
        Route::get('/create-contado', [FinSaleController::class, 'createContado'])->name('create-contado');
        Route::post('/', [FinSaleController::class, 'store'])->name('store');
        Route::post('/store-contado', [FinSaleController::class, 'storeContado'])->name('store-contado');
        Route::get('/export/excel', [FinSaleController::class, 'exportExcel'])->name('export.excel');
        Route::get('/template/excel', [FinSaleController::class, 'downloadTemplate'])->name('template.excel');
        Route::post('/import/excel', [FinSaleController::class, 'importExcel'])->name('import.excel');
        Route::get('/check-tag', [FinSaleController::class, 'checkTag'])->name('check-tag');
        Route::get('/{sale}', [FinSaleController::class, 'show'])->name('show');
        Route::get('/{sale}/edit', [FinSaleController::class, 'edit'])->name('edit');
        Route::put('/{sale}', [FinSaleController::class, 'update'])->name('update');
        Route::delete('/{sale}', [FinSaleController::class, 'destroy'])->name('destroy');
        Route::post('/{sale}/cancel', [FinSaleController::class, 'cancel'])->name('cancel');
        Route::post('/{sale}/notes', [FinSaleController::class, 'addNote'])->name('notes.store');
        Route::post('/{sale}/abonos', [UnifiedPosController::class, 'registerDirectFinancieraAbono'])->name('abonos.store');
        Route::get('/{sale}/pdf', [FinSaleController::class, 'exportPdf'])->name('pdf');
    });

    // Section 2: Inventario
    Route::prefix('inventario')->name('inventario.')->group(function () {
        Route::get('/', [FinDeviceController::class, 'index'])->name('index');
        Route::get('/create', [FinDeviceController::class, 'create'])->name('create');
        Route::post('/', [FinDeviceController::class, 'store'])->name('store');
        Route::get('/export/excel', [FinDeviceController::class, 'exportExcel'])->name('export.excel');
        Route::get('/template/excel', [FinDeviceController::class, 'downloadTemplate'])->name('template.excel');
        Route::post('/import/excel', [FinDeviceController::class, 'importExcel'])->name('import.excel');
        Route::post('/bulk-delete', [FinDeviceController::class, 'bulkDelete'])->name('bulk-delete');
        Route::get('/{device}', [FinDeviceController::class, 'show'])->name('show');
        Route::get('/{device}/edit', [FinDeviceController::class, 'edit'])->name('edit');
        Route::put('/{device}', [FinDeviceController::class, 'update'])->name('update');
        Route::delete('/{device}', [FinDeviceController::class, 'destroy'])->name('destroy');
        Route::post('/{device}/transfer', [FinDeviceController::class, 'transfer'])->name('transfer');
        Route::get('/{device}/history', [FinDeviceController::class, 'history'])->name('history');
    });

    // Section 3: Garantias
    Route::prefix('garantias')->name('garantias.')->group(function () {
        Route::get('/', [FinWarrantyController::class, 'index'])->name('index');
        Route::get('/create', [FinWarrantyController::class, 'create'])->name('create');
        Route::post('/', [FinWarrantyController::class, 'store'])->name('store');
        Route::get('/export/excel', [FinWarrantyController::class, 'exportExcel'])->name('export.excel');
        Route::get('/{warranty}', [FinWarrantyController::class, 'show'])->name('show');
        Route::get('/{warranty}/edit', [FinWarrantyController::class, 'edit'])->name('edit');
        Route::put('/{warranty}', [FinWarrantyController::class, 'update'])->name('update');
        Route::delete('/{warranty}', [FinWarrantyController::class, 'destroy'])->name('destroy');
        Route::post('/{warranty}/stage', [FinWarrantyController::class, 'changeStage'])->name('stage.update');
    });

    // Section 4: Robos
    Route::prefix('robos')->name('robos.')->group(function () {
        Route::get('/', [FinTheftReportController::class, 'index'])->name('index');
        Route::get('/create', [FinTheftReportController::class, 'create'])->name('create');
        Route::post('/', [FinTheftReportController::class, 'store'])->name('store');
        Route::get('/export/excel', [FinTheftReportController::class, 'exportExcel'])->name('export.excel');
        Route::get('/{theftReport}', [FinTheftReportController::class, 'show'])->name('show');
        Route::get('/{theftReport}/edit', [FinTheftReportController::class, 'edit'])->name('edit');
        Route::put('/{theftReport}', [FinTheftReportController::class, 'update'])->name('update');
        Route::delete('/{theftReport}', [FinTheftReportController::class, 'destroy'])->name('destroy');
        Route::post('/{theftReport}/status', [FinTheftReportController::class, 'updateStatus'])->name('status.update');
    });

    // Catalogs
    Route::prefix('catalogos')->name('catalogos.')->group(function () {
        Route::get('/', [FinCatalogController::class, 'index'])->name('index');
        Route::post('/financieras', [FinCatalogController::class, 'storeFinanciera'])->name('financieras.store');
        Route::put('/financieras/{financiera}', [FinCatalogController::class, 'updateFinanciera'])->name('financieras.update');
        Route::delete('/financieras/{financiera}', [FinCatalogController::class, 'destroyFinanciera'])->name('financieras.destroy');

        Route::post('/brands', [FinCatalogController::class, 'storeBrand'])->name('brands.store');
        Route::put('/brands/{brand}', [FinCatalogController::class, 'updateBrand'])->name('brands.update');
        Route::delete('/brands/{brand}', [FinCatalogController::class, 'destroyBrand'])->name('brands.destroy');

        Route::post('/warranty-stages', [FinCatalogController::class, 'storeWarrantyStage'])->name('warranty-stages.store');
        Route::put('/warranty-stages/{stage}', [FinCatalogController::class, 'updateWarrantyStage'])->name('warranty-stages.update');
        Route::delete('/warranty-stages/{stage}', [FinCatalogController::class, 'destroyWarrantyStage'])->name('warranty-stages.destroy');

        Route::post('/suppliers', [FinCatalogController::class, 'storeSupplier'])->name('suppliers.store');
        Route::put('/suppliers/{supplier}', [FinCatalogController::class, 'updateSupplier'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [FinCatalogController::class, 'destroySupplier'])->name('suppliers.destroy');
    });
});

require __DIR__.'/auth.php';
