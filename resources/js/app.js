import "../css/app.css";

document.addEventListener("DOMContentLoaded", async () => {
    await loadBridgePairs();
    await Promise.all([initNativeHomepageSwap(), initCustomHomepageSwap()]);
});

async function fetchBlockchains() {
    const response = await fetch("/global/blockchains", {
        headers: { Accept: "application/json" },
    });

    const json = await response.json();
    const blockchains = Array.isArray(json) ? json : (json.blockchains ?? []);

    return Array.isArray(blockchains) ? blockchains : [];
}

async function initNativeHomepageSwap() {
    const fromSelect = document.getElementById("fromBlockchain");
    const toSelect = document.getElementById("toBlockchain");
    const swapBtn = document.getElementById("swapBtn");

    if (!fromSelect || !toSelect || !swapBtn) return;

    let blockchains = [];

    function updateSwapState() {
        const fromVal = fromSelect.value;
        const toVal = toSelect.value;
        const disabled = !fromVal || !toVal || fromVal === toVal;

        swapBtn.disabled = disabled;
        swapBtn.classList.toggle("opacity-50", disabled);
        swapBtn.classList.toggle("cursor-not-allowed", disabled);
        swapBtn.classList.toggle("pointer-events-none", disabled);
    }

    function populateSelect(select, data, excludeAsset = null) {
        const currentValue = select.value;
        const placeholder = select.querySelector("option[disabled]");

        select.innerHTML = "";

        if (placeholder) {
            select.appendChild(placeholder);
        }

        data.forEach((chain) => {
            if (
                excludeAsset &&
                String(chain.asset_code) === String(excludeAsset)
            ) {
                return;
            }

            const option = document.createElement("option");
            option.value = chain.asset_code;
            option.textContent = chain.name;
            select.appendChild(option);
        });

        if (
            currentValue &&
            [...select.options].some((option) => option.value === currentValue)
        ) {
            select.value = currentValue;
        }
    }

    try {
        blockchains = await fetchBlockchains();

        if (blockchains.length === 0) return;

        populateSelect(fromSelect, blockchains);
        populateSelect(toSelect, blockchains);
        updateSwapState();
    } catch (error) {
        console.error("Failed to load blockchains", error);
        return;
    }

    fromSelect.addEventListener("change", () => {
        populateSelect(toSelect, blockchains, fromSelect.value);
        updateSwapState();
    });

    toSelect.addEventListener("change", () => {
        populateSelect(fromSelect, blockchains, toSelect.value);
        updateSwapState();
    });

    swapBtn.addEventListener("click", (event) => {
        event.preventDefault();

        if (swapBtn.disabled) return;

        window.location.href = buildExchangeUrl(
            fromSelect.value,
            toSelect.value,
        );
    });
}

async function initCustomHomepageSwap() {
    const selectors = Array.from(
        document.querySelectorAll("[data-blockchain-select]"),
    );
    const swapBtn = document.getElementById("swapBtnCustom");

    if (selectors.length < 2 || !swapBtn) return;

    const fromSelect = selectors.find(
        (element) => element.dataset.selectRole === "from",
    );
    const toSelect = selectors.find(
        (element) => element.dataset.selectRole === "to",
    );

    if (!fromSelect || !toSelect) return;

    let blockchains = [];

    function closeDropdown(select) {
        const dropdown = select.querySelector(".currency-dropdown");
        const chevron = select.querySelector(".currency-chevron");

        dropdown?.classList.add("hidden");

        if (chevron) {
            chevron.style.transform = "rotate(0deg)";
        }
    }

    function closeAllDropdowns() {
        selectors.forEach(closeDropdown);
    }

    function getSelectedAsset(select) {
        return select.dataset.selectedAsset || "";
    }

    function resetSelectedBlockchain(select) {
        delete select.dataset.selectedAsset;
        delete select.dataset.selectedName;
        delete select.dataset.selectedImage;

        const flag = select.querySelector(".currency-flag");
        const name = select.querySelector(".currency-name");
        const code = select.querySelector(".currency-code");

        if (flag) {
            flag.alt = "Select blockchain";
        }

        if (name) {
            name.textContent = "Select blockchain";
        }

        if (code) {
            code.textContent = "Choose asset";
        }
    }

    function setSelectedBlockchain(select, blockchain) {
        if (!blockchain) return;

        select.dataset.selectedAsset = blockchain.asset_code ?? "";
        select.dataset.selectedName = blockchain.name ?? "";
        select.dataset.selectedImage = blockchain.image ?? "";

        const flag = select.querySelector(".currency-flag");
        const name = select.querySelector(".currency-name");
        const code = select.querySelector(".currency-code");

        if (flag) {
            flag.src = blockchain.image || flag.src;
            flag.alt = blockchain.name || blockchain.asset_code || "Blockchain";
        }

        if (name) {
            name.textContent = blockchain.name || "Unknown blockchain";
        }

        if (code) {
            code.textContent = (blockchain.asset_code || "").toUpperCase();
        }
    }

    function buildOptionMarkup(blockchain, isDisabled) {
        const image = escapeHtml(blockchain.image || "");
        const name = escapeHtml(blockchain.name || "Unknown blockchain");
        const assetCode = escapeHtml(
            String(blockchain.asset_code || "").toUpperCase(),
        );
        const stateClasses = isDisabled
            ? "opacity-50 cursor-not-allowed pointer-events-none"
            : "hover:bg-white/10";

        return `
            <li>
                <button
                    type="button"
                    class="currency-option w-full flex items-center gap-3 px-4 py-3 text-left transition-all ${stateClasses}"
                    data-asset-code="${escapeAttribute(blockchain.asset_code || "")}"
                    ${isDisabled ? 'disabled aria-disabled="true"' : ""}
                >
                    <img src="${image}" alt="${name}" class="w-6 h-6 rounded-full object-cover">
                    <div>
                        <p class="text-white text-sm font-medium">${name}</p>
                        <p class="text-white/40 text-xs">${assetCode}</p>
                    </div>
                </button>
            </li>
        `;
    }

    function renderDropdownOptions(select, excludeAsset) {
        const dropdown = select.querySelector(".currency-dropdown");

        if (!dropdown) return;

        dropdown.innerHTML = blockchains
            .map((blockchain) =>
                buildOptionMarkup(
                    blockchain,
                    Boolean(
                        excludeAsset &&
                            String(blockchain.asset_code) ===
                                String(excludeAsset),
                    ),
                ),
            )
            .join("");

        dropdown.querySelectorAll(".currency-option").forEach((option) => {
            option.addEventListener("click", () => {
                const selectedBlockchain = blockchains.find(
                    (blockchain) =>
                        String(blockchain.asset_code) ===
                        String(option.dataset.assetCode),
                );

                setSelectedBlockchain(select, selectedBlockchain);
                syncSelections();
                closeAllDropdowns();
            });
        });
    }

    function updateSwapState() {
        const fromAsset = getSelectedAsset(fromSelect);
        const toAsset = getSelectedAsset(toSelect);
        const disabled = !fromAsset || !toAsset || fromAsset === toAsset;

        swapBtn.disabled = disabled;
        swapBtn.classList.toggle("opacity-50", disabled);
        swapBtn.classList.toggle("cursor-not-allowed", disabled);
        swapBtn.classList.toggle("pointer-events-none", disabled);
    }

    function syncSelections() {
        const fromAsset = getSelectedAsset(fromSelect);
        const toAsset = getSelectedAsset(toSelect);

        renderDropdownOptions(fromSelect, toAsset);
        renderDropdownOptions(toSelect, fromAsset);
        updateSwapState();
    }

    selectors.forEach((select) => {
        const btn = select.querySelector(".currency-btn");
        const dropdown = select.querySelector(".currency-dropdown");
        const chevron = select.querySelector(".currency-chevron");

        btn?.addEventListener("click", (event) => {
            event.stopPropagation();

            const isHidden = dropdown?.classList.contains("hidden");

            closeAllDropdowns();

            if (isHidden && dropdown) {
                dropdown.classList.remove("hidden");

                if (chevron) {
                    chevron.style.transform = "rotate(180deg)";
                }
            }
        });

        dropdown?.addEventListener("click", (event) => {
            event.stopPropagation();
        });
    });

    document.addEventListener("click", closeAllDropdowns);

    try {
        blockchains = await fetchBlockchains();

        if (blockchains.length === 0) return;

        resetSelectedBlockchain(fromSelect);
        resetSelectedBlockchain(toSelect);
        syncSelections();
    } catch (error) {
        console.error("Failed to load custom blockchain dropdowns", error);

        selectors.forEach((select) => {
            const dropdown = select.querySelector(".currency-dropdown");

            if (dropdown) {
                dropdown.innerHTML =
                    '<li><div class="px-4 py-3 text-sm text-red-300">Unable to load blockchains.</div></li>';
            }
        });

        return;
    }

    swapBtn.addEventListener("click", (event) => {
        event.preventDefault();

        const fromAsset = getSelectedAsset(fromSelect);
        const toAsset = getSelectedAsset(toSelect);

        if (!fromAsset || !toAsset || fromAsset === toAsset) return;

        window.location.href = buildExchangeUrl(fromAsset, toAsset);
    });
}

async function loadBridgePairs() {
    const stellarContainer = document.getElementById("stellarToRipplePairs");
    const rippleContainer = document.getElementById("rippleToStellarPairs");

    if (!stellarContainer || !rippleContainer) return;

    try {
        const res = await fetch("/global/bridge-pairs");
        const data = await res.json();

        renderPairs(stellarContainer, data.stellar_to_ripple || []);
        renderPairs(rippleContainer, data.ripple_to_stellar || []);
    } catch (error) {
        console.error("Failed to load bridge pairs", error);
    }
}

function renderPairs(container, pairs) {
    container.innerHTML = "";

    pairs.forEach((pair, index) => {
        container.innerHTML += `
            <div class="bg-[#1F1132] border border-[#2F1C4A] text-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                <div class="text-sm">${index + 1}</div>

                <div class="flex items-center gap-2 min-w-[80px]">
                    <img src="${pair.from.image}" class="w-8 h-8 rounded-full" alt="${escapeHtml(pair.from.name || pair.from.asset_code)}">
                    <span class="font-semibold text-sm">${pair.from.asset_code}</span>
                </div>

                <div class="bg-[#7ABCE7] text-white py-1 px-3 rounded-full text-sm">
                    ${pair.from.asset_code}
                </div>

                <div class="text-2xl font-light text-[#B3B3B3]">&#10230;</div>

                <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                    <img src="${pair.to.image}" class="w-8 h-8 rounded-full" alt="${escapeHtml(pair.to.name || pair.to.asset_code)}">
                    <span>${pair.to.asset_code}</span>
                </div>

                <div class="bg-[#9B6CEA] text-white py-1 px-3 rounded-full text-sm">
                    ${pair.to.asset_code}
                </div>
            </div>
        `;
    });
}

function buildExchangeUrl(fromAsset, toAsset) {
    return `/exchange?fromasset=${encodeURIComponent(fromAsset)}&toasset=${encodeURIComponent(toAsset)}`;
}

function escapeHtml(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");
}

function escapeAttribute(value) {
    return escapeHtml(value);
}
