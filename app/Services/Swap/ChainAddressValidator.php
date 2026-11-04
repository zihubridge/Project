<?php

namespace App\Services\Swap;

use App\Models\BlockchainNetwork;

class ChainAddressValidator
{
    /**
     * Validate a wallet address and optional memo/tag against a blockchain network's rules.
     *
     * @return array{valid: bool, requires_memo_or_tag: bool, message: string}
     */
    public function validate(BlockchainNetwork $network, string $address, ?string $memo = null): array
    {
        $trimmed = trim($address);

        if ($trimmed === '' || strlen($trimmed) > 191) {
            return [
                'valid' => false,
                'requires_memo_or_tag' => $network->requires_memo_or_tag,
                'message' => 'Address cannot be empty or exceed 191 characters.',
            ];
        }

        // Prevent path traversal or control characters
        if (preg_match('/[\s\/\\\\\?\&#%]/', $trimmed)) {
            return [
                'valid' => false,
                'requires_memo_or_tag' => $network->requires_memo_or_tag,
                'message' => 'Address contains invalid characters.',
            ];
        }

        $regex = $network->address_regex
            ?: ChainMetadataCatalog::resolveNetworkMetadata($network->slug)['address_regex'];

        if ($regex && !preg_match($regex, $trimmed)) {
            return [
                'valid' => false,
                'requires_memo_or_tag' => $network->requires_memo_or_tag,
                'message' => "Address format is invalid for {$network->display_name}.",
            ];
        }

        // Deep cryptographic checksum validation for Stellar addresses
        if ($network->chain_family === 'stellar' || strtolower($network->slug) === 'stellar') {
            if (!self::isValidStellarAddress($trimmed)) {
                return [
                    'valid' => false,
                    'requires_memo_or_tag' => $network->requires_memo_or_tag,
                    'message' => 'Invalid Stellar address',
                ];
            }
        }

        if ($memo !== null && trim($memo) !== '') {
            $cleanMemo = trim($memo);
            if (strlen($cleanMemo) > 64) {
                return [
                    'valid' => false,
                    'requires_memo_or_tag' => $network->requires_memo_or_tag,
                    'message' => 'Memo / Destination Tag cannot exceed 64 characters.',
                ];
            }

            if ($network->chain_family === 'xrpl' && (!ctype_digit($cleanMemo) || (int) $cleanMemo > 4294967295)) {
                return [
                    'valid' => false,
                    'requires_memo_or_tag' => $network->requires_memo_or_tag,
                    'message' => 'XRP Ledger Destination Tag must be an unsigned 32-bit integer.',
                ];
            }
        }

        return [
            'valid' => true,
            'requires_memo_or_tag' => $network->requires_memo_or_tag,
            'message' => "Valid {$network->display_name} address.",
        ];
    }

    /**
     * Cryptographically validate a Stellar address using StrKey Base32 and CRC16-XModem checksum.
     */
    public static function isValidStellarAddress(string $address): bool
    {
        $address = strtoupper(trim($address));
        $len = strlen($address);

        // Standard Ed25519 public key starts with G (56 chars)
        // Multiplexed address starts with M (69 chars)
        if ($len === 56 && $address[0] === 'G') {
            $expectedVersion = 0x30; // 6 << 3 = 48 (ed25519PublicKey)
            $expectedBytes = 35;
        } elseif ($len === 69 && $address[0] === 'M') {
            $expectedVersion = 0x60; // 12 << 3 = 96 (med25519PublicKey)
            $expectedBytes = 43;
        } else {
            return false;
        }

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $v = 0;
        $bits = 0;
        $decoded = [];

        for ($i = 0; $i < $len; $i++) {
            $val = strpos($alphabet, $address[$i]);
            if ($val === false) {
                return false;
            }
            $v = ($v << 5) | $val;
            $bits += 5;
            if ($bits >= 8) {
                $decoded[] = ($v >> ($bits - 8)) & 0xFF;
                $bits -= 8;
            }
        }

        if (count($decoded) !== $expectedBytes) {
            return false;
        }

        if ($decoded[0] !== $expectedVersion) {
            return false;
        }

        // CRC16-XModem calculation over version + payload
        $crc = 0x0000;
        $payloadLen = $expectedBytes - 2;
        for ($i = 0; $i < $payloadLen; $i++) {
            $code = ($crc >> 8) & 0xFF;
            $code ^= $decoded[$i];
            $code ^= $code >> 4;
            $crc = ($crc << 8) & 0xFFFF;
            $crc ^= $code;
            $code = ($code << 5) & 0xFFFF;
            $crc ^= $code;
            $code = ($code << 7) & 0xFFFF;
            $crc ^= $code;
        }
        $crc &= 0xFFFF;

        $expectedChecksum = $decoded[$expectedBytes - 2] | ($decoded[$expectedBytes - 1] << 8);

        return $crc === $expectedChecksum;
    }
}

