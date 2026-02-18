<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Display all notifications (or user-specific based on role)
     */
    public function index()
    {
        // Admin can see all notifications
        // Others see only relevant notifications
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            $notifications = Notification::orderByDesc('start_date')->get();
        } else {
            $notifications = Notification::query()
                ->where(function ($q) use ($user) {
                    $q->where('target_role', $user->role)
                      ->orWhere('target_role', 'all');
                })
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
        
        return Inertia::render('Admin/Notifications/Create');
    }

    /**
     * Store a new notification
     */
    public function store(Request $request)
    {
        $this->authorize('create', Notification::class);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'message'     => 'nullable|string|max:1000',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'target_role' => 'required|string|in:student,accounting,admin,all',
        ]);

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

        return Inertia::render('Admin/Notifications/Edit', [
            'notification' => $notification,
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
            'message'     => 'nullable|string|max:1000',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'target_role' => 'required|string|in:student,accounting,admin,all',
        ]);

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
}