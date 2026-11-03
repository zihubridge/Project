<?php

namespace App\Jobs;

use App\Models\SwapOrder;
use App\Services\Swap\SwapExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollSwapStatusJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 90;
    public int $timeout = 30;
    public int $uniqueFor = 15;

    public function __construct(
        public readonly int $swapOrderId,
    ) {}

    public function uniqueId(): string
    {
        return "poll_swap_order:{$this->swapOrderId}";
    }

    public function handle(SwapExecutionService $executionService): void
    {
        $order = SwapOrder::find($this->swapOrderId);
        if (!$order || $order->isTerminal()) {
            return;
        }

        try {
            $updated = $executionService->syncOrderStatus($order, 'SYSTEM_POLLER');

            if (!$updated->isTerminal()) {
                $this->release(15);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to poll swap status', [
                'swap_order_id' => $this->swapOrderId,
                'error' => $e->getMessage(),
            ]);

            $this->release(20);
        }
    }
}

