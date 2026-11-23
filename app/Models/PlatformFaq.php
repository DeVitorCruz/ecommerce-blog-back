<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Platform-level FAQ entry.
 * Managed by owner/admin/editor via platform.faq.manage permission.
 */
class PlatformFaq extends Model
{
    protected $connection = 'mercatura';
    protected $table = 'faqs';

    protected $fillable = [
        'question', 'answer', 'category', 'order', 'active',       
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
