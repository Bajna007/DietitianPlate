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
    var baseQuantities = [];
    var baseServings = INITIAL_SERVINGS;

    INGS.forEach(function (ing) {
        originalQuantities.push(ing.mennyiseg);
        state.quantities.push(ing.mennyiseg);
        baseQuantities.push(ing.mennyiseg);
    });

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
            el.textContent = val;
        });
    }

    // ???? Toast ?C display-alap??, nem opacity ????
    var toastTimer = null;
    function showToast(msg) {
        if (!dom.toast) { return; }
        clearTimeout(toastTimer);
        dom.toast.textContent = msg;
        dom.toast.style.display = 'block';
        // k??nyszer??tett reflow hogy az anim??ci?? m?0?3k?0?2dj?0?2n
        void dom.toast.offsetHeight;
        dom.toast.classList.add('is-visible');
        toastTimer = setTimeout(function () {
            dom.toast.classList.remove('is-visible');
            setTimeout(function () {
                dom.toast.style.display = 'none';
            }, 350);
        }, 2500);
    }

    // ???? Makr??k ????
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
        var fKcal = m.feherje * FACTORS.feherje;
        var chKcal = m.szenhidrat * FACTORS.szenhidrat;
        var zKcal = m.zsir * FACTORS.zsir;
        var totalEnergy = fKcal + chKcal + zKcal;
        if (totalEnergy <= 0) { return { feherje: 0, szenhidrat: 0, zsir: 0 }; }
        return {
            feherje:    Math.round((fKcal / totalEnergy) * 100),
            szenhidrat: Math.round((chKcal / totalEnergy) * 100),
            zsir:       Math.round((zKcal / totalEnergy) * 100)
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
        setText('.makro-val-feherje', m.feherje);
        setText('.makro-val-szenhidrat', m.szenhidrat);
        setText('.makro-val-zsir', m.zsir);
        setText('.makro-val-kcal', m.kcal);
        setText('.makro-pct-feherje', pct.feherje);
        setText('.makro-pct-szenhidrat', pct.szenhidrat);
        setText('.makro-pct-zsir', pct.zsir);
    }

    function updatePerAdag(m) {
        if (dom.perAdagDiv && state.servings > 1) {
            dom.perAdagDiv.innerHTML =
                '1 adag: <strong>' + r1(m.kcal / state.servings) + '</strong> kcal \u00B7 ' +
                '<strong>' + r1(m.feherje / state.servings) + '</strong>g feh. \u00B7 ' +
                '<strong>' + r1(m.szenhidrat / state.servings) + '</strong>g sz.h. \u00B7 ' +
                '<strong>' + r1(m.zsir / state.servings) + '</strong>g zs\u00EDr';
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

        if (dom.warning && MISSING.length > 0) {
            dom.warning.innerHTML = '<strong>\u26A0\uFE0F Hi\u00E1nyos makr\u00F3 adat:</strong> ' + MISSING.join(', ');
            dom.warning.style.display = 'block';
        }
    }

    function applyServings() {
        var ratio = state.servings / baseServings;
        INGS.forEach(function (ing, idx) {
            state.quantities[idx] = r1(baseQuantities[idx] * ratio);
        });
        updateUI();
    }

    function resetToOriginal() {
        var ratio = state.servings / INITIAL_SERVINGS;
        INGS.forEach(function (ing, idx) {
            state.quantities[idx] = r1(originalQuantities[idx] * ratio);
        });
        baseQuantities = state.quantities.slice();
        baseServings = state.servings;
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

    function normalizeNumericInput(raw) {
        var s = String(raw).replace(/,/g, '.');
        s = s.replace(/[^0-9.]/g, '');
        var parts = s.split('.');
        if (parts.length > 2) { s = parts[0] + '.' + parts.slice(1).join(''); }
        return s;
    }

    function onIngredientInput(e) {
        var idx = parseInt(e.target.dataset.index, 10);
        if (isNaN(idx)) { return; }

        var val = parseFloat(e.target.value);
        if (isNaN(val)) { return; }
        if (val <= 0) { val = 0.1; }
        if (val > 99999) { val = 99999; }

        state.quantities[idx] = val;
        baseQuantities = state.quantities.slice();
        baseServings = state.servings;

        var m = calcMacros();
        updateMacroDisplay(m);
        updatePerAdag(m);
        checkModified();
    }

    function onIngredientBlur(e) {
        var idx = parseInt(e.target.dataset.index, 10);
        if (isNaN(idx)) { return; }

        var normalized = normalizeNumericInput(e.target.value);
        var val = parseFloat(normalized);

        if (isNaN(val) || val <= 0) {
            val = state.quantities[idx];
            if (!val || val <= 0) { val = 0.1; }
        }
        if (val > 99999) { val = 99999; }

        state.quantities[idx] = r1(val);
        e.target.value = r1(val);
        baseQuantities = state.quantities.slice();
        baseServings = state.servings;

        var m = calcMacros();
        updateMacroDisplay(m);
        updatePerAdag(m);
        checkModified();
    }

    // ???? Bev??s??rl??lista ????
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

    // ???? Progress bar ????
    function updateProgress() {
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

    // ???? Init ????
    function init() {
        cacheDom();
        if (!dom.adagInput) { return; }

        // Toast alapb??l rejtett
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

        // ?0?0sszetev?0?2k
        dom.ingInputs.forEach(function (inp) {
            inp.addEventListener('input', onIngredientInput);
            inp.addEventListener('blur', onIngredientBlur);
        });
        if (dom.resetBtn) {
            dom.resetBtn.addEventListener('click', function () { resetToOriginal(); });
        }

        // Nyomtat??s
        if (dom.printBtn) {
            dom.printBtn.addEventListener('click', function () { window.print(); });
        }

        // Bev??s??rl??lista v??g??lapra
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

        // Megoszt??s
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

        // Progress bar
        dom.lepesCheckboxes.forEach(function (cb) {
            cb.addEventListener('change', updateProgress);
        });

        applyServings();
        updateProgress();
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