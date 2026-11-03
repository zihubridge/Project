<?php

namespace App\Services\Providers;

use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Services\Providers\Adapters\EvmDexRouterAdapter;
use App\Services\Providers\Adapters\NearRefFinanceAdapter;
use App\Services\Providers\Adapters\SolanaJupiterAdapter;
use App\Services\Providers\Adapters\StellarSdexAdapter;
use App\Services\Providers\Adapters\XrplDexAdapter;
use App\Services\Providers\Contracts\LocalChainExecutionAdapterInterface;
use InvalidArgumentException;

class LocalChainAdapterRegistry
{
    /** @var array<string, LocalChainExecutionAdapterInterface> */
    private array $adapters = [];

    public function __construct()
    {
        $this->register(new StellarSdexAdapter());
        $this->register(new XrplDexAdapter());
        $this->register(new EvmDexRouterAdapter());
        $this->register(new SolanaJupiterAdapter());
        $this->register(new NearRefFinanceAdapter());
    }

    public function register(LocalChainExecutionAdapterInterface $adapter): void
    {
        $this->adapters[$adapter->getAdapterKey()] = $adapter;
    }

    public function get(string $key): LocalChainExecutionAdapterInterface
    {
        if (!isset($this->adapters[$key])) {
            throw new InvalidArgumentException("Execution adapter '{$key}' is not registered.");
        }

        return $this->adapters[$key];
    }

    public function has(string $key): bool
    {
        return isset($this->adapters[$key]);
    }

    /**
     * @return array<string, LocalChainExecutionAdapterInterface>
     */
    public function all(): array
    {
        return $this->adapters;
    }

    /**
     * Find an adapter suitable for this blockchain network and asset pair.
     */
    public function findAdapter(BlockchainNetwork $network, CryptoAsset $intermediate, CryptoAsset $dest): ?LocalChainExecutionAdapterInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($network, $intermediate, $dest)) {
                return $adapter;
            }
        }

        return null;
    }
}

