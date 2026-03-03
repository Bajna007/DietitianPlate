/* ═══════════════════════════════════════════════════
   PDF MODAL – popup magyarázat a print előtt
   ═══════════════════════════════════════════════ */
function dpPdfConfirm() {
    var ov = document.createElement('div');
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:999999;display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px)';
    ov.innerHTML = '<div style="background:#fff;border-radius:16px;padding:28px 24px;max-width:360px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.3);font-family:-apple-system,BlinkMacSystemFont,sans-serif;text-align:center">'
        + '<div style="font-size:36px;margin-bottom:10px">📄</div>'
        + '<h3 style="margin:0 0 6px;font-size:17px;color:#1a1d1b">Mentés / Nyomtatás</h3>'
        + '<p style="margin:0 0 14px;font-size:13px;color:#666;line-height:1.6">Megnyílik a böngésző nyomtatás ablaka.<br>A PDF mentéséhez kövesd az alábbi lépéseket:</p>'
        + '<div style="background:#f0f7f4;border:1px solid #d4e7dd;border-radius:10px;padding:12px 14px;margin-bottom:18px;text-align:left">'
        + '<div style="font-size:12px;font-weight:700;color:#2d6a4f;margin-bottom:8px">🖨️ Chrome / Edge (PC):</div>'
        + '<div style="font-size:12px;color:#333;line-height:1.9;margin-bottom:10px">'
        + '1. <strong>Cél:</strong> mezőnél válaszd:<br>'
        + '&nbsp;&nbsp;&nbsp;→ <strong>"Mentés PDF-ként"</strong><br>'
        + '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(ne: Microsoft Print to PDF)<br>'
        + '2. Kattints a <strong>Mentés</strong> gombra'
        + '</div>'
        + '<div style="border-top:1px solid #d4e7dd;padding-top:8px;font-size:12px;color:#333;line-height:1.9">'
        + '📱 <strong>Android Chrome:</strong><br>'
        + '&nbsp;&nbsp;&nbsp;Nyomtató → <strong>PDF mentése</strong> → Letöltés<br>'
        + '🍎 <strong>iPhone / iPad:</strong><br>'
        + '&nbsp;&nbsp;&nbsp;Share gomb → <strong>Fájlokba mentés</strong>'
        + '</div></div>'
        + '<button id="dpok" style="width:100%;padding:13px;background:#2d6a4f;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;margin-bottom:8px">Rendben, megnyitás →</button>'
        + '<button id="dpcancel" style="width:100%;padding:8px;background:none;color:#aaa;border:none;font-size:13px;cursor:pointer">Mégsem</button>'
        + '</div>';
    document.body.appendChild(ov);
    document.getElementById('dpcancel').onclick = function () { document.body.removeChild(ov); };
    document.getElementById('dpok').onclick = function () { document.body.removeChild(ov); window.print(); };
}

(function () {
    'use strict';

    window.FitSite = window.FitSite || {};

    var DATA = window.receptCalcData;
    if (!DATA) { return; }

    var PAGE = window.receptPageData || { title: '', url: '' };

    var INITIAL_SERVINGS = DATA.initialServings || 1;
    var INGS             = DATA.ingredients || [];
    var MISSING          = DATA.missingMacroIngredients || [];
    var FACTORS          = DATA.kcalFactors || { feherje: 4.1, szenhidrat: 4.1, zsir: 9.3 };

    var state = { servings: INITIAL_SERVINGS, quantities: [] };

    var originalQuantities = [];
    INGS.forEach(function (ing) {
        originalQuantities.push(ing.mennyiseg);
        state.quantities.push(ing.mennyiseg);
    });

    /* ★ FIX #1: Előző adagszám tárolása a relatív skálázáshoz ★ */
    var prevServings = INITIAL_SERVINGS;

    var dom = {};

    function cacheDom() {
        dom.adagInput       = document.getElementById('adagok-input');
        dom.adagMinus       = document.getElementById('adag-minusz');
        dom.adagPlus        = document.getElementById('adag-plusz');
        dom.servLabel       = document.getElementById('tapanyag-adag-label');
        dom.warning         = document.querySelector('.makro-warning');
        dom.resetBtn        = document.getElementById('alaphelyzet-btn');
        dom.ingInputs       = document.querySelectorAll('.osszetevo-mennyiseg-input');
        dom.perAdagDiv      = document.getElementById('makro-per-adag');
        dom.printBtn        = document.getElementById('btn-nyomtatas');
        dom.listBtn         = document.getElementById('btn-bevasarlolista');
        dom.shareBtn        = document.getElementById('btn-share');
        dom.sharePanel      = document.getElementById('share-panel');
        dom.shareClose      = document.getElementById('share-close');
        dom.shareTextarea   = document.getElementById('share-textarea');
        dom.shareCopy       = document.getElementById('share-copy');
        dom.shareWA         = document.getElementById('share-whatsapp');
        dom.shareEmail      = document.getElementById('share-email');
        dom.toast           = document.getElementById('recept-toast');
        dom.progressBar     = document.getElementById('elkeszites-progress-bar');
        dom.progressText    = document.getElementById('elkeszites-progress-text');
        dom.lepesCheckboxes = document.querySelectorAll('.lepes-checkbox');
    }

    function r1(v) { return Math.round((v + Number.EPSILON) * 10) / 10; }
    function r2(v) { return Math.round((v + Number.EPSILON) * 100) / 100; }

    function setText(sel, val) {
        document.querySelectorAll(sel).forEach(function (el) {
            var firstNode = el.firstChild;
            if (firstNode && firstNode.nodeType === 3) {
                firstNode.textContent = val;
            } else {
                el.insertBefore(document.createTextNode(val), el.firstChild);
            }
        });
    }

    // ══ Toast ══
    var toastTimer = null;
    function showToast(msg) {
        if (!dom.toast) { return; }
        clearTimeout(toastTimer);
        dom.toast.textContent = msg;
        dom.toast.style.display = 'block';
        void dom.toast.offsetHeight;
        dom.toast.classList.add('is-visible');
        toastTimer = setTimeout(function () {
            dom.toast.classList.remove('is-visible');
            setTimeout(function () {
                dom.toast.style.display = 'none';
            }, 350);
        }, 2500);
    }

    // ══ Makrók ══
    function calcMacros() {
        var t = { kcal: 0, feherje: 0, szenhidrat: 0, zsir: 0 };
        INGS.forEach(function (ing, idx) {
            var qty = state.quantities[idx] || 0;
            var g = qty * ing.grammSzorzo;
            var p = ing.per100;
            t.kcal       += (p.kcal / 100) * g;
            t.feherje    += (p.feherje / 100) * g;
            t.szenhidrat += (p.szenhidrat / 100) * g;
            t.zsir       += (p.zsir / 100) * g;
        });
        return {
            kcal: r1(t.kcal),
            feherje: r1(t.feherje),
            szenhidrat: r1(t.szenhidrat),
            zsir: r1(t.zsir)
        };
    }

    function calcPercentages(m) {
        if (m.kcal <= 0) { return { feherje: 0, szenhidrat: 0, zsir: 0 }; }
        return {
            feherje:    r2((m.feherje * FACTORS.feherje / m.kcal) * 100),
            szenhidrat: r2((m.szenhidrat * FACTORS.szenhidrat / m.kcal) * 100),
            zsir:       r2((m.zsir * FACTORS.zsir / m.kcal) * 100)
        };
    }

    function checkModified() {
        var isModified = false;
        INGS.forEach(function (ing, idx) {
            var expected = r1(originalQuantities[idx] * state.servings / INITIAL_SERVINGS);
            var row = document.getElementById('osszetevo-' + idx);
            if (r1(state.quantities[idx]) !== expected) {
                isModified = true;
                if (row) { row.classList.add('is-modified'); }
            } else {
                if (row) { row.classList.remove('is-modified'); }
            }
        });
        if (dom.resetBtn) {
            dom.resetBtn.classList.toggle('is-visible', isModified);
        }
    }

    function updateMacroDisplay(m) {
        var pct = calcPercentages(m);

        setText('.makro-val-kcal', m.kcal);
        setText('.makro-val-feherje', m.feherje);
        setText('.makro-val-szenhidrat', m.szenhidrat);
        setText('.makro-val-zsir', m.zsir);

        setText('.makro-pct-feherje', pct.feherje.toFixed(1));
        setText('.makro-pct-szenhidrat', pct.szenhidrat.toFixed(1));
        setText('.makro-pct-zsir', pct.zsir.toFixed(1));
    }

    /* ★ FIX #2: Szép "1 adag" kártyás megjelenítés ★ */
    function updatePerAdag(m) {
        if (dom.perAdagDiv && state.servings > 1) {
            var s = state.servings;
            dom.perAdagDiv.innerHTML =
                '<div class="per-adag-header">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>' +
                    ' 1 adag' +
                '</div>' +
                '<div class="per-adag-grid">' +
                    '<div class="per-adag-item">' +
                        '<span class="per-adag-value">' + r1(m.kcal / s) + '</span>' +
                        '<span class="per-adag-label">kcal</span>' +
                    '</div>' +
                    '<div class="per-adag-sep"></div>' +
                    '<div class="per-adag-item">' +
                        '<span class="per-adag-value">' + r1(m.feherje / s) + '<small>g</small></span>' +
                        '<span class="per-adag-label">fehérje</span>' +
                    '</div>' +
                    '<div class="per-adag-sep"></div>' +
                    '<div class="per-adag-item">' +
                        '<span class="per-adag-value">' + r1(m.szenhidrat / s) + '<small>g</small></span>' +
                        '<span class="per-adag-label">szénhidrát</span>' +
                    '</div>' +
                    '<div class="per-adag-sep"></div>' +
                    '<div class="per-adag-item">' +
                        '<span class="per-adag-value">' + r1(m.zsir / s) + '<small>g</small></span>' +
                        '<span class="per-adag-label">zsír</span>' +
                    '</div>' +
                '</div>';
            dom.perAdagDiv.style.display = 'block';
        } else if (dom.perAdagDiv) {
            dom.perAdagDiv.style.display = 'none';
        }
    }

    function updateUI() {
        if (dom.servLabel) { dom.servLabel.textContent = state.servings; }

        dom.ingInputs.forEach(function (inp) {
            var idx = parseInt(inp.dataset.index, 10);
            if (!isNaN(idx) && state.quantities[idx] !== undefined) {
                inp.value = r1(state.quantities[idx]);
            }
        });

        var m = calcMacros();
        updateMacroDisplay(m);
        updatePerAdag(m);
        checkModified();

        // Piros flag kikapcsolva – 0 kcal alapanyagok (víz, só stb.) nem hibajelzés
        // if (dom.warning && MISSING.length > 0) {
        //     dom.warning.innerHTML = '<strong>\u26A0\uFE0F Hi\u00E1nyos makr\u00F3 adat:</strong> ' + MISSING.join(', ');
        //     dom.warning.style.display = 'block';
        // }
    }

    /* ★ FIX #1: Relatív skálázás – megőrzi a manuális módosításokat ★ */
    function applyServings() {
        var ratio = state.servings / prevServings;
        INGS.forEach(function (ing, idx) {
            state.quantities[idx] = r1(state.quantities[idx] * ratio);
        });
        prevServings = state.servings;
        updateUI();
    }

    /* Reset gomb: vissza az eredeti arányokra */
    function resetToOriginal() {
        var ratio = state.servings / INITIAL_SERVINGS;
        INGS.forEach(function (ing, idx) {
            state.quantities[idx] = r1(originalQuantities[idx] * ratio);
        });
        updateUI();
    }

    function onServingDelta(d) {
        var v = Math.max(1, (parseInt(dom.adagInput.value, 10) || INITIAL_SERVINGS) + d);
        dom.adagInput.value = v;
        state.servings = v;
        applyServings();
    }

    function onServingInput() {
        var v = parseInt(dom.adagInput.value, 10);
        state.servings = (isNaN(v) || v < 1) ? 1 : v;
        applyServings();
    }

    function onIngredientInput(e) {
        var idx = parseInt(e.target.dataset.index, 10);
        if (isNaN(idx)) { return; }
        var raw = e.target.value.trim();
        state.quantities[idx] = raw === '' ? 0 : (parseFloat(raw) || 0);
        var m = calcMacros();
        updateMacroDisplay(m);
        updatePerAdag(m);
        checkModified();
    }

    // ══ Bevásárlólista ══
    function buildShoppingLists() {
        var venni = [];
        var megvan = [];
        var rows = document.querySelectorAll('.osszetevo-sor');

        rows.forEach(function (row) {
            var cb   = row.querySelector('.osszetevo-checkbox-label input[type="checkbox"]');
            var inp  = row.querySelector('.osszetevo-mennyiseg-input');
            var me   = row.querySelector('.osszetevo-mertekegyseg');
            var nev  = row.querySelector('.osszetevo-nev');

            var qty  = inp ? inp.value : '';
            var unit = me ? me.textContent.trim() : '';
            var name = nev ? nev.textContent.trim().replace(/\s+/g, ' ') : '';

            if (!name) { return; }

            var line = '\u2022 ' + qty + ' ' + unit + ' ' + name;

            if (cb && cb.checked) {
                megvan.push(line);
            } else {
                venni.push(line);
            }
        });

        return { venni: venni, megvan: megvan };
    }

    function buildListText() {
        var lists = buildShoppingLists();
        var adag = state.servings;
        var text = '';

        text += '\uD83D\uDED2 BEV\u00C1S\u00C1RL\u00D3LISTA\n';
        text += '\u201E' + PAGE.title + '\u201D (' + adag + ' adag)\n';
        text += '\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\n\n';

        if (lists.venni.length > 0) {
            text += '\uD83D\uDED2 Venni kell:\n' + lists.venni.join('\n') + '\n\n';
        }
        if (lists.megvan.length > 0) {
            text += '\u2705 Van otthon (de sz\u00FCks\u00E9ges):\n' + lists.megvan.join('\n') + '\n\n';
        }

        text += '\uD83D\uDD17 Recept: ' + PAGE.url;
        return text;
    }

    function buildShareText() {
        var lists = buildShoppingLists();
        var adag = state.servings;
        var text = '';

        text += 'Szia! \uD83D\uDC4B\n\n';
        text += '\uD83D\uDED2 K\u00FCld\u00F6m a bev\u00E1s\u00E1rl\u00F3list\u00E1t a \u201E' + PAGE.title + '\u201D recepthez (' + adag + ' adag):\n\n';

        if (lists.venni.length > 0) {
            text += '\uD83D\uDED2 Venni kell:\n' + lists.venni.join('\n') + '\n\n';
        }
        if (lists.megvan.length > 0) {
            text += '\u2705 Van otthon (de sz\u00FCks\u00E9ges):\n' + lists.megvan.join('\n') + '\n\n';
        }

        text += '\uD83D\uDD17 A recept itt tal\u00E1lhat\u00F3: ' + PAGE.url;
        return text;
    }

    function doShare() {
        var text = buildShareText();

        if (navigator.share) {
            navigator.share({
                title: '\uD83D\uDED2 Bev\u00E1s\u00E1rl\u00F3lista: ' + PAGE.title,
                text: text
            }).catch(function () {});
            return;
        }

        if (dom.shareTextarea) { dom.shareTextarea.value = text; }

        var encoded = encodeURIComponent(text);
        if (dom.shareWA) {
            dom.shareWA.href = 'https://wa.me/?text=' + encoded;
        }
        if (dom.shareEmail) {
            dom.shareEmail.href = 'mailto:?subject=' + encodeURIComponent('\uD83D\uDED2 Bev\u00E1s\u00E1rl\u00F3lista: ' + PAGE.title) + '&body=' + encoded;
        }

        if (dom.sharePanel) { dom.sharePanel.classList.add('is-open'); }
    }

    // ══════════════════════════════════════════════════════
    // PROGRESS BAR – JAVÍTOTT VÁLTOZAT
    // ══════════════════════════════════════════════════════
    var STORAGE_KEY = 'recept_lepesek_' + (window.location.pathname || '');

    function loadSavedSteps() {
        try {
            var saved = localStorage.getItem(STORAGE_KEY);
            if (saved) { return JSON.parse(saved); }
        } catch (e) {}
        return {};
    }

    function saveSteps() {
        var data = {};
        dom.lepesCheckboxes.forEach(function (cb) {
            var idx = cb.getAttribute('data-lepes');
            if (idx !== null) {
                data[idx] = cb.checked;
            }
        });
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch (e) {}
    }

    function syncProgress() {
        if (!dom.lepesCheckboxes.length) { return; }

        var total = dom.lepesCheckboxes.length;
        var done = 0;

        dom.lepesCheckboxes.forEach(function (cb) {
            var li = cb.closest('li');
            if (!li) { return; }

            if (cb.checked) {
                done++;
                li.classList.add('lepes-done');
            } else {
                li.classList.remove('lepes-done');
            }
        });

        var pct = Math.round((done / total) * 100);

        if (dom.progressBar) {
            dom.progressBar.style.width = pct + '%';
        }
        if (dom.progressText) {
            dom.progressText.textContent = pct + '%';
        }
    }

    function onStepToggle(e) {
        e.stopPropagation();
        syncProgress();
        saveSteps();
    }

    function onStepLiClick(e) {
        var li = e.currentTarget;
        var cb = li.querySelector('.lepes-checkbox');
        if (!cb) { return; }

        if (e.target === cb || e.target.closest('.lepes-checkbox-label')) {
            return;
        }

        cb.checked = !cb.checked;
        syncProgress();
        saveSteps();
    }

    // ══ Init ══
    function init() {
        cacheDom();
        if (!dom.adagInput) { return; }

        // Toast alapból rejtett
        if (dom.toast) {
            dom.toast.style.display = 'none';
        }

        // Adag
        if (dom.adagMinus) {
            dom.adagMinus.addEventListener('click', function () { onServingDelta(-1); });
        }
        if (dom.adagPlus) {
            dom.adagPlus.addEventListener('click', function () { onServingDelta(1); });
        }
        dom.adagInput.addEventListener('input', onServingInput);

        // Összetevők
        dom.ingInputs.forEach(function (inp) {
            inp.addEventListener('input', onIngredientInput);
        });

        /* ★ FIX #1: Reset gomb → eredeti arányokra ★ */
        if (dom.resetBtn) {
            dom.resetBtn.addEventListener('click', function () { resetToOriginal(); });
        }

        // Nyomtatás
        if (dom.printBtn) {
            dom.printBtn.addEventListener('click', function () { dpPdfConfirm(); });
        }

        // Bevásárlólista vágólapra
        if (dom.listBtn) {
            dom.listBtn.addEventListener('click', function () {
                var text = buildListText();
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function () {
                        showToast('\u2705 Bev\u00E1s\u00E1rl\u00F3lista v\u00E1g\u00F3lapra m\u00E1solva!');
                    });
                }
            });
        }

        // Megosztás
        if (dom.shareBtn) {
            dom.shareBtn.addEventListener('click', doShare);
        }

        // Fallback panel
        if (dom.shareClose) {
            dom.shareClose.addEventListener('click', function () {
                if (dom.sharePanel) { dom.sharePanel.classList.remove('is-open'); }
            });
        }
        if (dom.shareCopy) {
            dom.shareCopy.addEventListener('click', function () {
                var text = dom.shareTextarea ? dom.shareTextarea.value : '';
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function () {
                        showToast('\u2705 M\u00E1solva a v\u00E1g\u00F3lapra!');
                    });
                }
            });
        }
        if (dom.sharePanel) {
            dom.sharePanel.addEventListener('click', function (e) {
                if (e.target === dom.sharePanel) {
                    dom.sharePanel.classList.remove('is-open');
                }
            });
        }

        // ── Progress bar – javított event binding ──
        var savedSteps = loadSavedSteps();

        dom.lepesCheckboxes.forEach(function (cb) {
            var idx = cb.getAttribute('data-lepes');

            if (idx !== null && savedSteps[idx]) {
                cb.checked = true;
            }

            cb.addEventListener('change', onStepToggle);
        });

        var lepesItems = document.querySelectorAll('.recept-lepesek li');
        lepesItems.forEach(function (li) {
            li.addEventListener('click', onStepLiClick);
        });

        /* Első inicializálás – prevServings már INITIAL_SERVINGS,
           ezért ratio = 1 → nem változtat semmit, csak updateUI()-t hív */
        applyServings();
        syncProgress();
    }

    window.FitSite.ReceptCalc = {
        init: init,
        calcMacros: calcMacros,
        getState: function () { return JSON.parse(JSON.stringify(state)); }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
