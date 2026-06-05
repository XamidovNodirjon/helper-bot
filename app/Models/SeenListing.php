<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeenListing extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'telegram_user_id',
        'url',
    ];

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class);
    }
}
