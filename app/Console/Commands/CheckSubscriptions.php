<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TelegramUser;
use App\Models\SavedSearch;
use App\Models\SeenListing;
use App\Services\TranslationService;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class CheckSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:check-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch OLX listings for active subscriptions and alert users of new matching items';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting subscription checks...');

        $subscriptions = SavedSearch::where('is_subscribed', true)->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No active subscriptions found.');
            return 0;
        }

        $this->info("Found {$subscriptions->count()} active subscription(s). Processing...");

        foreach ($subscriptions as $sub) {
            $user = $sub->telegramUser;
            if (!$user) {
                $this->warn("Subscription ID {$sub->id} has no corresponding Telegram user. Skipping.");
                continue;
            }

            $this->info("Processing subscription '{$sub->name}' for User ID {$user->telegram_id}");

            $pythonPath = 'python';
            $scriptPath = base_path('app/Services/olx_scraper.py');
            
            $cmd = sprintf(
                '%s %s --category=%s --region=%s',
                escapeshellcmd($pythonPath),
                escapeshellarg($scriptPath),
                escapeshellarg($sub->category),
                escapeshellarg($sub->region)
            );

            if ($sub->district) {
                $cmd .= ' --district=' . escapeshellarg($sub->district);
            }
            
            $filters = $sub->filters;
            if (is_array($filters)) {
                if (!empty($filters['price_min'])) {
                    $cmd .= ' --price_min=' . escapeshellarg($filters['price_min']);
                }
                if (!empty($filters['price_max'])) {
                    $cmd .= ' --price_max=' . escapeshellarg($filters['price_max']);
                }
                if (!empty($filters['price_currency'])) {
                    $cmd .= ' --currency=' . escapeshellarg(strtolower($filters['price_currency']));
                }
                if (!empty($filters['area_min'])) {
                    $cmd .= ' --area_min=' . escapeshellarg($filters['area_min']);
                }
                if (!empty($filters['area_max'])) {
                    $cmd .= ' --area_max=' . escapeshellarg($filters['area_max']);
                }
                if (!empty($filters['brand'])) {
                    $cmd .= ' --brand=' . escapeshellarg($filters['brand']);
                }
                if (!empty($filters['condition'])) {
                    $cmd .= ' --condition=' . escapeshellarg($filters['condition']);
                }
                if (!empty($filters['transmission'])) {
                    $cmd .= ' --transmission=' . escapeshellarg($filters['transmission']);
                }
                if (!empty($filters['fuel_type'])) {
                    $cmd .= ' --fuel_type=' . escapeshellarg($filters['fuel_type']);
                }
                if (!empty($filters['year_min'])) {
                    $cmd .= ' --year_min=' . escapeshellarg($filters['year_min']);
                }
                if (!empty($filters['year_max'])) {
                    $cmd .= ' --year_max=' . escapeshellarg($filters['year_max']);
                }
            }

            Log::debug("Subscription command execution: $cmd");
            $output = shell_exec($cmd);

            if (!$output) {
                $this->error("Failed to execute scraper for subscription ID {$sub->id}");
                continue;
            }

            $response = json_decode($output, true);
            if (isset($response['error'])) {
                $this->error("Scraper returned error for subscription ID {$sub->id}: " . $response['error']);
                continue;
            }

            $listings = $response['listings'] ?? [];
            if (empty($listings)) {
                $this->info("No listings returned for subscription ID {$sub->id}");
                continue;
            }

            // Filter out seen listings
            $seenUrls = SeenListing::where('telegram_user_id', $user->id)->pluck('url')->toArray();
            $newListings = [];
            foreach ($listings as $listing) {
                if (!in_array($listing['url'], $seenUrls)) {
                    $newListings[] = $listing;
                }
            }

            if (empty($newListings)) {
                $this->info("No new listings found for subscription ID {$sub->id}");
                continue;
            }

            $this->info("Found " . count($newListings) . " new listing(s) for user {$user->telegram_id}. Sending alerts...");

            // Limit notification to top 5 new listings to avoid spam
            $newListings = array_slice($newListings, 0, 5);

            foreach ($newListings as $listing) {
                $source = $listing['source'] ?? 'OLX.uz';
                $text = TranslationService::trans('subscription_notification_header', $user->language);
                $text .= "<b>📌 E'LON [{$source}]</b>\n\n";
                $text .= "<b>🏠 Sarlavha / Заголовок:</b> " . htmlspecialchars($listing['title']) . "\n";
                $text .= "💰 <b>Narxi / Цена:</b> " . htmlspecialchars($listing['price']) . "\n";
                
                if (!empty($listing['location'])) {
                    $text .= "📍 <b>Manzil / Адрес:</b> " . htmlspecialchars($listing['location']) . "\n";
                }
                
                if (!empty($listing['description'])) {
                    $desc = trim($listing['description']);
                    if (mb_strlen($desc) > 300) {
                        $desc = mb_substr($desc, 0, 280) . '...';
                    }
                    $text .= "\nℹ️ <b>Tavsif / Описание:</b>\n<i>" . htmlspecialchars($desc) . "</i>\n";
                }
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🔗 OLX.uz havolasi', 'url' => $listing['url']]
                        ]
                    ]
                ];

                $photos = !empty($listing['images']) ? $listing['images'] : [];
                $photos = array_filter($photos, function($url) {
                    return !empty($url) && (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0);
                });
                $photos = array_values($photos);

                $sent = false;

                if (count($photos) >= 2) {
                    try {
                        $media = [];
                        $selectedPhotos = array_slice($photos, 0, 3);
                        foreach ($selectedPhotos as $photoUrl) {
                            $media[] = [
                                'type' => 'photo',
                                'media' => $photoUrl
                            ];
                        }

                        Telegram::sendMediaGroup([
                            'chat_id' => $user->telegram_id,
                            'media' => json_encode($media)
                        ]);

                        Telegram::sendMessage([
                            'chat_id' => $user->telegram_id,
                            'text' => $text,
                            'reply_markup' => json_encode($keyboard),
                            'parse_mode' => 'HTML'
                        ]);
                        $sent = true;
                    } catch (\Exception $e) {
                        Log::warning("Subscription media group failed for listing: " . $e->getMessage());
                    }
                }

                if (!$sent && count($photos) === 1) {
                    try {
                        Telegram::sendPhoto([
                            'chat_id' => $user->telegram_id,
                            'photo' => $photos[0],
                            'caption' => $text,
                            'reply_markup' => json_encode($keyboard),
                            'parse_mode' => 'HTML'
                        ]);
                        $sent = true;
                    } catch (\Exception $e) {
                        Log::warning("Subscription sendPhoto failed for listing: " . $e->getMessage());
                    }
                }

                if (!$sent) {
                    try {
                        Telegram::sendMessage([
                            'chat_id' => $user->telegram_id,
                            'text' => $text,
                            'reply_markup' => json_encode($keyboard),
                            'parse_mode' => 'HTML'
                        ]);
                    } catch (\Exception $e) {
                        Log::error("Subscription sendMessage failed for listing: " . $e->getMessage());
                    }
                }

                // Register URL in seen_listings
                SeenListing::firstOrCreate([
                    'telegram_user_id' => $user->id,
                    'url' => $listing['url']
                ]);
            }
        }

        $this->info('Subscription checks completed.');
        return 0;
    }
}
