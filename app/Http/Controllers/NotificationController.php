<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Display all notifications (or user-specific based on role)
     */
    public function index(Request $request)
    {
        // Admin can see all notifications
        // Others see only relevant notifications
        $user = $request->user();
        
        if ($user->isAdmin()) {
            $notifications = Notification::orderByDesc('created_at')->get();
        } else {
            $notifications = Notification::active()
                ->forUser($user->id)
                ->withinDateRange()
                ->orderByDesc('start_date')
                ->get();
        }

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'role' => $user->role,
        ]);
    }

    /**
     * Show create notification form
     */
    public function create()
    {
        $this->authorize('create', Notification::class);
        
        // Get all students for the student selector
        $students = User::whereRole('student')
            ->select('id', 'first_name', 'last_name', 'middle_initial', 'email')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
        
        return Inertia::render('Admin/Notifications/Create', [
            'students' => $students,
        ]);
    }

    /**
     * Store a new notification
     */
    public function store(Request $request)
    {
        $this->authorize('create', Notification::class);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'message'     => 'nullable|string|max:2000',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'target_role' => 'required|string|in:student,accounting,admin,all',
            'user_id'     => 'nullable|integer|exists:users,id',
            'is_active'   => 'boolean',
        ]);

        // If user_id is specified, it's a specific student notification
        // Set target_role to null so it only shows to that specific user
        if ($validated['user_id']) {
            $validated['target_role'] = 'student'; // Still student role-based default
        }

        Notification::create($validated);

        return redirect('/notifications')
            ->with('success', 'Notification created successfully.');
    }

    /**
     * Show a specific notification
     */
    public function show(Notification $notification)
    {
        $this->authorize('view', $notification);

        return Inertia::render('Admin/Notifications/Show', [
            'notification' => $notification,
        ]);
    }

    /**
     * Show edit notification form
     */
    public function edit(Notification $notification)
    {
        $this->authorize('update', $notification);

        // Get all students for the student selector
        $students = User::whereRole('student')
            ->select('id', 'first_name', 'last_name', 'middle_initial', 'email')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Admin/Notifications/Edit', [
            'notification' => $notification,
            'students' => $students,
        ]);
    }

    /**
     * Update a notification
     */
    public function update(Request $request, Notification $notification)
    {
        $this->authorize('update', $notification);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'message'     => 'nullable|string|max:2000',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'target_role' => 'required|string|in:student,accounting,admin,all',
            'user_id'     => 'nullable|integer|exists:users,id',
            'is_active'   => 'boolean',
        ]);

        // If user_id is specified, it's a specific student notification
        if ($validated['user_id']) {
            $validated['target_role'] = 'student';
        }

        $notification->update($validated);

        return redirect('/notifications')
            ->with('success', 'Notification updated successfully.');
    }

    /**
     * Delete a notification
     */
    public function destroy(Notification $notification)
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return redirect('/notifications')
            ->with('success', 'Notification deleted successfully.');
    }

    /**
     * Mark notification as dismissed
     */
    public function dismiss(Notification $notification)
    {
        $notification->markDismissed();
        
        return back()->with('success', 'Notification dismissed.');
    }
}