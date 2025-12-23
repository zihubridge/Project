import "../css/app.css";

document.addEventListener("DOMContentLoaded", async () => {
    const fromSelect = document.getElementById("fromBlockchain");
    const toSelect = document.getElementById("toBlockchain");

    if (!fromSelect || !toSelect) return;

    let blockchains = [];

    try {
        const res = await fetch("/global/blockchains", {
            headers: { Accept: "application/json" },
        });

        const json = await res.json();
        blockchains = Array.isArray(json) ? json : json.blockchains ?? [];

        if (!Array.isArray(blockchains) || blockchains.length === 0) return;

        populateSelect(fromSelect, blockchains);
        populateSelect(toSelect, blockchains);
    } catch (err) {
        console.error("Failed to load blockchains ❌", err);
    }

    // When FROM changes → update TO
    fromSelect.addEventListener("change", () => {
        const selectedFrom = fromSelect.value;
        populateSelect(toSelect, blockchains, selectedFrom);
    });

    // When TO changes → update FROM
    toSelect.addEventListener("change", () => {
        const selectedTo = toSelect.value;
        populateSelect(fromSelect, blockchains, selectedTo);
    });

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
});
