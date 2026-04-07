<?php

use App\Http\Controllers\Api\AlertRuleController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\IntegrationConfigController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\KpiTargetController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PlannedShiftController;
use App\Http\Controllers\Api\ProductionLogController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SafetyIncidentController;
use App\Http\Controllers\Api\ShiftRecordController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\WorkerController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─── Health Check ────────────────────────────────────────────────────────
    Route::get('/health', [HealthController::class, 'index'])->name('v1.health');

    // ─── Public Auth Routes ──────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('v1.auth.login');
        Route::post('/register', [AuthController::class, 'register'])->name('v1.auth.register');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('v1.auth.forgot-password');
    });

    // ─── Protected Routes ────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('v1.auth.logout');
            Route::get('/me', [AuthController::class, 'me'])->name('v1.auth.me');
        });

        // Organization — both path styles supported:
        //   /organization/    (canonical, no ID needed — derives from auth token)
        //   /org/{id}         (alias used by frontend services)
        Route::prefix('organization')->group(function () {
            Route::get('/', [OrganizationController::class, 'show'])->name('v1.organization.show');
            Route::put('/', [OrganizationController::class, 'update'])->name('v1.organization.update');
            Route::post('/logo', [OrganizationController::class, 'uploadLogo'])->name('v1.organization.logo');
        });
        Route::prefix('org/{id}')->group(function () {
            Route::get('/', [OrganizationController::class, 'show'])->name('v1.org.show');
            Route::put('/', [OrganizationController::class, 'update'])->name('v1.org.update');
            Route::post('/logo', [OrganizationController::class, 'uploadLogo'])->name('v1.org.logo');
        });

        // User management (team / roles)
        Route::get('/org-users', [UserManagementController::class, 'orgUsers'])->name('v1.org-users.index');
        Route::put('/user-site-roles', [UserManagementController::class, 'updateRole'])->name('v1.user-site-roles.update');
        Route::delete('/user-site-roles', [UserManagementController::class, 'removeFromSite'])->name('v1.user-site-roles.destroy');
        Route::post('/invite-user', [UserManagementController::class, 'inviteUser'])->name('v1.invite-user');

        // Support
        Route::post('/support/message', [SupportController::class, 'message'])->name('v1.support.message');

        // Inventory
        Route::get('/inventory/categories', [InventoryController::class, 'categories'])->name('v1.inventory.categories');
        Route::get('/inventory/consumption', [InventoryController::class, 'consumptionRates'])->name('v1.inventory.consumption');
        Route::post('/inventory/{id}/consume', [InventoryController::class, 'consume'])->name('v1.inventory.consume');
        Route::post('/inventory/{id}/restock', [InventoryController::class, 'restock'])->name('v1.inventory.restock');
        Route::get('/inventory/{id}/transactions', [InventoryController::class, 'transactions'])->name('v1.inventory.item-transactions');
        Route::apiResource('inventory', InventoryController::class)->names([
            'index'   => 'v1.inventory.index',
            'store'   => 'v1.inventory.store',
            'show'    => 'v1.inventory.show',
            'update'  => 'v1.inventory.update',
            'destroy' => 'v1.inventory.destroy',
        ]);

        // Transactions (financial)
        Route::get('/transactions/categories', [TransactionController::class, 'categories'])->name('v1.transactions.categories');
        Route::apiResource('transactions', TransactionController::class)->names([
            'index'   => 'v1.transactions.index',
            'store'   => 'v1.transactions.store',
            'show'    => 'v1.transactions.show',
            'update'  => 'v1.transactions.update',
            'destroy' => 'v1.transactions.destroy',
        ]);

        // Suppliers
        Route::apiResource('suppliers', SupplierController::class)->names([
            'index'   => 'v1.suppliers.index',
            'store'   => 'v1.suppliers.store',
            'show'    => 'v1.suppliers.show',
            'update'  => 'v1.suppliers.update',
            'destroy' => 'v1.suppliers.destroy',
        ]);

        // Channels
        Route::apiResource('channels', ChannelController::class)->names([
            'index'   => 'v1.channels.index',
            'store'   => 'v1.channels.store',
            'show'    => 'v1.channels.show',
            'update'  => 'v1.channels.update',
            'destroy' => 'v1.channels.destroy',
        ]);

        // Orders
        Route::get('/orders/{id}/items', [OrderController::class, 'items'])->name('v1.orders.items');
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('v1.orders.status');
        Route::post('/orders/{id}/receive', [OrderController::class, 'receive'])->name('v1.orders.receive');
        Route::apiResource('orders', OrderController::class)->names([
            'index'   => 'v1.orders.index',
            'store'   => 'v1.orders.store',
            'show'    => 'v1.orders.show',
            'update'  => 'v1.orders.update',
            'destroy' => 'v1.orders.destroy',
        ]);

        // Workers
        Route::get('/workers/{id}/shift-records', [WorkerController::class, 'shiftRecords'])->name('v1.workers.shift-records');
        Route::apiResource('workers', WorkerController::class)->names([
            'index'   => 'v1.workers.index',
            'store'   => 'v1.workers.store',
            'show'    => 'v1.workers.show',
            'update'  => 'v1.workers.update',
            'destroy' => 'v1.workers.destroy',
        ]);

        // Shift Records
        Route::apiResource('shift-records', ShiftRecordController::class)->names([
            'index'   => 'v1.shift-records.index',
            'store'   => 'v1.shift-records.store',
            'show'    => 'v1.shift-records.show',
            'update'  => 'v1.shift-records.update',
            'destroy' => 'v1.shift-records.destroy',
        ]);

        // Planned Shifts
        Route::apiResource('planned-shifts', PlannedShiftController::class)->names([
            'index'   => 'v1.planned-shifts.index',
            'store'   => 'v1.planned-shifts.store',
            'show'    => 'v1.planned-shifts.show',
            'update'  => 'v1.planned-shifts.update',
            'destroy' => 'v1.planned-shifts.destroy',
        ]);

        // Messages
        Route::get('/messages', [MessageController::class, 'index'])->name('v1.messages.index');
        Route::post('/messages', [MessageController::class, 'store'])->name('v1.messages.store');

        // Campaigns
        Route::apiResource('campaigns', CampaignController::class)->names([
            'index'   => 'v1.campaigns.index',
            'store'   => 'v1.campaigns.store',
            'show'    => 'v1.campaigns.show',
            'update'  => 'v1.campaigns.update',
            'destroy' => 'v1.campaigns.destroy',
        ]);

        // Notifications — accept POST or PUT for mark-read (frontend uses POST)
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('v1.notifications.index');
            Route::match(['post', 'put'], '/{id}/read', [NotificationController::class, 'markRead'])->name('v1.notifications.mark-read');
            Route::match(['post', 'put'], '/read-all', [NotificationController::class, 'markAllRead'])->name('v1.notifications.read-all');
            Route::match(['post', 'put'], '/mark-all-read', [NotificationController::class, 'markAllRead'])->name('v1.notifications.mark-all-read');
        });

        // Equipment
        Route::apiResource('equipment', EquipmentController::class)->names([
            'index'   => 'v1.equipment.index',
            'store'   => 'v1.equipment.store',
            'show'    => 'v1.equipment.show',
            'update'  => 'v1.equipment.update',
            'destroy' => 'v1.equipment.destroy',
        ]);

        // Safety Incidents
        Route::put('/safety-incidents/{id}/resolve', [SafetyIncidentController::class, 'resolve'])->name('v1.safety-incidents.resolve');
        Route::apiResource('safety-incidents', SafetyIncidentController::class)->names([
            'index'   => 'v1.safety-incidents.index',
            'store'   => 'v1.safety-incidents.store',
            'show'    => 'v1.safety-incidents.show',
            'update'  => 'v1.safety-incidents.update',
            'destroy' => 'v1.safety-incidents.destroy',
        ]);

        // Documents
        Route::get('/documents', [DocumentController::class, 'index'])->name('v1.documents.index');
        Route::post('/documents', [DocumentController::class, 'store'])->name('v1.documents.store');
        Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('v1.documents.show');
        Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('v1.documents.destroy');

        // Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('v1.audit-logs.index');

        // Alert Rules
        Route::apiResource('alert-rules', AlertRuleController::class)->names([
            'index'   => 'v1.alert-rules.index',
            'store'   => 'v1.alert-rules.store',
            'show'    => 'v1.alert-rules.show',
            'update'  => 'v1.alert-rules.update',
            'destroy' => 'v1.alert-rules.destroy',
        ]);

        // Customers
        Route::apiResource('customers', CustomerController::class)->names([
            'index'   => 'v1.customers.index',
            'store'   => 'v1.customers.store',
            'show'    => 'v1.customers.show',
            'update'  => 'v1.customers.update',
            'destroy' => 'v1.customers.destroy',
        ]);

        // Expense Categories
        Route::apiResource('expense-categories', ExpenseCategoryController::class)->names([
            'index'   => 'v1.expense-categories.index',
            'store'   => 'v1.expense-categories.store',
            'show'    => 'v1.expense-categories.show',
            'update'  => 'v1.expense-categories.update',
            'destroy' => 'v1.expense-categories.destroy',
        ]);

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('/summary', [ReportController::class, 'summary'])->name('v1.reports.summary');
            Route::get('/monthly-trend', [ReportController::class, 'monthlyTrend'])->name('v1.reports.monthly-trend');
            Route::get('/expenses-by-category', [ReportController::class, 'expensesByCategory'])->name('v1.reports.expenses-by-category');
            Route::get('/production-by-day', [ReportController::class, 'productionByDay'])->name('v1.reports.production-by-day');
            Route::get('/customer-summary', [ReportController::class, 'customerSummary'])->name('v1.reports.customer-summary');
        });

        // Production Logs
        Route::post('/production-logs/upsert', [ProductionLogController::class, 'upsert'])->name('v1.production-logs.upsert');
        Route::put('/production-logs/{id}', [ProductionLogController::class, 'upsert'])->name('v1.production-logs.update');
        Route::get('/production-logs', [ProductionLogController::class, 'index'])->name('v1.production-logs.index');
        Route::post('/production-logs', [ProductionLogController::class, 'upsert'])->name('v1.production-logs.store');
        Route::get('/production-logs/{id}', [ProductionLogController::class, 'show'])->name('v1.production-logs.show');
        Route::delete('/production-logs/{id}', [ProductionLogController::class, 'destroy'])->name('v1.production-logs.destroy');

        // KPI Targets
        Route::post('/kpi-targets/upsert', [KpiTargetController::class, 'upsert'])->name('v1.kpi-targets.upsert');
        Route::put('/kpi-targets/{id}', [KpiTargetController::class, 'upsert'])->name('v1.kpi-targets.update');
        Route::get('/kpi-targets', [KpiTargetController::class, 'index'])->name('v1.kpi-targets.index');
        Route::post('/kpi-targets', [KpiTargetController::class, 'upsert'])->name('v1.kpi-targets.store');
        Route::get('/kpi-targets/{id}', [KpiTargetController::class, 'show'])->name('v1.kpi-targets.show');
        Route::delete('/kpi-targets/{id}', [KpiTargetController::class, 'destroy'])->name('v1.kpi-targets.destroy');

        // Integration Configs
        Route::get('/integration-configs', [IntegrationConfigController::class, 'index'])->name('v1.integration-configs.index');
        Route::post('/integration-configs', [IntegrationConfigController::class, 'store'])->name('v1.integration-configs.store');
        Route::put('/integration-configs/{id}', [IntegrationConfigController::class, 'update'])->name('v1.integration-configs.update');
        Route::post('/integration-configs/{id}/toggle', [IntegrationConfigController::class, 'toggle'])->name('v1.integration-configs.toggle');
    });
});
