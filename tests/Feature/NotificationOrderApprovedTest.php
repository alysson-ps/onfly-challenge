<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Notifications\OrderApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationOrderApprovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_order_approved_renders_correctly(): void
    {
        $order = Order::factory()->create([
            'status' => 'approved',
        ]);

        $notification = new OrderApproved($order);

        $channels = $notification->via($order->user);
        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);

        $mailMessage = $notification->toMail($order->user);
        $this->assertEquals('Your Order Has Been Approved', $mailMessage->subject);
        $this->assertStringContainsString('Hi ' . $order->user->name, $mailMessage->introLines[0]);
        $this->assertStringContainsString('We will contact you with further details soon.', $mailMessage->introLines[1]);

        $databaseMessage = $notification->toArray($order->user);
        $this->assertEquals('Hi ' . $order->user->name . ', Your order has been approved.', $databaseMessage['message']);
        $this->assertEquals($order->id, $databaseMessage['order_id']);
    }
}
