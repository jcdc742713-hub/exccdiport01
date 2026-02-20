<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReminder extends Model
{
    protected $fillable = [
        'user_id',
        'student_assessment_id',
        'student_payment_term_id',
        'type',
        'message',
        'outstanding_balance',
        'status',
        'read_at',
        'dismissed_at',
        'in_app_sent',
        'email_sent',
        'email_sent_at',
        'scheduled_for',
        'sent_at',
        'trigger_reason',
        'triggered_by',
        'metadata',
    ];

    protected $casts = [
        'outstanding_balance' => 'decimal:2',
        'in_app_sent' => 'boolean',
        'email_sent' => 'boolean',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Reminder types
    const TYPE_PAYMENT_DUE = 'payment_due';
    const TYPE_APPROACHING_DUE = 'approaching_due';
    const TYPE_OVERDUE = 'overdue';
    const TYPE_PARTIAL_PAYMENT = 'partial_payment';
    const TYPE_PAYMENT_RECEIVED = 'payment_received';

    // Status values
    const STATUS_SENT = 'sent';
    const STATUS_READ = 'read';
    const STATUS_DISMISSED = 'dismissed';

    // Trigger reasons
    const TRIGGER_ADMIN_UPDATE = 'admin_update';
    const TRIGGER_SCHEDULED_JOB = 'scheduled_job';
    const TRIGGER_DUE_DATE_CHANGE = 'due_date_change';
    const TRIGGER_THRESHOLD_REACHED = 'threshold_reached';

    /**
     * User who will receive the reminder
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Assessment this reminder is about
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(StudentAssessment::class, 'student_assessment_id');
    }

    /**
     * Payment term this reminder is about
     */
    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(StudentPaymentTerm::class, 'student_payment_term_id');
    }

    /**
     * Admin who triggered this reminder
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        if ($this->status !== self::STATUS_READ) {
            $this->update([
                'status' => self::STATUS_READ,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark as dismissed
     */
    public function markAsDismissed(): void
    {
        $this->update([
            'status' => self::STATUS_DISMISSED,
            'dismissed_at' => now(),
        ]);
    }

    /**
     * Get unread reminders for a user
     */
    public static function unreadForUser($userId)
    {
        return self::where('user_id', $userId)
            ->where('status', '!=', self::STATUS_DISMISSED)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Check if reminder is for an overdue payment
     */
    public function isOverdueReminder(): bool
    {
        return $this->type === self::TYPE_OVERDUE;
    }

    /**
     * Get days until due or days overdue
     */
    public function getDaysInfo()
    {
        if (!$this->paymentTerm) {
            return null;
        }

        $daysUntilDue = now()->diffInDays($this->paymentTerm->due_date, false);
        
        return [
            'days' => abs($daysUntilDue),
            'is_overdue' => $daysUntilDue < 0,
            'is_approaching' => $daysUntilDue >= 0 && $daysUntilDue <= 3,
        ];
    }
}
