<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramUserLog extends Model
{
    // Disable timestamps since we use a custom created_at column managed by database
    public $timestamps = false;

    protected $fillable = [
        'telegram_user_id',
        'action',
        'details',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
    }
}
