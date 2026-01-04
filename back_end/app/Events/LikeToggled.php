<?php

namespace App\Events;

use App\Models\Like;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LikeToggled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $postId;
    public $likesCount;
    public $action; // 'liked' or 'unliked'

    public function __construct($postId, $likesCount, $action)
    {
        $this->postId = $postId;
        $this->likesCount = $likesCount;
        $this->action = $action;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('posts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'like.toggled';
    }
}
