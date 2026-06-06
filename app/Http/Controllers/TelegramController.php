<?php

namespace App\Http\Controllers;

use App\Models\TelegramUser;
use App\Models\SeenListing;
use App\Models\SavedSearch;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    protected $callbackMessageId = null;

    public function handle(Request $request)
    {
        $update = $request->all();
        Log::debug('Telegram Webhook Update: ' . json_encode($update));

        $chatId = null;
        $username = null;
        $text = null;
        $callbackQuery = null;

        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'] ?? null;
            $username = $update['message']['from']['username'] ?? null;
            $text = $update['message']['text'] ?? null;
        } elseif (isset($update['callback_query'])) {
            $chatId = $update['callback_query']['message']['chat']['id'] ?? null;
            $username = $update['callback_query']['from']['username'] ?? null;
            $callbackQuery = $update['callback_query'];
        }

        if (!$chatId) {
            return response()->json(['status' => 'no_chat_id']);
        }

        // Find or create TelegramUser
        $user = TelegramUser::firstOrCreate(
            ['telegram_id' => $chatId],
            ['username' => $username, 'step' => 'select_language']
        );

        // Update username if it has changed
        if ($username && $user->username !== $username) {
            $user->update(['username' => $username]);
        }

        if ($user->is_banned) {
            $this->logAction($user, 'banned_attempt', 'User attempted to interact but is restricted/banned.');
            $this->sendMessage($user->telegram_id, TranslationService::trans('user_banned', $user->language));
            return response()->json(['status' => 'banned']);
        }

        if ($callbackQuery) {
            $this->handleCallback($user, $callbackQuery);
        } elseif ($text) {
            $this->handleText($user, $text);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleText($user, $text)
    {
        $this->logAction($user, 'text_input', "User sent text: '{$text}' (Step: {$user->step})");

        if (strpos($text, '/start') === 0) {
            $this->cleanOldMessages($user->telegram_id);
            $this->removeOldFilterKeyboard($user->telegram_id);
            $user->resetFilters();
            $user->update(['step' => 'select_language']);
            $this->sendStep($user);
            return;
        }

        // Remove the inline keyboard from the previous bot message to prevent clicking it again
        $this->removeOldFilterKeyboard($user->telegram_id);

        switch ($user->step) {
            case 'save_profile_name':
                $name = trim($text);
                if (mb_strlen($name) > 0 && mb_strlen($name) < 50) {
                    if ($user->savedSearches()->count() >= 3) {
                        $this->sendMessage($user->telegram_id, TranslationService::trans('profile_limit_reached', $user->language));
                    } else {
                        $filters = [
                            'district' => $user->district,
                            'brand' => $user->brand,
                            'condition' => $user->condition,
                            'transmission' => $user->transmission,
                            'fuel_type' => $user->fuel_type,
                            'year_min' => $user->year_min,
                            'year_max' => $user->year_max,
                            'price_currency' => $user->price_currency,
                            'price_min' => $user->price_min,
                            'price_max' => $user->price_max,
                        ];
                        
                        $user->savedSearches()->create([
                            'name' => $name,
                            'category' => $user->arenda_type,
                            'region' => $user->region,
                            'district' => $user->district,
                            'filters' => $filters,
                        ]);
                        $this->sendMessage($user->telegram_id, TranslationService::trans('profile_saved', $user->language));
                    }
                    $user->update(['step' => 'arenda_type']);
                    $this->sendStep($user);
                } else {
                    $this->sendMessage($user->telegram_id, "Iltimos, to'g'ri nom kiriting (1-50 ta belgi) / Пожалуйста, введите корректное имя (1-50 символов):");
                }
                break;

            case 'year_min':
                $val = $this->parseNumber($text);
                if ($val !== null && $val >= 1900 && $val <= (int)date('Y') + 1) {
                    $user->update(['year_min' => $val, 'step' => 'year_max']);
                    $this->sendStep($user);
                } else {
                    $this->sendMessage($user->telegram_id, TranslationService::trans('invalid_year_please', $user->language));
                }
                break;

            case 'year_max':
                $val = $this->parseNumber($text);
                if ($val !== null && $val >= 1900 && $val <= (int)date('Y') + 1) {
                    if ($user->year_min && $val < $user->year_min) {
                        $this->sendMessage($user->telegram_id, TranslationService::trans('max_less_than_min', $user->language));
                    } else {
                        $user->update(['year_max' => $val, 'step' => 'price_currency']);
                        $this->sendStep($user);
                    }
                } else {
                    $this->sendMessage($user->telegram_id, TranslationService::trans('invalid_year_please', $user->language));
                }
                break;

            case 'area_min':
                $val = $this->parseNumber($text);
                if ($val !== null) {
                    $user->update(['area_min' => $val, 'step' => 'area_max']);
                    $this->sendStep($user);
                } else {
                    $this->sendMessage($user->telegram_id, TranslationService::trans('only_numbers_please', $user->language));
                }
                break;

            case 'area_max':
                $val = $this->parseNumber($text);
                if ($val !== null) {
                    if ($user->area_min && $val < $user->area_min) {
                        $this->sendMessage($user->telegram_id, TranslationService::trans('max_less_than_min', $user->language));
                    } else {
                        $user->update(['area_max' => $val, 'step' => 'price_currency']);
                        $this->sendStep($user);
                    }
                } else {
                    $this->sendMessage($user->telegram_id, TranslationService::trans('only_numbers_please', $user->language));
                }
                break;

            case 'price_min':
                $val = $this->parsePrice($text, $user->price_currency);
                if ($val !== null) {
                    $user->update(['price_min' => $val, 'step' => 'price_max']);
                    $this->sendStep($user);
                } else {
                    $this->sendMessage($user->telegram_id, TranslationService::trans('only_numbers_please', $user->language));
                }
                break;

            case 'price_max':
                $val = $this->parsePrice($text, $user->price_currency);
                if ($val !== null) {
                    if ($user->price_min && $val < $user->price_min) {
                        $this->sendMessage($user->telegram_id, TranslationService::trans('max_less_than_min', $user->language));
                    } else {
                        $user->update(['price_max' => $val, 'step' => 'showing_results']);
                        $this->sendStep($user);
                    }
                } else {
                    $this->sendMessage($user->telegram_id, TranslationService::trans('only_numbers_please', $user->language));
                }
                break;

            default:
                $this->sendMessage($user->telegram_id, "Iltimos, quyidagi menyu variantlaridan foydalaning:");
                $this->sendStep($user);
                break;
        }
    }

    protected function handleCallback($user, $callbackQuery)
    {
        $data = $callbackQuery['data'];
        $this->logAction($user, 'callback_query', "User clicked button: '{$data}' (Step: {$user->step})");

        $callbackQueryId = $callbackQuery['id'];
        $chatId = $user->telegram_id;
        $this->callbackMessageId = $callbackQuery['message']['message_id'] ?? null;

        try {
            Telegram::answerCallbackQuery(['callback_query_id' => $callbackQueryId]);
        } catch (\Exception $e) {
            Log::error('Error answering callback query: ' . $e->getMessage());
        }

        if ($data === 'restart') {
            $this->cleanOldMessages($chatId);
            $user->resetFilters();
            $user->update(['step' => 'select_language']);
            $this->sendStep($user);
            return;
        }

        if ($data === 'refresh_search') {
            $user->update(['step' => 'showing_results']);
            $this->sendStep($user);
            return;
        }

        if (strpos($data, 'toggle_sub_profile_') === 0) {
            $profileId = intval(substr($data, 19));
            $profile = SavedSearch::where('id', $profileId)->where('telegram_user_id', $user->id)->first();
            if ($profile) {
                $profile->update([
                    'is_subscribed' => !$profile->is_subscribed
                ]);
                $msgKey = $profile->is_subscribed ? 'subscribed_success' : 'unsubscribed_success';
                $this->sendMessage($user->telegram_id, TranslationService::trans($msgKey, $user->language));
            }
            $user->update(['step' => 'saved_searches']);
            $this->sendStep($user);
            return;
        }

        if ($data === 'back') {
            $prevStep = $this->getPreviousStep($user);
            $user->update(['step' => $prevStep]);
            $this->sendStep($user);
            return;
        }

        if ($data === 'change_language') {
            $user->update(['step' => 'select_language']);
            $this->sendStep($user);
            return;
        }

        if (strpos($data, 'lang_') === 0) {
            $lang = substr($data, 5);
            $user->update(['language' => $lang, 'step' => 'arenda_type']);
            $this->sendStep($user);
            return;
        }

        if ($data === 'saved_searches') {
            $user->update(['step' => 'saved_searches']);
            $this->sendStep($user);
            return;
        }

        if (strpos($data, 'run_profile_') === 0) {
            $profileId = intval(substr($data, 12));
            $profile = SavedSearch::where('id', $profileId)->where('telegram_user_id', $user->id)->first();
            if ($profile) {
                $filters = $profile->filters;
                $user->update(array_merge([
                    'arenda_type' => $profile->category,
                    'region' => $profile->region,
                    'district' => $profile->district,
                    'step' => 'showing_results'
                ], $filters));
                $this->sendStep($user);
            } else {
                $this->sendMessage($user->telegram_id, "Profil topilmadi / Профиль не найден.");
            }
            return;
        }

        if (strpos($data, 'delete_profile_') === 0) {
            $profileId = intval(substr($data, 15));
            $profile = SavedSearch::where('id', $profileId)->where('telegram_user_id', $user->id)->first();
            if ($profile) {
                $profile->delete();
                $this->sendMessage($user->telegram_id, TranslationService::trans('profile_deleted', $user->language));
            }
            $user->update(['step' => 'saved_searches']);
            $this->sendStep($user);
            return;
        }

        if ($data === 'save_current') {
            $user->update(['step' => 'save_profile_name']);
            $this->sendMessage($user->telegram_id, TranslationService::trans('save_profile_prompt', $user->language));
            return;
        }

        if ($data === 'toggle_new_only') {
            Cache::put('show_new_only_' . $user->telegram_id, true, 3600);
            $this->showResults($user);
            return;
        }

        if ($data === 'toggle_all_ads') {
            Cache::put('show_new_only_' . $user->telegram_id, false, 3600);
            $this->showResults($user);
            return;
        }

        switch ($user->step) {
            case 'arenda_type':
                if (in_array($data, ['uy', 'office', 'dokon', 'telefon', 'kompyuter', 'mashina'])) {
                    $user->update(['arenda_type' => $data, 'step' => 'region']);
                    $this->sendStep($user);
                }
                break;

            case 'region':
                $user->update(['region' => $data, 'step' => 'district']);
                $this->sendStep($user);
                break;

            case 'district':
                $district = null;
                if (strpos($data, 'district_') === 0) {
                    $district = substr($data, 9);
                }
                if ($district === 'all') {
                    $district = null;
                }
                if ($district === 'main') {
                    $district = null; // Matches the default slug of the region
                }
                
                // Determine next step
                $nextStep = in_array($user->arenda_type, ['telefon', 'kompyuter', 'mashina']) ? 'brand' : 'area_min';
                $user->update(['district' => $district, 'step' => $nextStep]);
                $this->sendStep($user);
                break;

            case 'brand':
                $brand = ($data === 'skip' || $data === 'all') ? 'all' : $data;
                $nextStep = ($user->arenda_type === 'mashina') ? 'transmission' : 'condition';
                $user->update(['brand' => $brand, 'step' => $nextStep]);
                $this->sendStep($user);
                break;

            case 'condition':
                $cond = ($data === 'skip' || $data === 'all') ? 'all' : $data;
                $user->update(['condition' => $cond, 'step' => 'price_currency']);
                $this->sendStep($user);
                break;

            case 'transmission':
                $trans = ($data === 'skip' || $data === 'all') ? 'all' : $data;
                $user->update(['transmission' => $trans, 'step' => 'fuel_type']);
                $this->sendStep($user);
                break;

            case 'fuel_type':
                $fuel = ($data === 'skip' || $data === 'all') ? 'all' : $data;
                $user->update(['fuel_type' => $fuel, 'step' => 'year_min']);
                $this->sendStep($user);
                break;

            case 'year_min':
                $year = ($data === 'skip') ? null : intval($data);
                $user->update(['year_min' => $year, 'step' => 'year_max']);
                $this->sendStep($user);
                break;

            case 'year_max':
                $year = ($data === 'skip') ? null : intval($data);
                $user->update(['year_max' => $year, 'step' => 'price_currency']);
                $this->sendStep($user);
                break;

            case 'area_min':
                if ($data === 'skip') {
                    $user->update(['area_min' => null, 'step' => 'area_max']);
                } else {
                    $user->update(['area_min' => intval($data), 'step' => 'area_max']);
                }
                $this->sendStep($user);
                break;

            case 'area_max':
                if ($data === 'skip') {
                    $user->update(['area_max' => null, 'step' => 'price_currency']);
                } else {
                    $user->update(['area_max' => intval($data), 'step' => 'price_currency']);
                }
                $this->sendStep($user);
                break;

            case 'price_currency':
                if (in_array($data, ['uzs', 'usd'])) {
                    $user->update(['price_currency' => strtoupper($data), 'step' => 'price_min']);
                    $this->sendStep($user);
                }
                break;

            case 'price_min':
                if ($data === 'skip') {
                    $user->update(['price_min' => null, 'step' => 'price_max']);
                } else {
                    $user->update(['price_min' => intval($data), 'step' => 'price_max']);
                }
                $this->sendStep($user);
                break;

            case 'price_max':
                if ($data === 'skip') {
                    $user->update(['price_max' => null, 'step' => 'showing_results']);
                } else {
                    $user->update(['price_max' => intval($data), 'step' => 'showing_results']);
                }
                $this->sendStep($user);
                break;

            case 'showing_results':
                if ($data === 'next' || $data === 'prev') {
                    $results = $user->last_results;
                    if ($results && is_array($results)) {
                        $total = count($results);
                        $totalPages = ceil($total / 2);
                        $page = $user->current_page;

                        if ($data === 'next' && $page < $totalPages) {
                            $user->increment('current_page');
                        } elseif ($data === 'prev' && $page > 1) {
                            $user->decrement('current_page');
                        }
                        $this->showResults($user);
                    }
                }
                break;
        }
    }

    protected function sendStep($user)
    {
        $chatId = $user->telegram_id;

        switch ($user->step) {
            case 'select_language':
                $text = TranslationService::trans('welcome', $user->language ?: 'uz');
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🇺🇿 O\'zbekcha', 'callback_data' => 'lang_uz'],
                            ['text' => '🇷🇺 Русский', 'callback_data' => 'lang_ru'],
                        ]
                    ]
                ];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'arenda_type':
                $text = TranslationService::trans('select_category', $user->language);
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => TranslationService::trans('btn_uy', $user->language), 'callback_data' => 'uy'],
                            ['text' => TranslationService::trans('btn_mashina', $user->language), 'callback_data' => 'mashina'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_telefon', $user->language), 'callback_data' => 'telefon'],
                            ['text' => TranslationService::trans('btn_kompyuter', $user->language), 'callback_data' => 'kompyuter'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_office', $user->language), 'callback_data' => 'office'],
                            ['text' => TranslationService::trans('btn_dokon', $user->language), 'callback_data' => 'dokon'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_saved_searches', $user->language), 'callback_data' => 'saved_searches'],
                            ['text' => TranslationService::trans('btn_change_language', $user->language), 'callback_data' => 'change_language'],
                        ]
                    ]
                ];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'region':
                $text = TranslationService::trans('select_region', $user->language);
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Toshkent sh. 🏙️', 'callback_data' => 'tashkent'],
                            ['text' => 'Samarqand 🕌', 'callback_data' => 'samarkand'],
                        ],
                        [
                            ['text' => 'Farg\'ona 🏔️', 'callback_data' => 'fergana'],
                            ['text' => 'Andijon 🍎', 'callback_data' => 'andijon'],
                        ],
                        [
                            ['text' => 'Buxoro 🏺', 'callback_data' => 'buxoro'],
                            ['text' => 'Namangan 🌸', 'callback_data' => 'namangan'],
                        ],
                        [
                            ['text' => 'Navoiy 🏭', 'callback_data' => 'navoi'],
                            ['text' => 'Qashqadaryo 🐎', 'callback_data' => 'karshi'],
                        ],
                        [
                            ['text' => 'Surxondaryo ☀️', 'callback_data' => 'termez'],
                            ['text' => 'Sirdaryo 🌾', 'callback_data' => 'gulistan'],
                        ],
                        [
                            ['text' => 'Jizzax 🌳', 'callback_data' => 'dzhizak'],
                            ['text' => 'Xorazm 🏰', 'callback_data' => 'urgench'],
                        ],
                        [
                            ['text' => 'Qoraqalpog\'iston 🌅', 'callback_data' => 'nukus'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                            ['text' => TranslationService::trans('btn_restart', $user->language), 'callback_data' => 'restart']
                        ]
                    ]
                ];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'district':
                $regionName = $this->getRegionName($user->region);
                $text = TranslationService::trans('select_district', $user->language, ['region' => $regionName]);
                
                $buttons = [];
                if ($user->region === 'tashkent') {
                    $districts = [
                        ['name' => 'Chilonzor', 'id' => '23'],
                        ['name' => 'Yunusobod', 'id' => '25'],
                        ['name' => 'Mirzo Ulug\'bek', 'id' => '12'],
                        ['name' => 'Mirabod', 'id' => '13'],
                        ['name' => 'Yashnobod', 'id' => '22'],
                        ['name' => 'Yakkasaroy', 'id' => '26'],
                        ['name' => 'Sergeli', 'id' => '19'],
                        ['name' => 'Uchtepa', 'id' => '21'],
                        ['name' => 'Shayxontohur', 'id' => '24'],
                        ['name' => 'Olmazor', 'id' => '20'],
                        ['name' => 'Bektemir', 'id' => '18'],
                    ];
                    
                    for ($i = 0; $i < count($districts); $i += 2) {
                        $row = [
                            ['text' => $districts[$i]['name'], 'callback_data' => 'district_' . $districts[$i]['id']]
                        ];
                        if (isset($districts[$i+1])) {
                            $row[] = ['text' => $districts[$i+1]['name'], 'callback_data' => 'district_' . $districts[$i+1]['id']];
                        }
                        $buttons[] = $row;
                    }
                } else {
                    $mainCity = $this->getRegionName($user->region);
                    $buttons[] = [
                        ['text' => "{$mainCity} shahri", 'callback_data' => 'district_main']
                    ];
                }
                
                $buttons[] = [
                    ['text' => TranslationService::trans('btn_all_districts', $user->language), 'callback_data' => 'district_all']
                ];
                $buttons[] = [
                    ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                    ['text' => TranslationService::trans('btn_restart', $user->language), 'callback_data' => 'restart']
                ];
                
                $keyboard = ['inline_keyboard' => $buttons];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'brand':
                $buttons = [];
                if ($user->arenda_type === 'telefon') {
                    $text = TranslationService::trans('select_brand_phone', $user->language);
                    $buttons = [
                        [
                            ['text' => 'Apple (iPhone) 🍏', 'callback_data' => '2065'],
                            ['text' => 'Samsung 📱', 'callback_data' => '2101'],
                        ],
                        [
                            ['text' => 'Xiaomi 🇨🇳', 'callback_data' => '2999'],
                            ['text' => 'Artel 🇺🇿', 'callback_data' => '2062'],
                        ],
                        [
                            ['text' => 'Vivo 📱', 'callback_data' => '2801'],
                            ['text' => 'Realme 📱', 'callback_data' => 'Realme'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_all_brands', $user->language), 'callback_data' => 'all']
                        ]
                    ];
                } elseif ($user->arenda_type === 'kompyuter') {
                    $text = TranslationService::trans('select_brand_computer', $user->language);
                    $buttons = [
                        [
                            ['text' => 'Apple (MacBook) 🍎', 'callback_data' => '2112'],
                            ['text' => 'Lenovo 💻', 'callback_data' => '2126'],
                        ],
                        [
                            ['text' => 'HP 🇺🇸', 'callback_data' => '2122'],
                            ['text' => 'Asus 🎮', 'callback_data' => '2113'],
                        ],
                        [
                            ['text' => 'Acer 💻', 'callback_data' => '2111'],
                            ['text' => 'Dell 🏢', 'callback_data' => '2117'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_all_brands', $user->language), 'callback_data' => 'all']
                        ]
                    ];
                } else { // mashina
                    $text = TranslationService::trans('select_brand_car', $user->language);
                    $buttons = [
                        [
                            ['text' => 'Chevrolet 🚘', 'callback_data' => 'chevrolet'],
                            ['text' => 'Lada (VAZ) 🚗', 'callback_data' => 'lada-vaz'],
                        ],
                        [
                            ['text' => 'BYD ⚡', 'callback_data' => 'byd'],
                            ['text' => 'Daewoo 🇺🇿', 'callback_data' => 'daewoo'],
                        ],
                        [
                            ['text' => 'Kia 🇰🇷', 'callback_data' => 'kia'],
                            ['text' => 'Hyundai 🚘', 'callback_data' => 'hyundai'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_all_brands', $user->language), 'callback_data' => 'all']
                        ]
                    ];
                }
                $buttons[] = [
                    ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                    ['text' => TranslationService::trans('btn_restart', $user->language), 'callback_data' => 'restart']
                ];
                $keyboard = ['inline_keyboard' => $buttons];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'condition':
                $text = TranslationService::trans('select_condition', $user->language);
                $buttons = [
                    [
                        ['text' => TranslationService::trans('btn_new', $user->language), 'callback_data' => 'new'],
                        ['text' => TranslationService::trans('btn_used', $user->language), 'callback_data' => 'used'],
                    ],
                    [
                        ['text' => TranslationService::trans('btn_all_conditions', $user->language), 'callback_data' => 'all'],
                    ],
                    [
                        ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                        ['text' => TranslationService::trans('btn_restart', $user->language), 'callback_data' => 'restart']
                    ]
                ];
                $keyboard = ['inline_keyboard' => $buttons];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'transmission':
                $text = TranslationService::trans('select_transmission', $user->language);
                $buttons = [
                    [
                        ['text' => TranslationService::trans('btn_auto', $user->language), 'callback_data' => '546'],
                        ['text' => TranslationService::trans('btn_manual', $user->language), 'callback_data' => '545'],
                    ],
                    [
                        ['text' => TranslationService::trans('btn_all_conditions', $user->language), 'callback_data' => 'all'],
                    ],
                    [
                        ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                        ['text' => TranslationService::trans('btn_restart', $user->language), 'callback_data' => 'restart']
                    ]
                ];
                $keyboard = ['inline_keyboard' => $buttons];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'fuel_type':
                $text = TranslationService::trans('select_fuel', $user->language);
                $buttons = [
                    [
                        ['text' => TranslationService::trans('btn_petrol', $user->language), 'callback_data' => '542'],
                        ['text' => TranslationService::trans('btn_gas_petrol', $user->language), 'callback_data' => '545'],
                    ],
                    [
                        ['text' => TranslationService::trans('btn_electric', $user->language), 'callback_data' => '546'],
                        ['text' => TranslationService::trans('btn_hybrid', $user->language), 'callback_data' => '544'],
                    ],
                    [
                        ['text' => TranslationService::trans('btn_all_conditions', $user->language), 'callback_data' => 'all'],
                    ],
                    [
                        ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                        ['text' => TranslationService::trans('btn_restart', $user->language), 'callback_data' => 'restart']
                    ]
                ];
                $keyboard = ['inline_keyboard' => $buttons];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'year_min':
                $text = TranslationService::trans('min_year', $user->language);
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '2015', 'callback_data' => '2015'],
                            ['text' => '2018', 'callback_data' => '2018'],
                            ['text' => '2020', 'callback_data' => '2020'],
                        ],
                        [
                            ['text' => '2022', 'callback_data' => '2022'],
                            ['text' => '2024', 'callback_data' => '2024'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_skip', $user->language), 'callback_data' => 'skip'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                            ['text' => TranslationService::trans('btn_restart', $user->language), 'callback_data' => 'restart']
                        ]
                    ]
                ];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'year_max':
                $text = TranslationService::trans('max_year', $user->language);
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '2018', 'callback_data' => '2018'],
                            ['text' => '2020', 'callback_data' => '2020'],
                            ['text' => '2022', 'callback_data' => '2022'],
                        ],
                        [
                            ['text' => '2024', 'callback_data' => '2024'],
                            ['text' => '2026', 'callback_data' => '2026'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_skip', $user->language), 'callback_data' => 'skip'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                            ['text' => TranslationService::trans('btn_restart', $user->language), 'callback_data' => 'restart']
                        ]
                    ]
                ];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'area_min':
                $text = TranslationService::trans('min_area', $user->language);
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '30 m²', 'callback_data' => '30'],
                            ['text' => '40 m²', 'callback_data' => '40'],
                            ['text' => '50 m²', 'callback_data' => '50'],
                        ],
                        [
                            ['text' => '60 m²', 'callback_data' => '60'],
                            ['text' => '80 m²', 'callback_data' => '80'],
                            ['text' => '100 m²', 'callback_data' => '100'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_skip', $user->language), 'callback_data' => 'skip'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                            ['text' => TranslationService::trans('btn_restart', $user->language), 'callback_data' => 'restart']
                        ]
                    ]
                ];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'area_max':
                $text = TranslationService::trans('max_area', $user->language);
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '50 m²', 'callback_data' => '50'],
                            ['text' => '70 m²', 'callback_data' => '70'],
                            ['text' => '90 m²', 'callback_data' => '90'],
                        ],
                        [
                            ['text' => '120 m²', 'callback_data' => '120'],
                            ['text' => '150 m²', 'callback_data' => '150'],
                            ['text' => '200 m²', 'callback_data' => '200'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_skip', $user->language), 'callback_data' => 'skip'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                            ['text' => TranslationService::trans('btn_restart', $user->language), 'callback_data' => 'restart']
                        ]
                    ]
                ];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'price_currency':
                $text = TranslationService::trans('select_currency', $user->language);
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => TranslationService::trans('btn_usd', $user->language), 'callback_data' => 'usd'],
                            ['text' => TranslationService::trans('btn_uzs', $user->language), 'callback_data' => 'uzs'],
                        ],
                        [
                            ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                            ['text' => TranslationService::trans('btn_restart', $user->language), 'callback_data' => 'restart']
                        ]
                    ]
                ];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'price_min':
                $currency = $user->price_currency === 'USD' ? 'USD' : 'UZS';
                $text = TranslationService::trans('min_price', $user->language, ['currency' => $currency]);
                
                $buttons = [];
                if ($currency === 'USD') {
                    $buttons = [
                        [
                            ['text' => '100 $', 'callback_data' => '100'],
                            ['text' => '200 $', 'callback_data' => '200'],
                            ['text' => '300 $', 'callback_data' => '300'],
                        ],
                        [
                            ['text' => '500 $', 'callback_data' => '500'],
                            ['text' => '700 $', 'callback_data' => '700'],
                            ['text' => '1000 $', 'callback_data' => '1000'],
                        ]
                    ];
                } else {
                    $buttons = [
                        [
                            ['text' => '1 mln UZS', 'callback_data' => '1000000'],
                            ['text' => '2 mln UZS', 'callback_data' => '2000000'],
                            ['text' => '3 mln UZS', 'callback_data' => '3000000'],
                        ],
                        [
                            ['text' => '4 mln UZS', 'callback_data' => '4000000'],
                            ['text' => '5 mln UZS', 'callback_data' => '5000000'],
                            ['text' => '10 mln UZS', 'callback_data' => '10000000'],
                        ]
                    ];
                }
                $buttons[] = [['text' => TranslationService::trans('btn_skip', $user->language), 'callback_data' => 'skip']];
                $buttons[] = [
                    ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                    ['text' => TranslationService::trans('btn_restart', $user->language), 'callback_data' => 'restart']
                ];
                
                $keyboard = ['inline_keyboard' => $buttons];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'price_max':
                $currency = $user->price_currency === 'USD' ? 'USD' : 'UZS';
                $text = TranslationService::trans('max_price', $user->language, ['currency' => $currency]);
                
                $buttons = [];
                if ($currency === 'USD') {
                    $buttons = [
                        [
                            ['text' => '300 $', 'callback_data' => '300'],
                            ['text' => '500 $', 'callback_data' => '500'],
                            ['text' => '700 $', 'callback_data' => '700'],
                        ],
                        [
                            ['text' => '1000 $', 'callback_data' => '1000'],
                            ['text' => '1500 $', 'callback_data' => '1500'],
                            ['text' => '2000 $', 'callback_data' => '2000'],
                        ]
                    ];
                } else {
                    $buttons = [
                        [
                            ['text' => '3 mln UZS', 'callback_data' => '3000000'],
                            ['text' => '5 mln UZS', 'callback_data' => '5000000'],
                            ['text' => '8 mln UZS', 'callback_data' => '8000000'],
                        ],
                        [
                            ['text' => '10 mln UZS', 'callback_data' => '10000000'],
                            ['text' => '15 mln UZS', 'callback_data' => '15000000'],
                            ['text' => '20 mln UZS', 'callback_data' => '20000000'],
                        ]
                    ];
                }
                $buttons[] = [['text' => TranslationService::trans('btn_skip', $user->language), 'callback_data' => 'skip']];
                $buttons[] = [
                    ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                    ['text' => TranslationService::trans('btn_restart', $user->language), 'callback_data' => 'restart']
                ];
                
                $keyboard = ['inline_keyboard' => $buttons];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'showing_results':
                if ($this->callbackMessageId) {
                    try {
                        Telegram::editMessageText([
                            'chat_id' => $chatId,
                            'message_id' => $this->callbackMessageId,
                            'text' => TranslationService::trans('scraping_loading', $user->language),
                            'reply_markup' => json_encode(['inline_keyboard' => []]),
                            'parse_mode' => 'HTML'
                        ]);
                        Cache::put('tg_filter_msg_' . $chatId, $this->callbackMessageId, 3600);
                    } catch (\Exception $e) {
                        $this->sendMessage($chatId, TranslationService::trans('scraping_loading', $user->language));
                    }
                } else {
                    $this->sendMessage($chatId, TranslationService::trans('scraping_loading', $user->language));
                }
                $this->runScraperAndShow($user);
                break;

            case 'saved_searches':
                $profiles = $user->savedSearches;
                $text = TranslationService::trans('saved_profiles_title', $user->language);
                
                $buttons = [];
                if ($profiles->isEmpty()) {
                    $text .= "\n\n<i>" . TranslationService::trans('no_saved_profiles', $user->language) . "</i>";
                } else {
                    foreach ($profiles as $profile) {
                        $subIcon = $profile->is_subscribed ? "🔔" : "🔕";
                        $subBtnText = $profile->is_subscribed 
                            ? TranslationService::trans('btn_unsubscribe', $user->language) 
                            : TranslationService::trans('btn_subscribe', $user->language);

                        $buttons[] = [
                            ['text' => "🔍 " . $profile->name . " [" . $subIcon . "]", 'callback_data' => 'run_profile_' . $profile->id],
                        ];
                        $buttons[] = [
                            ['text' => $subBtnText, 'callback_data' => 'toggle_sub_profile_' . $profile->id],
                            ['text' => TranslationService::trans('btn_delete', $user->language), 'callback_data' => 'delete_profile_' . $profile->id],
                        ];
                    }
                }
                $buttons[] = [
                    ['text' => TranslationService::trans('btn_back', $user->language), 'callback_data' => 'back'],
                ];
                $keyboard = ['inline_keyboard' => $buttons];
                $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;

            case 'showing_results':
                if ($this->callbackMessageId) {
                    try {
                        Telegram::editMessageText([
                            'chat_id' => $chatId,
                            'message_id' => $this->callbackMessageId,
                            'text' => TranslationService::trans('scraping_loading', $user->language),
                            'reply_markup' => json_encode(['inline_keyboard' => []]),
                            'parse_mode' => 'HTML'
                        ]);
                        Cache::put('tg_filter_msg_' . $chatId, $this->callbackMessageId, 3600);
                    } catch (\Exception $e) {
                        $this->sendMessage($chatId, TranslationService::trans('scraping_loading', $user->language));
                    }
                } else {
                    $this->sendMessage($chatId, TranslationService::trans('scraping_loading', $user->language));
                }
                $this->runScraperAndShow($user);
                break;
        }
    }

    protected function runScraperAndShow($user)
    {
        $queryDetails = "Category: {$user->arenda_type}, Region: {$user->region}";
        if ($user->district) $queryDetails .= ", District: {$user->district}";
        if ($user->price_min || $user->price_max) $queryDetails .= ", Price: {$user->price_min}-{$user->price_max} {$user->price_currency}";
        if ($user->brand) $queryDetails .= ", Brand: {$user->brand}";
        $this->logAction($user, 'search_executed', "Executed search: {$queryDetails}");

        $chatId = $user->telegram_id;
        $pythonPath = 'python';
        $scriptPath = base_path('app/Services/olx_scraper.py');
        
        $cmd = sprintf(
            '%s %s --category=%s --region=%s',
            escapeshellcmd($pythonPath),
            escapeshellarg($scriptPath),
            escapeshellarg($user->arenda_type),
            escapeshellarg($user->region)
        );

        if ($user->district) {
            $cmd .= ' --district=' . escapeshellarg($user->district);
        }
        if ($user->price_min) {
            $cmd .= ' --price_min=' . escapeshellarg($user->price_min);
        }
        if ($user->price_max) {
            $cmd .= ' --price_max=' . escapeshellarg($user->price_max);
        }
        if ($user->price_currency) {
            $cmd .= ' --currency=' . escapeshellarg(strtolower($user->price_currency));
        }
        if ($user->area_min) {
            $cmd .= ' --area_min=' . escapeshellarg($user->area_min);
        }
        if ($user->area_max) {
            $cmd .= ' --area_max=' . escapeshellarg($user->area_max);
        }
        if ($user->brand) {
            $cmd .= ' --brand=' . escapeshellarg($user->brand);
        }
        if ($user->condition) {
            $cmd .= ' --condition=' . escapeshellarg($user->condition);
        }
        if ($user->transmission) {
            $cmd .= ' --transmission=' . escapeshellarg($user->transmission);
        }
        if ($user->fuel_type) {
            $cmd .= ' --fuel_type=' . escapeshellarg($user->fuel_type);
        }
        if ($user->year_min) {
            $cmd .= ' --year_min=' . escapeshellarg($user->year_min);
        }
        if ($user->year_max) {
            $cmd .= ' --year_max=' . escapeshellarg($user->year_max);
        }

        Log::debug("Executing scraper: $cmd");
        $output = shell_exec($cmd);
        
        if (!$output) {
            $this->removeOldFilterMessage($chatId);
            $this->sendMessage($chatId, TranslationService::trans('scrape_error', $user->language));
            $user->resetFilters();
            $this->sendStep($user);
            return;
        }

        $response = json_decode($output, true);
        if (isset($response['error'])) {
            Log::error("Scraper Error Output: " . $response['error']);
            $this->removeOldFilterMessage($chatId);
            $this->sendMessage($chatId, TranslationService::trans('scrape_error', $user->language));
            $user->resetFilters();
            $this->sendStep($user);
            return;
        }

        $listings = $response['listings'] ?? [];
        if (empty($listings)) {
            $this->removeOldFilterMessage($chatId);
            $this->sendMessage($chatId, TranslationService::trans('results_empty', $user->language));
            $user->resetFilters();
            $this->sendStep($user);
            return;
        }

        // Cache the parsed listings in DB
        $user->update([
            'last_results' => $listings,
            'current_page' => 1
        ]);

        $this->showResults($user);
    }

    protected function showResults($user)
    {
        $chatId = $user->telegram_id;
        $listings = $user->last_results;
        $page = $user->current_page;
        
        if (!$listings || !is_array($listings)) {
            $user->resetFilters();
            $this->sendStep($user);
            return;
        }

        // 1. Filter out seen listings if user requested "Only New"
        $showNewOnly = Cache::get('show_new_only_' . $user->telegram_id, false);
        if ($showNewOnly) {
            $seenUrls = SeenListing::where('telegram_user_id', $user->id)->pluck('url')->toArray();
            $listings = array_filter($listings, function($item) use ($seenUrls) {
                return !in_array($item['url'], $seenUrls);
            });
            $listings = array_values($listings);
        }

        // Clean up the loading/filter message
        $this->removeOldFilterMessage($chatId);

        // Clean up only the previous control message to avoid duplicate pagination keyboards
        $this->cleanOldControlMessage($chatId);

        $total = count($listings);
        $totalPages = ceil($total / 2);
        
        // Handle page overflow if filters changed total count
        if ($total > 0 && $page > $totalPages) {
            $page = $totalPages;
            $user->update(['current_page' => $page]);
        }

        $startIndex = ($page - 1) * 2;
        $pageListings = array_slice($listings, $startIndex, 2);
        
        $sentMessageIds = [];

        // 2. Send current page listings and mark them as seen
        foreach ($pageListings as $index => $listing) {
            $currentNumber = $startIndex + $index + 1;
            $source = $listing['source'] ?? 'OLX.uz';
            
            $text = "<b>📌 E'LON #{$currentNumber} [{$source}]</b>\n\n";
            $text .= "<b>🏠 Sarlavha / Заголовок:</b> " . htmlspecialchars($listing['title']) . "\n";
            $text .= "💰 <b>Narxi / Цена:</b> " . htmlspecialchars($listing['price']) . "\n";
            
            if (!empty($listing['location'])) {
                $text .= "📍 <b>Manzil / Адрес:</b> " . htmlspecialchars($listing['location']) . "\n";
            }
            
            if (!empty($listing['description'])) {
                $desc = trim($listing['description']);
                if (mb_strlen($desc) > 500) {
                    $desc = mb_substr($desc, 0, 480) . '...';
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
                    $selectedPhotos = array_slice($photos, 0, 5);
                    foreach ($selectedPhotos as $photoUrl) {
                        $media[] = [
                            'type' => 'photo',
                            'media' => $photoUrl
                        ];
                    }

                    $mediaResponse = Telegram::sendMediaGroup([
                        'chat_id' => $chatId,
                        'media' => json_encode($media)
                    ]);

                    foreach ($mediaResponse as $msg) {
                        $sentMessageIds[] = $this->getMessageIdFromResponse($msg);
                    }

                    $response = Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text' => $text,
                        'reply_markup' => json_encode($keyboard),
                        'parse_mode' => 'HTML'
                    ]);
                    $sentMessageIds[] = $this->getMessageIdFromResponse($response);
                    $sent = true;
                } catch (\Exception $e) {
                    Log::warning("sendMediaGroup failed for listing {$currentNumber}: " . $e->getMessage());
                }
            }

            if (!$sent && count($photos) === 1) {
                try {
                    $response = Telegram::sendPhoto([
                        'chat_id' => $chatId,
                        'photo' => $photos[0],
                        'caption' => $text,
                        'reply_markup' => json_encode($keyboard),
                        'parse_mode' => 'HTML'
                    ]);
                    $sentMessageIds[] = $this->getMessageIdFromResponse($response);
                    $sent = true;
                } catch (\Exception $e) {
                    Log::warning("sendPhoto failed for listing {$currentNumber}: " . $e->getMessage());
                }
            }

            if (!$sent) {
                if (!empty($photos)) {
                    $text .= "\n🖼️ <b>Rasmlar / Фото:</b>\n";
                    foreach ($photos as $pIdx => $photoUrl) {
                        $text .= "<a href=\"" . htmlspecialchars($photoUrl) . "\">Rasm " . ($pIdx + 1) . "</a> ";
                    }
                    $text .= "\n";
                }

                try {
                    $response = Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text' => $text,
                        'reply_markup' => json_encode($keyboard),
                        'parse_mode' => 'HTML'
                    ]);
                    $sentMessageIds[] = $this->getMessageIdFromResponse($response);
                } catch (\Exception $e) {
                    Log::error("sendMessage fallback failed for listing {$currentNumber}: " . $e->getMessage());
                }
            }

            // Register URL in seen_listings
            SeenListing::firstOrCreate([
                'telegram_user_id' => $user->id,
                'url' => $listing['url']
            ]);
        }

        // 3. Construct Control and Pagination Keyboard
        $controlText = "";
        if ($showNewOnly) {
            $controlText .= TranslationService::trans('showing_only_new', $user->language, ['count' => $total]) . "\n\n";
        }
        $controlText .= TranslationService::trans('listings_result', $user->language, [
            'page' => $total > 0 ? $page : 0,
            'total_pages' => $totalPages,
            'total' => $total
        ]);
        
        $controlButtons = [];
        $navRow = [];
        if ($page > 1 && $total > 0) {
            $navRow[] = ['text' => TranslationService::trans('btn_prev', $user->language), 'callback_data' => 'prev'];
        }
        if ($page < $totalPages && $total > 0) {
            $navRow[] = ['text' => TranslationService::trans('btn_next', $user->language), 'callback_data' => 'next'];
        }
        if (!empty($navRow)) {
            $controlButtons[] = $navRow;
        }
        
        // Seen Toggle Button
        if ($showNewOnly) {
            $controlButtons[] = [
                ['text' => TranslationService::trans('btn_all_ads', $user->language), 'callback_data' => 'toggle_all_ads']
            ];
        } else {
            $controlButtons[] = [
                ['text' => TranslationService::trans('btn_only_new', $user->language), 'callback_data' => 'toggle_new_only']
            ];
        }

        // Save Current Search Button (if profiles < 3)
        if ($user->savedSearches()->count() < 3) {
            $controlButtons[] = [
                ['text' => TranslationService::trans('btn_save_search', $user->language), 'callback_data' => 'save_current']
            ];
        }

        $webAppUrl = url('/webapp/' . $chatId);
        if (strpos($webAppUrl, 'http://') === 0) {
            $webAppUrl = 'https://' . substr($webAppUrl, 7);
        }
        $controlButtons[] = [
            ['text' => TranslationService::trans('btn_view_all_webapp', $user->language), 'web_app' => ['url' => $webAppUrl]]
        ];
        
        $controlButtons[] = [
            ['text' => TranslationService::trans('btn_refresh', $user->language), 'callback_data' => 'refresh_search'],
            ['text' => TranslationService::trans('btn_new_search', $user->language), 'callback_data' => 'restart']
        ];

        try {
            $response = Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $controlText,
                'reply_markup' => json_encode(['inline_keyboard' => $controlButtons]),
                'parse_mode' => 'HTML'
            ]);
            $controlMsgId = $this->getMessageIdFromResponse($response);
            if ($controlMsgId) {
                Cache::put('tg_control_msg_' . $chatId, $controlMsgId, 3600);
                $sentMessageIds[] = $controlMsgId;
            }
        } catch (\Exception $e) {
            Log::error("Failed to send control message: " . $e->getMessage());
        }

        // Cache the sent message IDs so we can delete them on next page or reset
        $existingMsgIds = Cache::get('tg_msg_' . $chatId, []);
        $allMsgIds = array_merge($existingMsgIds, $sentMessageIds);
        Cache::put('tg_msg_' . $chatId, $allMsgIds, 3600);
    }

    protected function cleanOldMessages($chatId)
    {
        $key = 'tg_msg_' . $chatId;
        $msgIds = Cache::get($key, []);
        foreach ($msgIds as $msgId) {
            try {
                Telegram::deleteMessage([
                    'chat_id' => $chatId,
                    'message_id' => $msgId
                ]);
            } catch (\Exception $e) {
                // Message might be deleted or too old to delete
            }
        }
        Cache::forget($key);
        Cache::forget('tg_control_msg_' . $chatId);
    }

    protected function parseNumber($text)
    {
        $cleaned = str_replace([' ', 'm2', 'kv', 'kvadrat'], '', strtolower($text));
        if (is_numeric($cleaned)) {
            return intval($cleaned);
        }
        return null;
    }

    protected function parsePrice($text, $currency)
    {
        $cleaned = str_replace([' ', '$', 'sum', 'som', 'so\'m', 'uzs', 'usd'], '', strtolower($text));
        
        if (strpos($cleaned, 'mln') !== false || strpos($cleaned, 'm') !== false) {
            $numStr = str_replace(['mln', 'm'], '', $cleaned);
            $num = floatval($numStr);
            return intval($num * 1000000);
        }
        
        if (is_numeric($cleaned)) {
            return intval($cleaned);
        }
        
        return null;
    }

    protected function sendMessage($chatId, $text)
    {
        try {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send message: " . $e->getMessage());
        }
    }

    protected function sendMessageWithKeyboard($chatId, $text, $keyboard)
    {
        if ($this->callbackMessageId) {
            try {
                Telegram::editMessageText([
                    'chat_id' => $chatId,
                    'message_id' => $this->callbackMessageId,
                    'text' => $text,
                    'reply_markup' => json_encode($keyboard),
                    'parse_mode' => 'HTML'
                ]);
                Cache::put('tg_filter_msg_' . $chatId, $this->callbackMessageId, 3600);
                return;
            } catch (\Exception $e) {
                Log::warning("Failed to edit message {$this->callbackMessageId}, sending new: " . $e->getMessage());
            }
        }

        try {
            $response = Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'reply_markup' => json_encode($keyboard),
                'parse_mode' => 'HTML'
            ]);
            $newMsgId = $this->getMessageIdFromResponse($response);
            if ($newMsgId) {
                Cache::put('tg_filter_msg_' . $chatId, $newMsgId, 3600);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send keyboard message: " . $e->getMessage());
        }
    }

    protected function removeOldFilterKeyboard($chatId)
    {
        $oldFilterMsgId = Cache::get('tg_filter_msg_' . $chatId);
        if ($oldFilterMsgId) {
            try {
                Telegram::editMessageReplyMarkup([
                    'chat_id' => $chatId,
                    'message_id' => $oldFilterMsgId,
                    'reply_markup' => json_encode(['inline_keyboard' => []])
                ]);
            } catch (\Exception $e) {
                // Ignore
            }
            Cache::forget('tg_filter_msg_' . $chatId);
        }
    }

    protected function removeOldFilterMessage($chatId)
    {
        $oldFilterMsgId = Cache::get('tg_filter_msg_' . $chatId);
        if ($oldFilterMsgId) {
            try {
                Telegram::deleteMessage([
                    'chat_id' => $chatId,
                    'message_id' => $oldFilterMsgId
                ]);
            } catch (\Exception $e) {
                // Ignore
            }
            Cache::forget('tg_filter_msg_' . $chatId);
        }
    }

    protected function cleanOldControlMessage($chatId)
    {
        $key = 'tg_control_msg_' . $chatId;
        $msgId = Cache::get($key);
        if ($msgId) {
            try {
                Telegram::deleteMessage([
                    'chat_id' => $chatId,
                    'message_id' => $msgId
                ]);
            } catch (\Exception $e) {
                // Ignore
            }
            Cache::forget($key);
        }
    }

    protected function getRegionName($slug)
    {
        $regions = [
            'tashkent' => 'Toshkent shahri',
            'samarkand' => 'Samarqand viloyati',
            'fergana' => 'Farg\'ona viloyati',
            'andijon' => 'Andijon viloyati',
            'buxoro' => 'Buxoro viloyati',
            'namangan' => 'Namangan viloyati',
            'navoi' => 'Navoiy viloyati',
            'karshi' => 'Qashqadaryo viloyati',
            'termez' => 'Surxondaryo viloyati',
            'gulistan' => 'Sirdaryo viloyati',
            'dzhizak' => 'Jizzax viloyati',
            'urgench' => 'Xorazm viloyati',
            'nukus' => 'Qoraqalpog\'iston Res.',
        ];

        return $regions[$slug] ?? ucfirst($slug);
    }

    protected function getMessageIdFromResponse($response)
    {
        if (is_object($response)) {
            if (method_exists($response, 'getMessageId')) {
                return $response->getMessageId();
            }
            if (isset($response->message_id)) {
                return $response->message_id;
            }
            if (method_exists($response, 'get')) {
                return $response->get('message_id');
            }
        }
        if (is_array($response)) {
            return $response['message_id'] ?? null;
        }
        return null;
    }

    protected function getPreviousStep($user)
    {
        $category = $user->arenda_type;
        switch ($user->step) {
            case 'arenda_type':
                return 'select_language';
            case 'region':
                return 'arenda_type';
            case 'district':
                return 'region';
            case 'brand':
            case 'area_min':
                return 'district';
            case 'condition':
                return 'brand';
            case 'transmission':
                return 'brand';
            case 'fuel_type':
                return 'transmission';
            case 'year_min':
                return 'fuel_type';
            case 'year_max':
                return 'year_min';
            case 'area_max':
                return 'area_min';
            case 'price_currency':
                if (in_array($category, ['telefon', 'kompyuter'])) {
                    return 'condition';
                } elseif ($category === 'mashina') {
                    return 'year_max';
                } else {
                    return 'area_max';
                }
            case 'price_min':
                return 'price_currency';
            case 'price_max':
                return 'price_min';
            case 'showing_results':
                return 'price_max';
            default:
                return 'arenda_type';
        }
    }

    public function webApp($telegramId)
    {
        $user = TelegramUser::where('telegram_id', $telegramId)->first();
        $listings = $user ? ($user->last_results ?? []) : [];
        return view('webapp', compact('listings'));
    }

    protected function logAction($user, $action, $details = null)
    {
        try {
            $user->logs()->create([
                'action' => $action,
                'details' => $details,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save TelegramUser log: ' . $e->getMessage());
        }
    }
}
