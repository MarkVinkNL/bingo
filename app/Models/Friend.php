<?php

namespace App\Models;

use App\Enums\FriendStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friend extends Model
{
    protected $table = 'friends';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FriendStatus::class,
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', FriendStatus::Accepted);
    }

    public function scopePending($query)
    {
        return $query->where('status', FriendStatus::Pending);
    }
}
