<?php

namespace App\Services\Swap;

use InvalidArgumentException;

class AmountNormalizer
{
    /**
     * Convert a human-readable decimal amount string (e.g. "1.5") to integer base units (e.g. "1500000").
     */
    public static function toBaseUnits(string $humanAmount, int $decimals): string
    {
        $clean = trim($humanAmount);
        if (!preg_match('/^\d+(\.\d+)?$/', $clean)) {
            throw new InvalidArgumentException("Invalid numeric amount [{$humanAmount}].");
        }

        if (bccomp($clean, '0', 18) <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        $multiplier = bcpow('10', (string) $decimals, 0);
        $raw = bcmul($clean, $multiplier, 0);

        if (bccomp($raw, '0', 0) <= 0) {
            throw new InvalidArgumentException('Amount is too small for the asset precision.');
        }

        return $raw;
    }

    /**
     * Convert an integer base-unit string (e.g. "1500000") to a trimmed human decimal string (e.g. "1.5").
     */
    public static function toHumanUnits(string $baseUnits, int $decimals): string
    {
        $clean = preg_replace('/[^0-9]/', '', trim($baseUnits)) ?: '0';

        if ($decimals <= 0) {
            return $clean;
        }

        $divisor = bcpow('10', (string) $decimals, 0);
        $formatted = bcdiv($clean, $divisor, $decimals);

        return self::trimTrailingZeros($formatted);
    }

    public static function trimTrailingZeros(string $decimalString): string
    {
        if (!str_contains($decimalString, '.')) {
            return $decimalString;
        }

        $trimmed = rtrim(rtrim($decimalString, '0'), '.');

        return $trimmed === '' ? '0' : $trimmed;
    }

    public static function calculateExchangeRate(string $amountInHuman, string $amountOutHuman): string
    {
        if (bccomp($amountInHuman, '0', 18) <= 0) {
            return '0';
        }

        return self::trimTrailingZeros(bcdiv($amountOutHuman, $amountInHuman, 12));
    }

    /**
     * Convert raw technical provider error strings into concise, user-friendly messages.
     */
    public static function humanizeProviderError(
        string $rawMessage,
        string $originSymbol,
        int $originDecimals,
        string $originNetworkName,
        string $destSymbol,
        string $destNetworkName,
        ?string $recipientAddress = null,
    ): string {
        $msg = trim($rawMessage);

        if (stripos($msg, 'trustline') !== false) {
            $wallet = $recipientAddress ? trim($recipientAddress) : null;
            if (!$wallet && preg_match('/Destination address:\s*([A-Za-z0-9_\-:]+)/i', $msg, $m)) {
                $wallet = $m[1];
            }

            return $wallet
                ? "Wallet address {$wallet} does not have a trustline for {$destSymbol}."
                : "The destination {$destNetworkName} wallet does not have a trustline for {$destSymbol}.";
        }

        if (preg_match('/try at least\s+(\d+)/i', $msg, $m)) {
            $minHuman = self::toHumanUnits($m[1], $originDecimals);
            return "Amount is too low. Minimum amount for this route is {$minHuman} {$originSymbol}.";
        }

        if (stripos($msg, 'refundTo is not valid') !== false) {
            return "Origin refund wallet address is not valid for {$originNetworkName}.";
        }

        if (stripos($msg, 'recipient is not valid') !== false) {
            return "Destination wallet address is not valid for {$destNetworkName}.";
        }

        if (stripos($msg, 'No liquidity available') !== false || stripos($msg, 'Failed to get quote') !== false) {
            return "No liquidity is currently available to swap {$originSymbol} ({$originNetworkName}) to {$destSymbol} ({$destNetworkName}). Please try a different token pair or amount.";
        }

        return $msg;
    }
}

