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
          APPEALS_STEP = 'appeals',
          ASK_APPEAL_TITLE = 'askAppealTitle',
          ASK_APPEAL_DESCRIPTION = 'ask_sms_type',
          SETTINGS_STEP = 'settings',
          CHANGE_LANG_STEP = 'change_lang',
          DELETE_ACCOUNT_STEP = 'delete_account',
          CHOOSE_LANGUAGE_TEXT = "Muloqot uchun tilni tanlang\n\nВыберите язык для общения\n\nSelect language";

    public static function checkPhone($phone): bool|string
    {
        $phone = str_replace([' ', '-', '+'], '', $phone);
        if (strlen($phone) == 9 || strlen($phone) == 12) {
            return strlen($phone) == 9 ? '998' . $phone : $phone;
        }
        return false;
    }

    public static function getValue($model, $language, $attribute='name') {
        $mainAttr = $attribute;
        switch ($language) {
            case 'ru':
                $attribute .= '_ru';
                return $model->$attribute ?? $model->$mainAttr;
            case 'en':
                $attribute .= '_en';
                return $model->$attribute ?? $model->$mainAttr;
            default:
                return $model->$attribute;
        }
    }
}
