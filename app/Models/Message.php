<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'client_id',
        'guest_name',
        'message',
        'reactions_count',
    ];

    protected $casts = [
        'reactions_count' => 'array',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    /**
     * All reaction records for this message.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(MessageLike::class);
    }

    /**
     * A message is "popular" when it has more than 3 reactions total.
     * Used to switch the bubble to purple in the view.
     */
    public function isPopular(): bool
    {
        $reactions = $this->reactions_count ?? [];
        return array_sum($reactions) > 3;
    }
}
