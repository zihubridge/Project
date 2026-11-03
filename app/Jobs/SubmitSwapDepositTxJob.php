<?php

namespace App\Jobs;

use App\Models\SwapOrder;
use App\Services\Providers\SwapProviderRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubmitSwapDepositTxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 25;

    public function __construct(
        public readonly int $swapOrderId,
        public readonly string $txHash,
    ) {}

    public function handle(SwapProviderRegistry $registry): void
    {
        $order = SwapOrder::with('provider')->find($this->swapOrderId);
        if (!$order || !$order->provider) {
            return;
        }

        $provider = $registry->get($order->provider->slug);
        $provider->submitDepositTransaction($order, $this->txHash);
    }
}

