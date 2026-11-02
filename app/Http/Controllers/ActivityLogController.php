<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Tampilkan jejak rekam audit aktivitas sistem (Audit Trail).
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = [
            'search' => $request->input('search'),
            'action' => $request->input('action'),
            'module' => $request->input('module'),
            'user_id' => $request->input('user_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $query = ActivityLog::with('user')->filter($filters)->latest('id');

        $logs = $query->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $logs,
            ]);
        }

        $users = User::orderBy('name')->get(['id', 'name', 'username', 'role']);
        $modules = ActivityLog::distinct()->pluck('module')->sort()->values();
        $actions = ActivityLog::distinct()->pluck('action')->sort()->values();

        // Statistik ringkas aktivitas
        $stats = [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
            'logins' => ActivityLog::where('action', 'LOGIN')->whereDate('created_at', today())->count(),
            'mutations' => ActivityLog::whereIn('action', ['CREATE', 'UPDATE', 'DELETE'])->count(),
        ];

        return view('admin.activity_log.index', compact('logs', 'users', 'modules', 'actions', 'stats'));
    }
}
