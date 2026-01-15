<?php

declare(strict_types=1);

namespace App\Domain\Automations\Models;

use App\Domain\Integrations\Models\Integration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ClassifiedEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'automation_id',
        'integration_id',
        'gmail_id',
        'email_message_id',
        'email_from',
        'email_subject',
        'email_date',
        'gmail_label',
        'metadata',
    ];

    protected $casts = [
        'email_date' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function getGmailUrlAttribute(): string
    {
        if (!$this->gmail_id) {
            return '#';
        }

        return "https://mail.google.com/mail/u/0/#all/{$this->gmail_id}";
    }
}
