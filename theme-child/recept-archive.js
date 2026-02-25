(function() {
    'use strict';

    var grid        = document.getElementById('recept-grid');
    var pagination  = document.getElementById('recept-pagination');
    var countEl     = document.getElementById('recept-talalat');
    var searchInput = document.getElementById('szuro-kereses');
    var searchClear = document.getElementById('search-clear');
    var dropdown    = document.getElementById('szuro-dropdown');
    var resetBtn    = document.getElementById('szuro-reset');
    var toggleBtn   = document.getElementById('szuro-toggle');
    var panel       = document.getElementById('szuro-panel');
    var panelInner  = document.getElementById('szuro-panel-inner');

    if (!grid || typeof RECEPT_ADATOK === 'undefined') return;

    var state = {
        page: 1,
        search: '',
        perPage: 24,
        cols: 4,
        filters: {
            kategoria: 'all',
            jelleg:    'all',
            dieta:     [],
            nehezseg:  'all',
            ido:       'all'
        }
    };

    var panelOpen = false;
    var searchTimer = null;
    var renderTimer = null;
    var totalAll = RECEPT_ADATOK.length;

    // Restore localStorage
    var savedPP = localStorage.getItem('recept_per_page');
    if (savedPP && ['24','36','48'].indexOf(savedPP) !== -1) {
        state.perPage = parseInt(savedPP);
    }
    var savedCols = localStorage.getItem('recept_grid_cols');
    if (savedCols && ['3','4','5'].indexOf(savedCols) !== -1) {
        state.cols = parseInt(savedCols);
    }

    applyGridCols();
    syncPPButtons();
    syncColButtons();

    // ══════════════════════════════════════════
    // SZURO PANEL TOGGLE
    // ══════════════════════════════════════════

    if (toggleBtn && panel && panelInner) {
        panel.style.maxHeight = '0px';
        panel.style.overflow = 'hidden';
        toggleBtn.addEventListener('click', function() {
            panelOpen = !panelOpen;
            if (panelOpen) {
                panel.style.maxHeight = panelInner.scrollHeight + 'px';
                panel.classList.add('is-open');
                toggleBtn.classList.add('is-active');
            } else {
                panel.style.maxHeight = '0px';
                panel.classList.remove('is-open');
                toggleBtn.classList.remove('is-active');
            }
        });
        window.addEventListener('resize', function() {
            if (panelOpen) panel.style.maxHeight = panelInner.scrollHeight + 'px';
        });
    }

    // ══════════════════════════════════════════
    // CHIP SZUROK
    // ══════════════════════════════════════════

    document.querySelectorAll('.szuro-chips:not(.szuro-chips--multi)').forEach(function(group) {
        var filterName = group.dataset.filter;
        group.querySelectorAll('.szuro-chip').forEach(function(chip) {
            chip.addEventListener('click', function() {
                group.querySelectorAll('.szuro-chip').forEach(function(c) { c.classList.remove('is-active'); });
                chip.classList.add('is-active');
                state.filters[filterName] = chip.dataset.value;
                state.page = 1;
                updateFilterBadge();
                renderResults();
            });
        });
    });

    document.querySelectorAll('.szuro-chips--multi').forEach(function(group) {
        var allChip = group.querySelector('.szuro-chip[data-value="all"]');
        var otherChips = group.querySelectorAll('.szuro-chip:not([data-value="all"])');
        if (allChip) {
            allChip.addEventListener('click', function() {
                otherChips.forEach(function(c) { c.classList.remove('is-active'); });
                allChip.classList.add('is-active');
                state.filters.dieta = [];
                state.page = 1;
                updateFilterBadge();
                renderResults();
            });
        }
        otherChips.forEach(function(chip) {
            chip.addEventListener('click', function() {
                chip.classList.toggle('is-active');
                state.filters.dieta = [];
                var hasActive = false;
                otherChips.forEach(function(c) {
                    if (c.classList.contains('is-active')) { state.filters.dieta.push(c.dataset.value); hasActive = true; }
                });
                if (allChip) { allChip.classList.toggle('is-active', !hasActive); }
                state.page = 1;
                updateFilterBadge();
                renderResults();
            });
        });
    });

    // ══════════════════════════════════════════
    // SZURO BADGE
    // ══════════════════════════════════════════

    function updateFilterBadge() {
        if (!toggleBtn) return;
        var count = 0;
        if (state.filters.kategoria !== 'all') count++;
        if (state.filters.jelleg !== 'all') count++;
        if (state.filters.dieta.length > 0) count++;
        if (state.filters.nehezseg !== 'all') count++;
        if (state.filters.ido !== 'all') count++;
        var badge = toggleBtn.querySelector('.szuro-toggle-badge');
        if (badge) badge.remove();
        if (count > 0) {
            badge = document.createElement('span');
            badge.className = 'szuro-toggle-badge';
            badge.textContent = count;
            toggleBtn.appendChild(badge);
        }
    }

    // ══════════════════════════════════════════
    // ALAPHELYZET
    // ══════════════════════════════════════════

    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            state.filters.kategoria = 'all';
            state.filters.jelleg = 'all';
            state.filters.dieta = [];
            state.filters.nehezseg = 'all';
            state.filters.ido = 'all';
            state.search = '';
            state.page = 1;
            if (searchInput) searchInput.value = '';
            if (searchClear) searchClear.style.display = 'none';
            closeDropdown();
            document.querySelectorAll('.szuro-chips:not(.szuro-chips--multi) .szuro-chip').forEach(function(c) {
                c.classList.toggle('is-active', c.dataset.value === 'all');
            });
            document.querySelectorAll('.szuro-chips--multi .szuro-chip').forEach(function(c) {
                c.classList.toggle('is-active', c.dataset.value === 'all');
            });
            updateFilterBadge();
            renderResults();
        });
    }

    // ══════════════════════════════════════════
    // OSZLOP VALASZTO
    // ══════════════════════════════════════════

    document.querySelectorAll('.recept-cols-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            state.cols = parseInt(this.dataset.cols);
            localStorage.setItem('recept_grid_cols', String(state.cols));
            syncColButtons();
            applyGridCols();
        });
    });

    function syncColButtons() {
        document.querySelectorAll('.recept-cols-btn').forEach(function(b) {
            b.classList.toggle('is-active', parseInt(b.dataset.cols) === state.cols);
        });
    }

    function applyGridCols() {
        if (window.innerWidth > 900) {
            grid.style.gridTemplateColumns = 'repeat(' + state.cols + ', 1fr)';
        }
    }

    // ══════════════════════════════════════════
    // PER PAGE
    // ══════════════════════════════════════════

    document.querySelectorAll('.recept-perpage-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            state.perPage = parseInt(this.dataset.pp);
            localStorage.setItem('recept_per_page', String(state.perPage));
            syncPPButtons();
            state.page = 1;
            renderResults();
        });
    });

    function syncPPButtons() {
        document.querySelectorAll('.recept-perpage-btn').forEach(function(b) {
            b.classList.toggle('is-active', parseInt(b.dataset.pp) === state.perPage);
        });
    }

    // ══════════════════════════════════════════
    // KERESO + DROPDOWN
    // ══════════════════════════════════════════

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var val = this.value.trim();
            if (searchClear) searchClear.style.display = val ? 'flex' : 'none';
            state.search = val;
            state.page = 1;
            clearTimeout(searchTimer);
            if (val.length >= 2) {
                searchTimer = setTimeout(function() { showDropdown(val); }, 150);
            } else {
                closeDropdown();
            }
            clearTimeout(renderTimer);
            renderTimer = setTimeout(renderResults, 300);
        });
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2 && dropdown && dropdown.children.length > 0) {
                dropdown.classList.add('is-open');
            }
        });
    }

    if (searchClear) {
        searchClear.style.display = 'none';
        searchClear.addEventListener('click', function() {
            if (searchInput) { searchInput.value = ''; searchInput.focus(); }
            state.search = '';
            if (searchClear) searchClear.style.display = 'none';
            closeDropdown();
            state.page = 1;
            renderResults();
        });
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.recept-search-bar')) closeDropdown();
    });

    function closeDropdown() {
        if (dropdown) { dropdown.classList.remove('is-open'); }
    }

    function showDropdown(term) {
        if (!dropdown) return;
        var q = term.toLowerCase();
        var matches = RECEPT_ADATOK.filter(function(r) {
            return r.cimLower.indexOf(q) !== -1;
        }).slice(0, 6);

        if (matches.length === 0) {
            dropdown.innerHTML = '<div class="dd-empty">Nincs tal\u00E1lat</div>';
        } else {
            var html = '';
            for (var i = 0; i < matches.length; i++) {
                html += buildDropdownItem(matches[i]);
            }
            dropdown.innerHTML = html;
        }
        dropdown.classList.add('is-open');
    }

    function buildDropdownItem(r) {
        var badges = '';
        if (r.vegan) badges += '<span class="dd-badge dd-badge--vegan">\uD83C\uDF31 V</span>';
        else if (r.vegetarianus) badges += '<span class="dd-badge dd-badge--vege">\uD83C\uDF3F V</span>';
        if (r.tejmentes) badges += '<span class="dd-badge dd-badge--tejmentes">\uD83E\uDD5B\u0338</span>';
        if (r.tojasmentes) badges += '<span class="dd-badge dd-badge--tojasmentes">\uD83E\uDD5A\u0338</span>';
        if (r.nehezseg) badges += '<span class="dd-badge dd-badge--' + nehCss(r.nehezseg) + '">' + nehLabel(r.nehezseg) + '</span>';
        if (r.ido) badges += '<span class="dd-badge dd-badge--ido">\u23F1 ' + r.ido + ' perc</span>';

        var kep = r.kepSm
            ? '<img class="dd-item-img" src="' + r.kepSm + '" alt="" loading="lazy">'
            : '<span class="dd-item-img dd-item-img--placeholder">\uD83C\uDF7D</span>';

        return '<a href="' + r.url + '" class="dd-item">' + kep +
            '<div class="dd-item-body"><span class="dd-item-title">' + esc(r.cim) + '</span>' +
            (badges ? '<div class="dd-item-badges">' + badges + '</div>' : '') +
            '</div></a>';
    }

    function nehLabel(v) { return v ? v.charAt(0).toUpperCase() + v.slice(1) : ''; }
    function nehCss(v) {
        var map = { 'könnyű':'konnyu','közepes':'kozepes','nehéz':'nehez' };
        return map[v] || v;
    }

    // ══════════════════════════════════════════
    // SZURES + RENDEZES
    // ══════════════════════════════════════════

    function getFiltered() {
        var q = state.search.toLowerCase();

        return RECEPT_ADATOK.filter(function(r) {
            if (q && r.cimLower.indexOf(q) === -1) return false;

            if (state.filters.kategoria !== 'all') {
                if (r.kategoria.indexOf(state.filters.kategoria) === -1) return false;
            }
            if (state.filters.jelleg !== 'all') {
                if (r.jelleg.indexOf(state.filters.jelleg) === -1) return false;
            }
            if (state.filters.dieta.length > 0) {
                for (var d = 0; d < state.filters.dieta.length; d++) {
                    if (r.dieta.indexOf(state.filters.dieta[d]) === -1) return false;
                }
            }
            if (state.filters.nehezseg !== 'all') {
                if (r.nehezseg !== state.filters.nehezseg) return false;
            }
            if (state.filters.ido !== 'all') {
                var max = parseInt(state.filters.ido);
                if (max <= 60) { if (r.ido > max) return false; }
                else { if (r.ido <= 60) return false; }
            }
            return true;
        });
    }

    // ══════════════════════════════════════════
    // RENDERES
    // ══════════════════════════════════════════

    function renderResults() {
        var all = getFiltered();
        var total = all.length;
        var pages = Math.ceil(total / state.perPage);
        if (state.page > pages) state.page = Math.max(1, pages);

        var start = (state.page - 1) * state.perPage;
        var items = all.slice(start, start + state.perPage);

        // Count
        var hasFilter = state.search ||
            state.filters.kategoria !== 'all' ||
            state.filters.jelleg !== 'all' ||
            state.filters.dieta.length > 0 ||
            state.filters.nehezseg !== 'all' ||
            state.filters.ido !== 'all';

        if (countEl) {
            if (hasFilter) {
                countEl.innerHTML = '<strong>' + total + '</strong> / ' + totalAll + ' recept';
            } else {
                countEl.innerHTML = '<strong>' + total + '</strong> recept';
            }
        }

        if (items.length === 0) {
            grid.innerHTML =
                '<div class="recept-empty">' +
                '<div class="recept-empty-icon">\uD83D\uDD0D</div>' +
                '<div class="recept-empty-text">Nincs tal\u00E1lat</div>' +
                '<div class="recept-empty-sub">Pr\u00F3b\u00E1lj m\u00E1s keres\u0151sz\u00F3t vagy \u00E1ll\u00EDtsd \u00E1t a sz\u0171r\u0151ket.</div>' +
                '</div>';
            if (pagination) pagination.innerHTML = '';
            return;
        }

        var html = '';
        for (var i = 0; i < items.length; i++) {
            html += renderCard(items[i], i);
        }
        grid.innerHTML = html;

        renderPagination(total, pages, state.page);

        if (state.page > 1) {
            window.scrollTo({ top: grid.offsetTop - 100, behavior: 'smooth' });
        }
    }

    // ══════════════════════════════════════════
    // KARTYA RENDERES
    // ══════════════════════════════════════════

    function renderCard(r, idx) {
        var h = '';
        h += '<a href="' + r.url + '" class="recept-kartya" style="animation-delay:' + (idx * 0.04) + 's">';

        // Kep
        h += '<div class="kartya-kep">';
        if (r.kep) {
            h += '<img src="' + r.kep + '" alt="' + esc(r.cim) + '" loading="lazy">';
        } else {
            h += '<div class="kartya-kep-placeholder">\uD83C\uDF7D</div>';
        }
        if (r.nehezseg) {
            h += '<span class="kartya-nehezseg kartya-nehezseg--' + nehCss(r.nehezseg) + '">' + esc(nehLabel(r.nehezseg)) + '</span>';
        }

        // Badges bal
        h += '<div class="kartya-badges-left">';
        if (r.vegan) {
            h += '<span class="kartya-vbadge kartya-vbadge--vegan" title="Veg\u00E1n"><span class="kartya-vbadge-icon">\uD83C\uDF31</span><span class="kartya-vbadge-text">V</span></span>';
        } else if (r.vegetarianus) {
            h += '<span class="kartya-vbadge kartya-vbadge--vegetarianus" title="Veget\u00E1ri\u00E1nus"><span class="kartya-vbadge-icon">\uD83C\uDF3F</span><span class="kartya-vbadge-text">V</span></span>';
        }
        if (r.tejmentes) {
            h += '<span class="kartya-vbadge kartya-vbadge--tejmentes" title="Tejmentes"><svg class="kartya-vbadge-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M8 2h8l1 7v10a3 3 0 0 1-3 3h-4a3 3 0 0 1-3-3V9z"/><line x1="3" y1="3" x2="21" y2="21"/></svg></span>';
        }
        if (r.tojasmentes) {
            h += '<span class="kartya-vbadge kartya-vbadge--tojasmentes" title="Toj\u00E1smentes"><svg class="kartya-vbadge-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><ellipse cx="12" cy="14" rx="7" ry="9"/><line x1="3" y1="3" x2="21" y2="21"/></svg></span>';
        }
        h += '</div>';
        h += '</div>';

        // Body
        h += '<div class="kartya-body">';
        h += '<h2 class="kartya-cim">' + esc(r.cim) + '</h2>';

        h += '<div class="kartya-meta">';
        if (r.ido) h += '<span class="kartya-meta-item">\u23F1 ' + r.ido + ' perc</span>';
        if (r.adagok) h += '<span class="kartya-meta-item">\uD83C\uDF7D ' + r.adagok + ' adag</span>';
        h += '</div>';

        h += '<div class="kartya-makro">';
        h += '<span class="kartya-makro-item"><strong>' + r.kcal + '</strong> kcal</span>';
        h += '<span class="kartya-makro-item"><strong>' + r.feherje + 'g</strong> feh\u00E9rje</span>';
        h += '</div>';

        if (r.cimkek && r.cimkek.length) {
            h += '<div class="kartya-cimkek">';
            for (var j = 0; j < r.cimkek.length; j++) {
                h += '<span class="kartya-cimke kartya-cimke--' + r.cimkek[j].slug + '">' + esc(r.cimkek[j].name) + '</span>';
            }
            h += '</div>';
        }

        h += '</div>';
        h += '</a>';
        return h;
    }

    // ══════════════════════════════════════════
    // LAPOZAS
    // ══════════════════════════════════════════

    function renderPagination(total, pages, current) {
        if (!pagination) return;
        if (pages <= 1) { pagination.innerHTML = ''; return; }

        var h = '<div class="recept-pag-inner">';
        h += '<button class="recept-pag-btn' + (current <= 1 ? ' is-disabled' : '') + '" data-page="' + (current - 1) + '">\u2039</button>';

        var start = Math.max(1, current - 2);
        var end = Math.min(pages, current + 2);

        if (start > 1) {
            h += '<button class="recept-pag-btn" data-page="1">1</button>';
            if (start > 2) h += '<span class="recept-pag-dots">\u2026</span>';
        }
        for (var i = start; i <= end; i++) {
            h += '<button class="recept-pag-btn' + (i === current ? ' is-active' : '') + '" data-page="' + i + '">' + i + '</button>';
        }
        if (end < pages) {
            if (end < pages - 1) h += '<span class="recept-pag-dots">\u2026</span>';
            h += '<button class="recept-pag-btn" data-page="' + pages + '">' + pages + '</button>';
        }

        h += '<button class="recept-pag-btn' + (current >= pages ? ' is-disabled' : '') + '" data-page="' + (current + 1) + '">\u203A</button>';
        h += '</div>';

        pagination.innerHTML = h;

        pagination.querySelectorAll('.recept-pag-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (this.classList.contains('is-disabled') || this.classList.contains('is-active')) return;
                state.page = parseInt(this.dataset.page);
                renderResults();
            });
        });
    }

    // ══════════════════════════════════════════
    // RESPONSIVE
    // ═════════════════════���════════════════════

    function handleResize() {
        if (window.innerWidth <= 900) {
            grid.style.gridTemplateColumns = 'repeat(2, 1fr)';
        } else {
            applyGridCols();
        }
        if (panelOpen && panel && panelInner) {
            panel.style.maxHeight = panelInner.scrollHeight + 'px';
        }
    }
    window.addEventListener('resize', handleResize);
    handleResize();

    // ══════════════════════════════════════════
    // SEGED
    // ══════════════════════════════════════════

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    // ══════════════════════════════════════════
    // INIT
    // ══════════════════════════════════════════

    renderResults();

})();