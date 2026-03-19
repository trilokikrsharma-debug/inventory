<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Employee;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\Payroll;
use Illuminate\Http\Request;

class HrController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'employees' => Employee::query()
                ->orderByDesc('id')
                ->paginate((int) $request->input('per_page', 20)),
            'pending_leaves' => LeaveRequest::query()->where('status', 'pending')->count(),
            'payroll_processed' => Payroll::query()
                ->where('status', 'processed')
                ->whereMonth('pay_period_start', now()->month)
                ->count(),
        ]);
    }

    public function storeEmployee(Request $request)
    {
        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:60', 'unique:employees,employee_code'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'designation' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'join_date' => ['nullable', 'date'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $employee = Employee::query()->create($validated + [
            'status' => 'active',
            'basic_salary' => $validated['basic_salary'] ?? 0,
        ]);

        return response()->json(['data' => $employee], 201);
    }

    public function markAttendance(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,leave,half_day'],
            'check_in' => ['nullable', 'date'],
            'check_out' => ['nullable', 'date', 'after_or_equal:check_in'],
        ]);

        $attendance = Attendance::query()->updateOrCreate(
            [
                'employee_id' => $validated['employee_id'],
                'attendance_date' => $validated['attendance_date'],
            ],
            $validated + [
                'work_minutes' => 0,
            ]
        );

        return response()->json(['data' => $attendance]);
    }
}
