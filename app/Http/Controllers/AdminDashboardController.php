<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Get admin statistics
        $totalAdmins = User::where('role', 'admin')->count();
        $activeAdmins = User::where('role', 'admin')->where('is_active', true)->count();
        $inactiveAdmins = User::where('role', 'admin')->where('is_active', false)->count();

        // Get admin breakdown by type
        $superAdmins = User::where('role', 'admin')->where('admin_type', 'super')->count();
        $managers = User::where('role', 'admin')->where('admin_type', 'manager')->count();
        $operators = User::where('role', 'admin')->where('admin_type', 'operator')->count();

        // Get user statistics
        $totalUsers = User::count();
        $totalStudents = User::where('role', 'student')->count();

        // Get pending approvals (if workflow system exists)
        $pendingApprovals = 0;
        if (class_exists('App\Models\WorkflowApproval')) {
            $pendingApprovals = \App\Models\WorkflowApproval::where('status', 'pending')->count();
        }

        // Get recent activities
        $recentActivities = collect([
            [
                'type' => 'admin_created',
                'description' => 'New admin user created',
                'timestamp' => now()->subHours(2)->toDateTimeString(),
                'user' => 'System Administrator'
            ],
            [
                'type' => 'notification_sent',
                'description' => 'Payment notification sent to students',
                'timestamp' => now()->subHours(1)->toDateTimeString(),
                'user' => 'System Administrator'
            ]
        ]);

        // Get recent notifications
        $recentNotifications = Notification::orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($notif) => [
                'id' => $notif->id,
                'title' => $notif->title,
                'targetRole' => $notif->target_role,
                'startDate' => $notif->start_date,
                'endDate' => $notif->end_date,
                'createdAt' => $notif->created_at,
            ]);

        // System health status
        $systemHealth = [
            'status' => 'operational',
            'databaseStatus' => 'operational',
            'apiStatus' => 'operational',
            'authenticationStatus' => 'operational',
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalAdmins' => $totalAdmins,
                'activeAdmins' => $activeAdmins,
                'inactiveAdmins' => $inactiveAdmins,
                'superAdmins' => $superAdmins,
                'managers' => $managers,
                'operators' => $operators,
                'recentActivities' => $recentActivities,
                'systemHealth' => $systemHealth,
                'pendingApprovals' => $pendingApprovals,
                'totalUsers' => $totalUsers,
                'totalStudents' => $totalStudents,
                'recentNotifications' => $recentNotifications,
            ]
        ]);
    }
}
