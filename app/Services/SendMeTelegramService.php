<?php

namespace App\Services;


use App\Helpers\TelegramHelper;

class SendMeTelegramService
{
    private string $chat_id;
    private string|null $text;
    private SendMeTelegram $telegram;

    public function __construct(
        SendMeTelegram             $telegram,
    )
    {
        $this->telegram = $telegram;
        $this->chat_id = $telegram->ChatID();
        $this->text = $telegram->Text();
    }

    public function start(): bool
    {
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => 'Welcome']);
        return true;
        if ($this->text == '/start') {
            $this->repository->checkOrCreate($this->chat_id);
            $this->chooseLanguage();
        } else {
            switch ($this->repository->page($this->chat_id)) {
                case TelegramHelper::START_STEP:
                    switch ($this->text) {
                        case TelegramHelper::UZBEK_LANGUAGE:
                            $this->repository->language($this->chat_id, 'uz');
                            $this->askPhone();
                            break;
                        case TelegramHelper::RUSSIAN_LANGUAGE:
                            $this->repository->language($this->chat_id, 'ru');
                            $this->askPhone();
                            break;
                        case TelegramHelper::ENGLISH_LANGUAGE:
                            $this->repository->language($this->chat_id, 'en');
                            $this->askPhone();
                            break;
                        default:
                            $this->chooseButton();
                            break;
                    }
                    break;
                case TelegramHelper::PHONE_STEP:
                    if ($phone = TelegramHelper::checkPhone($this->text)) {
                        $this->repository->phone($this->chat_id, $phone);
                        $this->showMainPage();
                    } else {
                        $this->askCorrectPhone();
                    }
                    break;
                case TelegramHelper::MAIN_PAGE_STEP:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->repository->language($this->chat_id));
                    switch ($keyword) {
                        case 'add_card_button':
                            $this->askCard();
                            break;
                        case 'settings_button':
                            $this->showSettings();
                            break;
                        case 'balance_button':
                            $this->showBalance();
                            break;
                        default:
                            $this->showMainPage();
                            break;

                    }
                    break;
                case TelegramHelper::ASK_CARD_PAGE:
                    $text = $this->textRepository->getOrCreate('main_page_button', $this->repository->language($this->chat_id));
                    if ($this->text == $text) {
                        $this->showMainPage();
                    } else {
                        $card = $this->cardRepository->checkAndCreate($this->text, $this->chat_id);
                        if ($card['success']) {
                            $this->askExpiry();
                        } else {
                            $this->askCorrectCard($card['message']);
                        }
                    }
                    break;
                case TelegramHelper::ASK_EXPIRY:
                    if ($this->cardRepository->checkExpiry($this->chat_id, $this->text)) {
                        $this->askSmsType();
                    } else {
                        $this->askCorrectExpiry();
                    }
                    break;
                case TelegramHelper::ASK_SMS_TYPE:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->repository->language($this->chat_id));
                    switch ($keyword) {
                        case 'main_page_button':
                            $this->showMainPage();
                            break;
                        case 'sms_sms_type':
                            $this->successAdd('sms');
                            break;
                        case 'telegram_sms_type':
                            $this->successAdd('telegram');
                            break;
                        default:
                            $this->chooseButton();
                            break;
                    }
                    break;
                case TelegramHelper::SETTINGS_STEP:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->repository->language($this->chat_id));
                    switch ($keyword) {
                        case 'change_language_button':
                            $this->chooseLanguage(true);
                            break;
                        case 'main_page_button':
                            $this->showMainPage();
                            break;
                        default:
                            $this->chooseButton();
                            break;
                    }
                    break;
                case TelegramHelper::CHANGE_LANG_STEP:
                    switch ($this->text) {
                        case TelegramHelper::UZBEK_LANGUAGE:
                            $this->repository->language($this->chat_id, 'uz');
                            break;
                        case TelegramHelper::RUSSIAN_LANGUAGE:
                            $this->repository->language($this->chat_id, 'ru');
                            break;
                        case TelegramHelper::ENGLISH_LANGUAGE:
                            $this->repository->language($this->chat_id, 'en');
                            break;
                        default:
                            $this->chooseButton();
                            break;
                    }
                    $this->successChangeLang();
                    break;
            }
        }
        return true;
    }

    private function chooseLanguage($is_setting = false): void
    {
        $text = $this->textRepository->getOrCreate('choose_language', $this->repository->language($this->chat_id));
        $is_setting ? $this->repository->page($this->chat_id, TelegramHelper::CHANGE_LANG_STEP) : $this->repository->page($this->chat_id, TelegramHelper::START_STEP);
        $option = [[$this->telegram->buildKeyboardButton(TelegramHelper::UZBEK_LANGUAGE)], [$this->telegram->buildKeyboardButton(TelegramHelper::RUSSIAN_LANGUAGE), $this->telegram->buildKeyboardButton(TelegramHelper::ENGLISH_LANGUAGE)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    private function chooseButton(): void
    {
        $text = $this->textRepository->getOrCreate('choose_button', $this->repository->language($this->chat_id));
        $option = [[$this->telegram->buildKeyboardButton(TelegramHelper::UZBEK_LANGUAGE)], [$this->telegram->buildKeyboardButton(TelegramHelper::RUSSIAN_LANGUAGE), $this->telegram->buildKeyboardButton(TelegramHelper::ENGLISH_LANGUAGE)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    private function askPhone(): void
    {
        $text = $this->textRepository->getOrCreate('ask_phone', $this->repository->language($this->chat_id));
        $textButton = $this->textRepository->getOrCreate('ask_phone_button', $this->repository->language($this->chat_id));
        $this->repository->page($this->chat_id, TelegramHelper::PHONE_STEP);
        $option = [[$this->telegram->buildKeyboardButton($textButton, true)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    private function askCorrectPhone(): void
    {
        $text = $this->textRepository->getOrCreate('ask_correct_phone', $this->repository->language($this->chat_id));
        $textButton = $this->textRepository->getOrCreate('ask_phone_button', $this->repository->language($this->chat_id));
        $option = [[$this->telegram->buildKeyboardButton($textButton, true)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function showMainPage(): void
    {
        $text = $this->textRepository->getOrCreate('main_page', $this->repository->language($this->chat_id));
        $textButton_1 = $this->textRepository->getOrCreate('balance_button', $this->repository->language($this->chat_id));
        $textButton_2 = $this->textRepository->getOrCreate('add_card_button', $this->repository->language($this->chat_id));
        $textButton_3 = $this->textRepository->getOrCreate('settings_button', $this->repository->language($this->chat_id));
        $this->repository->page($this->chat_id, TelegramHelper::MAIN_PAGE_STEP);
        $option = [[$this->telegram->buildKeyboardButton($textButton_1)], [$this->telegram->buildKeyboardButton($textButton_2)], [$this->telegram->buildKeyboardButton($textButton_3)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function askCard(): void
    {
        $text = $this->textRepository->getOrCreate('ask_card', $this->repository->language($this->chat_id));
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->repository->language($this->chat_id));
        $this->repository->page($this->chat_id, TelegramHelper::ASK_CARD_PAGE);
        $option = [[$this->telegram->buildKeyboardButton($textButtonMain)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function askCorrectCard($message): void
    {
        $text = $this->textRepository->getOrCreate($message, $this->repository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }

    public function askExpiry(): void
    {
        $text = $this->textRepository->getOrCreate('ask_expiry', $this->repository->language($this->chat_id));
        $this->repository->page($this->chat_id, TelegramHelper::ASK_EXPIRY);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }

    public function askCorrectExpiry(): void
    {
        $text = $this->textRepository->getOrCreate('ask_correct_expiry', $this->repository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }

    public function askSmsType(): void
    {
        $text = $this->textRepository->getOrCreate('ask_sms_type_message', $this->repository->language($this->chat_id));
        $textButton = $this->textRepository->getOrCreate('telegram_sms_type', $this->repository->language($this->chat_id));
        $textButton_2 = $this->textRepository->getOrCreate('sms_sms_type', $this->repository->language($this->chat_id));
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->repository->language($this->chat_id));
        $this->repository->page($this->chat_id, TelegramHelper::ASK_SMS_TYPE);
        $option = [[$this->telegram->buildKeyboardButton($textButton), $this->telegram->buildKeyboardButton($textButton_2)], [$this->telegram->buildKeyboardButton($textButtonMain)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function successAdd($type): void
    {
        $this->cardRepository->setTypeAndActivate($this->chat_id, $type);
        $text = $this->textRepository->getOrCreate('success_add_card', $this->repository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMainPage();
    }

    public function showBalance(): void
    {
        $text = $this->cardRepository->getAllCardsBalance($this->chat_id);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }

    public function showSettings(): void
    {
        $text = $this->textRepository->getOrCreate('main_page', $this->repository->language($this->chat_id));
        $textButton = $this->textRepository->getOrCreate('change_language_button', $this->repository->language($this->chat_id));
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->repository->language($this->chat_id));
        $this->repository->page($this->chat_id, TelegramHelper::SETTINGS_STEP);
        $option = [[$this->telegram->buildKeyboardButton($textButton)], [$this->telegram->buildKeyboardButton($textButtonMain)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function successChangeLang(): void
    {
        $text = $this->textRepository->getOrCreate('success_change_language', $this->repository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMainPage();
    }

    public function technicalWork(): void
    {
        $text = $this->textRepository->getOrCreate('technical_work', $this->repository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMainPage();
    }
}
