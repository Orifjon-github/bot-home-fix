<?php

namespace App\Services;


use App\Helpers\TelegramHelper;
use App\Models\Branch;
use App\Models\Material;
use App\Models\Objects;
use App\Models\QuantityType;
use App\Models\Task;
use App\Models\User;
use App\Repositories\ObjectRepository;
use App\Repositories\TelegramTextRepository;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use function PHPUnit\Framework\isFalse;

class TelegramService
{
    private string $chat_id;
    private string|null $text;
    private Telegram $telegram;
    private UserRepository $userRepository;
    private TelegramTextRepository $textRepository;
    private ObjectRepository $objectRepository;

    public function __construct(
        Telegram               $telegram,
        UserRepository         $userRepository,
        TelegramTextRepository $textRepository,
        ObjectRepository       $objectRepository,
    )
    {
        $this->telegram = $telegram;
        $this->chat_id = $telegram->ChatID();
        $this->text = $telegram->Text();
        $this->userRepository = $userRepository;
        $this->textRepository = $textRepository;
        $this->objectRepository = $objectRepository;
    }

    public function start(): bool
    {
        if ($this->text === '/start') {
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
                case TelegramHelper::SETTINGS_STEP:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'change_language_button':
                            $this->chooseLanguage(true);
                            break;
                        case 'main_page_button':
                            $this->showMainPage();
                            break;
                        default:
                            $this->showSettings();
                            break;
                    }
                    break;
                case TelegramHelper::MAIN_PAGE_STEP:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'settings_button':
                            $this->showSettings();
                            break;
                        case 'add_object_button':
                            $this->askObjectName();
                            break;
                        case 'all_objects_button':
                            $this->showObjects();
                            break;
                        case 'my_works_button':
                            $this->technicalWork();
                            break;
                        default:
                            $this->showMainPage();
                            break;
                    }
                    break;
                case TelegramHelper::ALL_OBJECTS:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    if ($keyword === 'main_page_button') {
                        $this->showMainPage();
                    } else {
                        $object = Objects::where('name', $this->text)->first();
                        if (!$object) $this->showObjects();
                        $this->userRepository->object($this->chat_id, $object->id);
                        $this->showBranches();
                    }
                    break;
                case TelegramHelper::ALL_BRANCHES:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'main_page_button':
                            $this->showMainPage();
                            break;
                        case 'add_branch_button':
                            $this->askBranchName();
                            break;
                        default:
                            $branch = Branch::where('name', $this->text)->first();
                            if (!$branch) {
                                $this->showBranches();
                            } else {
                                $this->userRepository->branch($this->chat_id, $branch->id);
                                if ($this->userRepository->role($this->chat_id) == 'manager') {
                                    $this->generateExcel($branch->id);
                                } else {
                                    $this->showTasks();
                                }
                            }
                            break;
                    }
                    break;
                case TelegramHelper::ALL_TASKS:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'main_page_button':
                            $this->showMainPage();
                            break;
                        case 'add_task_button':
                            $this->askTaskName();
                            break;
                        default:
                            $task = Task::where('name', $this->text)->first();
                            if (!$task) $this->showTasks();
                            $this->userRepository->task($this->chat_id, $task->id);
                            if ($this->userRepository->role($this->chat_id) == 'manager') {
                                $this->technicalWork();
                            } else {
                                $this->showMaterials(); // show materials
                            }
                            break;
                    }
                    break;
                case TelegramHelper::ALL_MATERIALS:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'main_page_button':
                            $this->showMainPage();
                            break;
                        case 'add_material_button':
                            $this->askMaterialName();
                            break;
                        default:
                            $material = Material::where('name', $this->text)->first();
                            if (!$material) $this->showMaterials();
                            $this->userRepository->material($this->chat_id, $material->id);
                            $this->showMaterialInfo();
                            break;
                    }
                    break;
                case TelegramHelper::ASK_OBJECT_NAME:
                    $object = $this->objectRepository->createObject($this->chat_id, $this->text);
                    $this->userRepository->object($this->chat_id, $object->id);
                    $this->askBranchName();
                    break;
                case TelegramHelper::ASK_BRANCH_NAME:
                    $branch = $this->objectRepository->createBranch($this->text, $this->userRepository->object($this->chat_id));
                    $this->userRepository->branch($this->chat_id, $branch->id);
                    $this->askBranchAddress();
                    break;
                case TelegramHelper::ASK_BRANCH_ADDRESS:
                    $this->objectRepository->updateBranch($this->text, $this->userRepository->branch($this->chat_id));
                    $this->confirmObject();
                    break;
                case TelegramHelper::ASK_TASK_NAME:
                    $task = $this->objectRepository->createTask($this->text, $this->userRepository->branch($this->chat_id));
                    $this->userRepository->task($this->chat_id, $task->id);
                    $this->askTaskQuantity();
                    break;
                case TelegramHelper::ASK_TASK_QUANTITY:
                    $this->objectRepository->updateTask(['quantity' => $this->text], $this->userRepository->task($this->chat_id));
                    $this->askTaskDescription();
                    break;
                case TelegramHelper::ASK_TASK_DESCRIPTION:
                    $this->objectRepository->updateTask(['description' => $this->text], $this->userRepository->task($this->chat_id));
                    $this->askTaskPriceForWork();
                    break;
                case TelegramHelper::ASK_TASK_PRICE_FOR_WORK:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    if ($keyword != 'next_button') {
                        $this->objectRepository->updateTask(['price_for_work' => $this->text], $this->userRepository->task($this->chat_id));
                    }
                    $this->askTaskImage();
                    break;
                case TelegramHelper::ASK_TASK_IMAGE:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    if ($keyword == 'ready_task_button') {
                        $this->confirmTask();
                    } else{
                        $photoArray = $this->telegram->getUpdateType();
                        if ($photoArray && is_array($photoArray)) {
                            $photo = end($photoArray);
                            $fileId = $photo['file_id'];
                            $file = $this->telegram->getFile($fileId);
                            $filePath = $file['result']['file_path'] ?? null;
                            if ($filePath) {
                                $this->saveImage($filePath, $fileId);
                            }
                        }
                        $this->askTaskImage(true);
                    }
                    break;
                case TelegramHelper::ASK_MATERIAL_NAME:
                    $material = $this->objectRepository->createMaterial($this->text, $this->userRepository->task($this->chat_id));
                    $this->userRepository->material($this->chat_id, $material->id);
                    $this->askMaterialQuantityType();
                    break;
                case TelegramHelper::ASK_MATERIAL_QUANTITY_TYPE:
                    $this->objectRepository->updateMaterial(['quantity_type' => $this->text], $this->userRepository->material($this->chat_id));
                    $this->askMaterialQuantity();
                    break;
                case TelegramHelper::ASK_MATERIAL_QUANTITY:
                    $this->objectRepository->updateMaterial(['quantity' => $this->text], $this->userRepository->material($this->chat_id));
                    $this->askMaterialImage();
                    break;
                case TelegramHelper::ASK_MATERIAL_IMAGE:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    if ($keyword == 'ready_material_button' || $keyword == 'next_button') {
                        $this->confirmMaterial();
                    } else{
                        $photoArray = $this->telegram->getUpdateType();
                        if ($photoArray && is_array($photoArray)) {
                            $photo = end($photoArray);
                            $fileId = $photo['file_id'];
                            $file = $this->telegram->getFile($fileId);
                            $filePath = $file['result']['file_path'] ?? null;
                            if ($filePath) {
                                $this->saveImage($filePath, $fileId, 'material');
                            }
                        }
                        $this->askMaterialImage(true);
                    }
                    break;
                case TelegramHelper::ADD_MATERIAL_PRICE:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    if ($keyword == 'add_material_price_button') {
                        $this->askMaterialPriceForQuantityType();
                    } else {
                        $this->showMaterials();
                    }
                    break;
                case TelegramHelper::ASK_MATERIAL_PRICE_FOR_TYPE:
                    $this->objectRepository->updateMaterial(['price_for_type' => $this->text], $this->userRepository->material($this->chat_id));
                    $this->showMaterials();
                    break;
                case TelegramHelper::CONFIRM_OBJECT:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'confirm_object_button':
                            $this->confirmObjectButton();
                            break;
                        case 'cancel_object_button':
                            $this->cancelObjectButton();
                            break;
                        default:
                            $this->confirmObject();
                            break;
                    }
                    break;
                case TelegramHelper::CONFIRM_TASK:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'confirm_task_button':
                            $this->confirmTaskButton();
                            break;
                        case 'cancel_task_button':
                            $this->cancelTaskButton();
                            break;
                        default:
                            $this->confirmTask();
                            break;
                    }
                    break;
                case TelegramHelper::ASK_AFTER_CONFIRM_TASK:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'tasks_page_button':
                            $this->showTasks();
                            break;
                        case 'add_material_button':
                            $this->askMaterialName();
                            break;
                        default:
                            $this->confirmTaskButton();
                            break;
                    }
                    break;
                case TelegramHelper::CONFIRM_MATERIAL:
                    $keyword = $this->textRepository->getKeyword($this->text, $this->userRepository->language($this->chat_id));
                    switch ($keyword) {
                        case 'confirm_material_button':
                            $this->confirmMaterialButton();
                            break;
                        case 'cancel_material_button':
                            $this->cancelMaterialButton();
                            break;
                        default:
                            $this->confirmMaterial();
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
        $role = $this->userRepository->role($this->chat_id);
        $text = $this->textRepository->getOrCreate('main_page_text', $this->userRepository->language($this->chat_id));
        $textButton_3 = $this->textRepository->getOrCreate('my_works_button', $this->userRepository->language($this->chat_id));
        $textButton_2 = $this->textRepository->getOrCreate('all_objects_button', $this->userRepository->language($this->chat_id));
        switch ($role) {
            case 'manager':
                $textButton_1 = $this->textRepository->getOrCreate('add_object_button', $this->userRepository->language($this->chat_id));
                $option = [[$this->telegram->buildKeyboardButton($textButton_1)], [$this->telegram->buildKeyboardButton($textButton_2)], [$this->telegram->buildKeyboardButton($textButton_3)]];
                break;
            case 'employee':
            case 'warehouse':
                $option = [[$this->telegram->buildKeyboardButton($textButton_2)], [$this->telegram->buildKeyboardButton($textButton_3)]];
                break;
            default:
                $textWait = $this->textRepository->getOrCreate('wait_for_role_text', $this->userRepository->language($this->chat_id));
                $textButton_6 = $this->textRepository->getOrCreate('check_role_button', $this->userRepository->language($this->chat_id));
                $option = [[$this->telegram->buildKeyboardButton($textButton_6)]];
                $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $textWait, 'parse_mode' => 'html']);
        }

        $textButton_5 = $this->textRepository->getOrCreate('settings_button', $this->userRepository->language($this->chat_id));
        $option[] = [$this->telegram->buildKeyboardButton($textButton_5)];
        $this->userRepository->page($this->chat_id, TelegramHelper::MAIN_PAGE_STEP);

        $keyboard = $this->telegram->buildKeyBoard($option, true, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function askObjectName(): void
    {
        $text = $this->textRepository->getOrCreate('ask_object_name_text', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_OBJECT_NAME);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }

    public function askBranchName(): void
    {
        $text = $this->textRepository->getOrCreate('ask_branch_name_text', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_BRANCH_NAME);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }

    public function askTaskName(): void
    {
        $text = $this->textRepository->getOrCreate('ask_task_name_text', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_TASK_NAME);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }

    public function askTaskQuantity(): void
    {
        $text = $this->textRepository->getOrCreate('ask_task_quantity_text', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_TASK_QUANTITY);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }

    public function askTaskDescription(): void
    {
        $text = $this->textRepository->getOrCreate('ask_task_description_text', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_TASK_DESCRIPTION);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }
    public function askTaskPriceForWork(): void
    {
        $text = $this->textRepository->getOrCreate('ask_task_price_for_work_text', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $nextButton = $this->textRepository->getOrCreate('next_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_TASK_PRICE_FOR_WORK);
        $option = [[$this->telegram->buildKeyboardButton($nextButton)]];
        $keyboard = $this->telegram->buildKeyBoard($option, true, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function askTaskImage($button=false): void
    {
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_TASK_IMAGE);
        if ($button) {
            $text = $this->textRepository->getOrCreate('ask_again_task_photo_or_click_ready', $this->userRepository->language($this->chat_id));
            $readyTask = $this->textRepository->getOrCreate('ready_task_button', $this->userRepository->language($this->chat_id));
            $option = [[$this->telegram->buildKeyboardButton($readyTask)]];
            $keyboard = $this->telegram->buildKeyBoard($option, false, true);
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text,'reply_markup' => $keyboard, 'parse_mode' => 'html']);
        } else {
            $text = $this->textRepository->getOrCreate('ask_task_image_text', $this->userRepository->language($this->chat_id));
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        }
    }

    public function askMaterialName(): void
    {
        $text = $this->textRepository->getOrCreate('ask_material_name_text', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_MATERIAL_NAME);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }

    public function askMaterialQuantityType(): void
    {
        $text = $this->textRepository->getOrCreate('ask_material_quantity_type_text', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $types = QuantityType::all();
        $option = [];
        foreach ($types as $type) {
            $lang = $this->userRepository->language($this->chat_id);
            switch ($lang) {
                case 'uz':
                    $buttonText = $type->name;
                    break;
                default:
                    $name = 'name_' . $lang;
                    $buttonText = $type->$name;
            }
            $temp[] = $this->telegram->buildKeyboardButton($buttonText);
            if (count($temp) === 3) {
                $option[] = $temp;
                $temp = [];
            }
        }
        if (!empty($temp)) {
            $option[] = $temp;
        }
        $keyboard = $this->telegram->buildKeyBoard($option, true, true);
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_MATERIAL_QUANTITY_TYPE);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard ,'parse_mode' => 'html']);
    }

    public function askMaterialQuantity(): void
    {
        $text = $this->textRepository->getOrCreate('ask_material_quantity_text', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_MATERIAL_QUANTITY);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }
    public function askMaterialImage($button=false): void
    {
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_MATERIAL_IMAGE);
        if ($button) {
            $text = $this->textRepository->getOrCreate('ask_again_material_photo_or_click_ready', $this->userRepository->language($this->chat_id));
            $readyTask = $this->textRepository->getOrCreate('ready_material_button', $this->userRepository->language($this->chat_id));
            $option = [[$this->telegram->buildKeyboardButton($readyTask)]];
            $keyboard = $this->telegram->buildKeyBoard($option, false, true);
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text,'reply_markup' => $keyboard, 'parse_mode' => 'html']);
        } else {
            $text = $this->textRepository->getOrCreate('ask_material_image_text', $this->userRepository->language($this->chat_id));
            $next_button = $this->textRepository->getOrCreate('next_button', $this->userRepository->language($this->chat_id));
            $option = [[$this->telegram->buildKeyboardButton($next_button)]];
            $keyboard = $this->telegram->buildKeyBoard($option, false, true);
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'reply_markup' => $keyboard,'text' => $text, 'parse_mode' => 'html']);
        }
    }

    public function askMaterialPriceForQuantityType(): void
    {
        $text = $this->textRepository->getOrCreate('ask_material_price_for_quantity_type_text', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_MATERIAL_PRICE_FOR_TYPE);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }

    public function askMaterialPriceForWork(): void
    {
        $text = $this->textRepository->getOrCreate('ask_material_price_for_work_text', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_MATERIAL_PRICE_FOR_WORK);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }

    public function askBranchAddress(): void
    {
        $text = $this->textRepository->getOrCreate('ask_branch_address_text', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_BRANCH_ADDRESS);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
    }

    public function confirmObject(): void
    {
        $object = (new Objects)->find($this->userRepository->object($this->chat_id));
        $branch_id = $this->userRepository->branch($this->chat_id);
        $branch = Branch::find($branch_id);
        $text = "Object name: $object->name\n\nFilial name: $branch->name\nFilial address: $branch->address\n\nKiritgan malumotlaringiz Barcha xodimlarga yuboriladi!! Obyekt va Filialni tasdiqlaysizmi?";
        $textConfirm = $this->textRepository->getOrCreate('confirm_object_button', $this->userRepository->language($this->chat_id));
        $textCancel = $this->textRepository->getOrCreate('cancel_object_button', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::CONFIRM_OBJECT);
        $option = [[$this->telegram->buildKeyboardButton($textCancel), $this->telegram->buildKeyboardButton($textConfirm)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function confirmTask(): void
    {
        $task_id = $this->userRepository->task($this->chat_id);
        $task = (new Task)->find($task_id);
        $price = $task->price_for_work;
        $price_text = $price ?? "(The price for the service is fixed in advance)";
        $text = "Task name: $task->name\n\nTask Quantity: $task->quantity\n\nTask Description: $task->description\n\nTask Price For Service: $price_text";
        $textConfirm = $this->textRepository->getOrCreate('confirm_task_button', $this->userRepository->language($this->chat_id));
        $textCancel = $this->textRepository->getOrCreate('cancel_task_button', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::CONFIRM_TASK);
        $option = [[$this->telegram->buildKeyboardButton($textCancel), $this->telegram->buildKeyboardButton($textConfirm)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function confirmMaterial(): void
    {
        $material_id = $this->userRepository->material($this->chat_id);
        $material = Material::find($material_id);
        $task = (new Task)->find($material->task_id);
        $text = "Task name: $task->name\n\nMaterial: $material->name\nMaterial Quantity: $material->quantity $material->quantity_type";
        $textConfirm = $this->textRepository->getOrCreate('confirm_material_button', $this->userRepository->language($this->chat_id));
        $textCancel = $this->textRepository->getOrCreate('cancel_material_button', $this->userRepository->language($this->chat_id));
        $backButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
        $this->userRepository->page($this->chat_id, TelegramHelper::CONFIRM_MATERIAL);
        $option = [[$this->telegram->buildKeyboardButton($textCancel), $this->telegram->buildKeyboardButton($textConfirm)]];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function cancelObjectButton(): void
    {
        $object_id = $this->userRepository->object($this->chat_id);
        $object = (new Objects)->find($object_id);
        $object->delete();
        $text = $this->textRepository->getOrCreate('success_cancel_object_text', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showObjects();
    }

    public function confirmObjectButton(): void
    {
        $branch_id = $this->userRepository->branch($this->chat_id);
        $branch = Branch::find($branch_id);
        $this->sendAll($branch->id);
        $text = $this->textRepository->getOrCreate('success_confirm_object_text', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showObjects();
    }

    public function cancelTaskButton(): void
    {
        $task_id = $this->userRepository->task($this->chat_id);
        $task = (new Task)->find($task_id);
        $task->delete();
        $text = $this->textRepository->getOrCreate('success_cancel_task_text', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showTasks();
    }

    public function confirmTaskButton(): void
    {
        $text = $this->textRepository->getOrCreate('success_confirm_task_text', $this->userRepository->language($this->chat_id));
        $textConfirm = $this->textRepository->getOrCreate('tasks_page_button', $this->userRepository->language($this->chat_id));
        $textCancel = $this->textRepository->getOrCreate('add_material_button', $this->userRepository->language($this->chat_id));

        $this->userRepository->page($this->chat_id, TelegramHelper::ASK_AFTER_CONFIRM_TASK);
        $option = [[$this->telegram->buildKeyboardButton($textConfirm), $this->telegram->buildKeyboardButton($textCancel)]];
        $keyboard = $this->telegram->buildKeyBoard($option, true, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);

    }

    public function cancelMaterialButton(): void
    {
        $material_id = $this->userRepository->material($this->chat_id);
        $material = (new Task)->find($material_id);
        $material->delete();
        $text = $this->textRepository->getOrCreate('success_cancel_material_text', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMaterials();
    }

    public function confirmMaterialButton(): void
    {
        $text = $this->textRepository->getOrCreate('success_confirm_material_text', $this->userRepository->language($this->chat_id));
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        $this->showMaterials();
    }

    public function showObjects(): void
    {
        $text = $this->textRepository->getOrCreate('all_objects_text', $this->userRepository->language($this->chat_id));
        $objects = Objects::all();
        foreach ($objects as $object) {
            $buttonText = $object->name;
            $temp[] = $this->telegram->buildKeyboardButton($buttonText);
            if (count($temp) === 3) {
                $option[] = $temp;
                $temp = [];
            }
        }

        if (!empty($temp)) {
            $option[] = $temp;
        }
        $this->userRepository->page($this->chat_id, TelegramHelper::ALL_OBJECTS);
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->userRepository->language($this->chat_id));
        $option[] = [$this->telegram->buildKeyboardButton($textButtonMain)];
        $keyboard = $this->telegram->buildKeyBoard($option, true, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function showBranches(): void
    {
        $object_id = $this->userRepository->object($this->chat_id);
        $branches = Branch::where('objects_id', $object_id)->get();
        $text = $this->textRepository->getOrCreate('all_branches_text', $this->userRepository->language($this->chat_id));
        foreach ($branches as $branch) {
            $buttonText = $branch->name;
            $temp[] = $this->telegram->buildKeyboardButton($buttonText);
            if (count($temp) === 3) {
                $option[] = $temp;
                $temp = [];
            }
        }

        if (!empty($temp)) {
            $option[] = $temp;
        }
        $this->userRepository->page($this->chat_id, TelegramHelper::ALL_BRANCHES);
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->userRepository->language($this->chat_id));
        $textButtonAdd = $this->textRepository->getOrCreate('add_branch_button', $this->userRepository->language($this->chat_id));
        if ($this->userRepository->role($this->chat_id) == 'manager') {
            array_unshift($option, [$this->telegram->buildKeyboardButton($textButtonAdd)]);
        }
        $option[] = [$this->telegram->buildKeyboardButton($textButtonMain)];
        $keyboard = $this->telegram->buildKeyBoard($option, true, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
    }

    public function showTasks(): void
    {
        $branch_id = $this->userRepository->branch($this->chat_id);
        $tasks = Task::where('branch_id', $branch_id)->get();
        $text = $this->textRepository->getOrCreate('all_tasks_text', $this->userRepository->language($this->chat_id));
        $temp = [];
        $option = [];
        foreach ($tasks as $task) {
            $buttonText = $task->name;
            $temp[] = $this->telegram->buildKeyboardButton($buttonText);

            if (count($temp) === 3) {
                $option[] = $temp;
                $temp = []; // Reset temp after grouping 3 buttons
            }
        }
        if (!empty($temp)) {
            $option[] = $temp;
        }
        $textButtonAdd = $this->textRepository->getOrCreate('add_task_button', $this->userRepository->language($this->chat_id));
        if ($this->userRepository->role($this->chat_id) == 'employee') {
            array_unshift($option, [$this->telegram->buildKeyboardButton($textButtonAdd)]);
        }
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->userRepository->language($this->chat_id));
        $option[] = [$this->telegram->buildKeyboardButton($textButtonMain)];
        $keyboard = $this->telegram->buildKeyBoard($option, true, true);
        $this->userRepository->page($this->chat_id, TelegramHelper::ALL_TASKS);
        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html'
        ]);
    }

    public function showMaterials(): void
    {
        $task_id = $this->userRepository->task($this->chat_id);
        $materials = Material::where('task_id', $task_id)->get();

        $text = $this->textRepository->getOrCreate('all_materials_text', $this->userRepository->language($this->chat_id));
        foreach ($materials as $material) {
            $buttonText = $material->name;
            $temp[] = $this->telegram->buildKeyboardButton($buttonText);
            if (count($temp) === 3) {
                $option[] = $temp;
                $temp = [];
            }
        }

        $option = [];
        if (!empty($temp)) {
            $option[] = $temp;
        }
        $this->userRepository->page($this->chat_id, TelegramHelper::ALL_MATERIALS);
        $textButtonMain = $this->textRepository->getOrCreate('main_page_button', $this->userRepository->language($this->chat_id));
        $textButtonAdd = $this->textRepository->getOrCreate('add_material_button', $this->userRepository->language($this->chat_id));
        if ($this->userRepository->role($this->chat_id) == 'employee') {
            array_unshift($option, [$this->telegram->buildKeyboardButton($textButtonAdd)]);
        }
        $option[] = [$this->telegram->buildKeyboardButton($textButtonMain)];
        $keyboard = $this->telegram->buildKeyBoard($option, false, true);
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
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
    public function showMaterialInfo(): void
    {
        $material = Material::find($this->userRepository->material($this->chat_id));
        $task = $material->task;
        $price = $material->price_for_type ?? '(Narx kiritilmagan)';
        $text = "Muammo: $task->name\nMuammo haqida: $task->description\n\nZapchast: $material->name\nO'lchovi: $material->quantity_type\nMiqdori: $material->quantity\n$material->quantity_type uchun narx: $price";
        if ($this->userRepository->role($this->chat_id) == 'warehouse' && empty($material->price_for_type)) {
            $textAddPrice = $this->textRepository->getOrCreate('add_material_price_button', $this->userRepository->language($this->chat_id));
            $textBackButton = $this->textRepository->getOrCreate('back_button', $this->userRepository->language($this->chat_id));
            $option = [[$this->telegram->buildKeyboardButton($textBackButton), $this->telegram->buildKeyboardButton($textAddPrice)]];
            $this->userRepository->page($this->chat_id, TelegramHelper::ADD_MATERIAL_PRICE);
            $keyboard = $this->telegram->buildKeyBoard($option, false, true);
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'reply_markup' => $keyboard, 'parse_mode' => 'html']);
        } else {
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        }
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

    private function sendAll($branch_id): void
    {
        $users = User::where('role', 'employee')->where('status', 'active')->get();
        $branch = Branch::find($branch_id);
        $object = (new Objects)->find($branch->objects_id);
        foreach ($users as $user) {
            $prefix = "<strong>Yangi Filial Qo'shildi!!!</strong>";
            $text = "$prefix\n\nObject name: $object->name\n\nFilial name: $branch->name\nFilial address: $branch->address";
            $this->telegram->sendMessage(['chat_id' => $user->chat_id, 'text' => $text, 'parse_mode' => 'html']);
        }
    }

    private function saveImage($filePath, $file_name, $name='task'): void
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $url = "https://api.telegram.org/file/bot{$token}/{$filePath}";
        $imageContent = file_get_contents($url);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION); // fayl formatini olish
        $storagePath = "$name-images/{$file_name}.{$extension}";
        Storage::disk('public')->put($storagePath, $imageContent);
        $path = str_replace('public/', '/storage/', $storagePath);
        if ($name == 'task') {
            $task = (new Task)->find($this->userRepository->task($this->chat_id));
            $task->images()->create(['image' => $path]);
        } else {
            $material = (new Material)->find($this->userRepository->material($this->chat_id));
            $material->images()->create(['image' => $path]);
        }

    }

    /**
     * @throws ModelNotFoundException
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */


    public function generateExcel()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Bosh uslublarni o'rnatish (o'rtaga joylash va chegaralar)
        $defaultStyle = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];

        // Kompaniya nomi va adres qismi
        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('A1', 'Название компании:');
        $sheet->setCellValue('C1', 'Coffee Issimo');
        $sheet->mergeCells('A2:B2');
        $sheet->setCellValue('A2', 'Адрес:');
        $sheet->setCellValue('C2', 'Talabalar');

        // Цель осмотра qismi
        $sheet->mergeCells('A3:C3');
        $sheet->setCellValue('A3', 'Цель осмотра:');
        $sheet->mergeCells('D3:G3');
        $sheet->setCellValue('D3', 'Полный осмотр и диагностика электропроводки выдвижного зонта');

        // Общие сведения об осмотре
        $sheet->mergeCells('A4:G4');
        $sheet->setCellValue('A4', 'Общие сведения об осмотре: Осмотрели проводку. Лед лампы');

        // Jadval sarlavhalari
        $sheet->fromArray([
            ['№', 'Наименование', 'кол-во', 'Фото', 'Описание', 'время выполнения', 'расходный материал', 'ед-изм', 'кол-во', 'цена', 'сумма', 'Оплата за работу', 'Итого']
        ], null, 'A6');

        // Mahsulotlar ro'yxatini joylash
        $this->insertProductRow($sheet, 7, 1, 'Led светильник', 16, 'storage/material-images/AgACAgIAAxkBAAIIhmb4LvmyrFpCdy17bsBrbWu4RK2vAAIr4zEbRUuxSyUkgjspD0KiAQADAgADeQADNgQ.jpg', 'Заменить. (Окисление в контактах. Пластмассовые детали утеряли прочность от перепада температуры)', 'Led 24v', 'п.м', 3.2, 47040, 150528, 78400, 1404928);
        $this->insertProductRow($sheet, 8, 2, 'Led светильник', 12, 'storage/images/picture-2.jpg', 'Неисправность. Замена.', 'Led 24v', 'п.м', 2.4, 47040, 112896, 78400, 1053696);
        $this->insertProductRow($sheet, 9, 3, 'Проводка между лампами', 35, 'storage/images/picture-3.jpg', 'Окисление. Обрыв. Замена.', 'Провод 2*2,5 (Морозостойкий)', 'п.м', 40, 28560, 1142400, 78400, 3886400);
        $this->insertProductRow($sheet, 10, 4, 'Основной провод питания', 8, 'storage/material-images/AgACAgIAAxkBAAIIhmb4LvmyrFpCdy17bsBrbWu4RK2vAAIr4zEbRUuxSyUkgjspD0KiAQADAgADeQADNgQ.jpg', 'Обрыв в сети. Нужно заменить.', 'Провод 2*2,5 (Морозостойкий)', 'п.м', 10, 28560, 285600, 78400, 912800);

        // Yakuniy summa
        $sheet->mergeCells('A11:L11');
        $sheet->setCellValue('A11', 'Итого');
        $sheet->setCellValue('M11', '7 257 824');

        // Uslublar qo'shish
        $sheet->getStyle('A1:M11')->applyFromArray($defaultStyle);

        // Faylni saqlash
        $fileName = 'Coffee_Issimo_' . time() . '.xlsx';
        $filePath = storage_path('app/public/reports/' . $fileName);

        // Papka yo'q bo'lsa, yaratish
        Storage::makeDirectory('public/reports');

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        // Faylni Telegram orqali jo'natish
        $this->sendToTelegram($filePath);

        return response()->json(['message' => 'Excel file generated and sent via Telegram.']);
    }

    // Mahsulotni kiritish va rasmni joylash

    /**
     * @throws Exception
     */
    private function insertProductRow($sheet, $row, $number, $name, $quantity, $imagePath, $description, $material, $unit, $unitQty, $price, $totalPrice, $workPayment, $finalTotal): void
    {
        $sheet->setCellValue('A' . $row, $number);
        $sheet->setCellValue('B' . $row, $name);
        $sheet->setCellValue('C' . $row, $quantity);

        // Rasmni qo'shish
        if (file_exists(public_path($imagePath))) {
            $drawing = new Drawing();
            $drawing->setName($name);
            $drawing->setDescription($name);
            $drawing->setPath(public_path($imagePath)); // Rasmning yo'li
            $drawing->setHeight(80); // Rasmni balandligini sozlash
            $drawing->setCoordinates('D' . $row); // Rasmni joylash
            $drawing->setWorksheet($sheet);
        } else {
            // Rasm topilmasa, text sifatida yo'lni yozish
            $sheet->setCellValue('D' . $row, 'Rasm topilmadi: ' . $imagePath);
        }

        // Qolgan ma'lumotlar
        $sheet->setCellValue('E' . $row, $description);
        $sheet->setCellValue('F' . $row, ''); // vaqt hozir kiritilmagan
        $sheet->setCellValue('G' . $row, $material);
        $sheet->setCellValue('H' . $row, $unit);
        $sheet->setCellValue('I' . $row, $unitQty);
        $sheet->setCellValue('J' . $row, $price);
        $sheet->setCellValue('K' . $row, $totalPrice);
        $sheet->setCellValue('L' . $row, $workPayment);
        $sheet->setCellValue('M' . $row, $finalTotal);
    }

    // Telegramga fayl jo'natish
    private function sendToTelegram($filePath): void
    {
        $chatId = $this->chat_id; // Telegram chat ID
        $botToken = env('TELEGRAM_BOT_TOKEN'); // Bot token

        $url = "https://api.telegram.org/bot$botToken/sendDocument";

        // Faylni jo'natish
        $response = Http::attach('document', fopen($filePath, 'r'), basename($filePath))
            ->post($url, [
                'chat_id' => $chatId,
            ]);

        $response->json();
    }
}
