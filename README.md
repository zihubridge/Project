<p align="center">
  <img src="public/assets/images/logo.png" width="200" alt="ZihuBridge Logo">
</p>

<p align="center">
  Trustless cross-chain swaps between <b>Stellar</b> and <b>XRPL</b>
</p>

# ZihuBridge

ZihuBridge is a cross-chain bridge enabling token swaps between 
Stellar and XRPL. The platform is live on mainnet with completed 
end-to-end swap flows running in production.

We are currently building a trustless atomic swap architecture 
powered by Soroban smart contracts that replaces centralized 
settlement with on-chain guarantees.

## Current Status

Live on Stellar mainnet and XRPL with real transaction flows.

Soroban HTLC contract deployed to Stellar testnet:
Contract ID: CCDPW5KIRBXYLM5WKND5M6FCEN3LARRQOH3YVK45DVVCLBA4BBK2ZHLI
Explorer: https://stellar.expert/explorer/testnet/contract/CCDPW5KIRBXYLM5WKND5M6FCEN3LARRQOH3YVK45DVVCLBA4BBK2ZHLI

Smart contract source and documentation:
https://github.com/zihubridge/Project/tree/feature/soroban-htlc/contracts/htlc

## Features

- Cross-chain token swaps (Stellar to XRPL and XRPL to Stellar)
- Memo and Destination Tag based routing
- Automatic deposit detection
- Internal token conversion via Stellar DEX
- Real-time 7-step status pipeline
- Refund system for expired swaps
- Token Listing Escrow (in development)

## Roadmap

Phase 1 (Completed): Live bridge with real mainnet swap flows

Phase 2 (Completed): Interactive swap interface, trustline 
validation, multi-asset support

Phase 3 (In Progress): Trustless atomic swap mechanism via 
Soroban HTLC contract, Token Listing Escrow, XRPL native 
escrow integration, on-chain pricing engine

Phase 4 (Planned): Expanded token pairs, ZihuBridge API, 
analytics dashboard

## Tech Stack

- Soroban / Rust (smart contracts)
- Laravel 12 (backend orchestration)
- Stellar SDK (PHP)
- XRPL RPC
- Queue-based architecture

## Links

Website: https://zihubridge.com
X: https://x.com/ZihuBridge
GitHub: https://github.com/zihubridge/Project