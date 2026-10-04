<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityFile;
use App\Support\PublicUploadPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Activity Files
 */
class ActivityFileController extends Controller
{
    /**
     * Загрузить файл к мероприятию
     *
     * Доступ:
     * - admin: может загружать файл к любому мероприятию
     * - mentor: может загружать файл только к своему мероприятию (creator_id)
     *
     * @group Activity Files (Admin/Mentor)
     *
     * @urlParam activity_id integer required ID мероприятия. Example: 1
     *
     * @bodyParam file file required Файл с материалами (pdf, docx, doc, zip, макс. 10MB).
     *
     * @response 200 {
     *   "message": "Файл успешно загружен!",
     *   "file": {
     *     "id": 1,
     *     "activity_id": 1,
     *     "original_name": "materials.pdf",
     *     "mime_type": "application/pdf",
     *     "file_size": 1024000,
     *     "formatted_size": "1.00 МБ",
     *     "created_at": "2026-03-06T12:00:00.000000Z"
     *   }
     * }
     * @response 404 {
     *   "message": "Мероприятие не найдено"
     * }
     * @response 403 {
     *   "message": "У вас нет прав для загрузки файлов к этому мероприятию."
     * }
     * @response 422 {
     *   "message": "Ошибка валидации",
     *   "errors": {
     *     "file": ["Файл должен быть в формате: pdf, docx, doc, zip."]
     *   }
     * }
     */
    public function upload(Request $request, int $activity_id): JsonResponse
    {
        // Проверка существования мероприятия
        $activity = Activity::findOrFail($activity_id);

        $user = $request->user();
        if ($user?->role === 'mentor' && (int) $activity->creator_id !== (int) $user->id) {
            return response()->json([
                'message' => 'У вас нет прав для загрузки файлов к этому мероприятию.',
            ], 403);
        }

        // Валидация файла
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf,docx,doc,zip', 'max:10240'], // 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');

        // Сохранение файла
        $pathToFile = $file->store(PublicUploadPath::activityAttachment($activity_id), 'public');

        // Создание записи в БД
        $activityFile = ActivityFile::create([
            'activity_id' => $activity_id,
            'file_path' => $pathToFile,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'Файл успешно загружен!',
            'file' => [
                'id' => $activityFile->id,
                'activity_id' => $activityFile->activity_id,
                'original_name' => $activityFile->original_name,
                'mime_type' => $activityFile->mime_type,
                'file_size' => $activityFile->file_size,
                'formatted_size' => $activityFile->formatted_size,
                'created_at' => $activityFile->created_at,
            ],
        ]);
    }

    /**
     * Скачать файл мероприятия
     *
     * @group Activity Files
     *
     * @unauthenticated
     *
     * @urlParam file_id integer required ID файла. Example: 1
     *
     * @response 200 binary
     * @response 404 {
     *   "message": "Файл не найден"
     * }
     */
    public function download(int $file_id)
    {
        $activityFile = ActivityFile::findOrFail($file_id);

        $filePath = storage_path('app/public/'.$activityFile->file_path);

        if (! file_exists($filePath)) {
            return response()->json(['message' => 'Файл не найден'], 404);
        }

        return response()->download($filePath, $activityFile->original_name);
    }

    /**
     * Удалить файл мероприятия
     *
     * Доступ:
     * - admin: может удалять файл любого мероприятия
     * - mentor: может удалять файл только своего мероприятия (creator_id)
     *
     * @group Activity Files (Admin/Mentor)
     *
     * @urlParam file_id integer required ID файла. Example: 1
     *
     * @response 200 {
     *   "message": "Файл успешно удалён!"
     * }
     * @response 404 {
     *   "message": "Файл не найден"
     * }
     * @response 403 {
     *   "message": "У вас нет прав для удаления файлов этого мероприятия."
     * }
     */
    public function destroy(Request $request, int $file_id): JsonResponse
    {
        $activityFile = ActivityFile::with('activity')->findOrFail($file_id);

        $user = $request->user();
        if ($user?->role === 'mentor' && (int) $activityFile->activity?->creator_id !== (int) $user->id) {
            return response()->json([
                'message' => 'У вас нет прав для удаления файлов этого мероприятия.',
            ], 403);
        }

        $activityFile->delete();

        return response()->json(['message' => 'Файл успешно удалён!']);
    }

    /**
     * Получить список файлов мероприятия
     *
     * @group Activity Files
     *
     * @unauthenticated
     *
     * @urlParam activity_id integer required ID мероприятия. Example: 1
     */
    public function index(int $activity_id): JsonResponse
    {
        $files = ActivityFile::where('activity_id', $activity_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'file_size' => $file->file_size,
                    'formatted_size' => $file->formatted_size,
                    'created_at' => $file->created_at,
                ];
            });

        return response()->json(['files' => $files]);
    }
}
