<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteMessage extends Model
{
    protected $fillable = [
        'quote_conversation_id',
        'sender_user_id',
        'message',
        'is_initial',
        'read_by_client_at',
        'read_by_mariachi_at',
    ];

    protected function casts(): array
    {
        return [
            'is_initial' => 'boolean',
            'read_by_client_at' => 'datetime',
            'read_by_mariachi_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(QuoteConversation::class, 'quote_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
