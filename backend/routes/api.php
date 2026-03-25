<?php

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AdminHrAccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\HrEmployeeAccountController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\LeaveTypeController;
use App\Http\Controllers\Api\V1\PayslipController;
use App\Http\Controllers\Api\V1\PayrollSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    // ── Public: Auth (rate-limited) ──────────────────────────────
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');

    // ── Protected: Requires Sanctum token ────────────────────────
    Route::middleware('auth:sanctum')->group(function (): void {

        // Auth
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // HR Account management (admin only)
        Route::post('/admin/hr-accounts', [AdminHrAccountController::class, 'store'])->middleware('admin_only');

        // Departments
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::post('/departments', [DepartmentController::class, 'store'])->middleware('hr_or_admin');

        // Employee Accounts (HR creates employee profiles)
        Route::post('/hr/employee-accounts', [HrEmployeeAccountController::class, 'store'])->middleware('hr_or_admin');

        // Employees
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store'])->middleware('hr_or_admin');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->middleware('hr_or_admin');
        Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->middleware('hr_or_admin');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->middleware('hr_or_admin');

        // Analytics
        Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
        Route::get('/analytics/payroll', [AnalyticsController::class, 'payroll']);
        Route::get('/analytics/reports', [AnalyticsController::class, 'reports']);

        // Payroll Settings
        Route::get('/payroll-settings', [PayrollSettingController::class, 'show']);
        Route::put('/payroll-settings', [PayrollSettingController::class, 'update'])->middleware('hr_or_admin');

        // Payslips
        Route::get('/payslips', [PayslipController::class, 'index']);
        Route::get('/payslips/{payslip}', [PayslipController::class, 'show']);

        // Leave Types
        Route::get('/leave-types', [LeaveTypeController::class, 'index']);
        Route::post('/leave-types', [LeaveTypeController::class, 'store'])->middleware('hr_or_admin');

        // Leave Requests
        Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
        Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->middleware('hr_or_admin');
        Route::patch('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->middleware('hr_or_admin');
        Route::patch('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->middleware('hr_or_admin');
    });
});
