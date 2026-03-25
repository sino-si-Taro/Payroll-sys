<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Employee;
use App\Services\PayslipCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HrEmployeeAccountController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PayslipCalculationService $payslipService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'hire_date' => ['nullable', 'date'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $employeeNo = 'EMP-'.date('Y').'-'.str_pad((string) ((int) Employee::max('id') + 1), 4, '0', STR_PAD_LEFT);

        $employee = Employee::query()->create([
            'employee_no' => $employeeNo,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'position' => $validated['position'] ?? null,
            'hire_date' => $validated['hire_date'] ?? null,
            'employment_status' => 'active',
            'basic_salary' => $validated['basic_salary'] ?? 0,
        ])->load('department:id,name');

        $this->payslipService->ensureMonthlyPayslip($employee);

        return $this->success([
            'employee' => $employee,
        ], 'Employee created successfully.', 201);
    }
}
