<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! in_array($user->role, ['admin', 'hr'], true)) {
            return $this->error('Access denied. Only HR or Admin can view leave requests.', 403);
        }

        $limit = $request->integer('limit', 15);
        $sortField = $request->string('sort', 'created_at')->toString();
        $sortOrder = $request->string('order', 'desc')->toString();

        $allowedSorts = ['created_at', 'start_date', 'end_date', 'days_requested', 'status'];
        if (! in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
        }
        $sortOrder = in_array($sortOrder, ['asc', 'desc'], true) ? $sortOrder : 'desc';

        $query = LeaveRequest::query()
            ->with([
                'employee:id,employee_no,first_name,last_name,middle_name',
                'leaveType:id,code,name,is_paid',
                'reviewer:id,name,email',
            ])
            ->orderBy($sortField, $sortOrder);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        return $this->paginated($query->paginate($limit));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! in_array($user->role, ['admin', 'hr'], true)) {
            return $this->error('Access denied. Only HR or Admin can submit leave requests.', 403);
        }

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->startOfDay();
        $validated['days_requested'] = (string) ($startDate->diffInDays($endDate) + 1);
        $validated['status'] = 'pending';

        $leaveRequest = LeaveRequest::create($validated)
            ->load(['employee:id,employee_no,first_name,last_name,middle_name', 'leaveType:id,code,name,is_paid']);

        return $this->success($leaveRequest, 'Leave request submitted successfully.', 201);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($leaveRequest->status !== 'pending') {
            return $this->error('Only pending leave requests can be approved.', 422);
        }

        $leaveRequest->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? null,
        ]);

        return $this->success(
            $leaveRequest->fresh(['employee:id,employee_no,first_name,last_name,middle_name', 'leaveType:id,code,name,is_paid', 'reviewer:id,name,email']),
            'Leave request approved.'
        );
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'review_notes' => ['required', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(['rejected', 'cancelled'])],
        ]);

        if ($leaveRequest->status !== 'pending') {
            return $this->error('Only pending leave requests can be rejected or cancelled.', 422);
        }

        $leaveRequest->update([
            'status' => $validated['status'] ?? 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'],
        ]);

        return $this->success(
            $leaveRequest->fresh(['employee:id,employee_no,first_name,last_name,middle_name', 'leaveType:id,code,name,is_paid', 'reviewer:id,name,email']),
            'Leave request updated.'
        );
    }
}
