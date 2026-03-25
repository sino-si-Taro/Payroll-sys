<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Employee;
use App\Services\PayslipCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PayslipCalculationService $payslipService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 15);
        $sortField = $request->string('sort', 'last_name')->toString();
        $sortOrder = $request->string('order', 'asc')->toString();

        $allowedSorts = ['last_name', 'first_name', 'hire_date', 'basic_salary', 'employee_no', 'created_at'];
        if (! in_array($sortField, $allowedSorts, true)) {
            $sortField = 'last_name';
        }
        $sortOrder = in_array($sortOrder, ['asc', 'desc'], true) ? $sortOrder : 'asc';

        $query = Employee::query()
            ->where('employment_status', '!=', 'inactive')
            ->with('department:id,name')
            ->orderBy($sortField, $sortOrder);

        // Secondary sort for consistency
        if ($sortField !== 'last_name') {
            $query->orderBy('last_name');
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($departmentQuery) use ($search) {
                        $departmentQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->string('employment_status'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        return $this->paginated($query->paginate($limit));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'unique:employees,user_id'],
            'employee_no' => ['nullable', 'string', 'max:50', 'unique:employees,employee_no'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:employees,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'hire_date' => ['nullable', 'date'],
            'employment_status' => ['nullable', Rule::in(['active', 'on_leave', 'inactive', 'pending'])],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (empty($validated['employee_no'])) {
            $nextId = (int) Employee::max('id') + 1;
            $validated['employee_no'] = 'EMP-'.date('Y').'-'.str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
        }

        $employee = Employee::create($validated)->load('department:id,name');
        $this->payslipService->ensureMonthlyPayslip($employee);

        return $this->success($employee, 'Employee created successfully.', 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        $employee->load('department:id,name');

        return $this->success($employee);
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($employee->id)],
            'employee_no' => ['sometimes', 'string', 'max:50', Rule::unique('employees', 'employee_no')->ignore($employee->id)],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'hire_date' => ['nullable', 'date'],
            'employment_status' => ['nullable', Rule::in(['active', 'on_leave', 'inactive', 'pending'])],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $employee->update($validated);

        return $this->success($employee->fresh('department:id,name'), 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employee->load('user');
        $linkedUser = $employee->user;

        $employee->update([
            'employment_status' => 'inactive',
        ]);

        if ($linkedUser) {
            $linkedUser->delete();
        }

        return $this->success(message: 'Employee offboarded successfully.');
    }
}
