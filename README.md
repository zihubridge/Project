<p align="center">
  <img src="/assets/images/logo.png" width="200" alt="ZihuBridge Logo">
</p>

<h1 align="center">ZihuBridge</h1>

<p align="center">
  Swap tokens between <b>Stellar</b> and <b>XRPL</b> — without centralized exchanges.
</p>

---

# ZihuBridge

ZihuBridge is a cross-chain bridge that allows users to swap tokens between Stellar and XRPL seamlessly.

It enables:
- Stellar → XRPL token swaps
- XRPL → Stellar token swaps
- Automatic routing using on-chain liquidity + ChangeNOW

---

## Features

- Cross-chain token swaps (Stellar ↔ XRPL)
- Memo / Destination Tag based routing
- Automatic deposit detection
- Internal token conversion (DEX / AMM)
- External provider integration (ChangeNOW)
- Refund system for expired swaps

---

## How It Works

1. User initiates swap
2. Unique memo / destination tag is generated
3. User deposits tokens
4. System detects deposit via blockchain scanner
5. Internal swap (Token → XLM/XRP)
6. External swap via ChangeNOW
7. Final token delivered to destination wallet

---

## Tech Stack

- Laravel 12
- Stellar SDK (PHP)
- XRPL RPC
- ChangeNOW API
- Queue-based architecture