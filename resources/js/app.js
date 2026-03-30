import "../css/app.css";

document.addEventListener("DOMContentLoaded", async () => {
    // always load homepage bridge cards
    await loadBridgePairs();

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
        const placeholder = select.querySelector("option[disabled]");

        select.innerHTML = "";
        if (placeholder) select.appendChild(placeholder);

        data.forEach((chain) => {
            if (
                excludeAsset &&
                String(chain.asset_code) === String(excludeAsset)
            )
                return;

            const opt = document.createElement("option");
            opt.value = chain.asset_code;
            opt.textContent = chain.name;
            select.appendChild(opt);
        });
    }

    try {
        const res = await fetch("/global/blockchains", {
            headers: { Accept: "application/json" },
        });

        const json = await res.json();
        blockchains = Array.isArray(json) ? json : (json.blockchains ?? []);

        if (!Array.isArray(blockchains) || blockchains.length === 0) return;

        populateSelect(fromSelect, blockchains);
        populateSelect(toSelect, blockchains);

        // button should start disabled
        updateSwapState();
    } catch (err) {
        console.error("Failed to load blockchains ❌", err);
    }

    fromSelect.addEventListener("change", () => {
        populateSelect(toSelect, blockchains, fromSelect.value);
        updateSwapState();
    });

    toSelect.addEventListener("change", () => {
        populateSelect(fromSelect, blockchains, toSelect.value);
        updateSwapState();
    });

    swapBtn.addEventListener("click", (e) => {
        e.preventDefault();
        if (swapBtn.disabled) return;

        const fromAsset = fromSelect.value;
        const toAsset = toSelect.value;

        window.location.href = `/exchange?fromasset=${encodeURIComponent(fromAsset)}&toasset=${encodeURIComponent(toAsset)}`;
    });
});

async function loadBridgePairs() {
    
    const stellarContainer = document.getElementById("stellarToRipplePairs");
    const rippleContainer = document.getElementById("rippleToStellarPairs");
    console.log(123);

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
                    <img src="${pair.from.image}" class="w-8 h-8 rounded-full">
                    <span class="font-semibold text-sm">${pair.from.asset_code}</span>
                </div>

                <div class="bg-[#7ABCE7] text-white py-1 px-3 rounded-full text-sm">
                    ${pair.from.asset_code}
                </div>

                <div class="text-2xl font-light text-[#B3B3B3]">⟶</div>

                <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                    <img src="${pair.to.image}" class="w-8 h-8 rounded-full">
                    <span>${pair.to.asset_code}</span>
                </div>

                <div class="bg-[#9B6CEA] text-white py-1 px-3 rounded-full text-sm">
                    ${pair.to.asset_code}
                </div>
            </div>
        `;
    });
}
