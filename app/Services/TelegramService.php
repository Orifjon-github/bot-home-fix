<?php

namespace App\Services;


use App\Helpers\TelegramHelper;
use App\Models\AppealType;
use App\Repositories\AppealRepository;
use App\Repositories\TelegramTextRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $chat_id;
    private string|null $text;
    private Telegram $telegram;
    private UserRepository $userRepository;
    private TelegramTextRepository $textRepository;
    private AppealRepository $appealRepository;

    public function __construct(
        Telegram               $telegram,
        UserRepository         $userRepository,
        TelegramTextRepository $textRepository,
        AppealRepository       $appealRepository,
    )
    {
        $this->telegram = $telegram;
        $this->chat_id = $telegram->ChatID();
        $this->text = $telegram->Text();
        $this->userRepository = $userRepository;
        $this->textRepository = $textRepository;
        $this->appealRepository = $appealRepository;
    }

    public function start(): bool
    {
        if ($this->text == '/start' || $this->textRepository->checkTextWithKeyboard($this->text)) {
            $this->handleRegistration();
        } else {
            switch ($this->userRepository->page($this->chat_id)) {
                case TelegramHelper::START_STEP:
                    switch ($this->text) {
                        case TelegramHelper::UZBEK_LANGUAGE:
                            $this->userRepository->language($this->chat_id, 'uz');
                            $this->askPhone();
                            break;
                        case TelegramHelper::RUSSIAN_LANGUAGE:
                            $this->userRepository->language($this->chat_id, 'ru');
                            $this->askPhone();
                            break;
                        case TelegramHelper::ENGLISH_LANGUAGE:
                            $this->userRepository->language($this->chat_id, 'en');
                            $this->askPhone();
                            break;
                        default:
                            $this->chooseLanguage();
                            break;
                    }
                    break;
                case TelegramHelper::PHONE_STEP:
                    if ($phone = TelegramHelper::checkPhone($this->text)) {
                        $this->userRepository->phone($this->chat_id, $phone);
                        $this->showMainPage();
                    } else {
                        $this->askCorrectPhone();
                    }
                    break;
                case TelegramHelper::MAIN_PAGE_STEP:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'settings_button':
                            $this->showSettings();
                            break;
                        case 'contact_button':
                            $this->showContact();
                            break;
                        case 'help_button':
                            $this->showHelp();
                            break;
                        case 'appeals_button':
                            $this->showAppeals();
                            break;
                        default:
                            $this->showMainPage();
                            break;
                    }
                    break;
                case TelegramHelper::SETTINGS_STEP:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'change_language_button':
                            $this->chooseLanguage(true);
                            break;
                        case 'delete_account_button':
                            $this->deleteAccount();
                            break;
                        case 'main_page_button':
                            $this->showMainPage();
                            break;
                        default:
                            $this->showSettings();
                            break;
                    }
                    break;
                case TelegramHelper::DELETE_ACCOUNT_STEP:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'confirm_delete_account_button':
                            $this->confirmDeleteAccount();
                            break;
                        case 'cancel_delete_account_button':
                            $this->cancelDeleteAccount();
                            break;
                        default:
                            $this->deleteAccount();
                            break;

                    }
                    break;
                case TelegramHelper::CHANGE_LANG_STEP:
                    switch ($this->text) {
                        case TelegramHelper::UZBEK_LANGUAGE:
                            $this->userRepository->language($this->chat_id, 'uz');
                            break;
                        case TelegramHelper::RUSSIAN_LANGUAGE:
                            $this->userRepository->language($this->chat_id, 'ru');
                            break;
                        case TelegramHelper::ENGLISH_LANGUAGE:
                            $this->userRepository->language($this->chat_id, 'en');
                            break;
                        default:
                            $this->chooseLanguage();
                            break;
                    }
                    $this->successChangeLang();
                    break;
                case TelegramHelper::APPEALS_STEP:
                    $lang = $this->userRepository->language($this->chat_id);
                    $attr = ($lang == 'uz') ? 'name' : "name_$lang";
                    $appeal = $this->appealRepository->getAppealType($attr, $this->text);
                    if ($appeal) {
                        $this->appealRepository->updateOrCreateAppeal($this->chat_id, ['theme' => $appeal->$attr]);
                        $this->askAppealTitle();
                    } elseif ($this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id)) == 'main_page_button') {
                        $this->showMainPage();
                    } else {
                        $this->showAppeals();
                    }
                    break;
                case TelegramHelper::ASK_APPEAL_TITLE:
                    if ($this->text == 'back_button') {
                        $this->back(TelegramHelper::APPEALS_STEP, 'showAppeals');
                    } elseif ($this->text == 'main_page_button') {
                        $this->showMainPage();
                    } else {
                        $this->appealRepository->updateOrCreateAppeal($this->chat_id, ['title' => $this->text]);
                        $this->askAppealDescription();
                    }
                    break;
                case TelegramHelper::ASK_APPEAL_DESCRIPTION:
                    if ($this->text == 'back_button') {
                        $this->back(TelegramHelper::APPEALS_STEP, 'askAppealTitle');
                    } elseif ($this->text == 'main_page_button') {
                        $this->showMainPage();
                    } else {
                        $chat = $this->appealRepository->updateOrCreateAppeal($this->chat_id, ['message' => $this->text, 'status' => 'ready']);
                        $this->successAcceptAppeal($chat);
                    }
            }
        }
        return true;
    }

    private function chooseLanguage($is_setting = false): void
    {
        $text = TelegramHelper::CHOOSE_LANGUAGE_TEXT;
        if ($is_setting) $this->userRepository->page($this->chat_id, TelegramHelper::CHANGE_LANG_STEP);
        $option = [[$this->telegram->buildKeyboardButton(TelegramHelper::UZBEK_LANGUAGE)], [$this->telegram->buildKeyboardButton(TelegramHelper::RUSSIAN_LANGUAGE), $this->telegram->buildKeyboardButton(TelegramHelper::ENGLISH_LANGUAGE)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    private function askPhone(): void
    {
        $text = $this->textRepository->getOrCreate('ask_phone_text', $this->userRepository->language($this->chat_id));
        $textButton = $this->textRepository->getOrCreate('ask_phone_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::PHONE_STEP);
        $option = [[$this->telegram->buildKeyboardButton($textButton, true)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    private function askCorrectPhone(): void
    {
        $text = $this->textRepository->getOrCreate('ask_correct_phone_text', $this->userRepository->language($this->chat_id));
        $textButton = $this->textRepository->getOrCreate('ask_phone_button', $this->userRepository->language($this->chat_id));
        $option = [[$this->telegram->buildKeyboardButton($textButton, true)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function showMainPage(): void
    {
        $text = $this->textRepository->getOrCreate('main_page_text', $this->userRepository->language($this->chat_id));
        $textButton_1 = $this->textRepository->getOrCreate('consultation_button', $this->userRepository->language($this->chat_id));
        $textButton_2 = $this->textRepository->getOrCreate('help_button', $this->userRepository->language($this->chat_id));
        $textButton_3 = $this->textRepository->getOrCreate('appeals_button', $this->userRepository->language($this->chat_id));
        $textButton_4 = $this->textRepository->getOrCreate('history_of_appeals_button', $this->userRepository->language($this->chat_id));
        $textButton_5 = $this->textRepository->getOrCreate('settings_button', $this->userRepository->language($this->chat_id));
        $textButton_6 = $this->textRepository->getOrCreate('contact_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::MAIN_PAGE_STEP);
        $option = [[$this->telegram->buildKeyboardButton($textButton_1), $this->telegram->buildKeyboardButton($textButton_3), $this->telegram->buildKeyboardButton($textButton_5)], [$this->telegram->buildKeyboardButton($textButton_2), $this->telegram->buildKeyboardButton($textButton_4), $this->telegram->buildKeyboardButton($textButton_6)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function deleteAccount(): void
    {
        $text = $this->textRepository->getOrCreate('confirm_delete_account_text', $this->userRepository->language($this->chat_id));
        $textConfirm = $this->textRepository->getOrCreate('confirm_delete_account_button', $this->userRepository->language($this->chat_id));
        $textCancel = $this->textRepository->getOrCreate('cancel_delete_account_button', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::DELETE_ACCOUNT_STEP);
        $option = [[$this->telegram->buildKeyboardButton($textCancel), $this->telegram->buildKeyboardButton($textConfirm)], [$this->telegram->buildKeyboardButton($backButton)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function cancelDeleteAccount(): void
    {
        $text = $this->textRepository->getOrCreate('cancel_delete_account_text', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMainPage();
    }

    public function confirmDeleteAccount(): void
    {
        $this->userRepository->delete($this->chat_id);
        $text = $this->textRepository->getOrCreate('success_delete_account_text', $this->userRepository->language($this->chat_id));
        $textRegister = $this->textRepository->getOrCreate('register_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::START_STEP);
        $option = [[$this->telegram->buildKeyboardButton($textRegister)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function showContact(): void
    {
        $text = $this->textRepository->getOrCreate('contact_text', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html', 'disable_web_page_preview' => true]);
    }

    public function showHelp(): void
    {
        $text = $this->textRepository->getOrCreate('help_text', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html', 'disable_web_page_preview' => true]);
    }

    public function showAppeals(): void
    {
        $text = $this->textRepository->getOrCreate('choose_appeals_text', $this->userRepository->language($this->chat_id));
        $appeals = AppealType::all();
        $option = [];
        $temp = [];
        $lang = $this->userRepository->language($this->chat_id);
        foreach ($appeals as $appeal) {
            $buttonText = TelegramHelper::getValue($appeal, $lang);
            $temp[] = $this->telegram->buildKeyboardButton($buttonText);
            if (count($temp) === 3) {
                $option[] = $temp;
                $temp = [];
            }
        }

        if (!empty($temp)) {
            $option[] = $temp;
        }
        $this->userRepository->page($this->chat_id, TelegramHelper::APPEALS_STEP);
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->userRepository->language($this->chat_id));
        $option[] = [$this->telegram->buildKeyboardButton($textButtonMain)];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function askAppealTitle(): void
    {
        $text = $this->textRepository->getOrCreate('ask_appeal_title_text', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_APPEAL_TITLE);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $this->backButton(), 'parse_mode' => 'html', 'disable_web_page_preview' => true]);
    }

    public function askAppealDescription(): void
    {
        $text = $this->textRepository->getOrCreate('ask_appeal_description_text', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_APPEAL_DESCRIPTION);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $this->backButton(), 'parse_mode' => 'html', 'disable_web_page_preview' => true]);
    }

    public function successAcceptAppeal($chat): void
    {
        $text = $this->textRepository->successAcceptText($this->userRepository->language($this->chat_id), $chat->id, $chat->updated_at);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMainPage();
    }

    public function showSettings(): void
    {
        $text = $this->textRepository->getOrCreate('main_page_text', $this->userRepository->language($this->chat_id));
        $textButtonChangeLang = $this->textRepository->getOrCreate('change_language_button', $this->userRepository->language($this->chat_id));
        $textButtonDelete = $this->textRepository->getOrCreate('delete_account_button', $this->userRepository->language($this->chat_id));
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::SETTINGS_STEP);
        $option = [[$this->telegram->buildKeyboardButton($textButtonChangeLang), $this->telegram->buildKeyboardButton($textButtonDelete)], [$this->telegram->buildKeyboardButton($textButtonMain)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function successChangeLang(): void
    {
        $text = $this->textRepository->getOrCreate('success_change_language', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMainPage();
    }

    public function alreadyRegistered(): void
    {
        $text = $this->textRepository->getOrCreate('already_registered_text', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMainPage();
    }

    public function technicalWork(): void
    {
        $text = $this->textRepository->getOrCreate('technical_work', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMainPage();
    }

    private function back($step, $function): void
    {
        $this->userRepository->page($this->chat_id, $step);
        $this->$function();
    }

    private function backButton(): bool|string
    {
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->userRepository->language($this->chat_id));
        $option = [[$this->telegram->buildKeyboardButton($backButton), $this->telegram->buildKeyboardButton($textButtonMain)]];
        return $this->telegram->buildKeyBoard($option, false, true);
    }

    public function handleRegistration(): void
    {
        $user = $this->userRepository->checkOrCreate($this->chat_id);
        if ($user['exists']) {
            $this->alreadyRegistered();
        } else {
            $this->chooseLanguage();
        }
    }
}
