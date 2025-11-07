<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Events;

use CleaniqueCoders\Eligify\Models\Rule;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RuleExecuted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Rule $rule;

    public array $data;

    public array $result;

    /**
     * Create a new event instance.
     */
    public function __construct(Rule $rule, array $data, array $result)
    {
        $this->rule = $rule;
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
