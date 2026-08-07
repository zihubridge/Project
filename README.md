<p align="center">
  <img src="public/assets/images/logo.png" width="200" alt="ZihuBridge Logo">
</p>

<p align="center">
  Cross-chain swaps between <b>Stellar</b> and <b>XRPL</b>
</p>

# ZihuBridge

ZihuBridge is a live cross-chain bridge enabling token swaps 
between Stellar and XRPL, running in production on mainnet.

## Current Architecture

The current bridge uses ChangeNOW for cross-chain settlement. 
This is being replaced with a trustless atomic swap architecture 
powered by Soroban smart contracts. Development is underway on 
the feature/soroban-htlc branch.

## Upcoming: Trustless Atomic Swap Architecture

We are replacing ChangeNOW with a native atomic swap mechanism:

User deposits go into a Soroban HTLC contract instead of a 
backend wallet. Funds are locked with a cryptographic hash lock. 
The swap executes only after the XRPL side confirms. If anything 
fails, the contract auto-refunds the user. Nobody can block it.

Soroban HTLC contract already deployed to Stellar testnet:

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

Phase 3 (In Progress): Replace ChangeNOW with trustless atomic 
swap mechanism via Soroban HTLC contract, Token Listing Escrow, 
XRPL native escrow integration, on-chain pricing engine

Phase 4 (Planned): Expanded token pairs, ZihuBridge API, 
analytics dashboard

## Tech Stack

- Soroban / Rust (smart contracts — in development)
- Laravel 12 (backend orchestration)
- Stellar SDK (PHP)
- XRPL RPC
- Queue-based architecture

## Links

Website: https://zihubridge.com
X: https://x.com/ZihuBridge
Smart Contract Branch: https://github.com/zihubridge/Project/tree/feature/soroban-htlc