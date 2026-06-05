<?php

namespace App\Services;

class TranslationService
{
    protected static $cache = [];

    /**
     * Get translated text
     *
     * @param string $key
     * @param string $lang ('uz' or 'ru')
     * @param array $replace Associated array of placeholders and values
     * @return string
     */
    public static function trans($key, $lang = 'uz', $replace = [])
    {
        $lang = in_array(strtolower($lang), ['uz', 'ru']) ? strtolower($lang) : 'uz';

        if (!isset(self::$cache[$lang])) {
            $filePath = base_path("lang/{$lang}.json");
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                self::$cache[$lang] = json_decode($content, true) ?: [];
            } else {
                self::$cache[$lang] = [];
            }
        }

        $translation = self::$cache[$lang][$key] ?? $key;

        foreach ($replace as $placeholder => $value) {
            $translation = str_replace("%{$placeholder}%", $value, $translation);
        }

        return $translation;
    }
}
