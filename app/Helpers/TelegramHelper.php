<?php

namespace App\Helpers;

use DateTime;

class TelegramHelper
{
    const UZBEK_LANGUAGE = 'O\'zbek tili',
          RUSSIAN_LANGUAGE = 'Русский язык',
          ENGLISH_LANGUAGE = 'Ўзбекча (кирилл)',
          START_STEP = 'start',
          PHONE_STEP = 'askPhone',
          NAME_STEP = 'askName',
          MAIN_PAGE_STEP = 'main',
          ALL_OBJECTS = 'all_objects',
          ALL_BRANCHES = 'all_branches',
          ALL_TASKS = 'all_tasks',
          ALL_MATERIALS = 'all_materials',
          SETTINGS_STEP = 'settings',
          CHANGE_LANG_STEP = 'change_lang',
          ASK_OBJECT_NAME = 'ask_object_name',
          ASK_BRANCH_NAME = 'ask_branch_name',
          ASK_BRANCH_ADDRESS = 'ask_branch_address',
          ASK_TASK_NAME = 'ask_task_name',
          ASK_TASK_QUANTITY = 'ask_task_quantity',
          ASK_TASK_DESCRIPTION = 'ask_task_description',
          ASK_TASK_PRICE_FOR_WORK = 'ask_task_price',
          ASK_TASK_IMAGE = 'ask_task_image',
          ASK_AFTER_CONFIRM_TASK = 'ask_after_confirm_task',
          ASK_MATERIAL_NAME = 'ask_material_name',
          ASK_MATERIAL_QUANTITY_TYPE = 'ask_material_quantity_type',
          ASK_MATERIAL_QUANTITY = 'ask_material_quantity',
          ASK_MATERIAL_PRICE_FOR_TYPE = 'ask_material_price_for_type',
          ASK_MATERIAL_IMAGE = 'ask_material_image',
          ASK_MATERIAL_PRICE_FOR_WORK = 'ask_material_price_for_work',
          CONFIRM_OBJECT = 'confirm_object',
          CONFIRM_TASK = 'confirm_task',
          CONFIRM_MATERIAL = 'confirm_material',
          ADD_MATERIAL_PRICE = 'add_material_price',
          CHOOSE_LANGUAGE_TEXT = "Muloqot uchun tilni tanlang\n\nВыберите язык для общения\n\nМулоқот учун тилни танланг";

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
