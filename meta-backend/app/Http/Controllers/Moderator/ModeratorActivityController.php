<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Support\PublicUploadPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @group Activities (Moderator)
 */
class ModeratorActivityController extends Controller
{
    private function getModeratorInstitutionId(): ?int
    {
        return Auth::user()->member->institution_id ?? null;
    }

    /**
     * Базовый запрос: только мероприятия своего учреждения
     */
    private function baseActivityQuery()
    {
        $institutionId = $this->getModeratorInstitutionId();

        return Activity::select('activities.*')
            ->join('members as creator_member', 'activities.creator_id', '=', 'creator_member.user_id')
            ->where('creator_member.institution_id', $institutionId);
    }

    /**
     * Получить список мероприятий
     */
    public function index(): JsonResponse
    {
        $activities = $this->baseActivityQuery()
            ->orderBy('activities.created_at', 'desc')
            ->paginate(20);

        return response()->json($activities);
    }

    /**
     * Создать мероприятие
     *
     * Создает новое мероприятие. По умолчанию ему присваивается статус "draft" (черновик).
     *
     * @bodyParam title string required Название мероприятия. Example: Весенний Хакатон 2026
     * @bodyParam description string Описание. Example: Масштабное соревнование разработчиков.
     * @bodyParam date_start string required Дата начала (Y-m-d H:i:s). Example: 2026-05-01 10:00:00
     * @bodyParam date_end string required Дата окончания (Y-m-d H:i:s). Example: 2026-05-03 20:00:00
     * @bodyParam score numeric Баллы за участие. Example: 50
     * @bodyParam planet_id numeric required ID направления. Example: 1
     * @bodyParam category_id numeric required ID категории. Example: 2
     * @bodyParam photo file Обложка мероприятия (jpeg, png).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date_start' => ['required', 'date'],
            'date_end' => ['required', 'date', 'after_or_equal:date_start'],
            'score' => ['nullable', 'numeric'],
            'planet_id' => ['required', 'numeric'],
            'category_id' => ['required', 'numeric'],
            'photo' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:2048'],
        ]);

        $data = $request->except('photo');
        $data['creator_id'] = Auth::id();
        $data['status'] = 'draft';

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store(PublicUploadPath::activityCover(), 'public');
        }

        $activity = Activity::create($data);

        return response()->json([
            'message' => 'Мероприятие успешно создано (черновик)!',
            'activity' => $activity,
        ]);
    }

    /**
     * Редактировать мероприятие
     *
     * Внимание: если вы отправляете файл (photo), запрос должен быть в формате `multipart/form-data`,
     * а в Laravel для PUT/PATCH запросов с файлами нужно использовать POST запрос и добавлять поле `_method=PUT`!
     *
     * @urlParam id integer required ID мероприятия. Example: 1
     *
     * @bodyParam title string required Название мероприятия. Example: Весенний Хакатон (Обновлен)
     * @bodyParam description string Описание. Example: Обновленное описание.
     * @bodyParam date_start string required Дата начала (Y-m-d H:i:s). Example: 2026-05-01 10:00:00
     * @bodyParam date_end string required Дата окончания (Y-m-d H:i:s). Example: 2026-05-03 20:00:00
     * @bodyParam score numeric Баллы за участие. Example: 100
     * @bodyParam planet_id numeric required ID направления. Example: 1
     * @bodyParam category_id numeric required ID категории. Example: 2
     * @bodyParam photo file Новая обложка мероприятия.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $activity = $this->baseActivityQuery()->where('activities.id', $id)->first();

        if (! $activity) {
            return response()->json(['error' => 'Мероприятие не найдено или доступ запрещен'], 404);
        }

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date_start' => ['required', 'date'],
            'date_end' => ['required', 'date', 'after_or_equal:date_start'],
            'score' => ['nullable', 'numeric'],
            'planet_id' => ['required', 'numeric'],
            'category_id' => ['required', 'numeric'],
            'photo' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:2048'],
        ]);

        $data = $request->except('photo');

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store(PublicUploadPath::activityCover(), 'public');
        }

        $activity->update($data);

        return response()->json(['message' => 'Мероприятие успешно обновлено!']);
    }

    /**
     * Публикация / Снятие с публикации
     *
     * Меняет статус мероприятия с `draft` на `scheduled` и обратно. Запрещено дергать, если статус `started` или `completed`.
     *
     * @urlParam id integer required ID мероприятия. Example: 1
     */
    public function publish(int $id): JsonResponse
    {
        $activity = $this->baseActivityQuery()->where('activities.id', $id)->first();

        if (! $activity) {
            return response()->json(['error' => 'Мероприятие не найдено или доступ запрещен'], 404);
        }

        if ($activity->status === 'draft') {
            $activity->status = 'scheduled';
            $statusText = 'опубликовано (запланировано)';
        } elseif ($activity->status === 'scheduled') {
            $activity->status = 'draft';
            $statusText = 'снято с публикации (черновик)';
        } else {
            return response()->json([
                'error' => "Невозможно изменить статус публикации для мероприятия в статусе: {$activity->status}",
            ], 400);
        }

        $activity->save();

        return response()->json(['message' => "Мероприятие успешно {$statusText}!"]);
    }

    /**
     * Удалить мероприятие (Двухшаговое удаление)
     *
     * Первое удаление снимает мероприятие с публикации (статус draft).
     * Второе удаление (если статус уже draft) удаляет его безвозвратно вместе с участниками.
     *
     * @urlParam id integer required ID мероприятия. Example: 1
     */
    public function destroy(int $id): JsonResponse
    {
        $activity = $this->baseActivityQuery()->where('activities.id', $id)->first();

        if (! $activity) {
            return response()->json(['error' => 'Мероприятие не найдено или доступ запрещен'], 404);
        }

        if ($activity->status !== 'draft') {
            $activity->status = 'draft';
            $activity->save();

            return response()->json([
                'message' => 'Мероприятие снято с публикации (переведено в черновик). Чтобы удалить его безвозвратно вместе с участниками, нажмите "Удалить" еще раз.',
            ]);
        }

        DB::table('activity_user')->where('activity_id', $id)->delete();

        $activity->delete();

        return response()->json(['message' => 'Мероприятие и все записи участников безвозвратно удалены!']);
    }
}
