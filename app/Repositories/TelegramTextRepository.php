<?php

namespace App\Repositories;

use App\Models\TelegramText;

class TelegramTextRepository
{
    private TelegramText $model;
    public function __construct(TelegramText $model)
    {
        $this->model = $model;
    }

    public function getOrCreate(string $keyword, string $language): string
    {
        $record = $this->model->firstOrCreate(
            ['keyword' => $keyword],
            ['keyword' => $keyword, 'ru' => $keyword, 'en' => $keyword, 'uz' => $keyword]
        );

        return str_replace('\n', "\n", $record->$language);
    }

    public function getKeyword($text, $language) {
        $keyword = $this->model->where($language, $text)->first();
        if ($keyword) return $keyword->keyword;
        return false;
    }
}
