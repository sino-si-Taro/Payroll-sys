<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(LeaveType::query()->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:leave_types,code'],
            'name' => ['required', 'string', 'max:255', 'unique:leave_types,name'],
            'is_paid' => ['nullable', 'boolean'],
            'annual_allocation' => ['nullable', 'numeric', 'min:0'],
            'requires_approval' => ['nullable', 'boolean'],
        ]);

        $leaveType = LeaveType::create($validated);

        return $this->success($leaveType, 'Leave type created successfully.', 201);
    }
}
