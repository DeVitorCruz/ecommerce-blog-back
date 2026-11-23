<?php

namespace App\Models;

use App\Models\PlatformUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform contact form submission.
 * Authenticated users linked via user_id; guests have user_id=null.
 * status: unread | read | replied
 */
class PlatformContact extends Model
{
    protected $connection = 'mercatura';
    protected $table = 'contacts';

    protected $fillable = [
        'user_id', 'session_token', 'ip_address',
        'name', 'email', 'subject', 'message',
        'reply', 'replied_by', 'replied_at', 'status'        
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    /**
     * Get the user who have the contact 
     * 
     * @return BelongsTo<PlatformUser>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'user_id');
    }

    /**
     * Check if the user is guest
     * 
     * @return bool 
     */
    public function isGuest(): bool { return is_null($this->user_id); }

    /**
     * Check if the contact was replied
     * 
     * @return bool
     */
    public function isReplied(): bool { return $this->status === 'replied'; }
}
