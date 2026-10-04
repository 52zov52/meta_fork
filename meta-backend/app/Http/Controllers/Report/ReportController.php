<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\ScoreCalculationService;
use App\Services\ScoreService;
use App\Services\SemesterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @group Reporting (Admin)
 */
class ReportController extends Controller
{
    /**
     * Скачать отчёт по пользователям (Excel)
     *
     * Формирует и отдаёт .xlsx-отчёт по участникам за текущий семестр.
     * Включает персональные данные, общие баллы (ограниченные/все) и баллы по категориям.
     * Доступ: админский отчёт.
     *
     * @response 200 scenario="Success" binary
     */
    public function generateUsersReport()
    {
        $semester = SemesterService::getCurrentSemester();

        $categories = DB::table('activity_categories')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->toArray();

        $categoryIds = array_map(intval(...), array_keys($categories));
        [$totalCappedSql, $totalCappedBindings] = ScoreService::totalCappedScoreSelectSql($categoryIds);
        $earnedScore = ScoreCalculationService::earnedScoreExpression();

        $scoreSubquery = DB::table('activity_user as au')
            ->join('activities as a', 'au.activity_id', '=', 'a.id')
            ->select('au.user_id')
            ->selectRaw($totalCappedSql.' as total_capped_score', $totalCappedBindings)
            ->selectRaw("COALESCE(SUM({$earnedScore}), 0) as total_real_score")
            ->where('au.is_confirmed', 1)
            ->whereBetween('au.updated_at', [$semester['start'], $semester['end']]);

        // Add per-category columns: cat_1_capped, cat_1_real, cat_2_capped, cat_2_real, etc.
        foreach ($categories as $catId => $catName) {
            $scoreSubquery->selectRaw(
                ScoreService::categoryCappedSumExpression()." as cat_{$catId}_capped",
                [(int) $catId]
            );
            $scoreSubquery->selectRaw(
                "COALESCE(SUM(CASE WHEN a.category_id = ? THEN {$earnedScore} END), 0) as cat_{$catId}_real",
                [$catId]
            );
        }

        $scoreSubquery->groupBy('user_id');

        // 3. Main query - select all fields INCLUDING the dynamic category columns
        $baseSelect = [
            'members.id',
            'members.last_name',
            'members.first_name',
            'members.middle_name',
            DB::raw("CASE WHEN members.gender = 0 THEN 'Муж.' ELSE 'Жен.' END as gender"),
            'members.birthday',
            'regions.name as region_name',
            'planets.name as planet_name',
            DB::raw('
                CASE
                    WHEN members.institution_id = 4 THEN members.institution
                    ELSE institutions.name
                END as institution_full
            '),
            'members.institution_id',
            DB::raw("CASE WHEN members.course IS NULL THEN 'Не указан' ELSE members.course END as course"),
            DB::raw("CASE WHEN members.student_group IS NULL THEN 'Не указана' ELSE members.student_group END as student_group"),
            'users.email',
            'users.role',
            DB::raw('COALESCE(user_scores.total_capped_score, 0) as total_capped_score'),
            DB::raw('COALESCE(user_scores.total_real_score, 0) as total_real_score'),
        ];

        // Dynamic category columns to SELECT
        foreach ($categories as $catId => $catName) {
            $baseSelect[] = DB::raw("COALESCE(user_scores.cat_{$catId}_capped, 0) as cat_{$catId}_capped");
            $baseSelect[] = DB::raw("COALESCE(user_scores.cat_{$catId}_real, 0) as cat_{$catId}_real");
        }

        $members = Member::join('users', 'members.user_id', '=', 'users.id')
            ->join('regions', 'members.region_id', '=', 'regions.id')
            ->join('planets', 'members.planet_id', '=', 'planets.id')
            ->leftJoin('institutions', 'members.institution_id', '=', 'institutions.id')
            ->leftJoinSub($scoreSubquery, 'user_scores', function ($join) {
                $join->on('users.id', '=', 'user_scores.user_id');
            })
            ->select($baseSelect)
            ->get();

        // Excel setup
        $filename = time().'.xlsx';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            '№ пп', 'ФИО', 'Пол', 'Дата рождения', 'Регион', 'Направление',
            'Учреждение', 'Класс или курс', 'Группа',
            'Эл.адрес', 'Роль', 'Баллы (огр.)', 'Баллы (все)',
        ];

        // Add per-category headers
        foreach ($categories as $catName) {
            $headers[] = "$catName (огр.)";
            $headers[] = "$catName (все)";
        }

        // Write headers
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column.'1', $header);
            $column++;
        }

        // Style headers
        $lastHeaderCol = $column;
        $sheet->getStyle('A1:'.$lastHeaderCol.'1')->getFont()->setBold(true);
        $sheet->getStyle('A1:'.$lastHeaderCol.'1')->getAlignment()->setHorizontal('center');

        // Write data rows
        $row = 2;
        foreach ($members as $index => $member) {
            $sheet->setCellValue('A'.$row, $index + 1);
            $sheet->setCellValue('B'.$row, trim($member->last_name.' '.$member->first_name.' '.$member->middle_name));
            $sheet->setCellValue('C'.$row, $member->gender);
            $sheet->setCellValue('D'.$row, $member->birthday);
            $sheet->setCellValue('E'.$row, $member->region_name);
            $sheet->setCellValue('F'.$row, $member->planet_name);
            $sheet->setCellValue('G'.$row, $member->institution_full);
            $sheet->setCellValue('H'.$row, $member->course);
            $sheet->setCellValue('I'.$row, $member->student_group);
            $sheet->setCellValue('J'.$row, $member->email);
            $sheet->setCellValue('K'.$row, $member->role);
            $sheet->setCellValue('L'.$row, $member->total_capped_score);
            $sheet->setCellValue('M'.$row, $member->total_real_score);

            // Write per-category scores (starting from column N)
            $col = 'N';
            foreach ($categories as $catId => $catName) {
                $sheet->setCellValue($col.$row, $member->{"cat_{$catId}_capped"});
                $col++;
                $sheet->setCellValue($col.$row, $member->{"cat_{$catId}_real"});
                $col++;
            }

            $row++;
        }

        // Final styling
        $lastDataCol = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // Apply borders to all cells
        $sheet->getStyle('A1:'.$lastDataCol.$lastRow)->getBorders()->getAllBorders()->applyFromArray([
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color' => ['rgb' => '808080'],
        ]);

        // Center specific columns
        $centerCols = ['A', 'C', 'D', 'H', 'L', 'M'];
        foreach ($centerCols as $col) {
            $sheet->getStyle($col.'1:'.$col.$lastRow)->getAlignment()->setHorizontal('center');
        }

        // Auto-size all columns
        $lastColIndex = Coordinate::columnIndexFromString($lastDataCol);
        for ($i = 1; $i <= $lastColIndex; $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Download
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return Response::download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
