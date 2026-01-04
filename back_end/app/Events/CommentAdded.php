<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;
    public $postId;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment->load('user');
        $this->postId = $comment->id_post;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('posts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.added';
    }
}
