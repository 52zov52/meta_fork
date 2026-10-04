<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @group Reporting (Admin)
 */
class DirectionDistributionController extends Controller
{
    public function index()
    {
        $members = Member::join('users', 'members.user_id', '=', 'users.id')
            ->join('planets', 'members.planet_id', '=', 'planets.id')
            ->select(
                'planets.name as planet_name',
                DB::raw('COUNT(members.planet_id) as count_item')
            )
            ->groupBy('planet_name')
            ->orderBy('count_item', 'DESC')
            ->get();

        $filename = time().'.xlsx';

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $offset = 1;
        $increment = 1;

        $sheet->setCellValue('A'.$offset, '№ пп');
        $sheet->setCellValue('B'.$offset, 'Наименование учреждения');
        $sheet->setCellValue('C'.$offset, 'Кол-во пользователей');

        $sheet->getStyle('A'.$offset.':C'.$offset)->getFont()->setBold(true);
        $sheet->getStyle('A'.$offset.':C'.$offset)
            ->applyFromArray(['alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ]]);

        $offset++;

        for ($i = 0; $i < count($members); $i++) {
            $sheet->setCellValue('A'.$offset, $increment);
            $sheet->setCellValue('B'.$offset, $members[$i]->planet_name);
            $sheet->setCellValue('C'.$offset, $members[$i]->count_item);

            $offset++;
            $increment++;
        }

        $sheet->getStyle('A1:C'.$offset)->getAlignment()->setWrapText(true);

        // Стиль для рамки
        $sheet->getStyle('A1:C'.($offset - 1))
            ->getBorders()
            ->getAllBorders()
            ->applyFromArray([
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '808080'],
            ]);

        $sheet->getStyle('A1:A'.($offset - 1))
            ->applyFromArray(['alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ]]);

        // Устанавливаем ширину полей
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);

        $writer = new Xlsx($spreadsheet);

        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return Response::download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
