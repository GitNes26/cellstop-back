<?php

// app/Events/NewNotification.php
namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class NewNotification implements ShouldBroadcast
{
   use Dispatchable, InteractsWithSockets, SerializesModels;

   public $notification;
   public $userIds;

   public function __construct(Notification $notification, array $userIds)
   {
      $this->notification = $notification;
      $this->userIds = $userIds;
   }

   public function broadcastOn()
   {
      // Canal privado por usuario
      return collect($this->userIds)->map(fn($id) => new PrivateChannel("user.{$id}"))->toArray();
   }

   public function broadcastAs()
   {
      return 'new-notification';
   }
}
