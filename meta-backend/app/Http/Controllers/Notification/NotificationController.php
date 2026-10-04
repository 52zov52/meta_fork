<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRequest;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;

/**
 * @group Notifications
 */
class NotificationController extends Controller
{
    /**
     * Получить список уведомлений
     *
     * Возвращает пагинированный список уведомлений, отсортированный по ID.
     *
     * @urlParam sortMethod string required Направление сортировки по id: asc|desc. Example: desc
     */
    public function index(string $sortMethod): JsonResponse
    {
        $notifications = Notification::orderBy('created_at', $sortMethod)
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Получить уведомления конкретного пользователя
     *
     * Возвращает пагинированный список уведомлений пользователя (по user_id),
     * отсортированный по дате создания (сначала новые).
     *
     * @urlParam user_id int required ID пользователя. Example: 15
     */
    public function getNotificationByUserId(int $user_id): JsonResponse
    {
        $notifications = Notification::where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Создать уведомление
     *
     * Создаёт новое уведомление для указанного пользователя.
     *
     * @bodyParam user_id int required ID пользователя. Example: 15
     * @bodyParam title string required Заголовок уведомления. Example: "Новое сообщение"
     * @bodyParam description string required Текст уведомления. Example: "Вам пришло новое сообщение."
     * @bodyParam checked boolean Статус прочтения (по умолчанию false). Example: false
     *
     * @response 201 scenario="Created" {"message":"Уведомление успешно создано!"}
     */
    public function store(NotificationRequest $notificationRequest): JsonResponse
    {
        $data = $notificationRequest->validated();
        $description = $data['description'] ?? $data['message'];
        $recipientType = $data['recipient_type'];

        return \DB::transaction(function () use ($data, $description, $recipientType) {
            $userIds = [];

            switch ($recipientType) {
                case 'individual':
                    $userIds[] = $data['user_id'];
                    break;
                case 'all':
                    $userIds = \App\Models\User::pluck('id')->toArray();
                    break;
                case 'students':
                case 'mentors':
                    $roleMap = [
                        'students' => 'student',
                        'mentors' => 'mentor',
                    ];
                    $role = $roleMap[$recipientType];
                    $userIds = \App\Models\User::where('role', $role)->pluck('id')->toArray();
                    break;
            }

            foreach ($userIds as $userId) {
                Notification::create([
                    'user_id' => $userId,
                    'title' => $data['title'],
                    'description' => $description,
                    'checked' => $data['checked'] ?? false,
                ]);
            }

            return response()->json(['message' => 'Уведомления успешно созданы!'], 201);
        });
    }

    /**
     * Получить уведомление по ID
     *
     * Возвращает данные одного уведомления.
     *
     * @urlParam id int required ID уведомления. Example: 40
     */
    public function edit(int $id): JsonResponse
    {
        $notification = Notification::findOrFail($id);

        return response()->json($notification);
    }

    /**
     * Обновить уведомление
     *
     * Обновляет поля уведомления по его ID.
     *
     * @urlParam id int required ID уведомления. Example: 100
     *
     * @bodyParam user_id int required ID пользователя. Example: 15
     * @bodyParam title string required Заголовок уведомления. Example: "Обновлённый заголовок"
     * @bodyParam description string required Текст уведомления. Example: "Обновлённый текст."
     * @bodyParam checked boolean Статус прочтения (по умолчанию false). Example: true
     *
     * @response 200 scenario="success" {"message": "Уведомление успешно обновлено!"}
     */
    public function update(NotificationRequest $notificationRequest, int $id): JsonResponse
    {
        $fields = $notificationRequest->validated();

        $notification = Notification::findOrFail($id);
        $notification->user_id = $fields->user_id;
        $notification->title = $fields->title;
        $notification->description = $fields->description;
        $notification->checked = $fields->checked ?? false;
        $notification->update();

        return response()->json(['message' => 'Уведомление успешно обновлено!']);
    }

    /**
     * Отметить уведомление как прочитанное
     *
     * Устанавливает checked=true для уведомления по ID.
     *
     * @urlParam id int required ID уведомления. Example: 100
     */
    public function markAsRead(int $id): JsonResponse
    {
        $notification = Notification::findOrFail($id);
        $notification->checked = true;
        $notification->save();

        return response()->json(['message' => 'Уведомление отмечено как прочитанное!']);
    }

    /**
     * Удалить уведомление
     *
     * Удаляет уведомление по ID.
     *
     * @urlParam id int required ID уведомления. Example: 100
     *
     * @response 200 scenario="success" {"message": "Уведомление успешно удалено!"}
     */
    public function destroy(int $id): JsonResponse
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Уведомление успешно удалено!']);
    }
}
