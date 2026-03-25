<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayrollSetting;

class PayslipCalculationService
{
    /**
     * Ensure the current month's payslip exists for a single employee.
     */
    public function ensureMonthlyPayslip(Employee $employee): void
    {
        $this->ensureMonthlyPayslipsForEmployeeIds([$employee->id]);
    }

    /**
     * Ensure the current month's payslips exist for a list of employee IDs.
     */
    public function ensureMonthlyPayslipsForEmployeeIds(array $employeeIds): void
    {
        if ($employeeIds === []) {
            return;
        }

        $settings = PayrollSetting::current();

        $employees = Employee::query()
            ->whereIn('id', $employeeIds)
            ->where('employment_status', 'active')
            ->get(['id', 'basic_salary']);

        foreach ($employees as $employee) {
            $basicSalary = (float) ($employee->basic_salary ?? 0);
            $estimatedTax = round($basicSalary * (float) $settings->tax_rate, 2);
            $estimatedPhilHealth = round($basicSalary * (float) $settings->philhealth_rate, 2);
            $totalDeductions = $estimatedTax + $estimatedPhilHealth;
            $netPay = max(0, $basicSalary - $totalDeductions);

            Payslip::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'period_start' => now()->startOfMonth()->toDateString(),
                    'period_end' => now()->endOfMonth()->toDateString(),
                ],
                [
                    'gross_pay' => $basicSalary,
                    'total_deductions' => $totalDeductions,
                    'net_pay' => $netPay,
                    'earnings' => [
                        ['label' => 'Basic Salary', 'amount' => $basicSalary],
                    ],
                    'deductions' => [
                        ['label' => 'Estimated Tax', 'amount' => $estimatedTax],
                        ['label' => 'Estimated PhilHealth', 'amount' => $estimatedPhilHealth],
                    ],
                    'released_at' => now()->toDateString(),
                ]
            );
        }
    }
}
