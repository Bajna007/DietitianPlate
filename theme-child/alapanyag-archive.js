// ============================================================
// Alapanyag Archív JS – v6 FINAL
// Dual-range float szűrők, pill kereső dropdown, szűrő panel,
// oszlop választó, per page, neumorphism lapozó, prémium kártyák
// ============================================================
(function () {
    'use strict';

    /* ── DOM ── */
    var grid            = document.getElementById('aa-grid');
    var pagination      = document.getElementById('aa-pagination');
    var resultInfo      = document.getElementById('aa-result-info');
    var searchInput     = document.getElementById('aa-search');
    var searchClear     = document.getElementById('aa-search-clear');
    var searchDropdown  = document.getElementById('aa-search-dropdown');
    var sortSelect      = document.getElementById('aa-sort');
    var totalCountEl    = document.getElementById('aa-total-count');
    var filterToggle    = document.getElementById('aa-filter-toggle');
    var szuroPanel      = document.getElementById('aa-szuro-panel');
    var szuroPanelInner = document.getElementById('aa-szuro-panel-inner');
    var szuroReset      = document.getElementById('aa-szuro-reset');
    var sourceDesc      = document.getElementById('aa-source-desc');

    /* ── Állapot ── */
    var state = {
        page:     1,
        search:   '',
        source:   'all',
        sort:     'title_asc',
        perPage:  24,
        cols:     4,
        kcal:     { min: 0, max: 930 },
        protein:  { min: 0, max: 100 },
        carb:     { min: 0, max: 100 },
        fat:      { min: 0, max: 100 }
    };

    var panelOpen   = false;
    var fetchTimer  = null;
    var searchTimer = null;
    var abortCtrl   = null;

    /* ── Forrás leírások ── */
    var sourceDescriptions = {
        all:  '',
        off:  'Az Open Food Facts egy nyílt, közösségi élelmiszer-adatbázis. Az adatokat önkéntesek gyűjtik a termékek címkéiről – közel 3 millió termék adatait tartalmazza világszerte.',
        usda: 'Az USDA FoodData Central az Egyesült Államok Mezőgazdasági Minisztériumának hivatalos, laborvizsgálatokkal ellenőrzött tápanyag-adatbázisa. Ez az egyik legmegbízhatóbb forrás a tápértékekhez.',
        both: 'Olyan alapanyagok, amelyekhez mindkét adatbázisból (Open Food Facts + USDA) rendelkezünk adatokkal – így összevethetők az értékek.'
    };

    /* ── localStorage visszaállítás ── */
    var savedPP = localStorage.getItem('aa_per_page');
    if (savedPP && ['24','36','48'].indexOf(savedPP) !== -1) {
        state.perPage = parseInt(savedPP);
    }
    var savedCols = localStorage.getItem('aa_grid_cols');
    if (savedCols && ['3','4','5'].indexOf(savedCols) !== -1) {
        state.cols = parseInt(savedCols);
    }

    applyGridCols();
    syncPPButtons();
    syncColButtons();


    // ══════════════════════════════════════════════════════
    // DUAL-RANGE CSÚSZKA
    // ══════════════════════════════════════════════════════

    var rangeWraps = document.querySelectorAll('.aa-range-wrap');

    rangeWraps.forEach(function (wrap) {
        var key      = wrap.dataset.key;
        var dataMin  = parseFloat(wrap.dataset.min);
        var dataMax  = parseFloat(wrap.dataset.max);
        var step     = parseFloat(wrap.dataset.step);

        var thumbMin = wrap.querySelector('.aa-range-min');
        var thumbMax = wrap.querySelector('.aa-range-max');
        var numMin   = wrap.querySelector('.aa-range-num-min');
        var numMax   = wrap.querySelector('.aa-range-num-max');
        var fill     = wrap.querySelector('.aa-range-fill');

        var decimals = step < 1 ? String(step).split('.')[1].length : 0;

        function updateFill() {
            var lo  = parseFloat(thumbMin.value);
            var hi  = parseFloat(thumbMax.value);
            var pctL = ((lo - dataMin) / (dataMax - dataMin)) * 100;
            var pctR = ((hi - dataMin) / (dataMax - dataMin)) * 100;
            fill.style.left  = pctL + '%';
            fill.style.width = (pctR - pctL) + '%';
        }

        function syncState() {
            state[key].min = parseFloat(thumbMin.value);
            state[key].max = parseFloat(thumbMax.value);
        }

        function formatVal(v) {
            return decimals > 0 ? parseFloat(v).toFixed(decimals) : Math.round(v);
        }

        function onThumbMinInput() {
            var lo = parseFloat(thumbMin.value);
            var hi = parseFloat(thumbMax.value);
            if (lo > hi) { thumbMin.value = hi; lo = hi; }
            numMin.value = formatVal(lo);
            updateFill();
            syncState();
            debouncedFetch();
        }

        function onThumbMaxInput() {
            var lo = parseFloat(thumbMin.value);
            var hi = parseFloat(thumbMax.value);
            if (hi < lo) { thumbMax.value = lo; hi = lo; }
            numMax.value = formatVal(hi);
            updateFill();
            syncState();
            debouncedFetch();
        }

        function onNumMinChange() {
            var v = clamp(parseFloat(numMin.value) || dataMin, dataMin, dataMax);
            if (v > parseFloat(numMax.value)) v = parseFloat(numMax.value);
            numMin.value     = formatVal(v);
            thumbMin.value   = v;
            updateFill();
            syncState();
            debouncedFetch();
        }

        function onNumMaxChange() {
            var v = clamp(parseFloat(numMax.value) || dataMax, dataMin, dataMax);
            if (v < parseFloat(numMin.value)) v = parseFloat(numMin.value);
            numMax.value     = formatVal(v);
            thumbMax.value   = v;
            updateFill();
            syncState();
            debouncedFetch();
        }

        thumbMin.addEventListener('input', onThumbMinInput);
        thumbMax.addEventListener('input', onThumbMaxInput);
        numMin.addEventListener('change', onNumMinChange);
        numMax.addEventListener('change', onNumMaxChange);
        numMin.addEventListener('keyup', function(e) { if (e.key === 'Enter') onNumMinChange(); });
        numMax.addEventListener('keyup', function(e) { if (e.key === 'Enter') onNumMaxChange(); });

        wrap._reset = function () {
            thumbMin.value = dataMin;
            thumbMax.value = dataMax;
            numMin.value   = formatVal(dataMin);
            numMax.value   = formatVal(dataMax);
            state[key].min = dataMin;
            state[key].max = dataMax;
            updateFill();
        };

        updateFill();
    });

    function clamp(v, lo, hi) { return Math.max(lo, Math.min(hi, v)); }

    var debounceFetchTimer = null;
    function debouncedFetch() {
        clearTimeout(debounceFetchTimer);
        state.page = 1;
        updateFilterBadge();
        debounceFetchTimer = setTimeout(fetchResults, 400);
    }


    // ═════════════════════════════════════════════════════���
    // SZŰRŐ PANEL TOGGLE
    // ══════════════════════════════════════════════════════

    filterToggle.addEventListener('click', function () {
        panelOpen = !panelOpen;
        if (panelOpen) {
            szuroPanel.style.maxHeight = szuroPanelInner.scrollHeight + 'px';
            szuroPanel.classList.add('is-open');
            filterToggle.classList.add('is-active');
        } else {
            szuroPanel.style.maxHeight = '0px';
            szuroPanel.classList.remove('is-open');
            filterToggle.classList.remove('is-active');
        }
    });


    // ══════════════════════════════════════════════════════
    // FORRÁS CHIPEK + LEÍRÁS
    // ══════════════════════════════════════════════════════

    document.querySelectorAll('.aa-chip[data-filter="source"]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            document.querySelectorAll('.aa-chip[data-filter="source"]').forEach(function (c) {
                c.classList.remove('is-active');
            });
            this.classList.add('is-active');
            state.source = this.dataset.value;
            updateSourceDesc();
            state.page = 1;
            updateFilterBadge();
            fetchResults();
        });
    });

    function updateSourceDesc() {
        var text = sourceDescriptions[state.source] || '';
        if (text) {
            sourceDesc.innerHTML =
                '<div class="aa-source-desc-inner">' +
                '<svg class="aa-source-desc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>' +
                '<span>' + text + '</span></div>';
            sourceDesc.style.display = 'block';
        } else {
            sourceDesc.innerHTML = '';
            sourceDesc.style.display = 'none';
        }
    }


    // ══════════════════════════════════════════════════════
    // SZŰRŐ BADGE
    // ══════════════════════════════════════════════════════

    function updateFilterBadge() {
        var count = 0;
        if (state.source !== 'all') count++;
        if (state.kcal.min > 0 || state.kcal.max < 930) count++;
        if (state.protein.min > 0 || state.protein.max < 100) count++;
        if (state.carb.min > 0 || state.carb.max < 100) count++;
        if (state.fat.min > 0 || state.fat.max < 100) count++;

        var existing = filterToggle.querySelector('.aa-filter-badge');
        if (existing) existing.remove();

        if (count > 0) {
            var badge = document.createElement('span');
            badge.className = 'aa-filter-badge';
            badge.textContent = count;
            filterToggle.appendChild(badge);
        }
    }


    // ══════════════════════════════════════════════════════
    // ALAPHELYZET
    // ══════════════════════════════════════════════════════

    szuroReset.addEventListener('click', function () {
        state.source = 'all';
        state.page   = 1;

        document.querySelectorAll('.aa-chip[data-filter="source"]').forEach(function (c) {
            c.classList.remove('is-active');
        });
        document.querySelector('.aa-chip[data-filter="source"][data-value="all"]').classList.add('is-active');

        rangeWraps.forEach(function (w) { w._reset(); });

        updateSourceDesc();
        updateFilterBadge();
        fetchResults();
    });


    // ══════════════════════════════════════════════════════
    // OSZLOP VÁLASZTÓ
    // ══════════════════════════════════════════════════════

    document.querySelectorAll('.aa-col-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            state.cols = parseInt(this.dataset.cols);
            localStorage.setItem('aa_grid_cols', String(state.cols));
            syncColButtons();
            applyGridCols();
        });
    });

    function syncColButtons() {
        document.querySelectorAll('.aa-col-btn').forEach(function (b) {
            b.classList.toggle('is-active', parseInt(b.dataset.cols) === state.cols);
        });
    }

    function applyGridCols() {
        if (window.innerWidth > 900) {
            grid.style.gridTemplateColumns = 'repeat(' + state.cols + ', 1fr)';
        }
    }


    // ══════════════════════════════════════════════════════
    // PER PAGE
    // ══════════════════════════════════════════════════════

    document.querySelectorAll('.aa-pp-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            state.perPage = parseInt(this.dataset.pp);
            localStorage.setItem('aa_per_page', String(state.perPage));
            syncPPButtons();
            state.page = 1;
            fetchResults();
        });
    });

    function syncPPButtons() {
        document.querySelectorAll('.aa-pp-btn').forEach(function (b) {
            b.classList.toggle('is-active', parseInt(b.dataset.pp) === state.perPage);
        });
    }


    // ══════════════════════════════════════════════════════
    // RENDEZÉS
    // ══════════════════════════════════════════════════════

    sortSelect.addEventListener('change', function () {
        state.sort = this.value;
        state.page = 1;
        fetchResults();
    });


    // ══════════════════════════════════════════════════════
    // KERESŐ
    // ══════════════════════════════════════════════════════

    searchInput.addEventListener('input', function () {
        var val = this.value.trim();
        searchClear.style.display = val ? 'flex' : 'none';

        clearTimeout(searchTimer);
        if (val.length >= 2) {
            searchTimer = setTimeout(function () { fetchDropdown(val); }, 150);
        } else {
            closeDropdown();
        }

        clearTimeout(fetchTimer);
        state.search = val;
        state.page   = 1;
        fetchTimer   = setTimeout(fetchResults, 400);
    });

    searchInput.addEventListener('focus', function () {
        if (this.value.trim().length >= 2 && searchDropdown.children.length > 0) {
            searchDropdown.classList.add('is-open');
        }
    });

    searchClear.addEventListener('click', function () {
        searchInput.value = '';
        state.search = '';
        this.style.display = 'none';
        closeDropdown();
        state.page = 1;
        fetchResults();
        searchInput.focus();
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.aa-search-bar')) closeDropdown();
    });

    function closeDropdown() { searchDropdown.classList.remove('is-open'); }

    function fetchDropdown(term) {
        var fd = new FormData();
        fd.append('action',      'aa_archive_filter');
        fd.append('nonce',       aaData.nonce);
        fd.append('search',      term);
        fd.append('source',      'all');
        fd.append('sort',        'title_asc');
        fd.append('per_page',    5);
        fd.append('page',        1);
        fd.append('kcal_min',    0);    fd.append('kcal_max',    930);
        fd.append('protein_min', 0);    fd.append('protein_max', 100);
        fd.append('carb_min',    0);    fd.append('carb_max',    100);
        fd.append('fat_min',     0);    fd.append('fat_max',     100);

        fetch(aaData.ajaxUrl, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (resp) {
                if (!resp.success || !resp.data.items.length) {
                    searchDropdown.innerHTML = '<div class="aa-dd-empty">Nincs találat a keresésre</div>';
                    searchDropdown.classList.add('is-open');
                    return;
                }
                var html = '';
                resp.data.items.forEach(function (item) {
                    html += '<a href="' + item.url + '" class="aa-dd-item">';
                    html += '<div class="aa-dd-info">';
                    html += '<span class="aa-dd-title">' + escHtml(item.title) + '</span>';
                    html += '<span class="aa-dd-meta">';
                    html += '<span class="aa-dd-badge aa-dd-badge-kcal">' + Math.round(item.kcal) + ' kcal</span>';
                    html += '<span class="aa-dd-badge aa-dd-badge-prot">' + (item.protein||0).toFixed(1) + 'g fehérje</span>';
                    if (item.has_off) html += '<span class="aa-dd-badge aa-dd-badge-off">Open Food Facts</span>';
                    if (item.has_usda) html += '<span class="aa-dd-badge aa-dd-badge-usda">USDA</span>';
                    html += '</span></div></a>';
                });
                searchDropdown.innerHTML = html;
                searchDropdown.classList.add('is-open');
            })
            .catch(function () {});
    }


    // ══════════════════════════════════════════════════════
    // FETCH RESULTS
    // ══════════════════════════════════════════════════════

    function fetchResults() {
        if (abortCtrl) abortCtrl.abort();
        abortCtrl = new AbortController();

        grid.innerHTML = '<div class="aa-loading"><div class="aa-loading-spinner"></div><span>Betöltés...</span></div>';
        pagination.innerHTML = '';

        var fd = new FormData();
        fd.append('action',      'aa_archive_filter');
        fd.append('nonce',       aaData.nonce);
        fd.append('search',      state.search);
        fd.append('source',      state.source);
        fd.append('sort',        state.sort);
        fd.append('per_page',    state.perPage);
        fd.append('page',        state.page);
        fd.append('kcal_min',    state.kcal.min);
        fd.append('kcal_max',    state.kcal.max);
        fd.append('protein_min', state.protein.min);
        fd.append('protein_max', state.protein.max);
        fd.append('carb_min',    state.carb.min);
        fd.append('carb_max',    state.carb.max);
        fd.append('fat_min',     state.fat.min);
        fd.append('fat_max',     state.fat.max);

        fetch(aaData.ajaxUrl, { method: 'POST', body: fd, signal: abortCtrl.signal })
            .then(function (r) { return r.json(); })
            .then(function (resp) {
                if (!resp.success) {
                    grid.innerHTML = '<div class="aa-empty"><div class="aa-empty-icon">❌</div><div class="aa-empty-text">Hiba történt</div></div>';
                    return;
                }
                var d = resp.data;

                var hasFilter = state.search || state.source !== 'all' ||
                    state.kcal.min > 0 || state.kcal.max < 930 ||
                    state.protein.min > 0 || state.protein.max < 100 ||
                    state.carb.min > 0 || state.carb.max < 100 ||
                    state.fat.min > 0 || state.fat.max < 100;

                if (hasFilter) {
                    resultInfo.innerHTML = '<strong>' + d.total + '</strong> / ' + totalCountEl.textContent + ' alapanyag';
                } else {
                    resultInfo.innerHTML = '<strong>' + d.total + '</strong> alapanyag';
                    totalCountEl.textContent = d.total.toLocaleString();
                }

                if (!d.items || d.items.length === 0) {
                    grid.innerHTML =
                        '<div class="aa-empty">' +
                        '<div class="aa-empty-icon">🔍</div>' +
                        '<div class="aa-empty-text">Nincs találat</div>' +
                        '<div class="aa-empty-sub">Próbálj más keresőszót vagy állítsd át a szűrőket.</div>' +
                        '</div>';
                    return;
                }

                var html = '';
                d.items.forEach(function (item, i) { html += renderCard(item, i); });
                grid.innerHTML = html;

                renderPagination(d.total, d.pages, d.current_page);

                if (state.page > 1) {
                    window.scrollTo({ top: grid.offsetTop - 100, behavior: 'smooth' });
                }
            })
            .catch(function (err) {
                if (err.name !== 'AbortError') {
                    grid.innerHTML = '<div class="aa-empty"><div class="aa-empty-icon">❌</div><div class="aa-empty-text">Hálózati hiba</div></div>';
                }
            });
    }


    // ══════════════════════════════════════════════════════
    // KÁRTYA RENDERELÉS – v6 prémium
    // ══════════════════════════════════════════════════════

    function renderCard(item, idx) {
        var h = '';
        h += '<a href="' + item.url + '" class="aa-card" style="animation-delay:' + (idx * 0.04) + 's">';

        // Header: badge-ek + cím
        h += '<div class="aa-card-header">';
        h += '<div class="aa-card-source-badges">';
        if (item.has_off)  h += '<span class="aa-badge aa-badge-off">Open Food Facts</span>';
        if (item.has_usda) h += '<span class="aa-badge aa-badge-usda">USDA</span>';
        h += '</div>';
        h += '<h3 class="aa-card-title">' + escHtml(item.title) + '</h3>';
        if (item.original_name && item.original_name !== item.title) {
            h += '<div class="aa-card-original">' + escHtml(item.original_name) + '</div>';
        }
        h += '</div>';

        // Kalória szekció
        h += '<div class="aa-card-kcal">';
        h += '<div class="aa-card-kcal-num">' + Math.round(item.kcal || 0) + '</div>';
        h += '<div class="aa-card-kcal-side">';
        h += '<div class="aa-card-kcal-unit">kcal</div>';
        h += '<div class="aa-card-kcal-per">/ 100g</div>';
        h += '</div>';
        h += '</div>';

        // Makró szekció
        h += '<div class="aa-card-nutrients">';

        h += '<div class="aa-nutrient aa-nutrient-prot">';
        h += '<div class="aa-nutrient-value">' + (item.protein || 0).toFixed(1) + '<span class="aa-nutrient-g">g</span></div>';
        h += '<div class="aa-nutrient-name">fehérje</div>';
        h += '</div>';

        h += '<div class="aa-nutrient-divider"></div>';

        h += '<div class="aa-nutrient aa-nutrient-carb">';
        h += '<div class="aa-nutrient-value">' + (item.carb || 0).toFixed(1) + '<span class="aa-nutrient-g">g</span></div>';
        h += '<div class="aa-nutrient-name">szénhidrát</div>';
        h += '</div>';

        h += '<div class="aa-nutrient-divider"></div>';

        h += '<div class="aa-nutrient aa-nutrient-fat">';
        h += '<div class="aa-nutrient-value">' + (item.fat || 0).toFixed(1) + '<span class="aa-nutrient-g">g</span></div>';
        h += '<div class="aa-nutrient-name">zsír</div>';
        h += '</div>';

        h += '</div>';

        h += '</a>';
        return h;
    }


    // ══════════════════════════════════════════════════════
    // LAPOZÁS
    // ══════════════════════════════════════════════════════

    function renderPagination(total, pages, current) {
        if (pages <= 1) { pagination.innerHTML = ''; return; }

        var h = '<div class="aa-pag-inner">';
        h += '<button class="aa-pag-btn' + (current <= 1 ? ' is-disabled' : '') + '" data-page="' + (current - 1) + '">‹</button>';

        var start = Math.max(1, current - 2);
        var end   = Math.min(pages, current + 2);

        if (start > 1) {
            h += '<button class="aa-pag-btn" data-page="1">1</button>';
            if (start > 2) h += '<span class="aa-pag-dots">…</span>';
        }
        for (var i = start; i <= end; i++) {
            h += '<button class="aa-pag-btn' + (i === current ? ' is-active' : '') + '" data-page="' + i + '">' + i + '</button>';
        }
        if (end < pages) {
            if (end < pages - 1) h += '<span class="aa-pag-dots">…</span>';
            h += '<button class="aa-pag-btn" data-page="' + pages + '">' + pages + '</button>';
        }

        h += '<button class="aa-pag-btn' + (current >= pages ? ' is-disabled' : '') + '" data-page="' + (current + 1) + '">›</button>';
        h += '</div>';

        pagination.innerHTML = h;

        pagination.querySelectorAll('.aa-pag-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (this.classList.contains('is-disabled') || this.classList.contains('is-active')) return;
                state.page = parseInt(this.dataset.page);
                fetchResults();
            });
        });
    }


    // ══════════════════════════════════════════════════════
    // RESPONSIVE
    // ══════════════════════════════════════════════════════

    function handleResize() {
        if (window.innerWidth <= 900) {
            grid.style.gridTemplateColumns = 'repeat(2, 1fr)';
        } else {
            applyGridCols();
        }
        if (panelOpen) {
            szuroPanel.style.maxHeight = szuroPanelInner.scrollHeight + 'px';
        }
    }
    window.addEventListener('resize', handleResize);
    handleResize();


    // ══════════════════════════════════════════════════════
    // SEGÉD
    // ══════════════════════════════════════════════════════

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    // Első betöltés
    fetchResults();

})();