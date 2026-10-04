<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'is_activated' => 1]);
    Sanctum::actingAs($this->admin);
});

test('a notification can be sent to an individual user', function () {
    $user = User::factory()->create();

    $payload = [
        'recipient_type' => 'individual',
        'user_id' => $user->id,
        'title' => 'Individual Test',
        'message' => 'This is a test notification.',
    ];

    $this->postJson('/api/v1/admin/notification/store', $payload)
         ->assertStatus(201)
         ->assertJson(['message' => 'Уведомления успешно созданы!']);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $user->id,
        'title' => 'Individual Test',
    ]);

    $this->assertDatabaseCount('notifications', 1);
});

test('a notification can be sent to all students', function () {
    User::factory()->count(3)->create(['role' => 'student']);
    User::factory()->count(2)->create(['role' => 'mentor']);

    $payload = [
        'recipient_type' => 'students',
        'title' => 'Student Group Test',
        'message' => 'This is for students only.',
    ];

    $this->postJson('/api/v1/admin/notification/store', $payload)->assertStatus(201);

    $this->assertDatabaseCount('notifications', 3);
    $studentIds = User::where('role', 'student')->pluck('id');
    foreach ($studentIds as $id) {
        $this->assertDatabaseHas('notifications', ['user_id' => $id, 'title' => 'Student Group Test']);
    }
});

test('a notification can be sent to all users', function () {
    User::factory()->count(2)->create(['role' => 'student']);
    User::factory()->count(1)->create(['role' => 'mentor']);
    // Admin user also exists

    $payload = [
        'recipient_type' => 'all',
        'title' => 'All Users Test',
        'message' => 'This is for everyone.',
    ];

    $this->postJson('/api/v1/admin/notification/store', $payload)->assertStatus(201);

    // 2 students + 1 mentor + 1 admin = 4
    $this->assertDatabaseCount('notifications', 4);
});

test('user_id is required when recipient_type is individual', function () {
    $payload = [
        'recipient_type' => 'individual',
        'user_id' => null,
        'title' => 'Validation Test',
        'message' => 'This should fail.',
    ];

    $this->postJson('/api/v1/admin/notification/store', $payload)
         ->assertStatus(422)
         ->assertJsonValidationErrors('user_id');
});

test('recipient_type must be valid', function () {
    $payload = [
        'recipient_type' => 'nonexistent_group',
        'title' => 'Validation Test',
        'message' => 'This should fail.',
    ];

    $this->postJson('/api/v1/admin/notification/store', $payload)
         ->assertStatus(422)
         ->assertJsonValidationErrors('recipient_type');
});

test('sending to admins role is not allowed', function () {
    $payload = [
        'recipient_type' => 'admins',
        'title' => 'Admins Test',
        'message' => 'This should fail validation.',
    ];

    $this->postJson('/api/v1/admin/notification/store', $payload)
         ->assertStatus(422)
         ->assertJsonValidationErrors('recipient_type');
});

test('notifications are sorted by created_at', function () {
    $user = User::factory()->create();
    
    // Создаем уведомление, которое будет иметь меньший ID, но БОЛЕЕ НОВУЮ дату
    \App\Models\Notification::create([
        'user_id' => $user->id,
        'title' => 'NewestButSmallID',
        'description' => 'Desc',
        'checked' => false,
        'created_at' => now(),
    ]);

    // Создаем уведомление, которое будет иметь больший ID, но БОЛЕЕ СТАРУЮ дату
    \App\Models\Notification::create([
        'user_id' => $user->id,
        'title' => 'OldestButLargeID',
        'description' => 'Desc',
        'checked' => false,
        'created_at' => now()->subDay(),
    ]);

    // Проверяем desc (сначала новые по времени)
    $response = $this->getJson('/api/v1/admin/notifications/order/desc');
    $response->assertStatus(200);
    
    // По времени первым должно быть "NewestButSmallID"
    // Но текущий бэкенд по ID desc выдаст "OldestButLargeID"
    expect($response->json('data.0.title'))->toBe('NewestButSmallID');
    expect($response->json('data.1.title'))->toBe('OldestButLargeID');
});
