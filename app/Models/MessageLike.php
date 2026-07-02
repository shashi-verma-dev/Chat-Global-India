<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLike extends Model
{
    protected $fillable = [
        'message_id',
        'client_id',
        'type',
    ];

    /**
     * The message this like belongs to.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
