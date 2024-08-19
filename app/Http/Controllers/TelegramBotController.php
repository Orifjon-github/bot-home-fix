<?php

namespace App\Http\Controllers;

use App\Services\SendMeTelegramService;

class TelegramBotController extends Controller
{
    private SendMeTelegramService $telegram_service;

    public function __construct(SendMeTelegramService $telegram_service)
    {
        $this->telegram_service = $telegram_service;
    }
    public function start(): bool
    {
        return $this->telegram_service->start();
    }
}
