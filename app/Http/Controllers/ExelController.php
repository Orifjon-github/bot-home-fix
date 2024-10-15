<?php

namespace App\Http\Controllers;

use App\Models\Objects;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Rap2hpoutre\FastExcel\FastExcel;

class ExelController extends Controller
{
    public function index($id, Request $request)
    {
        // Fetch the object and the associated branch
        $object = Objects::findOrFail($id);
        $branch = $this->getBranch($object, $request->branch_id);

        // Generate the related systems
        $systems = $this->getTaskSystems($branch);

        // Prepare Excel data
        $sheets = $this->prepareExcelData($object, $branch, $systems);

        // Export the collection to an Excel file
        return (new FastExcel($sheets))->download('ventilation_report.xlsx');
    }

    private function getBranch($object, $branchId)
    {
        return $object->branches()->findOrFail($branchId);
    }

    private function getTaskSystems($branch)
    {
        return $branch->tasks->map(function ($task, $key) {
            return [
                '№' => $key + 1,
                'Наименование' => $task->name,
                'кол-во' => $task->quantity,
                'Фото' => $task->images->first()->image ?? 'no_image.png', // Fallback if no image exists
                'Описание' => $task->description,
                'ед.изм' => '', // Placeholder for unit of measurement
                'цена' => '', // Placeholder for price
                'сумма' => '', // Placeholder for total
                'итого' => '', // Placeholder for grand total
            ];
        })->toArray();
    }

    private function prepareExcelData($object, $branch, $systems)
    {
        return collect([
            [
                'Название компании' => $object->name,
                'Адрес' => $branch->address,
                'Цель осмотра' => 'Полная диагностика вентилляционной системы',
                'Общие сведения об осмотре' => 'Выявлены ряд недостатков в вентиляционной системе. Загрязнена вся система. Нужно провести химическую профилактику и заменить фильтры.',
                'Дата осмотра' => '03.10.2024',
                'Детали осмотра' => '',
            ],
            [],
            [
                '№' => '№',
                'Наименование' => 'Наименование',
                'кол-во' => 'кол-во',
                'Фото' => 'Фото',
                'Описание' => 'Описание',
                'ед.изм' => 'ед.изм',
                'цена' => 'цена',
                'сумма' => 'сумма',
                'итого' => 'итого',
            ],
        ])->merge($systems);
    }
}
