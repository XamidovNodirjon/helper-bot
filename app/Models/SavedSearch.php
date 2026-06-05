<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'name',
        'category',
        'region',
        'district',
        'filters',
        'is_subscribed',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_subscribed' => 'boolean',
    ];

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class);
    }
}
