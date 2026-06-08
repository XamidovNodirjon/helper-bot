<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    protected $fillable = [
        'telegram_id',
        'username',
        'language',
        'step',
        'arenda_type',
        'deal_type',
        'region',
        'district',
        'brand',
        'condition',
        'transmission',
        'fuel_type',
        'year_min',
        'year_max',
        'price_currency',
        'price_min',
        'price_max',
        'current_page',
        'last_results',
        'is_subscribed',
        'is_banned',
        'subscription_expires_at',
    ];

    protected $casts = [
        'last_results' => 'array',
        'is_subscribed' => 'boolean',
        'is_banned' => 'boolean',
        'subscription_expires_at' => 'datetime',
    ];

    /**
     * Clear all filters and reset to the start step
     */
    public function resetFilters()
    {
        $this->update([
            'step' => 'arenda_type',
            'arenda_type' => null,
            'deal_type' => null,
            'region' => null,
            'district' => null,
            'brand' => null,
            'condition' => null,
            'transmission' => null,
            'fuel_type' => null,
            'year_min' => null,
            'year_max' => null,
            'price_currency' => null,
            'price_min' => null,
            'price_max' => null,
            'current_page' => 1,
            'last_results' => null,
        ]);
    }

    public function seenListings()
    {
        return $this->hasMany(SeenListing::class);
    }

    public function savedSearches()
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function logs()
    {
        return $this->hasMany(TelegramUserLog::class);
    }
}
