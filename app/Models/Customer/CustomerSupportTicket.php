<?php

namespace App\Models\Customer;

use App\Models\Traits\HasHashid;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerSupportTicket extends Model
{
    use HasFactory, HasHashid;

    protected $fillable = [
        'customer_id', 'ticket_number', 'subject', 'description',
        'category', 'priority', 'status', 'assigned_to', 'tags',
        'resolution', 'resolved_at', 'closed_at', 'last_response_at',
        'customer_satisfaction_rating', 'customer_feedback', 'internal_notes'
    ];

    protected $casts = [
        'tags' => 'array',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_response_at' => 'datetime',
        'customer_satisfaction_rating' => 'integer',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(CustomerSupportResponse::class, 'ticket_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress', 'waiting_customer']);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeOverdue($query)
    {
        return $query->where('created_at', '<', now()->subDays(3))
            ->whereNotIn('status', ['resolved', 'closed']);
    }

    // Methods
    public function markAsResolved(string $resolution = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolved_at' => now()
        ]);
    }

    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now()
        ]);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress', 'waiting_customer']);
    }

    public function isOverdue(): bool
    {
        return $this->created_at->lt(now()->subDays(3)) && $this->isOpen();
    }
}
