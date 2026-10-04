<?php

use App\Models\Marketplace;
use App\Models\MarketplaceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createStudentWithMember(array $userAttributes = [], array $memberAttributes = []): User
{
    $refs = ensureMemberReferenceData();

    $student = User::factory()->create(array_merge([
        'role' => 'student',
        'previous_semester_score' => 100,
        'is_activated' => 1,
    ], $userAttributes));

    DB::table('members')->insert(array_merge([
        'last_name' => 'Иванов',
        'first_name' => 'Иван',
        'middle_name' => 'Иванович',
        'gender' => 0,
        'birthday' => '2000-01-01',
        'region_id' => $refs['region_id'],
        'planet_id' => $refs['planet_id'],
        'institution_id' => $refs['institution_id'],
        'institution' => null,
        'course' => 2,
        'student_group' => 'К2Т',
        'profile_photo_path' => null,
        'user_id' => $student->id,
        'academy_id' => null,
        'education_level_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $memberAttributes));

    return $student;
}

function ensureMemberReferenceData(): array
{
    foreach ([
        1 => 'ЧОУ ВО ЮУ (ИУБиП)',
        2 => 'ЧПОУ СИЮУ',
        3 => 'ЧПОУ ДИЮУ',
        4 => 'Другое',
    ] as $id => $name) {
        DB::table('institutions')->updateOrInsert(
            ['id' => $id],
            ['name' => $name, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    $regionId = DB::table('regions')->value('id');
    if (! $regionId) {
        $regionId = DB::table('regions')->insertGetId([
            'code' => 610000,
            'name' => 'Ростовская область',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $planetId = DB::table('planets')->value('id');
    if (! $planetId) {
        $planetId = DB::table('planets')->insertGetId([
            'name' => 'Тестовая планета',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $institutionId = DB::table('institutions')->value('id');
    if (! $institutionId) {
        $institutionId = DB::table('institutions')->insertGetId([
            'name' => 'Тестовое учреждение',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return [
        'region_id' => $regionId,
        'planet_id' => $planetId,
        'institution_id' => $institutionId,
    ];
}

function createProduct(int $creatorId, array $attributes = []): Marketplace
{
    return Marketplace::create(array_merge([
        'name' => 'Тестовый приз',
        'score' => 30,
        'is_archive' => false,
        'creator_id' => $creatorId,
    ], $attributes));
}

test('student gets only active marketplace products', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_activated' => 1]);
    $student = createStudentWithMember();

    createProduct($admin->id, ['name' => 'Активный товар', 'is_archive' => false]);
    createProduct($admin->id, ['name' => 'Архивный товар', 'is_archive' => true]);

    Sanctum::actingAs($student);

    $response = $this->getJson('/api/v1/student/marketplace/products');

    $response
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Активный товар');
});

test('admin can view and update marketplace settings', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_activated' => 1]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/admin/marketplace/settings')
        ->assertStatus(200)
        ->assertJsonPath('data.pickup_hint', '');

    $this->putJson('/api/v1/admin/marketplace/settings', [
        'pickup_hint' => 'Кабинет 101',
    ])->assertStatus(200);

    $this->getJson('/api/v1/admin/marketplace/settings')
        ->assertStatus(200)
        ->assertJsonPath('data.pickup_hint', 'Кабинет 101');
});

test('student can view marketplace settings when institution is not other', function () {
    MarketplaceSetting::create(['pickup_hint' => 'Стойка выдачи']);
    $student = createStudentWithMember();

    Sanctum::actingAs($student);

    $this->getJson('/api/v1/student/marketplace/settings')
        ->assertStatus(200)
        ->assertJsonPath('data.pickup_hint', 'Стойка выдачи');
});

test('student from other institution does not receive marketplace pickup hint', function () {
    MarketplaceSetting::create(['pickup_hint' => 'Стойка выдачи']);
    $student = createStudentWithMember([], [
        'institution_id' => 4,
        'institution' => 'Внешнее учреждение',
    ]);

    Sanctum::actingAs($student);

    $this->getJson('/api/v1/student/marketplace/settings')
        ->assertStatus(200)
        ->assertJsonPath('data.pickup_hint', '');
});

test('admin can create marketplace product with photo', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin', 'is_activated' => 1]);

    Sanctum::actingAs($admin);

    $response = $this->post('/api/v1/admin/marketplace/products', [
        'name' => 'Фирменный значок',
        'score' => 10,
        'photo' => UploadedFile::fake()->create('badge.jpg', 100, 'image/jpeg'),
    ], ['Accept' => 'application/json']);

    $response
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'Фирменный значок')
        ->assertJsonPath('data.score', 10);

    $photoPath = $response->json('data.photo_path');

    expect($photoPath)->toBeString()->toStartWith('files/marketplace/products/');
    Storage::disk('public')->assertExists($photoPath);
});

test('student can create marketplace order and balance is deducted', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_activated' => 1]);
    $student = createStudentWithMember(['previous_semester_score' => 80]);
    $product = createProduct($admin->id, ['name' => 'Термокружка', 'score' => 25]);

    Sanctum::actingAs($student);

    $this->postJson('/api/v1/student/marketplace/orders', [
        'marketplace_id' => $product->id,
    ])
        ->assertStatus(201)
        ->assertJson([
            'message' => 'Покупка успешна.',
        ]);

    $student->refresh();
    expect($student->previous_semester_score)->toBe(55);

    $this->assertDatabaseHas('marketplace_orders', [
        'user_id' => $student->id,
        'marketplace_id' => $product->id,
        'item_name_snapshot' => 'Термокружка',
        'item_score_snapshot' => 25,
        'status' => 'pending',
    ]);
});

test('student cannot create marketplace order when score is not enough', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_activated' => 1]);
    $student = createStudentWithMember(['previous_semester_score' => 10]);
    $product = createProduct($admin->id, ['score' => 20]);

    Sanctum::actingAs($student);

    $this->postJson('/api/v1/student/marketplace/orders', [
        'marketplace_id' => $product->id,
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Недостаточно баллов для покупки этого товара.');

    $student->refresh();
    expect($student->previous_semester_score)->toBe(10);

    $this->assertDatabaseCount('marketplace_orders', 0);
});

test('admin can list and issue marketplace orders', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_activated' => 1]);
    $student = createStudentWithMember([], [
        'last_name' => 'Петров',
        'first_name' => 'Петр',
        'middle_name' => 'Петрович',
    ]);
    $product = createProduct($admin->id, ['name' => 'Блокнот', 'score' => 15]);

    Sanctum::actingAs($student);
    $this->postJson('/api/v1/student/marketplace/orders', [
        'marketplace_id' => $product->id,
    ])->assertStatus(201);

    Sanctum::actingAs($admin);

    $listResponse = $this->getJson('/api/v1/admin/marketplace/orders?status=pending&search=Петров');
    $listResponse
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.student_last_name', 'Петров')
        ->assertJsonPath('data.0.student_group', 'К2Т')
        ->assertJsonPath('data.0.item_name_snapshot', 'Блокнот')
        ->assertJsonPath('data.0.status', 'pending');

    // Search by group
    $this->getJson('/api/v1/admin/marketplace/orders?status=pending&search=К2Т')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.student_group', 'К2Т');

    $orderId = $listResponse->json('data.0.id');

    $this->patchJson('/api/v1/admin/marketplace/orders/'.$orderId, [
        'status' => 'issued',
    ])
        ->assertStatus(200)
        ->assertJsonPath('message', 'Покупка отмечена как выданная.');

    $this->assertDatabaseHas('marketplace_orders', [
        'id' => $orderId,
        'status' => 'issued',
        'issued_by' => $admin->id,
    ]);
});

test('student can list own marketplace orders', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_activated' => 1]);
    $student = createStudentWithMember(['previous_semester_score' => 80]);
    $product = createProduct($admin->id, ['name' => 'Пенал', 'score' => 20]);

    Sanctum::actingAs($student);

    $this->postJson('/api/v1/student/marketplace/orders', [
        'marketplace_id' => $product->id,
    ])->assertStatus(201);

    $this->getJson('/api/v1/student/marketplace/orders')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.item_name_snapshot', 'Пенал')
        ->assertJsonPath('data.0.item_score_snapshot', 20)
        ->assertJsonPath('data.0.status', 'pending');
});

test('admin can cancel pending marketplace order and refund score once', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_activated' => 1]);
    $student = createStudentWithMember(['previous_semester_score' => 70]);
    $product = createProduct($admin->id, ['name' => 'Футболка', 'score' => 30]);

    Sanctum::actingAs($student);
    $this->postJson('/api/v1/student/marketplace/orders', [
        'marketplace_id' => $product->id,
    ])->assertStatus(201);

    $student->refresh();
    expect($student->previous_semester_score)->toBe(40);

    Sanctum::actingAs($admin);
    $orderId = $this->getJson('/api/v1/admin/marketplace/orders?status=pending')->json('data.0.id');

    $this->patchJson('/api/v1/admin/marketplace/orders/'.$orderId, [
        'status' => 'cancelled',
    ])
        ->assertStatus(200)
        ->assertJsonPath('message', 'Покупка отменена, баллы возвращены.');

    $student->refresh();
    expect($student->previous_semester_score)->toBe(70);

    $this->assertDatabaseHas('marketplace_orders', [
        'id' => $orderId,
        'status' => 'cancelled',
        'cancelled_by' => $admin->id,
    ]);

    $this->patchJson('/api/v1/admin/marketplace/orders/'.$orderId, [
        'status' => 'cancelled',
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Изменение статуса недоступно.');

    $student->refresh();
    expect($student->previous_semester_score)->toBe(70);
});

test('student can sort and filter own marketplace orders', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_activated' => 1]);
    $student = createStudentWithMember(['previous_semester_score' => 100]);

    $product1 = createProduct($admin->id, ['name' => 'Ручка', 'score' => 10]);
    $product2 = createProduct($admin->id, ['name' => 'Блокнот', 'score' => 20]);

    Sanctum::actingAs($student);

    $this->postJson('/api/v1/student/marketplace/orders', [
        'marketplace_id' => $product1->id,
    ])->assertStatus(201);

    $this->postJson('/api/v1/student/marketplace/orders', [
        'marketplace_id' => $product2->id,
    ])->assertStatus(201);

    $orders = $student->marketplaceOrders()->orderBy('id')->get();
    $firstOrder = $orders->first();
    $secondOrder = $orders->skip(1)->first();

    // Issue first order
    $firstOrder->update([
        'status' => 'issued',
        'issued_at' => now()->subDay(),
        'issued_by' => $admin->id,
    ]);

    // Cancel second order
    $secondOrder->update([
        'status' => 'cancelled',
        'cancelled_at' => now(),
        'cancelled_by' => $admin->id,
    ]);

    // Sort by processed_at desc
    $response = $this->getJson('/api/v1/student/marketplace/orders?sort=processed_at&direction=desc');
    $response
        ->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.status', 'cancelled')
        ->assertJsonPath('data.1.status', 'issued');

    // Filter by pending
    $this->getJson('/api/v1/student/marketplace/orders?status=pending')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data');

    // Filter by issued
    $this->getJson('/api/v1/student/marketplace/orders?status=issued')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'issued');

    // Sort by item_score_snapshot asc
    $this->getJson('/api/v1/student/marketplace/orders?sort=item_score_snapshot&direction=asc')
        ->assertStatus(200)
        ->assertJsonPath('data.0.item_score_snapshot', 10)
        ->assertJsonPath('data.1.item_score_snapshot', 20);
});

test('admin cannot move issued order back to pending', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_activated' => 1]);
    $student = createStudentWithMember();
    $product = createProduct($admin->id, ['name' => 'Рюкзак', 'score' => 40]);

    Sanctum::actingAs($student);
    $this->postJson('/api/v1/student/marketplace/orders', [
        'marketplace_id' => $product->id,
    ])->assertStatus(201);

    Sanctum::actingAs($admin);
    $orderId = $this->getJson('/api/v1/admin/marketplace/orders?status=pending')->json('data.0.id');

    $this->patchJson('/api/v1/admin/marketplace/orders/'.$orderId, [
        'status' => 'issued',
    ])->assertStatus(200);

    $this->patchJson('/api/v1/admin/marketplace/orders/'.$orderId, [
        'status' => 'pending',
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Изменение статуса недоступно.');
});
