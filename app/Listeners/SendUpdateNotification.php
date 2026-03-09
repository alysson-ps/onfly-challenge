<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Notifications\OrderApproved;
use App\Notifications\OrderCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendUpdateNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if ($order->status === 'cancelled') {
            $user->notify(new OrderCancelled($order));
        } else if ($order->status === 'approved') {
            $user->notify(new OrderApproved($order));
        }
    }
}
