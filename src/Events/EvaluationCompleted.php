<?php

namespace CleaniqueCoders\Eligify\Events;

use CleaniqueCoders\Eligify\Models\Criteria;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EvaluationCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Criteria $criteria;

    public array $data;

    public array $result;

    /**
     * Create a new event instance.
     */
    public function __construct(Criteria $criteria, array $data, array $result)
    {
        $this->criteria = $criteria;
        $this->data = $data;
        $this->result = $result;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
