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
}

