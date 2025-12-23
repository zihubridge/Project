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

    function populateSelect(select, data, excludeId = null) {
        const placeholder = select.querySelector("option[disabled]");

        select.innerHTML = "";
        if (placeholder) select.appendChild(placeholder);

        data.forEach((chain) => {
            if (excludeId && String(chain.id) === String(excludeId)) return;

            const opt = document.createElement("option");
            opt.value = chain.id;
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

    swapBtn.addEventListener("click", () => {
        const fromId = fromSelect.value;
        const toId = toSelect.value;

        if (!fromId || !toId) return;
        if (fromId === toId) return;

        window.location.href = `/exchange?from=${fromId}&to=${toId}`;
    });
});
