<?php

namespace App\Services\Providers;

use App\Services\Providers\Contracts\SwapExecutionProviderInterface;
use InvalidArgumentException;

class SwapProviderRegistry
{
    /**
     * @var array<string, SwapExecutionProviderInterface>
     */
    private array $providers = [];

    public function register(SwapExecutionProviderInterface $provider): void
    {
        $this->providers[$provider->getProviderSlug()] = $provider;
    }

    public function get(string $slug): SwapExecutionProviderInterface
    {
        if (!isset($this->providers[$slug])) {
            throw new InvalidArgumentException("Swap execution provider [{$slug}] is not registered.");
        }

        return $this->providers[$slug];
    }

    public function getDefault(): SwapExecutionProviderInterface
    {
        $defaultSlug = (string) config('services.swap.default_provider', 'near_intents');

        return $this->get($defaultSlug);
    }

    /**
     * @return array<string, SwapExecutionProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }
}

