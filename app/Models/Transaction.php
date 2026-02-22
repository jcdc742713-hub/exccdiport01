<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Services\AccountService;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'account_id', 'fee_id', 'reference', 
        'payment_channel', 'kind', 'type', 'amount', 'status', 
        'paid_at', 'meta', 'year', 'semester'
    ];

    protected $casts = [
        'meta' => 'array',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    public function workflowInstances(): MorphMany
    {
        return $this->morphMany(WorkflowInstance::class, 'workflowable');
    }

    public function pendingApproval(): ?WorkflowInstance
    {
        return $this->workflowInstances()
            ->where('status', 'in_progress')
            ->latest()
            ->first();
    }

    protected static function booted()
    {
        static::saved(function ($transaction) {
            AccountService::recalculate($transaction->user);
        });
    }

    public function download()
    {
        $transactions = auth()->user()->transactions()->with('fee')->get();

        // Use a PDF generator like DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.transactions', [
            'transactions' => $transactions
        ]);

        return $pdf->download('transactions.pdf');
    }
}