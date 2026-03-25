<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Employee;
use App\Models\Payslip;
use App\Services\PayslipCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PayslipCalculationService $payslipService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! in_array($user->role, ['admin', 'hr'], true)) {
            return $this->error('Access denied. Only HR or Admin can access payslips.', 403);
        }

        $limit = $request->integer('limit', 15);
        $sortField = $request->string('sort', 'period_end')->toString();
        $sortOrder = $request->string('order', 'desc')->toString();

        $allowedSorts = ['period_end', 'period_start', 'net_pay', 'gross_pay', 'created_at'];
        if (! in_array($sortField, $allowedSorts, true)) {
            $sortField = 'period_end';
        }
        $sortOrder = in_array($sortOrder, ['asc', 'desc'], true) ? $sortOrder : 'desc';

        $employeeIds = Employee::query()
            ->when($request->filled('employee_id'), fn ($q) => $q->where('id', $request->integer('employee_id')))
            ->pluck('id')
            ->all();

        $this->payslipService->ensureMonthlyPayslipsForEmployeeIds($employeeIds);

        $query = Payslip::query()
            ->with(['employee:id,user_id,employee_no,first_name,last_name,middle_name,position,employment_status', 'employee.department:id,name'])
            ->orderBy($sortField, $sortOrder);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        return $this->paginated($query->paginate($limit));
    }

    public function show(Request $request, Payslip $payslip): JsonResponse
    {
        $payslip->load(['employee:id,user_id,employee_no,first_name,last_name,middle_name,position,employment_status', 'employee.department:id,name']);

        return $this->success($payslip);
    }
}
