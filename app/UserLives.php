<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserLives extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lives_remaining',
        'last_reset',
        'is_unlimited'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(LivesTransaction::class);
    }
}
