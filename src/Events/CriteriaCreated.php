<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Events;

use CleaniqueCoders\Eligify\Models\Criteria;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CriteriaCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Criteria $criteria;

    /**
     * Create a new event instance.
     */
    public function __construct(Criteria $criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
