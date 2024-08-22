<?php

namespace App\Helpers;

use DateTime;

class TelegramHelper
{
    const UZBEK_LANGUAGE = 'O\'zbek tili',
          RUSSIAN_LANGUAGE = 'Русский язык',
          ENGLISH_LANGUAGE = 'English language',
          START_STEP = 'start',
          PHONE_STEP = 'askPhone',
          MAIN_PAGE_STEP = 'main',
          ASK_CARD_PAGE = 'askCard',
          ASK_EXPIRY = 'askExpiry',
          SETTINGS_STEP = 'settings',
          CHANGE_LANG_STEP = 'change_lang',
          ASK_SMS_TYPE = 'ask_sms_type',
          CHOOSE_LANGUAGE_TEXT = "Muloqot uchun tilni tanlang\n\nВыберите язык для общения\n\nSelect language";

    public static function checkPhone($phone): bool|string
    {
        $phone = str_replace([' ', '-', '+'], '', $phone);
        if (strlen($phone) == 9 || strlen($phone) == 12) {
            return strlen($phone) == 9 ? '998' . $phone : $phone;
        }
        return false;
    }

    public static function checkCard($card): bool
    {

        return false;
    }

    public static function luhnAlgorithm($pan): bool
    {
        $number = strrev($pan);
        $sum = 0;
        for ($i = 0, $j = strlen($number); $i < $j; $i++) {
            if (($i % 2) == 0) {
                $val = $number[$i];
            } else {
                $val = $number[$i] * 2;
                if ($val > 9) {
                    $val -= 9;
                }
            }
            $sum += $val;
        }
        return (($sum % 10) === 0);
    }

    public static function expiry($date, $format = 'my'): string
    {
        return DateTime::createFromFormat($format, $date)->format('ym');
    }
}
