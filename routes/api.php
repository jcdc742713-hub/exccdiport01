<?php

use App\Http\Controllers\Api\WorkflowApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Workflow API endpoints
    Route::get('workflows', [WorkflowApiController::class, 'index']);
    Route::get('workflows/{workflow}', [WorkflowApiController::class, 'show']);
    Route::get('workflows/{workflow}/instances', [WorkflowApiController::class, 'instances']);
    
    // Workflow instance endpoints
    Route::get('workflow-instances/{instance}', [WorkflowApiController::class, 'instanceDetail']);
    Route::post('workflow-instances/{instance}/advance', [WorkflowApiController::class, 'advanceInstance']);
    
    // Approval endpoints
    Route::get('my-approvals', [WorkflowApiController::class, 'myApprovals']);
    Route::post('approvals/{approval}/approve', [WorkflowApiController::class, 'approve']);
    Route::post('approvals/{approval}/reject', [WorkflowApiController::class, 'reject']);
});