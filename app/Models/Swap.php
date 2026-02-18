<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Swap extends Model
{
    protected $guarded = [];

    public function fromBlockchain()
    {
        return $this->belongsTo(Blockchain::class, 'from_blockchain_id');
    }

    public function toBlockchain()
    {
        return $this->belongsTo(Blockchain::class, 'to_blockchain_id');
    }

    public function fromToken()
    {
        return $this->belongsTo(Token::class, 'from_token_id');
    }

    public function toToken()
    {
        return $this->belongsTo(Token::class, 'to_token_id');
    }

    public function deposit()
    {
        return $this->hasOne(SwapDeposit::class);
    }

    public function exchange()
    {
        return $this->hasOne(SwapExchange::class);
    }

    public function payout()
    {
        return $this->hasOne(SwapPayout::class);
    }

    public function events()
    {
        return $this->hasMany(SwapEvent::class);
    }

    public function swapState()
    {
        return $this->belongsTo(SwapState::class, 'swap_state_id');
    }

    public function internalSwaps()
    {
        return $this->hasMany(InternalSwap::class);
    }

    public function sourceInternalSwap()
    {
        return $this->hasOne(InternalSwap::class)
            ->where('leg', 'source');
    }

    public function destinationInternalSwap()
    {
        return $this->hasOne(InternalSwap::class)
            ->where('leg', 'destination');
    }

    public function getFrontendStep(): int
    {
        return match($this->swapState->name ?? '') {
            'created', 'waiting_deposit' => 1,
            'deposit_confirmed' => 2,
            'internal_swap_started', 'internal_swap_completed' => 3,
            'provider_processing' => 4,
            'provider_completed' => 5,
            'payout_processing' => 6,
            'completed' => 7,
            'expired', 'failed', 'refunded' => 0,
            default => 1,
        };
    }

    /**
     * Get human-readable step name for frontend display
     */
    public function getFrontendStepName(): string
    {
        return match($this->getFrontendStep()) {
            1 => 'Awaiting Deposit',
            2 => 'Deposit Confirmed',
            3 => 'Swapping to Coin',
            4 => 'Exchanging Coins',
            5 => 'Swapping to Token',
            6 => 'Sending Tokens',
            7 => 'Completed',
            0 => 'Failed',
            default => 'Processing',
        };
    }
}
