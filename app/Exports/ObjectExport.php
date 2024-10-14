<?php

namespace App\Exports;

use App\Models\Objects;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
class ObjectExport implements FromCollection , WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $objectId;
    protected  $branc_id;

    public function __construct($objectId , $branch_id)
    {
        $this->objectId = $objectId; // Store the ID of the object you want to export
        $this->branc_id = $branch_id;
    }
    public function collection()
    {
        $object = Objects::findOrFail($this->objectId);
        $branch = $object->branches()->findOrFail($this->branc_id);

        // Prepare the initial data including headings
        $data = [
            ['Название компании', $object->name], // Company name
            ['Адрес', $branch->address], // Address
            ['Цель осмотра', ''], // Purpose of inspection
            ['Общие сведения об осмотре:', ''], // General information about the inspection
            ['Дата осмотра', ''], // Date of inspection
            [''], // Empty row for spacing
            ['№' , 'Наименование', 'кол-во', 'Фото', 'Описание', 'ед.изм', 'цена', 'сумма', 'итого'], // Headings
        ];

        // Add tasks associated with the branch
        $tasks = $branch->tasks; // Fetch the tasks related to the branch

        // Append tasks to the data array
        foreach ($tasks as $key=>$task) {
            $data[] = [
               $key+1,
                $task->name, // Task name
                $task->quantity, // Task quantity
                '', // Placeholder for 'Фото'
                $task->description, // Task description
                '', // Placeholder for 'ед.изм'
                $task->price_for_work, // Price for work
                '', // Placeholder for 'сумма'
                '', // Placeholder for 'итого'
            ];
        }

        return collect($data);
    }


    public function headings(): array
    {
        return [

        ];
    }
    public function styles($sheet)
    {
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(30); // Adjust width for 'Название компании'
        $sheet->getColumnDimension('B')->setWidth(20); // Adjust width for 'Наименование'

        return [
            // Apply styles to the heading row (1)
            1 => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFFF00',
                    ],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
            // Apply styles to all other rows (2 and below)
            'A2:B' => [ // Adjust range based on your columns
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
        foreach ($sheet->getRowIterator() as $row) {
            $sheet->getRowDimension($row->getRowIndex())->setRowHeight(20); // Adjust height for all rows
        }

        // Optionally, set specific row heights for the header
        $sheet->getRowDimension(1)->setRowHeight(30);

        return $styles; // Return the styles array
    }

}
