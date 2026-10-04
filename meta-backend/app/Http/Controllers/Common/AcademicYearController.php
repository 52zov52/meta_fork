<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Services\SemesterService;

class AcademicYearController extends Controller
{
    /**
     * Возвращает даты начала и конца текущего учебного года.
     * Учебный год определяется динамически через SemesterService.
     *
     * @return array{string, string} Массив с датами в формате ['Y-m-d', 'Y-m-d'].
     */
    public static function getCurrentStudyYear(): array
    {
        $academicYear = SemesterService::getCurrentAcademicYear();

        return [
            $academicYear['start']->format('Y-m-d'),
            $academicYear['end']->format('Y-m-d'),
        ];
    }
}
