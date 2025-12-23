import "../css/app.css";

document.addEventListener("DOMContentLoaded", async () => {
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
        blockchains = Array.isArray(json) ? json : json.blockchains ?? [];

        if (!Array.isArray(blockchains) || blockchains.length === 0) return;

        populateSelect(fromSelect, blockchains);
        populateSelect(toSelect, blockchains);

        // button should start disabled
        updateSwapState();
    } catch (err) {
        console.error("Failed to load blockchains ❌", err);
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

    swapBtn.addEventListener("click", (e) => {
        e.preventDefault();
        if (swapBtn.disabled) return;

        const fromAsset = fromSelect.value;
        const toAsset = toSelect.value;

        window.location.href = `/exchange?fromasset=${encodeURIComponent(
            fromAsset
        )}&toasset=${encodeURIComponent(toAsset)}`;
    });
});
