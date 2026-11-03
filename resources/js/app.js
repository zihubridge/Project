/**
 * ZihuBridge Non-Custodial Cross-Chain Swap Frontend Application
 */

const STORAGE_KEYS = {
    WALLET: 'zihubridge_connected_wallet_v2',
    RECENT_SWAPS: 'zihubridge_recent_swaps_v2',
};

const appState = {
    networks: [],
    sourceAssets: [],
    destAssets: [],
    selectedSourceAsset: null,
    selectedDestAsset: null,
    slippageBps: 100,
    currentQuote: null,
    quoteDebounceTimer: null,
    wallet: loadSavedWallet(),
};

document.addEventListener('DOMContentLoaded', () => {
    initLayoutAndWalletModal();
    initQuickTrackForm();
    initCopyButtons();

    if (document.getElementById('swapTerminalApp')) {
        initSwapTerminal();
    }

    if (document.getElementById('swapStatusPage')) {
        initSwapStatusTracker();
    }

    if (document.getElementById('swapHistoryPage')) {
        initSwapHistoryPage();
    }
});

/* ==========================================================================
   1. Non-Custodial Wallet Connection Manager
   ========================================================================== */

function loadSavedWallet() {
    try {
        const raw = localStorage.getItem(STORAGE_KEYS.WALLET);
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function saveWallet(walletData) {
    appState.wallet = walletData;
    try {
        if (walletData) {
            localStorage.setItem(STORAGE_KEYS.WALLET, JSON.stringify(walletData));
        } else {
            localStorage.removeItem(STORAGE_KEYS.WALLET);
        }
    } catch {
        // Ignore storage errors
    }
    renderWalletBadge();
}

function shortenAddress(addr) {
    if (!addr || addr.length < 12) return addr || '';
    return `${addr.slice(0, 6)}...${addr.slice(-4)}`;
}

function renderWalletBadge() {
    const badgeText = document.getElementById('connectedWalletBadgeText');
    const connectedCard = document.getElementById('walletConnectedCard');
    const providerNameEl = document.getElementById('walletConnectedProviderName');
    const addrDisplayEl = document.getElementById('walletConnectedAddressDisplay');
    const useConnectedRefundBtn = document.getElementById('useConnectedWalletForRefundBtn');

    if (appState.wallet && appState.wallet.address) {
        if (badgeText) {
            badgeText.textContent = `${appState.wallet.providerLabel}: ${shortenAddress(appState.wallet.address)}`;
        }
        if (connectedCard) connectedCard.classList.remove('hidden');
        if (providerNameEl) providerNameEl.textContent = appState.wallet.providerLabel;
        if (addrDisplayEl) addrDisplayEl.textContent = appState.wallet.address;
        if (useConnectedRefundBtn) useConnectedRefundBtn.classList.remove('hidden');
    } else {
        if (badgeText) badgeText.textContent = 'Connect Wallet';
        if (connectedCard) connectedCard.classList.add('hidden');
        if (useConnectedRefundBtn) useConnectedRefundBtn.classList.add('hidden');
    }
}

function initLayoutAndWalletModal() {
    renderWalletBadge();

    const mobileBtn = document.getElementById('mobileMenuBtn');
    const mobileDrawer = document.getElementById('mobileMenuDrawer');
    if (mobileBtn && mobileDrawer) {
        mobileBtn.addEventListener('click', () => {
            mobileDrawer.classList.toggle('hidden');
        });
    }

    const modal = document.getElementById('walletConnectModal');
    const openBtn = document.getElementById('openWalletModalBtn');
    const closeBtn = document.getElementById('closeWalletModalBtn');
    const disconnectBtn = document.getElementById('disconnectWalletBtn');
    const errorEl = document.getElementById('walletModalError');

    if (openBtn && modal) {
        openBtn.addEventListener('click', () => {
            if (errorEl) errorEl.classList.add('hidden');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    }

    if (closeBtn && modal) {
        closeBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    }

    if (disconnectBtn) {
        disconnectBtn.addEventListener('click', () => {
            saveWallet(null);
        });
    }

    document.querySelectorAll('[data-wallet-connect]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const type = btn.getAttribute('data-wallet-connect');
            if (errorEl) errorEl.classList.add('hidden');

            try {
                if (type === 'evm') {
                    await connectEvmWallet();
                } else if (type === 'solana') {
                    await connectSolanaWallet();
                } else if (type === 'stellar') {
                    await connectStellarWallet();
                }

                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }

                autoPopulateWalletAddresses();
            } catch (err) {
                if (errorEl) {
                    errorEl.textContent = err.message || 'Failed to connect wallet.';
                    errorEl.classList.remove('hidden');
                }
            }
        });
    });
}

async function connectEvmWallet() {
    if (!window.ethereum) {
        throw new Error('No EVM wallet extension detected (install MetaMask, Rabby, or Coinbase Wallet).');
    }
    const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
    if (!accounts || !accounts.length) {
        throw new Error('No account returned by EVM wallet.');
    }
    saveWallet({
        family: 'evm',
        providerLabel: 'EVM Wallet',
        address: accounts[0],
    });
}

async function connectSolanaWallet() {
    const provider = window.solana || (window.phantom && window.phantom.solana);
    if (!provider) {
        throw new Error('No Solana wallet detected (install Phantom or Solflare).');
    }
    const resp = await provider.connect();
    const pubKey = resp?.publicKey?.toString() || provider.publicKey?.toString();
    if (!pubKey) {
        throw new Error('Could not read Solana public key.');
    }
    saveWallet({
        family: 'solana',
        providerLabel: 'Solana',
        address: pubKey,
    });
}

async function connectStellarWallet() {
    if (!window.freighterApi) {
        throw new Error('Freighter wallet extension not detected.');
    }
    const allowed = await window.freighterApi.setAllowed();
    if (!allowed) {
        throw new Error('Freighter access was declined.');
    }
    const pubKeyResp = await window.freighterApi.getPublicKey();
    const address = typeof pubKeyResp === 'string' ? pubKeyResp : pubKeyResp?.publicKey;
    if (!address) {
        throw new Error('Unable to retrieve Stellar address from Freighter.');
    }
    saveWallet({
        family: 'stellar',
        providerLabel: 'Freighter',
        address,
    });
}

function autoPopulateWalletAddresses() {
    if (!appState.wallet) return;

    const refundInput = document.getElementById('refundAddressInput');
    const recipientInput = document.getElementById('recipientAddressInput');
    const walletAddr = (appState.wallet.address || '').trim();

    if (refundInput && appState.selectedSourceAsset) {
        const currentVal = refundInput.value.trim();
        const matchesFamily = appState.selectedSourceAsset.chain_family === appState.wallet.family;
        if (!currentVal && matchesFamily) {
            refundInput.value = walletAddr;
        } else if (currentVal === walletAddr && !matchesFamily) {
            refundInput.value = '';
        }
    }

    if (recipientInput && appState.selectedDestAsset) {
        const currentVal = recipientInput.value.trim();
        const matchesFamily = appState.selectedDestAsset.chain_family === appState.wallet.family;
        if (!currentVal && matchesFamily) {
            recipientInput.value = walletAddr;
        } else if (currentVal === walletAddr && !matchesFamily) {
            recipientInput.value = '';
        }
    }
}

/* ==========================================================================
   2. Cross-Chain Swap Terminal (/ and /swap)
   ========================================================================== */

async function initSwapTerminal() {
    const sourceNetSelect = document.getElementById('sourceNetworkSelect');
    const destNetSelect = document.getElementById('destNetworkSelect');
    const sourceAssetSelect = document.getElementById('sourceAssetSelect');
    const destAssetSelect = document.getElementById('destAssetSelect');
    const amountInput = document.getElementById('swapAmountInput');
    const flipBtn = document.getElementById('flipSwapDirectionBtn');
    const refreshQuoteBtn = document.getElementById('refreshQuoteBtn');
    const initiateSwapBtn = document.getElementById('initiateSwapBtn');
    const recipientInput = document.getElementById('recipientAddressInput');
    const refundInput = document.getElementById('refundAddressInput');
    const useConnectedRefundBtn = document.getElementById('useConnectedWalletForRefundBtn');

    document.querySelectorAll('.slippage-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            appState.slippageBps = parseInt(btn.getAttribute('data-slippage-bps') || '100', 10);
            document.querySelectorAll('.slippage-btn').forEach((b) => {
                b.className = 'slippage-btn px-2.5 py-1 rounded-lg text-slate-300 hover:text-white cursor-pointer';
            });
            btn.className = 'slippage-btn px-2.5 py-1 rounded-lg bg-cyan-500/20 text-cyan-300 font-semibold cursor-pointer';
            scheduleQuoteRefresh();
        });
    });

    if (useConnectedRefundBtn && refundInput) {
        useConnectedRefundBtn.addEventListener('click', () => {
            if (appState.wallet?.address) {
                refundInput.value = appState.wallet.address;
                validateAddressField('refund');
            }
        });
    }

    try {
        const netRes = await fetchJson('/api/v1/networks');
        appState.networks = (netRes.data || []).sort(
            (a, b) => (b.active_assets_count || 0) - (a.active_assets_count || 0)
        );

        const totalNetworksEl = document.getElementById('statActiveNetworks');
        const totalAssetsEl = document.getElementById('statActiveAssets');
        if (totalNetworksEl) totalNetworksEl.textContent = String(appState.networks.length);
        if (totalAssetsEl) {
            const sumAssets = appState.networks.reduce((acc, n) => acc + (n.active_assets_count || 0), 0);
            totalAssetsEl.textContent = String(sumAssets);
        }

        if (!appState.networks.length) {
            showSwapError('No supported networks are currently available in the catalog.');
            return;
        }

        populateNetworkSelect(sourceNetSelect, appState.networks, 0);
        populateNetworkSelect(destNetSelect, appState.networks, appState.networks.length > 1 ? 1 : 0);

        await loadAssetsForSide('source', sourceNetSelect.value);
        await loadAssetsForSide('dest', destNetSelect.value);

        await requestLiveQuote();
    } catch (err) {
        showSwapError(err.message || 'Failed to load supported networks and assets.');
    }

    sourceNetSelect.addEventListener('change', async () => {
        await loadAssetsForSide('source', sourceNetSelect.value);
        scheduleQuoteRefresh();
    });

    destNetSelect.addEventListener('change', async () => {
        await loadAssetsForSide('dest', destNetSelect.value);
        scheduleQuoteRefresh();
    });

    sourceAssetSelect.addEventListener('change', () => {
        const id = parseInt(sourceAssetSelect.value, 10);
        appState.selectedSourceAsset = appState.sourceAssets.find((a) => a.id === id) || null;
        updateSelectedAssetLabels();
        scheduleQuoteRefresh();
    });

    destAssetSelect.addEventListener('change', () => {
        const id = parseInt(destAssetSelect.value, 10);
        appState.selectedDestAsset = appState.destAssets.find((a) => a.id === id) || null;
        updateSelectedAssetLabels();
        scheduleQuoteRefresh();
    });

    amountInput.addEventListener('input', () => {
        scheduleQuoteRefresh();
    });

    if (flipBtn) {
        flipBtn.addEventListener('click', async () => {
            const prevSourceNet = sourceNetSelect.value;
            const prevDestNet = destNetSelect.value;
            const prevSourceAssetId = sourceAssetSelect.value;
            const prevDestAssetId = destAssetSelect.value;

            sourceNetSelect.value = prevDestNet;
            destNetSelect.value = prevSourceNet;

            await loadAssetsForSide('source', sourceNetSelect.value, parseInt(prevDestAssetId, 10));
            await loadAssetsForSide('dest', destNetSelect.value, parseInt(prevSourceAssetId, 10));
            await requestLiveQuote();
        });
    }

    if (recipientInput) {
        recipientInput.addEventListener('blur', async () => {
            const ok = await validateAddressField('recipient');
            if (ok && recipientInput.value.trim()) {
                scheduleQuoteRefresh();
            }
        });
    }

    if (refundInput) {
        refundInput.addEventListener('blur', async () => {
            const ok = await validateAddressField('refund');
            if (ok && refundInput.value.trim()) {
                scheduleQuoteRefresh();
            }
        });
    }

    if (refreshQuoteBtn) {
        refreshQuoteBtn.addEventListener('click', () => requestLiveQuote());
    }

    if (initiateSwapBtn) {
        initiateSwapBtn.addEventListener('click', () => handleInitiateSwap());
    }
}

function populateNetworkSelect(selectEl, networks, defaultIndex = 0) {
    selectEl.innerHTML = '';
    networks.forEach((net, idx) => {
        const opt = document.createElement('option');
        opt.value = net.slug;
        opt.textContent = `${net.display_name} (${net.active_assets_count || 0})`;
        if (idx === defaultIndex) opt.selected = true;
        selectEl.appendChild(opt);
    });
}

async function loadAssetsForSide(side, networkSlug, preferredAssetId = null) {
    const selectEl = document.getElementById(side === 'source' ? 'sourceAssetSelect' : 'destAssetSelect');
    if (!selectEl || !networkSlug) return;

    selectEl.innerHTML = '<option value="">Loading tokens...</option>';
    const res = await fetchJson(`/api/v1/assets?network=${encodeURIComponent(networkSlug)}`);
    const assets = res.data || [];

    if (side === 'source') {
        appState.sourceAssets = assets;
    } else {
        appState.destAssets = assets;
    }

    let defaultAssetId = preferredAssetId;
    if (!defaultAssetId && assets.length) {
        const preferred =
            assets.find((a) => a.is_native) ||
            assets.find((a) => ['USDC', 'USDT', 'WNEAR', 'ETH', 'SOL', 'BTC'].includes((a.symbol || '').toUpperCase())) ||
            assets[0];
        defaultAssetId = preferred.id;
    }

    selectEl.innerHTML = '';
    assets.forEach((asset) => {
        const opt = document.createElement('option');
        opt.value = String(asset.id);
        opt.textContent = `${asset.symbol} — ${asset.display_name}`;
        if (defaultAssetId && asset.id === defaultAssetId) {
            opt.selected = true;
        }
        selectEl.appendChild(opt);
    });

    const selectedId = parseInt(selectEl.value, 10);
    if (side === 'source') {
        appState.selectedSourceAsset = assets.find((a) => a.id === selectedId) || null;
    } else {
        appState.selectedDestAsset = assets.find((a) => a.id === selectedId) || null;
    }

    updateSelectedAssetLabels();
    autoPopulateWalletAddresses();
}

function updateSelectedAssetLabels() {
    const srcPriceEl = document.getElementById('sourceAssetPriceLabel');
    const dstPriceEl = document.getElementById('destAssetPriceLabel');
    const recNetLabel = document.getElementById('recipientNetworkLabel');
    const refNetLabel = document.getElementById('refundNetworkLabel');
    const memoContainer = document.getElementById('recipientMemoContainer');

    if (srcPriceEl) {
        srcPriceEl.textContent = appState.selectedSourceAsset?.current_price_usd
            ? `1 ${appState.selectedSourceAsset.symbol} ≈ $${parseFloat(appState.selectedSourceAsset.current_price_usd).toFixed(4)}`
            : '';
    }

    if (dstPriceEl) {
        dstPriceEl.textContent = appState.selectedDestAsset?.current_price_usd
            ? `1 ${appState.selectedDestAsset.symbol} ≈ $${parseFloat(appState.selectedDestAsset.current_price_usd).toFixed(4)}`
            : '';
    }

    if (recNetLabel && appState.selectedDestAsset) {
        recNetLabel.textContent = appState.selectedDestAsset.network_name;
    }

    if (refNetLabel && appState.selectedSourceAsset) {
        refNetLabel.textContent = appState.selectedSourceAsset.network_name;
    }

    if (memoContainer) {
        if (appState.selectedDestAsset?.requires_memo_or_tag) {
            memoContainer.classList.remove('hidden');
        } else {
            memoContainer.classList.add('hidden');
        }
    }
}

function scheduleQuoteRefresh() {
    if (appState.quoteDebounceTimer) {
        clearTimeout(appState.quoteDebounceTimer);
    }
    appState.quoteDebounceTimer = setTimeout(() => {
        requestLiveQuote();
    }, 450);
}

async function requestLiveQuote() {
    const amountInput = document.getElementById('swapAmountInput');
    const outputInput = document.getElementById('estimatedOutputInput');
    const quoteCard = document.getElementById('quoteDetailsCard');
    const initiateBtn = document.getElementById('initiateSwapBtn');

    hideSwapError();

    if (!appState.selectedSourceAsset || !appState.selectedDestAsset) {
        return;
    }

    if (appState.selectedSourceAsset.id === appState.selectedDestAsset.id) {
        showSwapError('Please select two different assets to swap.');
        if (initiateBtn) initiateBtn.disabled = true;
        return;
    }

    const amount = (amountInput?.value || '').trim();
    if (!amount || isNaN(Number(amount)) || Number(amount) <= 0) {
        if (outputInput) outputInput.value = '';
        if (quoteCard) quoteCard.classList.add('hidden');
        if (initiateBtn) initiateBtn.disabled = true;
        return;
    }

    if (outputInput) outputInput.value = '...';
    if (initiateBtn) initiateBtn.disabled = true;

    try {
        const recipientAddr = document.getElementById('recipientAddressInput')?.value.trim() || null;
        const refundAddr = document.getElementById('refundAddressInput')?.value.trim() || null;

        const res = await fetchJson('/api/v1/quotes', {
            method: 'POST',
            body: JSON.stringify({
                source_asset_id: appState.selectedSourceAsset.id,
                destination_asset_id: appState.selectedDestAsset.id,
                amount,
                slippage_bps: appState.slippageBps,
                recipient_address: recipientAddr,
                refund_address: refundAddr,
            }),
        });

        const quote = res.data;
        appState.currentQuote = quote;

        if (outputInput) outputInput.value = quote.expected_amount_out;

        const inUsdEl = document.getElementById('amountInUsdEstimate');
        const outUsdEl = document.getElementById('amountOutUsdEstimate');
        if (inUsdEl) inUsdEl.textContent = quote.amount_in_usd ? `≈ $${quote.amount_in_usd}` : '';
        if (outUsdEl) outUsdEl.textContent = quote.amount_out_usd ? `≈ $${quote.amount_out_usd}` : '';

        if (quoteCard) {
            quoteCard.classList.remove('hidden');
            document.getElementById('quoteProviderBadge').textContent = quote.provider.display_name;
            document.getElementById('quoteRateText').textContent =
                `1 ${quote.source_asset.symbol} = ${quote.exchange_rate} ${quote.destination_asset.symbol}`;
            document.getElementById('quoteMinOutText').textContent =
                `${quote.min_amount_out} ${quote.destination_asset.symbol}`;
            document.getElementById('quoteFeeText').textContent =
                `${(quote.platform_fee_bps / 100).toFixed(2)}%`;
            document.getElementById('quoteEtaText').textContent =
                `~${quote.estimated_duration_seconds || 30}s`;
        }

        if (initiateBtn) initiateBtn.disabled = false;
    } catch (err) {
        appState.currentQuote = null;
        if (outputInput) outputInput.value = '';
        if (quoteCard) quoteCard.classList.add('hidden');

        const msg = err.message || 'Unable to fetch quote for this pair.';
        const recipientAddr = document.getElementById('recipientAddressInput')?.value.trim() || '';
        const refundAddr = document.getElementById('refundAddressInput')?.value.trim() || '';

        if (
            (!refundAddr && /refundTo|refund address/i.test(msg)) ||
            (!recipientAddr && /recipient|destination address|trustline/i.test(msg))
        ) {
            return;
        }

        showSwapError(msg);
    }
}

async function validateAddressField(type) {
    const isRecipient = type === 'recipient';
    const asset = isRecipient ? appState.selectedDestAsset : appState.selectedSourceAsset;
    const inputEl = document.getElementById(isRecipient ? 'recipientAddressInput' : 'refundAddressInput');
    const badgeEl = document.getElementById(isRecipient ? 'recipientValidationBadge' : 'refundValidationMessage');

    if (!asset || !inputEl || !inputEl.value.trim()) return true;

    try {
        const res = await fetchJson('/api/v1/addresses/validate', {
            method: 'POST',
            body: JSON.stringify({
                network_slug: asset.network_slug,
                address: inputEl.value.trim(),
            }),
        });

        if (badgeEl) {
            badgeEl.textContent = res.valid ? '✓ Valid address' : res.message;
            badgeEl.className = res.valid ? 'text-xs text-emerald-400' : 'text-xs text-rose-400';
        }
        return Boolean(res.valid);
    } catch (err) {
        if (badgeEl) {
            badgeEl.textContent = err.message || 'Invalid address';
            badgeEl.className = 'text-xs text-rose-400';
        }
        return false;
    }
}

async function handleInitiateSwap() {
    hideSwapError();

    if (!appState.currentQuote) {
        showSwapError('Please request a live quote first.');
        return;
    }

    const recipientAddress = document.getElementById('recipientAddressInput')?.value.trim() || '';
    const recipientMemo = document.getElementById('recipientMemoInput')?.value.trim() || '';
    const refundAddress = document.getElementById('refundAddressInput')?.value.trim() || '';

    if (!recipientAddress) {
        showSwapError('Please enter your destination recipient wallet address.');
        return;
    }

    if (!refundAddress) {
        showSwapError('Please enter your origin refund wallet address for automatic refund protection.');
        return;
    }

    const initiateBtn = document.getElementById('initiateSwapBtn');
    const originalLabel = initiateBtn.textContent;
    initiateBtn.disabled = true;
    initiateBtn.textContent = 'Locking Intent & Generating Deposit Address...';

    try {
        const res = await fetchJson('/api/v1/swaps', {
            method: 'POST',
            body: JSON.stringify({
                quote_uuid: appState.currentQuote.quote_uuid,
                recipient_address: recipientAddress,
                recipient_memo: recipientMemo || null,
                refund_address: refundAddress,
                connected_wallet_address: appState.wallet?.address || null,
            }),
        });

        const order = res.data;
        saveSwapUuidToLocalHistory(order.swap_uuid);
        window.location.href = order.tracking_url || `/swap/${order.swap_uuid}`;
    } catch (err) {
        showSwapError(err.message || 'Failed to initiate swap.');
        initiateBtn.disabled = false;
        initiateBtn.textContent = originalLabel;
    }
}

function showSwapError(message) {
    const el = document.getElementById('swapErrorBanner');
    if (el) {
        el.textContent = message;
        el.classList.remove('hidden');
    }
}

function hideSwapError() {
    const el = document.getElementById('swapErrorBanner');
    if (el) {
        el.textContent = '';
        el.classList.add('hidden');
    }
}

/* ==========================================================================
   3. Live Swap Status Tracker & Wallet Deposit Execution (/swap/{uuid})
   ========================================================================== */

function initSwapStatusTracker() {
    const container = document.getElementById('swapStatusPage');
    const swapUuid = container?.getAttribute('data-swap-uuid');
    if (!swapUuid) return;

    saveSwapUuidToLocalHistory(swapUuid);

    let currentOrder = null;
    let countdownInterval = null;

    const submitTxForm = document.getElementById('submitDepositTxForm');
    if (submitTxForm) {
        submitTxForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('manualTxHashInput');
            const feedback = document.getElementById('submitTxFeedback');
            const txHash = input?.value.trim() || '';
            if (!txHash) return;

            try {
                const res = await fetchJson(`/api/v1/swaps/${encodeURIComponent(swapUuid)}/deposit-tx`, {
                    method: 'POST',
                    body: JSON.stringify({ tx_hash: txHash }),
                });
                currentOrder = res.data;
                renderOrderStatus(currentOrder);
                if (feedback) {
                    feedback.textContent = 'Transaction hash submitted! Verifying on-chain confirmation...';
                    feedback.className = 'text-xs mt-2 text-emerald-400';
                    feedback.classList.remove('hidden');
                }
            } catch (err) {
                if (feedback) {
                    feedback.textContent = err.message || 'Failed to submit transaction hash.';
                    feedback.className = 'text-xs mt-2 text-rose-400';
                    feedback.classList.remove('hidden');
                }
            }
        });
    }

    const payBtn = document.getElementById('payWithConnectedWalletBtn');
    if (payBtn) {
        payBtn.addEventListener('click', async () => {
            if (!currentOrder) return;
            await executeWalletDeposit(currentOrder, swapUuid);
        });
    }

    async function pollStatus() {
        try {
            const res = await fetchJson(`/api/v1/swaps/${encodeURIComponent(swapUuid)}`);
            currentOrder = res.data;
            renderOrderStatus(currentOrder);

            if (!countdownInterval && currentOrder.deposit_instructions?.expires_timestamp_ms) {
                startExpiryCountdown(currentOrder.deposit_instructions.expires_timestamp_ms);
            }

            if (!currentOrder.is_terminal) {
                setTimeout(pollStatus, 7000);
            }
        } catch {
            setTimeout(pollStatus, 10000);
        }
    }

    function startExpiryCountdown(expiresMs) {
        const timerEl = document.getElementById('depositCountdownTimer');
        if (!timerEl) return;

        countdownInterval = setInterval(() => {
            const diffSec = Math.max(0, Math.floor((expiresMs - Date.now()) / 1000));
            const mins = String(Math.floor(diffSec / 60)).padStart(2, '0');
            const secs = String(diffSec % 60).padStart(2, '0');
            timerEl.textContent = `${mins}:${secs}`;
            if (diffSec <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    }

    pollStatus();
}

async function executeWalletDeposit(order, swapUuid) {
    const feedback = document.getElementById('walletPaymentFeedback');
    const showFeedback = (msg, isError = false) => {
        if (!feedback) return;
        feedback.textContent = msg;
        feedback.className = isError
            ? 'text-xs rounded-lg p-2.5 bg-rose-950/60 border border-rose-500/40 text-rose-300'
            : 'text-xs rounded-lg p-2.5 bg-emerald-950/60 border border-emerald-500/40 text-emerald-300';
        feedback.classList.remove('hidden');
    };

    if (order.source.chain_family === 'evm') {
        if (!window.ethereum) {
            showFeedback('No EVM browser wallet found. Please connect MetaMask/Rabby or send manually using the QR/address above.', true);
            return;
        }

        try {
            const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
            const from = accounts[0];

            if (order.source.evm_chain_id) {
                const hexChainId = '0x' + parseInt(order.source.evm_chain_id, 10).toString(16);
                await window.ethereum.request({
                    method: 'wallet_switchEthereumChain',
                    params: [{ chainId: hexChainId }],
                });
            }

            let txHash;
            if (order.source.is_native || !order.source.contract_or_issuer) {
                const valueHex = '0x' + BigInt(order.source.amount_in_base_units).toString(16);
                txHash = await window.ethereum.request({
                    method: 'eth_sendTransaction',
                    params: [{
                        from,
                        to: order.deposit_instructions.deposit_address,
                        value: valueHex,
                    }],
                });
            } else {
                // ERC-20 transfer(address,uint256) selector: 0xa9059cbb
                const cleanRecipient = order.deposit_instructions.deposit_address.replace(/^0x/, '').padStart(64, '0');
                const amountHex = BigInt(order.source.amount_in_base_units).toString(16).padStart(64, '0');
                const data = '0xa9059cbb' + cleanRecipient + amountHex;

                txHash = await window.ethereum.request({
                    method: 'eth_sendTransaction',
                    params: [{
                        from,
                        to: order.source.contract_or_issuer,
                        value: '0x0',
                        data,
                    }],
                });
            }

            showFeedback(`Broadcasted transaction ${txHash}. Submitting to execution provider...`);
            await fetchJson(`/api/v1/swaps/${encodeURIComponent(swapUuid)}/deposit-tx`, {
                method: 'POST',
                body: JSON.stringify({ tx_hash: txHash }),
            });
        } catch (err) {
            showFeedback(err.message || 'Wallet transaction was rejected or failed.', true);
        }
        return;
    }

    showFeedback(
        `For ${order.source.network_name}, copy the deposit address${order.deposit_instructions.memo_required ? ' and mandatory Memo/Tag' : ''} above to send from your wallet, then paste your transaction hash below.`,
        false
    );
}

function renderOrderStatus(order) {
    const headingEl = document.getElementById('swapStatusHeading');
    const pillEl = document.getElementById('swapStatusPill');
    const depositCard = document.getElementById('depositInstructionsCard');
    const failureBanner = document.getElementById('failureOrRefundBanner');
    const failureReasonEl = document.getElementById('failureReasonText');
    const outAmountEl = document.getElementById('orderAmountOut');

    if (headingEl) headingEl.textContent = order.status_label;
    if (pillEl) pillEl.textContent = order.status;
    if (outAmountEl && order.destination?.actual_amount_out) {
        outAmountEl.textContent = order.destination.actual_amount_out;
    }

    if (depositCard) {
        if (order.status === 'DEPOSIT_PENDING') {
            depositCard.classList.remove('hidden');
        } else {
            depositCard.classList.add('hidden');
        }
    }

    if (failureBanner) {
        if (['FAILED', 'REFUNDED', 'EXPIRED'].includes(order.status)) {
            failureBanner.classList.remove('hidden');
            if (failureReasonEl && order.failure_reason) {
                failureReasonEl.textContent = order.failure_reason;
            }
        } else {
            failureBanner.classList.add('hidden');
        }
    }

    if (Array.isArray(order.steps)) {
        order.steps.forEach((step) => {
            const row = document.querySelector(`[data-step-number="${step.step_number}"]`);
            if (!row) return;
            const statusText = row.querySelector('.step-status-text');
            const labelEl = row.querySelector('.step-label');
            const txLinkEl = row.querySelector('.step-tx-link');

            if (statusText) statusText.textContent = step.status;
            if (labelEl) labelEl.textContent = step.label;
            if (txLinkEl && step.tx_hash) {
                txLinkEl.innerHTML = step.explorer_url
                    ? `<a href="${step.explorer_url}" target="_blank" rel="noopener noreferrer" class="text-cyan-400 hover:underline font-mono">View Tx &nearr;</a>`
                    : `<span class="font-mono text-slate-400">${step.tx_hash.slice(0, 14)}...</span>`;
            }
        });
    }
}

/* ==========================================================================
   4. Swap History & Explorer (/history)
   ========================================================================== */

function saveSwapUuidToLocalHistory(uuid) {
    if (!uuid) return;
    try {
        const list = getSavedSwapUuids();
        if (!list.includes(uuid)) {
            list.unshift(uuid);
            localStorage.setItem(STORAGE_KEYS.RECENT_SWAPS, JSON.stringify(list.slice(0, 30)));
        }
    } catch {
        // Ignore storage errors
    }
}

function getSavedSwapUuids() {
    try {
        const raw = localStorage.getItem(STORAGE_KEYS.RECENT_SWAPS);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

async function initSwapHistoryPage() {
    const tbody = document.getElementById('swapHistoryTableBody');
    const searchForm = document.getElementById('walletHistorySearchForm');
    const searchInput = document.getElementById('walletHistorySearchInput');

    if (appState.wallet?.address && searchInput && !searchInput.value) {
        searchInput.value = appState.wallet.address;
    }

    async function loadHistory(walletFilter = '') {
        if (!tbody) return;
        const savedUuids = getSavedSwapUuids();
        const params = new URLSearchParams();

        if (walletFilter) {
            params.set('wallet_address', walletFilter);
        } else {
            savedUuids.forEach((id) => params.append('uuids[]', id));
        }

        try {
            const res = await fetchJson(`/api/v1/swaps?${params.toString()}`);
            const rows = res.data || [];

            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="6" class="py-8 text-center text-slate-500">No swap records found.</td></tr>`;
                return;
            }

            tbody.innerHTML = rows.map((item) => `
                <tr class="hover:bg-slate-900/50 transition">
                    <td class="py-3.5 px-4 font-mono text-cyan-400">${item.swap_uuid.slice(0, 12)}...</td>
                    <td class="py-3.5 px-4">
                        <span class="font-bold text-white">${item.source.amount_in} ${item.source.symbol}</span>
                        <span class="block text-xs text-slate-400">${item.source.network_name}</span>
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="font-bold text-emerald-400">${item.destination.actual_amount_out || item.destination.expected_amount_out} ${item.destination.symbol}</span>
                        <span class="block text-xs text-slate-400">${item.destination.network_name}</span>
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-200">${item.status_label}</span>
                    </td>
                    <td class="py-3.5 px-4 text-xs text-slate-400">${item.created_at ? new Date(item.created_at).toLocaleString() : '-'}</td>
                    <td class="py-3.5 px-4 text-right">
                        <a href="/swap/${encodeURIComponent(item.swap_uuid)}" class="text-xs px-3 py-1.5 rounded-lg bg-cyan-500/20 text-cyan-300 hover:bg-cyan-500/30 font-semibold">
                            View
                        </a>
                    </td>
                </tr>
            `).join('');
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" class="py-8 text-center text-rose-400">${err.message || 'Failed to load swap history.'}</td></tr>`;
        }
    }

    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            loadHistory(searchInput?.value.trim() || '');
        });
    }

    loadHistory(searchInput?.value.trim() || '');
}

/* ==========================================================================
   5. Shared Helpers
   ========================================================================== */

function initQuickTrackForm() {
    const form = document.getElementById('quickTrackSwapForm');
    const input = document.getElementById('quickTrackSwapInput');
    if (form && input) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const uuid = input.value.trim();
            if (uuid) {
                window.location.href = `/swap/${encodeURIComponent(uuid)}`;
            }
        });
    }
}

function initCopyButtons() {
    document.querySelectorAll('[data-copy-target]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const selector = btn.getAttribute('data-copy-target');
            const target = selector ? document.querySelector(selector) : null;
            const text = target?.textContent?.trim() || '';
            if (!text) return;

            try {
                await navigator.clipboard.writeText(text);
                const prev = btn.innerHTML;
                btn.textContent = 'Copied!';
                setTimeout(() => {
                    btn.innerHTML = prev;
                }, 1400);
            } catch {
                // Ignore clipboard errors
            }
        });
    });
}

async function fetchJson(url, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        ...(options.headers || {}),
    };

    const response = await fetch(url, { ...options, headers });
    const data = await response.json().catch(() => ({}));

    if (!response.ok || data.status === 0) {
        const firstValidationError = data.errors
            ? Object.values(data.errors).flat()[0]
            : null;
        throw new Error(firstValidationError || data.message || `Request failed (${response.status})`);
    }

    return data;
}
