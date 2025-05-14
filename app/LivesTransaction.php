<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LivesTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'change',          // e.g. +3, -1, +10
        'type',            // 'daily_reset', 'purchase', 'used', etc.
        'description'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
