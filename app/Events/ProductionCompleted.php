<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductionCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $productId,
        public int $batches,
        public float $cogsPerUnit
    ) {}
}

