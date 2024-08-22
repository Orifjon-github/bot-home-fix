<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;

class TelegramBotController extends Controller
{
    private TelegramService $telegram_service;

    public function __construct(TelegramService $telegram_service)
    {
        $this->telegram_service = $telegram_service;
    }
    public function start(): bool
    {
        return $this->telegram_service->start();
    }
}
