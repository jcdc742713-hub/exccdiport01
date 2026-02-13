<?php

namespace App\Http\Controllers;

use App\Models\WorkflowApproval;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkflowApprovalController extends Controller
{
    public function __construct(protected WorkflowService $workflowService)
    {
    }

    public function index(Request $request)
    {
        $approvals = WorkflowApproval::query()
            ->with(['workflowInstance.workflow', 'workflowInstance.workflowable'])
            ->where('approver_id', auth()->id())
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        return Inertia::render('Approvals/Index', [
            'approvals' => $approvals,
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(WorkflowApproval $approval)
    {
        $this->authorize('view', $approval);

        $approval->load([
            'workflowInstance.workflow',
            'workflowInstance.workflowable',
            'workflowInstance.approvals',
        ]);

        return Inertia::render('Approvals/Show', [
            'approval' => $approval,
        ]);
    }

    public function approve(Request $request, WorkflowApproval $approval)
    {
        $this->authorize('approve', $approval);

        if ($approval->status !== 'pending') {
            return back()->withErrors(['error' => 'This approval has already been processed']);
        }

        $validated = $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        $this->workflowService->approveStep(
            $approval,
            auth()->id(),
            $validated['comments'] ?? null
        );

        return redirect()->route('approvals.index')
            ->with('success', 'Approval granted successfully');
    }

    public function reject(Request $request, WorkflowApproval $approval)
    {
        $this->authorize('approve', $approval);

        if ($approval->status !== 'pending') {
            return back()->withErrors(['error' => 'This approval has already been processed']);
        }

        $validated = $request->validate([
            'comments' => 'required|string|max:1000',
        ]);

        $this->workflowService->rejectStep(
            $approval,
            auth()->id(),
            $validated['comments']
        );

        return redirect()->route('approvals.index')
            ->with('success', 'Approval rejected');
    }
}