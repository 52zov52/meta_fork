<?php

use App\Services\SemesterService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    SemesterService::clearCache();
});

afterEach(function () {
    Carbon::setTestNow();
    SemesterService::clearCache();
});

it('возвращает первый семестр (1 сен — 25 дек) в декабре', function () {
    Carbon::setTestNow('2025-12-15');

    $semester = SemesterService::getCurrentSemester();

    expect($semester['start']->format('Y-m-d'))->toBe('2025-09-01');
    expect($semester['end']->format('Y-m-d'))->toBe('2025-12-25');
});

it('возвращает первый семестр в сентябре', function () {
    Carbon::setTestNow('2025-09-15');

    $semester = SemesterService::getCurrentSemester();

    expect($semester['start']->format('Y-m-d'))->toBe('2025-09-01');
    expect($semester['end']->format('Y-m-d'))->toBe('2025-12-25');
});

it('возвращает второй семестр в марте', function () {
    Carbon::setTestNow('2026-03-15');

    $semester = SemesterService::getCurrentSemester();

    expect($semester['start']->format('Y-m-d'))->toBe('2026-02-01');
    expect($semester['end']->format('Y-m-d'))->toBe('2026-06-25');
});

it('возвращает второй семестр в мае', function () {
    Carbon::setTestNow('2026-05-15');

    $semester = SemesterService::getCurrentSemester();

    expect($semester['start']->format('Y-m-d'))->toBe('2026-02-01');
    expect($semester['end']->format('Y-m-d'))->toBe('2026-06-25');
});

it('возвращает первый семестр в январе (период blackout)', function () {
    Carbon::setTestNow('2026-01-15');

    $semester = SemesterService::getCurrentSemester();

    expect($semester['start']->format('Y-m-d'))->toBe('2025-09-01');
    expect($semester['end']->format('Y-m-d'))->toBe('2025-12-25');
});

it('возвращает второй семестр в августе (период blackout)', function () {
    Carbon::setTestNow('2026-08-15');

    $semester = SemesterService::getCurrentSemester();

    expect($semester['start']->format('Y-m-d'))->toBe('2026-02-01');
    expect($semester['end']->format('Y-m-d'))->toBe('2026-06-25');
});

it('возвращает предыдущий второй семестр в декабре', function () {
    Carbon::setTestNow('2025-12-15');

    $semester = SemesterService::getPreviousSemester();

    expect($semester['start']->format('Y-m-d'))->toBe('2025-02-01');
    expect($semester['end']->format('Y-m-d'))->toBe('2025-06-25');
});

it('возвращает предыдущий первый семестр в марте', function () {
    Carbon::setTestNow('2026-03-15');

    $semester = SemesterService::getPreviousSemester();

    expect($semester['start']->format('Y-m-d'))->toBe('2025-09-01');
    expect($semester['end']->format('Y-m-d'))->toBe('2025-12-25');
});

it('в сентябре текущий — первый семестр, предыдущий — второй семестр того же года', function () {
    Carbon::setTestNow('2026-09-10');

    $current = SemesterService::getCurrentSemester();
    $previous = SemesterService::getPreviousSemester();

    expect($current['start']->format('Y-m-d'))->toBe('2026-09-01');
    expect($current['end']->format('Y-m-d'))->toBe('2026-12-25');
    expect($previous['start']->format('Y-m-d'))->toBe('2026-02-01');
    expect($previous['end']->format('Y-m-d'))->toBe('2026-06-25');
});

it('возвращает учебный год 2025-2026 в марте (семестр 2)', function () {
    Carbon::setTestNow('2026-03-15');

    $year = SemesterService::getCurrentAcademicYear();

    expect($year['start']->format('Y-m-d'))->toBe('2025-09-01');
    expect($year['end']->format('Y-m-d'))->toBe('2026-06-25');
    expect($year['label'])->toBe('2025-2026');
});

it('возвращает учебный год 2026-2027 в октябре (семестр 1)', function () {
    Carbon::setTestNow('2026-10-15');

    $year = SemesterService::getCurrentAcademicYear();

    expect($year['start']->format('Y-m-d'))->toBe('2026-09-01');
    expect($year['end']->format('Y-m-d'))->toBe('2027-06-25');
    expect($year['label'])->toBe('2026-2027');
});

it('возвращает учебный год в январе (семестр 1, период blackout)', function () {
    Carbon::setTestNow('2026-01-10');

    $year = SemesterService::getCurrentAcademicYear();

    expect($year['start']->format('Y-m-d'))->toBe('2025-09-01');
    expect($year['end']->format('Y-m-d'))->toBe('2026-06-25');
});

it('возвращает учебный год в августе (семестр 2, период blackout)', function () {
    Carbon::setTestNow('2026-08-31');

    $year = SemesterService::getCurrentAcademicYear();

    expect($year['start']->format('Y-m-d'))->toBe('2025-09-01');
    expect($year['end']->format('Y-m-d'))->toBe('2026-06-25');
});

it('вычисляет правильный учебный год для произвольной даты на основе настроек blackout', function () {
    // 1. Октябрь 2025 (Семестр 1)
    $y1 = SemesterService::getAcademicYearForDate(Carbon::parse('2025-10-15'));
    expect($y1['start']->format('Y-m-d'))->toBe('2025-09-01')
        ->and($y1['end']->format('Y-m-d'))->toBe('2026-06-25')
        ->and($y1['label'])->toBe('2025-2026');

    // 2. Январь 2026 (Семестр 1, период зимнего blackout)
    $y2 = SemesterService::getAcademicYearForDate(Carbon::parse('2026-01-15'));
    expect($y2['start']->format('Y-m-d'))->toBe('2025-09-01')
        ->and($y2['end']->format('Y-m-d'))->toBe('2026-06-25')
        ->and($y2['label'])->toBe('2025-2026');

    // 3. Март 2026 (Семестр 2)
    $y3 = SemesterService::getAcademicYearForDate(Carbon::parse('2026-03-20'));
    expect($y3['start']->format('Y-m-d'))->toBe('2025-09-01')
        ->and($y3['end']->format('Y-m-d'))->toBe('2026-06-25')
        ->and($y3['label'])->toBe('2025-2026');

    // 4. Август 2026 (Период летнего blackout)
    $y4 = SemesterService::getAcademicYearForDate(Carbon::parse('2026-08-15'));
    expect($y4['start']->format('Y-m-d'))->toBe('2025-09-01')
        ->and($y4['end']->format('Y-m-d'))->toBe('2026-06-25')
        ->and($y4['label'])->toBe('2025-2026');

    // 5. Сентябрь 2026 (Новый учебный год, Семестр 1)
    $y5 = SemesterService::getAcademicYearForDate(Carbon::parse('2026-09-10'));
    expect($y5['start']->format('Y-m-d'))->toBe('2026-09-01')
        ->and($y5['end']->format('Y-m-d'))->toBe('2027-06-25')
        ->and($y5['label'])->toBe('2026-2027');
});

