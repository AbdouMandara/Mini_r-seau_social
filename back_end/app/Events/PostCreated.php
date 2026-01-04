<?php

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $post;

    public function __construct(Post $post)
    {
        $this->post = $post->load('user');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('posts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'post.created';
    }
}
