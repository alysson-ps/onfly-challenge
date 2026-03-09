<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_with_valid_data(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $departureDate = Carbon::now()->addDays(7)->toDateString();
        $returnDate = Carbon::now()->addDays(14)->toDateString();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('api/orders', [
            'destination' => 'Paris',
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'success' => true,
            'order' => [
                'id' => $response->json('order.id'),
                'user_id' => $user->id,
                'user_name' => $user->name,
                'destination' => 'Paris',
                'departure_date' => $departureDate,
                'return_date' => $returnDate,
                'status' => 'requested',
            ],
        ]);
        $response->assertJsonStructure([
            'success',
            'order' => [
                'id',
                'user_id',
                'user_name',
                'destination',
                'departure_date',
                'return_date',
                'status',
            ],
        ]);
    }

    public function test_create_order_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('api/orders', [
            'destination' => '',
            'departure_date' => 'invalid-date',
            'return_date' => 'invalid-date',
        ]);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'message',
                'fields' => [
                    'destination',
                    'departure_date',
                    'return_date',
                ],
            ]
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertEquals("Validation Error", $response->json('error.message'));
        $this->assertArrayHasKey('destination', $response->json('error.fields'));
        $this->assertArrayHasKey('departure_date', $response->json('error.fields'));
        $this->assertArrayHasKey('return_date', $response->json('error.fields'));
        $response->assertStatus(422);
    }

    public function test_create_order_with_unauthenticated_user(): void
    {
        $response = $this->postJson('api/orders', [
            'destination' => 'Paris',
            'departure_date' => Carbon::now()->addDays(7)->toDateString(),
            'return_date' => Carbon::now()->addDays(14)->toDateString(),
        ]);

        $response->assertStatus(401);
    }

    public function test_create_order_with_invalid_token(): void
    {
        $token = 'this_is_an_invalid_token';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('api/orders', [
            'destination' => 'Paris',
            'departure_date' => Carbon::now()->addDays(7)->toDateString(),
            'return_date' => Carbon::now()->addDays(14)->toDateString(),
        ]);

        $response->assertStatus(401);
    }

    public function test_create_order_with_return_date_before_departure_date(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('api/orders', [
            'destination' => 'Paris',
            'departure_date' => Carbon::now()->addDays(14)->toDateString(),
            'return_date' => Carbon::now()->addDays(7)->toDateString(),
        ]);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'message',
                'fields' => [
                    'return_date',
                ],
            ]
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertEquals("Validation Error", $response->json('error.message'));
        $this->assertArrayHasKey('return_date', $response->json('error.fields'));
        $this->assertEquals('The return date field must be a date after departure date.', $response->json('error.fields.return_date.0'));
        $response->assertStatus(422);
    }

    public function test_create_order_with_departure_date_in_the_past(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('api/orders', [
            'destination' => 'Paris',
            'departure_date' => Carbon::now()->subDays(1)->toDateString(),
            'return_date' => Carbon::now()->addDays(7)->toDateString(),
        ]);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'message',
                'fields' => [
                    'departure_date',
                ],
            ]
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertEquals("Validation Error", $response->json('error.message'));
        $this->assertArrayHasKey('departure_date', $response->json('error.fields'));
        $this->assertEquals('The departure date field must be a date after or equal to today.', $response->json('error.fields.departure_date.0'));
        $response->assertStatus(422);
    }

    public function test_create_order_with_return_date_in_the_past(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('api/orders', [
            'destination' => 'Paris',
            'departure_date' => Carbon::now()->addDays(7)->toDateString(),
            'return_date' => Carbon::now()->subDays(1)->toDateString(),
        ]);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'message',
                'fields' => [
                    'return_date',
                ],
            ]
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertEquals("Validation Error", $response->json('error.message'));
        $this->assertArrayHasKey('return_date', $response->json('error.fields'));
        $this->assertEquals('The return date field must be a date after departure date.', $response->json('error.fields.return_date.0'));
        $response->assertStatus(422);
    }

    public function test_try_cancel_order_with_invalid_status(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('api/orders/' . $order->id . '/status', [
            'status' => 'invalid_status',
        ]);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'message',
                'fields' => [
                    'status',
                ],
            ]
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertEquals("Validation Error", $response->json('error.message'));
        $this->assertArrayHasKey('status', $response->json('error.fields'));
        $this->assertEquals('The selected status is invalid.', $response->json('error.fields.status.0'));
        $response->assertStatus(422);
    }

    public function test_try_cancel_order_with_non_admin_user(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('api/orders/' . $order->id . '/status', [
            'status' => 'cancelled',
        ]);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'message',
                'reason',
                'code'
            ]
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertEquals("Access Denied", $response->json('error.message'));
        $this->assertEquals("Only admins can change the status of an order.", $response->json('error.reason'));
        $response->assertStatus(403);
    }

    public function test_try_cancel_approved_order(): void
    {
        $userAdmin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 'approved',
        ]);

        $token = $userAdmin->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('api/orders/' . $order->id . '/status', [
            'status' => 'cancelled',
        ]);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'message',
                'reason',
                'code'
            ]
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertEquals("Order Cancellation Error", $response->json('error.message'));
        $this->assertEquals("Approved orders cannot be cancelled.", $response->json('error.reason'));
        $response->assertStatus(400);
    }

    public function test_cancel_order_with_valid_data(): void
    {
        $userAdmin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 'requested',
        ]);

        $token = $userAdmin->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('api/orders/' . $order->id . '/status', [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'destination' => $order->destination,
                'departure_date' => Carbon::parse($order->departure_date)->toDateString(),
                'return_date' => Carbon::parse($order->return_date)->toDateString(),
                'status' => 'cancelled',
            ],
        ]);
        $response->assertJsonStructure([
            'success',
            'order' => [
                'id',
                'user_id',
                'user_name',
                'destination',
                'departure_date',
                'return_date',
                'status',
            ],
        ]);
    }

    public function test_approve_order_with_valid_data(): void
    {
        $userAdmin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 'requested',
        ]);

        $token = $userAdmin->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('api/orders/' . $order->id . '/status', [
            'status' => 'approved',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'destination' => $order->destination,
                'departure_date' => Carbon::parse($order->departure_date)->toDateString(),
                'return_date' => Carbon::parse($order->return_date)->toDateString(),
                'status' => 'approved',
            ],
        ]);
        $response->assertJsonStructure([
            'success',
            'order' => [
                'id',
                'user_id',
                'user_name',
                'destination',
                'departure_date',
                'return_date',
                'status',
            ],
        ]);
    }

    public function test_approve_order_with_non_admin_user(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 'requested',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('api/orders/' . $order->id . '/status', [
            'status' => 'approved',
        ]);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'message',
                'reason',
                'code'
            ]
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertEquals("Access Denied", $response->json('error.message'));
        $this->assertEquals("Only admins can change the status of an order.", $response->json('error.reason'));
        $response->assertStatus(403);
    }

    public function test_approve_order_with_invalid_token(): void
    {
        $userAdmin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 'requested',
        ]);

        $token = 'this_is_an_invalid_token';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('api/orders/' . $order->id . '/status', [
            'status' => 'approved',
        ]);

        $response->assertStatus(401);
    }

    public function test_try_approve_order_with_non_admin_user(): void
    {

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 'requested',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('api/orders/' . $order->id . '/status', [
            'status' => 'approved',
        ]);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'message',
                'reason',
                'code'
            ]
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertEquals("Access Denied", $response->json('error.message'));
        $this->assertEquals("Only admins can change the status of an order.", $response->json('error.reason'));
        $response->assertStatus(403);
    }

    public function test_get_orders_with_valid_token_and_no_orders(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('api/orders');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'orders' => []
        ]);
        $response->assertJsonCount(0, 'orders');
    }

    public function test_get_orders_with_valid_token_and_existing_orders(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        Order::factory()->count(3)->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 'requested',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('api/orders');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'orders' => [
                '*' => [
                    'id',
                    'user_id',
                    'user_name',
                    'destination',
                    'departure_date',
                    'return_date',
                    'status',
                ],
            ],
        ]);
        $response->assertJsonCount(3, 'orders');
    }

    public function test_get_orders_with_invalid_token(): void
    {
        $token = 'this_is_an_invalid_token';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('api/orders');

        $response->assertStatus(401);
    }

    public function test_get_orders_with_filters(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'destination' => 'Paris',
            'status' => 'requested',
        ]);

        Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'destination' => 'London',
            'status' => 'approved',
        ]);

        Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'destination' => 'New York',
            'status' => 'cancelled',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('api/orders?destination=Paris&status=requested');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'orders' => [
                '*' => [
                    'id',
                    'user_id',
                    'user_name',
                    'destination',
                    'departure_date',
                    'return_date',
                    'status',
                ],
            ],
        ]);
        $response->assertJsonCount(1, 'orders');
    }

    public function test_get_orders_with_filters_departure_date_range(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'destination' => 'Paris',
            'departure_date' => Carbon::now()->addDays(5)->toDateString(),
            'return_date' => Carbon::now()->addDays(10)->toDateString(),
            'status' => 'requested',
        ]);

        Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'destination' => 'London',
            'departure_date' => Carbon::now()->addDays(15)->toDateString(),
            'return_date' => Carbon::now()->addDays(20)->toDateString(),
            'status' => 'approved',
        ]);

        Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'destination' => 'New York',
            'departure_date' => Carbon::now()->addDays(25)->toDateString(),
            'return_date' => Carbon::now()->addDays(30)->toDateString(),
            'status' => 'cancelled',
        ]);

        $query = http_build_query([
            'departure_date' => [
                'from' => Carbon::now()->addDays(1)->toDateString(),
                'to' => Carbon::now()->addDays(20)->toDateString(),
            ],
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('api/orders?' . $query);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'orders' => [
                '*' => [
                    'id',
                    'user_id',
                    'user_name',
                    'destination',
                    'departure_date',
                    'return_date',
                    'status',
                ],
            ],
        ]);
        $response->assertJsonCount(2, 'orders');
    }

    public function test_get_orders_with_filters_return_date_range(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'destination' => 'Paris',
            'departure_date' => Carbon::now()->addDays(5)->toDateString(),
            'return_date' => Carbon::now()->addDays(10)->toDateString(),
            'status' => 'requested',
        ]);

        Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'destination' => 'London',
            'departure_date' => Carbon::now()->addDays(15)->toDateString(),
            'return_date' => Carbon::now()->addDays(20)->toDateString(),
            'status' => 'approved',
        ]);

        Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'destination' => 'New York',
            'departure_date' => Carbon::now()->addDays(25)->toDateString(),
            'return_date' => Carbon::now()->addDays(30)->toDateString(),
            'status' => 'cancelled',
        ]);

        $query = http_build_query([
            'return_date' => [
                'from' => Carbon::now()->addDays(1)->toDateString(),
                'to' => Carbon::now()->addDays(15)->toDateString(),
            ],
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('api/orders?' . $query);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'orders' => [
                '*' => [
                    'id',
                    'user_id',
                    'user_name',
                    'destination',
                    'departure_date',
                    'return_date',
                    'status',
                ],
            ],
        ]);
        $response->assertJsonCount(1, 'orders');
    }

    public function test_get_orders_with_filters_and_no_matching_orders(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'destination' => 'Paris',
            'status' => 'requested',
        ]);

        Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'destination' => 'London',
            'status' => 'approved',
        ]);

        Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'destination' => 'New York',
            'status' => 'cancelled',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('api/orders?destination=Tokyo&status=requested');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'orders' => []
        ]);
        $response->assertJsonCount(0, 'orders');
    }

    public function test_get_all_orders_with_admin_user(): void
    {
        $user = User::factory()->create();

        $adminUser = User::factory()->create([
            'role' => 'admin',
        ]);

        $token = $adminUser->createToken('auth_token')->plainTextToken;

        Order::factory()->count(5)->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 'requested',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('api/orders');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'orders' => [
                '*' => [
                    'id',
                    'user_id',
                    'user_name',
                    'destination',
                    'departure_date',
                    'return_date',
                    'status',
                ],
            ],
        ]);
        $response->assertJsonCount(5, 'orders');
    }

    public function test_update_order_with_valid_data(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 'requested',
        ]);

        $departureDate = Carbon::parse($order->departure_date)->addDays(10)->toDateString();
        $returnDate = Carbon::parse($order->return_date)->addDays(20)->toDateString();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('api/orders/' . $order->id, [
            'destination' => 'Tokyo',
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'destination' => 'Tokyo',
                'departure_date' => $departureDate,
                'return_date' => $returnDate,
                'status' => 'requested',
            ],
        ]);
        $response->assertJsonStructure([
            'success',
            'order' => [
                'id',
                'user_id',
                'user_name',
                'destination',
                'departure_date',
                'return_date',
                'status',
            ],
        ]);
    }

    public function test_update_order_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 'requested',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('api/orders/' . $order->id, [
            'destination' => '',
            'departure_date' => 'invalid-date',
            'return_date' => 'invalid-date',
        ]);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'message',
                'fields' => [
                    'destination',
                    'departure_date',
                    'return_date',
                ],
            ]
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertEquals("Validation Error", $response->json('error.message'));
        $this->assertArrayHasKey('destination', $response->json('error.fields'));
        $this->assertArrayHasKey('departure_date', $response->json('error.fields'));
        $this->assertArrayHasKey('return_date', $response->json('error.fields'));
        $response->assertStatus(422);
    }

    public function test_find_order_by_id_with_valid_token_and_existing_order(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 'requested',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('api/orders/' . $order->id);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'destination' => $order->destination,
                'departure_date' => Carbon::parse($order->departure_date)->format('Y-m-d'),
                'return_date' => Carbon::parse($order->return_date)->format('Y-m-d'),
                'status' => 'requested',
            ],
        ]);
        $response->assertJsonStructure([
            'success',
            'order' => [
                'id',
                'user_id',
                'user_name',
                'destination',
                'departure_date',
                'return_date',
                'status',
            ],
        ]);
    }

    public function test_find_order_by_id_with_valid_token_and_non_existing_order(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('api/orders/999');

        $this->assertFalse($response->json('success'));
        $this->assertEquals("Record Not Found", $response->json('error.message'));
        $response->assertStatus(404);
    }
}
