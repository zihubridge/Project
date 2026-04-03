/* empty css            */document.addEventListener("DOMContentLoaded",async()=>{await C(),await Promise.all([E(),B()])});async function b(){const r=await(await fetch("/global/blockchains",{headers:{Accept:"application/json"}})).json(),o=Array.isArray(r)?r:r.blockchains??[];return Array.isArray(o)?o:[]}async function E(){const s=document.getElementById("fromBlockchain"),r=document.getElementById("toBlockchain"),o=document.getElementById("swapBtn");if(!s||!r||!o)return;let a=[];function p(){const c=s.value,i=r.value,d=!c||!i||c===i;o.disabled=d,o.classList.toggle("opacity-50",d),o.classList.toggle("cursor-not-allowed",d),o.classList.toggle("pointer-events-none",d)}function m(c,i,d=null){const y=c.value,h=c.querySelector("option[disabled]");c.innerHTML="",h&&c.appendChild(h),i.forEach(f=>{if(d&&String(f.asset_code)===String(d))return;const w=document.createElement("option");w.value=f.asset_code,w.textContent=f.name,c.appendChild(w)}),y&&[...c.options].some(f=>f.value===y)&&(c.value=y)}try{if(a=await b(),a.length===0)return;m(s,a),m(r,a),p()}catch(c){console.error("Failed to load blockchains",c);return}s.addEventListener("change",()=>{m(r,a,s.value),p()}),r.addEventListener("change",()=>{m(s,a,r.value),p()}),o.addEventListener("click",c=>{c.preventDefault(),!o.disabled&&(window.location.href=A(s.value,r.value))})}async function B(){const s=Array.from(document.querySelectorAll("[data-blockchain-select]")),r=document.getElementById("swapBtnCustom");if(s.length<2||!r)return;const o=s.find(e=>e.dataset.selectRole==="from"),a=s.find(e=>e.dataset.selectRole==="to");if(!o||!a)return;let p=[];function m(e){const t=e.querySelector(".currency-dropdown"),n=e.querySelector(".currency-chevron");t?.classList.add("hidden"),n&&(n.style.transform="rotate(0deg)")}function c(){s.forEach(m)}function i(e){return e.dataset.selectedAsset||""}function d(e){delete e.dataset.selectedAsset,delete e.dataset.selectedName,delete e.dataset.selectedImage;const t=e.querySelector(".currency-flag"),n=e.querySelector(".currency-name"),l=e.querySelector(".currency-code");t&&(t.alt="Select blockchain"),n&&(n.textContent="Select blockchain"),l&&(l.textContent="Choose asset")}function y(e,t){if(!t)return;e.dataset.selectedAsset=t.asset_code??"",e.dataset.selectedName=t.name??"",e.dataset.selectedImage=t.image??"";const n=e.querySelector(".currency-flag"),l=e.querySelector(".currency-name"),u=e.querySelector(".currency-code");n&&(n.src=t.image||n.src,n.alt=t.name||t.asset_code||"Blockchain"),l&&(l.textContent=t.name||"Unknown blockchain"),u&&(u.textContent=(t.asset_code||"").toUpperCase())}function h(e,t){const n=g(e.image||""),l=g(e.name||"Unknown blockchain"),u=g(String(e.asset_code||"").toUpperCase());return`
            <li>
                <button
                    type="button"
                    class="currency-option w-full flex items-center gap-3 px-4 py-3 text-left transition-all ${t?"opacity-50 cursor-not-allowed pointer-events-none":"hover:bg-white/10"}"
                    data-asset-code="${L(e.asset_code||"")}"
                    ${t?'disabled aria-disabled="true"':""}
                >
                    <img src="${n}" alt="${l}" class="w-6 h-6 rounded-full object-cover">
                    <div>
                        <p class="text-white text-sm font-medium">${l}</p>
                        <p class="text-white/40 text-xs">${u}</p>
                    </div>
                </button>
            </li>
        `}function f(e,t){const n=e.querySelector(".currency-dropdown");n&&(n.innerHTML=p.map(l=>h(l,!!(t&&String(l.asset_code)===String(t)))).join(""),n.querySelectorAll(".currency-option").forEach(l=>{l.addEventListener("click",()=>{const u=p.find(v=>String(v.asset_code)===String(l.dataset.assetCode));y(e,u),S(),c()})}))}function w(){const e=i(o),t=i(a),n=!e||!t||e===t;r.disabled=n,r.classList.toggle("opacity-50",n),r.classList.toggle("cursor-not-allowed",n),r.classList.toggle("pointer-events-none",n)}function S(){const e=i(o),t=i(a);f(o,t),f(a,e),w()}s.forEach(e=>{const t=e.querySelector(".currency-btn"),n=e.querySelector(".currency-dropdown"),l=e.querySelector(".currency-chevron");t?.addEventListener("click",u=>{u.stopPropagation();const v=n?.classList.contains("hidden");c(),v&&n&&(n.classList.remove("hidden"),l&&(l.style.transform="rotate(180deg)"))}),n?.addEventListener("click",u=>{u.stopPropagation()})}),document.addEventListener("click",c);try{if(p=await b(),p.length===0)return;d(o),d(a),S()}catch(e){console.error("Failed to load custom blockchain dropdowns",e),s.forEach(t=>{const n=t.querySelector(".currency-dropdown");n&&(n.innerHTML='<li><div class="px-4 py-3 text-sm text-red-300">Unable to load blockchains.</div></li>')});return}r.addEventListener("click",e=>{e.preventDefault();const t=i(o),n=i(a);!t||!n||t===n||(window.location.href=A(t,n))})}async function C(){const s=document.getElementById("stellarToRipplePairs"),r=document.getElementById("rippleToStellarPairs");if(!(!s||!r))try{const a=await(await fetch("/global/bridge-pairs")).json();x(s,a.stellar_to_ripple||[]),x(r,a.ripple_to_stellar||[])}catch(o){console.error("Failed to load bridge pairs",o)}}function x(s,r){s.innerHTML="",r.forEach((o,a)=>{s.innerHTML+=`
            <div class="bg-[#1F1132] border border-[#2F1C4A] text-white p-3 flex flex-wrap items-center justify-between gap-3 rounded-xl">
                <div class="text-sm">${a+1}</div>

                <div class="flex items-center gap-2 min-w-[80px]">
                    <img src="${o.from.image}" class="w-8 h-8 rounded-full" alt="${g(o.from.name||o.from.asset_code)}">
                    <span class="font-semibold text-sm">${o.from.asset_code}</span>
                </div>

                <div class="bg-[#7ABCE7] text-white py-1 px-3 rounded-full text-sm">
                    ${o.from.asset_code}
                </div>

                <div class="text-2xl font-light text-[#B3B3B3]">&#10230;</div>

                <div class="flex items-center gap-2 min-w-[80px] font-semibold">
                    <img src="${o.to.image}" class="w-8 h-8 rounded-full" alt="${g(o.to.name||o.to.asset_code)}">
                    <span>${o.to.asset_code}</span>
                </div>

                <div class="bg-[#9B6CEA] text-white py-1 px-3 rounded-full text-sm">
                    ${o.to.asset_code}
                </div>
            </div>
        `})}function A(s,r){return`/exchange?fromasset=${encodeURIComponent(s)}&toasset=${encodeURIComponent(r)}`}function g(s){return String(s).replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#39;")}function L(s){return g(s)}
