<?php

namespace Tests\Unit;

use App\Models\BlockchainNetwork;
use App\Services\Swap\AmountNormalizer;
use App\Services\Swap\ChainAddressValidator;
use PHPUnit\Framework\TestCase;

class AmountAndAddressTest extends TestCase
{
    public function test_amount_normalizer_converts_human_to_base_units_accurately(): void
    {
        $this->assertSame('1500000', AmountNormalizer::toBaseUnits('1.5', 6));
        $this->assertSame('1000000000000000000', AmountNormalizer::toBaseUnits('1', 18));
        $this->assertSame('12345670', AmountNormalizer::toBaseUnits('1.234567', 7));
    }

    public function test_amount_normalizer_converts_base_units_to_human_decimals(): void
    {
        $this->assertSame('1.5', AmountNormalizer::toHumanUnits('1500000', 6));
        $this->assertSame('1', AmountNormalizer::toHumanUnits('1000000000000000000', 18));
        $this->assertSame('0.00025', AmountNormalizer::toHumanUnits('25000', 8));
    }

    public function test_chain_address_validator_validates_multiple_blockchain_families(): void
    {
        $validator = new ChainAddressValidator();

        $eth = new BlockchainNetwork([
            'slug' => 'eth',
            'display_name' => 'Ethereum',
            'chain_family' => 'evm',
            'requires_memo_or_tag' => false,
            'address_regex' => '/^0x[a-fA-F0-9]{40}$/',
        ]);

        $this->assertTrue($validator->validate($eth, '0x71C7656EC7ab88b098defB751B7401B5f6d8976F')['valid']);
        $this->assertFalse($validator->validate($eth, '0x12345')['valid']);
        $this->assertFalse($validator->validate($eth, '0x71C7656EC7ab88b098defB751B7401B5f6d8976F/../admin')['valid']);

        $sol = new BlockchainNetwork([
            'slug' => 'sol',
            'display_name' => 'Solana',
            'chain_family' => 'solana',
            'requires_memo_or_tag' => false,
            'address_regex' => '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/',
        ]);

        $this->assertTrue($validator->validate($sol, '13QkxhNMrTPxoCkRdYdJ65tFuwXPhL5gLS2Z5Nr6gjRK')['valid']);

        $xrp = new BlockchainNetwork([
            'slug' => 'xrp',
            'display_name' => 'XRP Ledger',
            'chain_family' => 'xrpl',
            'requires_memo_or_tag' => true,
            'address_regex' => '/^(r[1-9A-HJ-NP-Za-km-z]{24,34}|[XT][1-9A-HJ-NP-Za-km-z]{46})$/',
        ]);

        $this->assertTrue($validator->validate($xrp, 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh', '123456')['valid']);
        $this->assertFalse($validator->validate($xrp, 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh', 'non-numeric-tag')['valid']);

        $stellar = new BlockchainNetwork([
            'slug' => 'stellar',
            'display_name' => 'Stellar',
            'chain_family' => 'stellar',
            'requires_memo_or_tag' => true,
            'address_regex' => '/^[GM][A-Z2-7]{55,68}$/',
        ]);

        // Valid Stellar addresses
        $this->assertTrue($validator->validate($stellar, 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN')['valid']);
        $this->assertTrue($validator->validate($stellar, 'GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5')['valid']);

        // Invalid Stellar address with invalid CRC checksum (must be rejected)
        $this->assertFalse($validator->validate($stellar, 'GDBY24O7XLV5HN56D4O24F6G6RHXL7EVJVEQFPYYEW4RA5RBPXYOBKA3')['valid']);
    }

    public function test_humanizes_provider_trustline_and_minimum_amount_errors(): void
    {
        $rawTrustlineError = 'Destination address does not have a trustline for this asset. Blockchain: stellar:pubnet Asset ID: nep245:v2_1.omni.hot.tg:1100_111bzQBB65GxAPAVoxqmMcgYo5oS3txhqs1Uh1cgahKQUeTUq1TJu Destination address: GDBY24O7XLV5HN56D4O24F6G6RHXL7EVJVEQFPYYEW4RA5RBPXYOBKA4 Token address: CCW67TSZV3SSS2HXMBQ5JFGCKJNXKZM7UQUWUZPUTHXSTZLEO7SJMI75 Details: The destination address must establish a trustline before receiving this asset.';

        $clean = AmountNormalizer::humanizeProviderError(
            rawMessage: $rawTrustlineError,
            originSymbol: 'WNEAR',
            originDecimals: 24,
            originNetworkName: 'NEAR Protocol',
            destSymbol: 'USDC',
            destNetworkName: 'Stellar',
            recipientAddress: 'GDBY24O7XLV5HN56D4O24F6G6RHXL7EVJVEQFPYYEW4RA5RBPXYOBKA4',
        );

        $this->assertSame(
            'Wallet address GDBY24O7XLV5HN56D4O24F6G6RHXL7EVJVEQFPYYEW4RA5RBPXYOBKA4 does not have a trustline for USDC.',
            $clean
        );
    }
}

