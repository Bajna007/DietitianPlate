(function() {
    'use strict';

    var grid           = document.getElementById('ta-grid');
    var pagination     = document.getElementById('ta-pagination');
    var resultInfo     = document.getElementById('ta-result-info');
    var searchInput    = document.getElementById('ta-search');
    var searchClear    = document.getElementById('ta-search-clear');
    var searchDropdown = document.getElementById('ta-search-dropdown');
    var sortSelect     = document.getElementById('ta-sort');
    var totalCountEl   = document.getElementById('ta-total-count');
    var filterToggle   = document.getElementById('ta-filter-toggle');
    var szuroPanel     = document.getElementById('ta-szuro-panel');
    var szuroPanelInner= document.getElementById('ta-szuro-panel-inner');
    var szuroReset     = document.getElementById('ta-szuro-reset');

    if (!grid || typeof TAPANYAG_ADATOK === 'undefined') return;

    var state = {
        page: 1,
        search: '',
        sort: 'title_asc',
        perPage: 24,
        cols: 4,
        filters: {
            tapanyag_tipus: null,
            oldhatosag: null,
            tapanyag_csoport: null,
            tapanyag_hatas: null,
            esszencialis: null
        }
    };

    var panelOpen = false;
    var searchTimer = null;
    var renderTimer = null;

    var tipusClass = {
        'Vitamin': 'vitamin',
        'Zsírsav': 'zsirsav',
        'Szénhidrát': 'szenhidrat',
        'Aminosav': 'aminosav',
        'Ásványi anyag': 'asvany'
    };

    var oldhatosagClass = {
        'Vízben oldódó': 'vizben',
        'Zsírban oldódó': 'zsirban'
    };

    var savedPP = localStorage.getItem('ta_per_page');
    if (savedPP && ['24','36','48'].indexOf(savedPP) !== -1) {
        state.perPage = parseInt(savedPP);
    }
    var savedCols = localStorage.getItem('ta_grid_cols');
    if (savedCols && ['3','4','5'].indexOf(savedCols) !== -1) {
        state.cols = parseInt(savedCols);
    }

    applyGridCols();
    syncPPButtons();
    syncColButtons();


    // ══════════════════════════════════════════════════════
    // SZŰRŐ PANEL TOGGLE
    // ══════════════════════════════════════════════════════

    filterToggle.addEventListener('click', function() {
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
    // CHIP SZŰRŐK
    // ══════════════════════════════════════════════════════

    document.querySelectorAll('.ta-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            var tax = this.dataset.tax;
            var val = this.dataset.value;

            if (state.filters[tax] === val) {
                state.filters[tax] = null;
                this.classList.remove('is-active');
            } else {
                document.querySelectorAll('.ta-chip[data-tax="' + tax + '"]').forEach(function(c) {
                    c.classList.remove('is-active');
                });
                state.filters[tax] = val;
                this.classList.add('is-active');
            }

            state.page = 1;
            updateFilterBadge();
            renderResults();
        });
    });


    // ══���═══════════════════════════════════════════════════
    // SZŰRŐ BADGE
    // ══════════════════════════════════════════════════════

    function updateFilterBadge() {
        var count = 0;
        var keys = Object.keys(state.filters);
        for (var i = 0; i < keys.length; i++) {
            if (state.filters[keys[i]]) count++;
        }

        var existing = filterToggle.querySelector('.ta-filter-badge');
        if (existing) existing.remove();

        if (count > 0) {
            var badge = document.createElement('span');
            badge.className = 'ta-filter-badge';
            badge.textContent = count;
            filterToggle.appendChild(badge);
        }
    }


    // ══════════════════════════════════════════════════════
    // ALAPHELYZET
    // ══════════════════════════════════════════════════════

    szuroReset.addEventListener('click', function() {
        var keys = Object.keys(state.filters);
        for (var i = 0; i < keys.length; i++) {
            state.filters[keys[i]] = null;
        }
        document.querySelectorAll('.ta-chip').forEach(function(c) {
            c.classList.remove('is-active');
        });
        if (searchInput) {
            searchInput.value = '';
            state.search = '';
            searchClear.style.display = 'none';
        }
        closeDropdown();
        state.page = 1;
        updateFilterBadge();
        renderResults();
    });


    // ══════════════════════════════════════════════════════
    // OSZLOP VÁLASZTÓ
    // ══════════════════════════════════════════════════════

    document.querySelectorAll('.ta-col-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            state.cols = parseInt(this.dataset.cols);
            localStorage.setItem('ta_grid_cols', String(state.cols));
            syncColButtons();
            applyGridCols();
        });
    });

    function syncColButtons() {
        document.querySelectorAll('.ta-col-btn').forEach(function(b) {
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

    document.querySelectorAll('.ta-pp-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            state.perPage = parseInt(this.dataset.pp);
            localStorage.setItem('ta_per_page', String(state.perPage));
            syncPPButtons();
            state.page = 1;
            renderResults();
        });
    });

    function syncPPButtons() {
        document.querySelectorAll('.ta-pp-btn').forEach(function(b) {
            b.classList.toggle('is-active', parseInt(b.dataset.pp) === state.perPage);
        });
    }


    // ══════════════════════════════════════════════════════
    // RENDEZÉS
    // ══════════════════════════════════════════════════════

    sortSelect.addEventListener('change', function() {
        state.sort = this.value;
        state.page = 1;
        renderResults();
    });


    // ══════════════════════════════════════════════════════
    // KERESŐ
    // ══════════════════════════════════════════════════════

    searchInput.addEventListener('input', function() {
        var val = this.value.trim();
        searchClear.style.display = val ? 'flex' : 'none';
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
        if (this.value.trim().length >= 2 && searchDropdown.children.length > 0) {
            searchDropdown.classList.add('is-open');
        }
    });

    searchClear.addEventListener('click', function() {
        searchInput.value = '';
        state.search = '';
        this.style.display = 'none';
        closeDropdown();
        state.page = 1;
        renderResults();
        searchInput.focus();
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.ta-search-bar')) closeDropdown();
    });

    function closeDropdown() {
        searchDropdown.classList.remove('is-open');
    }

    function showDropdown(term) {
        var q = term.toLowerCase();
        var matches = TAPANYAG_ADATOK.filter(function(t) {
            return [t.cim, t.kemiai_nev].join(' ').toLowerCase().indexOf(q) !== -1;
        }).slice(0, 6);

        if (matches.length === 0) {
            searchDropdown.innerHTML = '<div class="ta-dd-empty">Nincs találat a keresésre</div>';
        } else {
            var html = '';
            for (var i = 0; i < matches.length; i++) {
                var t = matches[i];
                var imgHtml = t.kep
                    ? '<img class="ta-dd-item-img" src="' + t.kep + '" alt="' + escHtml(t.cim) + '">'
                    : '<div class="ta-dd-item-img ta-dd-item-img--placeholder">💊</div>';

                html += '<a href="' + t.url + '" class="ta-dd-item">';
                html += imgHtml;
                html += '<div class="ta-dd-item-body">';
                html += '<span class="ta-dd-item-title">' + escHtml(t.cim) + '</span>';
                if (t.kemiai_nev) {
                    html += '<span class="ta-dd-item-sub">' + escHtml(t.kemiai_nev) + '</span>';
                }
                if (t.tipus.length) {
                    html += '<span class="ta-dd-badge ta-dd-badge-tipus">' + escHtml(t.tipus[0]) + '</span>';
                }
                html += '</div></a>';
            }
            searchDropdown.innerHTML = html;
        }
        searchDropdown.classList.add('is-open');
    }


    // ══════════════════════════════════════════════════════
    // SZŰRÉS + RENDEZÉS
    // ══════════════════════════════════════════════════════

    function getFilteredSorted() {
        var q = state.search.toLowerCase();

        var filtered = TAPANYAG_ADATOK.filter(function(t) {
            if (q) {
                var haystack = [t.cim, t.kemiai_nev, t.osszefoglalo].concat(t.tipus, t.hatas).join(' ').toLowerCase();
                if (haystack.indexOf(q) === -1) return false;
            }
            if (state.filters.tapanyag_tipus && t.tipus.indexOf(state.filters.tapanyag_tipus) === -1) return false;
            if (state.filters.oldhatosag && t.oldhatosag.indexOf(state.filters.oldhatosag) === -1) return false;
            if (state.filters.tapanyag_csoport && t.csoport.indexOf(state.filters.tapanyag_csoport) === -1) return false;
            if (state.filters.tapanyag_hatas && t.hatas.indexOf(state.filters.tapanyag_hatas) === -1) return false;
            if (state.filters.esszencialis && t.esszencialis.indexOf(state.filters.esszencialis) === -1) return false;
            return true;
        });

        filtered.sort(function(a, b) {
            switch (state.sort) {
                case 'title_desc':
                    return b.cim.localeCompare(a.cim, 'hu');
                case 'tipus_asc':
                    var ta = a.tipus[0] || '';
                    var tb = b.tipus[0] || '';
                    return ta.localeCompare(tb, 'hu') || a.cim.localeCompare(b.cim, 'hu');
                default:
                    return a.cim.localeCompare(b.cim, 'hu');
            }
        });

        return filtered;
    }


    // ══════════════════════════════════════════════════════
    // RENDERELÉS
    // ══════════════════════════════════════════════════════

    function renderResults() {
        var all = getFilteredSorted();
        var total = all.length;
        var pages = Math.ceil(total / state.perPage);
        if (state.page > pages) state.page = Math.max(1, pages);

        var start = (state.page - 1) * state.perPage;
        var items = all.slice(start, start + state.perPage);

        var hasFilter = state.search || state.filters.tapanyag_tipus || state.filters.oldhatosag || state.filters.tapanyag_csoport || state.filters.tapanyag_hatas || state.filters.esszencialis;

        if (hasFilter) {
            resultInfo.innerHTML = '<strong>' + total + '</strong> / ' + totalCountEl.textContent + ' tápanyag';
        } else {
            resultInfo.innerHTML = '<strong>' + total + '</strong> tápanyag';
            totalCountEl.textContent = total;
        }

        if (items.length === 0) {
            grid.innerHTML =
                '<div class="ta-empty">' +
                '<div class="ta-empty-icon">🔍</div>' +
                '<div class="ta-empty-text">Nincs találat</div>' +
                '<div class="ta-empty-sub">Próbálj más keresőszót vagy állítsd át a szűrőket.</div>' +
                '</div>';
            pagination.innerHTML = '';
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


    // ══════════════════════════════════════════════════════
    // KÁRTYA RENDERELÉS
    // ══════════════════════════════════════════════════════

    function renderCard(t, idx) {
        var tipusNev  = t.tipus[0] || '';
        var tipusCls  = tipusClass[tipusNev] || 'vitamin';
        var oldhatNev = t.oldhatosag[0] || '';
        var oldhatCls = oldhatosagClass[oldhatNev] || '';

        var h = '';
        h += '<a href="' + t.url + '" class="ta-card" style="animation-delay:' + (idx * 0.04) + 's">';

        h += '<div class="ta-card-img">';
        if (t.kep) {
            h += '<img src="' + t.kep + '" alt="' + escHtml(t.cim) + '" loading="lazy">';
        } else {
            h += '<div class="ta-card-img-placeholder">💊</div>';
        }

        if (tipusNev) {
            h += '<span class="ta-card-tipus ta-card-tipus--' + tipusCls + '">' + escHtml(tipusNev) + '</span>';
        }

        if (oldhatNev && oldhatCls) {
            h += '<span class="ta-card-oldhat ta-card-oldhat--' + oldhatCls + '">' + escHtml(oldhatNev) + '</span>';
        }

        h += '</div>';

        h += '<div class="ta-card-body">';
        h += '<h2 class="ta-card-title">' + escHtml(t.cim) + '</h2>';

        if (t.kemiai_nev) {
            h += '<p class="ta-card-kemiai">' + escHtml(t.kemiai_nev) + '</p>';
        }

        if (t.osszefoglalo) {
            h += '<p class="ta-card-desc">' + escHtml(t.osszefoglalo) + '</p>';
        }

        if (t.hatas.length) {
            h += '<div class="ta-card-chips">';
            var maxChips = Math.min(t.hatas.length, 3);
            for (var j = 0; j < maxChips; j++) {
                h += '<span class="ta-card-chip">' + escHtml(t.hatas[j]) + '</span>';
            }
            h += '</div>';
        }

        h += '</div>';
        h += '</a>';
        return h;
    }


    // ══════════════════════════════════════════════════════
    // LAPOZÁS
    // ══════════════════════════════════════════════════════

    function renderPagination(total, pages, current) {
        if (pages <= 1) { pagination.innerHTML = ''; return; }

        var h = '<div class="ta-pag-inner">';
        h += '<button class="ta-pag-btn' + (current <= 1 ? ' is-disabled' : '') + '" data-page="' + (current - 1) + '">‹</button>';

        var start = Math.max(1, current - 2);
        var end = Math.min(pages, current + 2);

        if (start > 1) {
            h += '<button class="ta-pag-btn" data-page="1">1</button>';
            if (start > 2) h += '<span class="ta-pag-dots">…</span>';
        }
        for (var i = start; i <= end; i++) {
            h += '<button class="ta-pag-btn' + (i === current ? ' is-active' : '') + '" data-page="' + i + '">' + i + '</button>';
        }
        if (end < pages) {
            if (end < pages - 1) h += '<span class="ta-pag-dots">…</span>';
            h += '<button class="ta-pag-btn" data-page="' + pages + '">' + pages + '</button>';
        }

        h += '<button class="ta-pag-btn' + (current >= pages ? ' is-disabled' : '') + '" data-page="' + (current + 1) + '">›</button>';
        h += '</div>';

        pagination.innerHTML = h;

        pagination.querySelectorAll('.ta-pag-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (this.classList.contains('is-disabled') || this.classList.contains('is-active')) return;
                state.page = parseInt(this.dataset.page);
                renderResults();
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

    renderResults();

})();