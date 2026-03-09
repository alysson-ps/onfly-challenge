<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Notifications\OrderCancelled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationOrderCancelledTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_order_cancelled_renders_correctly(): void
    {
        $order = Order::factory()->create([
            'status' => 'cancelled',
        ]);

        $notification = new OrderCancelled($order);

        $channels = $notification->via($order->user);
        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);

        $mailMessage = $notification->toMail($order->user);
        $this->assertEquals('Your Order Has Been Cancelled', $mailMessage->subject);
        $this->assertStringContainsString('Hi ' . $order->user->name, $mailMessage->introLines[0]);
        $this->assertStringContainsString('If you have any questions, please contact support.', $mailMessage->introLines[1]);

        $databaseMessage = $notification->toArray($order->user);
        $this->assertEquals('Hi ' . $order->user->name . ', Your order has been cancelled.', $databaseMessage['message']);
        $this->assertEquals($order->id, $databaseMessage['order_id']);
    }
}
