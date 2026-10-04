<?php

use Illuminate\Support\Facades\Route;

// Web маршруты для Sanctum SPA аутентификации
// Подключены в bootstrap/app.php

Route::get('/', function () {
    return ['status' => 'ok'];
});
