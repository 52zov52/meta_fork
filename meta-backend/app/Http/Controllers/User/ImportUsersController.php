<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Member;
use App\Support\StudentGroupRules;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/**
 * @group Users (Admin)
 */
class ImportUsersController extends Controller
{
    /**
     * Скачать шаблон DOCX для импорта пользователей
     */
    public function downloadTemplate()
    {
        $path = resource_path('templates/users-import.docx');

        if (! file_exists($path)) {
            return response()->json(['message' => 'Шаблон не найден'], 404);
        }

        return response()->download(
            $path,
            'users-import-template.docx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        );
    }

    /**
     * Импорт пользователей из DOCX
     *
     * @bodyParam file file required DOCX with columns: Фамилия,Имя,Отчество,Курс,Группа,Пол,Логин,Пароль
     * @bodyParam institution_id integer required ID учреждения (из таблицы institutions). Example: 1
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', File::types(['docx'])->max(10240)],
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
        ]);

        $institution = Institution::findOrFail($request->institution_id);

        $tempPath = $request->file('file')->store('temp');
        $docxPath = Storage::disk('local')->path($tempPath);

        try {
            $rows = $this->extractTableFromDocx($docxPath);
            Storage::disk('local')->delete($tempPath);

            if (empty($rows)) {
                return response()->json(['success' => false, 'message' => 'Таблица не найдена в документе'], 422);
            }

            $result = $this->importUsers($rows, $institution);
            $reportPath = $this->generateReport($result);

            return response()->download($reportPath, 'import_report_'.date('Y-m-d_His').'.docx')->deleteFileAfterSend();

        } catch (\Exception $e) {
            Storage::disk('local')->delete($tempPath);
            Log::error('DOCX import failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ошибка обработки файла'], 500);
        }
    }

    private function extractTableFromDocx(string $path): array
    {
        $phpWord = IOFactory::load($path);
        $rows = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (! method_exists($element, 'getRows')) {
                    continue;
                }

                $tableRows = $element->getRows();
                if (count($tableRows) < 2) {
                    continue;
                }

                // Skip header row (index 0)
                for ($i = 1; $i < count($tableRows); $i++) {
                    $cells = $tableRows[$i]->getCells();
                    if (count($cells) < 9) {
                        continue;
                    } // 9 cells: row number + 8 data columns

                    // Extract text from each cell properly
                    $rowData = [];
                    foreach ($cells as $cell) {
                        $text = '';
                        foreach ($cell->getElements() as $cellElement) {
                            if (method_exists($cellElement, 'getText')) {
                                $text .= $cellElement->getText();
                            } elseif (method_exists($cellElement, 'getElements')) {
                                // Handle nested elements (text runs)
                                foreach ($cellElement->getElements() as $subElement) {
                                    if (method_exists($subElement, 'getText')) {
                                        $text .= $subElement->getText();
                                    }
                                }
                            }
                        }
                        // Normalize whitespace: replace newlines/tabs with spaces and trim
                        $rowData[] = trim(preg_replace('/\s+/', ' ', $text));
                    }

                    // Skip if first column is row number and we have enough data
                    if (count($rowData) >= 9) {
                        array_shift($rowData); // Remove row number column
                    }

                    // SKIP EMPTY ROWS
                    if (empty($rowData[0]) && empty($rowData[1])) {
                        continue;
                    }

                    if (count($rowData) < 8) {
                        continue;
                    }

                    $rows[] = [
                        'last_name' => $rowData[0],
                        'first_name' => $rowData[1],
                        'middle_name' => $rowData[2],
                        'course' => $rowData[3],
                        'group' => $rowData[4],
                        'gender' => $rowData[5],
                        'login' => $rowData[6],
                        'password' => $rowData[7],
                    ];
                }
            }
        }

        return $rows;
    }

    private function importUsers(array $rows, Institution $institution): array
    {
        $static = [
            'region_id' => 67,
            'planet_id' => 7,
            'institution_id' => $institution->id,
            'institution' => $institution->id == 4 ? null : $institution->name,
            'role' => 'student',
            'is_terms_accepted' => true,
            'is_activated' => 1,
        ];

        $created = [];
        $skipped = [];

        foreach ($rows as $index => $row) {
            // Skip if FIO is empty
            if (empty($row['last_name']) || empty($row['first_name'])) {
                $skipped[] = array_merge($row, ['reason' => 'Неполное ФИО']);

                continue;
            }

            $group = trim((string) ($row['group'] ?? ''));
            if (! StudentGroupRules::isValid($group)) {
                $skipped[] = array_merge($row, ['reason' => StudentGroupRules::invalidMessage()]);

                continue;
            }

            // FIRST: Check if this exact person already exists (email + FIO match)
            $existingUserByEmail = User::where('email', $row['login'])->first();
            if ($existingUserByEmail) {
                $existingMember = Member::where('user_id', $existingUserByEmail->id)->first();

                if ($existingMember) {
                    $existingFio = trim("{$existingMember->last_name} {$existingMember->first_name} {$existingMember->middle_name}");
                    $incomingFio = trim("{$row['last_name']} {$row['first_name']} {$row['middle_name']}");

                    if ($existingFio === $incomingFio) {
                        // Exact same person trying to register again
                        $skipped[] = array_merge($row, [
                            'email' => $row['login'],
                            'reason' => 'Уже зарегистрирован',
                        ]);

                        continue;
                    } else {
                        // Different person with same email (email conflict)
                        $skipped[] = array_merge($row, [
                            'email' => $row['login'],
                            'reason' => "Почта уже занята пользователем: {$existingFio}",
                        ]);

                        continue;
                    }
                } else {
                    // User exists but has no member profile
                    $skipped[] = array_merge($row, [
                        'email' => $row['login'],
                        'reason' => 'Почта уже занята (неполный профиль)',
                    ]);

                    continue;
                }
            }

            // SECOND: Check if FIO already exists with DIFFERENT email
            $existing = Member::where([
                ['last_name', $row['last_name']],
                ['first_name', $row['first_name']],
                ['middle_name', $row['middle_name']],
            ])->with('user')->first();

            if ($existing) {
                $skipped[] = array_merge($row, [
                    'email' => $existing->user->email ?? '',
                    'reason' => 'Уже зарегистрирован',
                ]);

                continue;
            }

            DB::beginTransaction();
            try {
                $user = User::create([
                    'name' => strstr($row['login'], '@', true) ?: $row['login'],
                    'email' => $row['login'],
                    'password' => Hash::make($row['password']),
                    'role' => $static['role'],
                    'is_terms_accepted' => $static['is_terms_accepted'],
                    'is_activated' => $static['is_activated'],
                ]);

                Member::create([
                    'user_id' => $user->id,
                    'last_name' => $row['last_name'],
                    'first_name' => $row['first_name'],
                    'middle_name' => $row['middle_name'],
                    'gender' => (int) $row['gender'],
                    'region_id' => $static['region_id'],
                    'planet_id' => $static['planet_id'],
                    'institution_id' => $static['institution_id'],
                    'institution' => $static['institution'],
                    'course' => (int) $row['course'],
                    'student_group' => $group !== '' ? $group : null,
                ]);

                DB::commit();
                $created[] = array_merge($row, ['email' => $user->email]);

            } catch (\Exception $e) {
                DB::rollBack();
                $skipped[] = array_merge($row, ['reason' => 'Ошибка: '.$e->getMessage()]);
                Log::error("Import failed for {$row['last_name']}: ".$e->getMessage());
            }
        }

        return compact('created', 'skipped');
    }

    private function generateReport(array $result): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();

        $grouped = collect($result['created'])->concat($result['skipped'])->groupBy('group')->sortKeys();

        foreach ($grouped as $group => $users) {
            $section->addText($group, ['bold' => true, 'size' => 14]);

            $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
            $table->addRow();
            $table->addCell(4000)->addText('ФИО', ['bold' => true]);
            $table->addCell(3000)->addText('Логин', ['bold' => true]);
            $table->addCell(3000)->addText('Статус', ['bold' => true]);

            foreach ($users as $user) {
                $table->addRow();
                $fio = trim("{$user['last_name']} {$user['first_name']} {$user['middle_name']}");
                $table->addCell(4000)->addText($fio);
                $table->addCell(3000)->addText($user['email'] ?? $user['login']);
                $table->addCell(3000)->addText($user['reason'] ?? $user['password']);
            }

            $section->addTextBreak(1);
        }

        $path = Storage::disk('local')->path('temp/report_'.uniqid().'.docx');
        Storage::disk('local')->makeDirectory('temp');
        $phpWord->save($path, 'Word2007');

        return $path;
    }

    /**
     * Получение доступных типов учреждений
     *
     * Возвращает список всех доступных типов учреждений для импорта.
     * Используйте полученные ID в методе подтверждения импорта.
     */
    public function getInstitutionTypes()
    {
        return response()->json([
            'institution_types' => Institution::all(['id', 'name']),
        ]);
    }
}
