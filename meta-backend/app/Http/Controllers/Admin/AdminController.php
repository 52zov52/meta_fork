<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminController extends Controller
{
    /**
     * Получить информацию о пользователе
     *
     * @group Users (Admin)
     *
     * @urlParam user_id int required The ID of the user. Example: 2
     */
    public function getMy(int $user_id)
    {
        $user = User::findOrFail($user_id);

        return response()->json([
            'shortName' => $user->member->shortName,
            'fullName' => $user->member->fullName,
        ]);
    }
}
