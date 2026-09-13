<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FinancierasUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $section;
    public string $action;
    public string $message;
    public array $data;
    public string $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(string $section, string $action, string $message, array $data = [])
    {
        $this->section = $section;
        $this->action = $action;
        $this->message = $message;
        $this->data = $data;
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('financieras'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'financieras.updated';
    }
}
