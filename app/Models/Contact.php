<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'customer_id',
        'name',
        'title',
        'department',
        'email',
        'phone',
        'whatsapp',
        'is_primary',
        'is_decision_maker',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_decision_maker' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ContactNote::class)->latest();
    }

    /**
     * ContactNote records. The contacts table also has a nullable `notes` text
     * column, so `$this->notes` returns that attribute rather than this relation.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ContactNote>
     */
    public function noteRecords()
    {
        if ($this->relationLoaded('notes')) {
            return $this->getRelation('notes');
        }

        return $this->notes()->with('user')->get();
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable')->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class)->latest('occurred_at');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(CustomerTicket::class)->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        return config('contacts.statuses.'.$this->status, ucfirst($this->status));
    }
}
