<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CurrentClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayrollReportController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\WorkRealizationController;
use App\Http\Controllers\WorkReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::put('/current-client', [CurrentClientController::class, 'update'])
        ->name('current-client.update');

    Route::get('/search', [SearchController::class, 'index'])->name('search')->middleware('client');

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::resource('units', UnitController::class)->except(['show'])->middleware('client');
    Route::get('/units/options', [UnitController::class, 'options'])->name('units.options')->middleware('client');
    Route::resource('groups', GroupController::class)->except(['show'])->middleware('client');
    Route::get('/groups/options', [GroupController::class, 'options'])->name('groups.options')->middleware('client');
    Route::resource('shifts', ShiftController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('client');
    Route::get('/shifts/options', [WorkRealizationController::class, 'shiftOptions'])->name('shifts.options')->middleware('client');
    Route::resource('cost-centers', CostCenterController::class)->except(['show'])->middleware('client');
    Route::get('/cost-centers/options', [CostCenterController::class, 'options'])->name('cost-centers.options')->middleware('client');
    Route::resource('employees', EmployeeController::class)->except(['show', 'create', 'edit'])->middleware('client');
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('client');
    Route::get('/products/options', [ProductController::class, 'options'])->name('products.options')->middleware('client');
    Route::get('/clients/options', [ClientController::class, 'options'])->name('clients.options')->middleware('client');
    Route::get('/realizations', [WorkRealizationController::class, 'index'])->name('realizations.index')->middleware('client');
    Route::get('/realizations/create', [WorkRealizationController::class, 'create'])->name('realizations.create')->middleware('client');
    Route::get('/batches/options', [WorkRealizationController::class, 'batchOptions'])->name('batches.options')->middleware('client');
    Route::get('/realizations/{realization}', [WorkRealizationController::class, 'show'])->name('realizations.show')->middleware('client');
    Route::post('/realizations', [WorkRealizationController::class, 'store'])->name('realizations.store')->middleware('client');
    Route::put('/realizations/{realization}', [WorkRealizationController::class, 'update'])->name('realizations.update')->middleware('client');
    Route::post('/realizations/{realization}/assign', [WorkRealizationController::class, 'assign'])->name('realizations.assign')->middleware('client');
    Route::get('/deductions', [DeductionController::class, 'index'])->name('deductions.index')->middleware('client');
    Route::get('/deductions/create', [DeductionController::class, 'create'])->name('deductions.create')->middleware('client');
    Route::get('/deductions/export', [DeductionController::class, 'export'])->name('deductions.export')->middleware('client');
    Route::get('/deductions/template', [DeductionController::class, 'template'])->name('deductions.template')->middleware('client');
    Route::post('/deductions/import', [DeductionController::class, 'import'])->name('deductions.import')->middleware('client');
    Route::post('/deductions', [DeductionController::class, 'store'])->name('deductions.store')->middleware('client');
    Route::put('/deductions/{deduction}', [DeductionController::class, 'update'])->name('deductions.update')->middleware('client');
    Route::delete('/deductions/{deduction}', [DeductionController::class, 'destroy'])->name('deductions.destroy')->middleware('client');
    Route::get('/reports/payroll', PayrollReportController::class)->name('reports.payroll')->middleware('client');
    Route::get('/reports/payroll/export/excel', [PayrollReportController::class, 'exportExcel'])->name('reports.payroll.export.excel')->middleware('client');
    Route::get('/reports/payroll/export/pdf', [PayrollReportController::class, 'exportPdf'])->name('reports.payroll.export.pdf')->middleware('client');
    Route::get('/reports/payroll/payslip', [PayslipController::class, 'bulk'])->name('reports.payroll.payslip.bulk')->middleware('client');
    Route::get('/reports/payroll/payslip/{employee}', [PayslipController::class, 'show'])->name('reports.payroll.payslip')->middleware('client');
    Route::get('/reports/work', WorkReportController::class)->name('reports.work')->middleware('client');
    Route::get('/settings/access', [AccessController::class, 'index'])->name('settings.access')->middleware('client');
    Route::put('/settings/access/roles/{role}', [AccessController::class, 'updateRolePermissions'])->name('settings.access.roles.update');
    Route::put('/settings/access/users/{user}/status', [AccessController::class, 'toggleUserStatus'])->name('settings.access.users.status')->middleware('client');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index')->middleware('client');
    Route::resource('clients', ClientController::class)->only(['index', 'store', 'update', 'destroy'])->withoutMiddleware('client');
});

if (app()->environment(['local', 'testing'])) {
    Route::get('/design-system', function () {
        return redirect()->route(auth()->check() ? 'dashboard' : 'login');
    })->name('design-system');
}
