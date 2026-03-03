/**
 * Tápanyag archív JS – v2 REWORK
 * Kliens-oldali szűrés, kártyarenderer, pagination, localStorage
 * Recept archív JS mintájára
 */
(function () {
    'use strict';

    /* ═══ ADATOK ═══ */
    const DATA = window.TAPANYAG_ADATOK || [];
    if (!DATA.length) return;

    /* ═══ DOM ELEMEK ═══ */
    const grid         = document.getElementById('ta-grid');
    const pagination   = document.getElementById('ta-pagination');
    const searchInput  = document.getElementById('ta-search');
    const searchClear  = document.getElementById('ta-search-clear');
    const dropdown     = document.getElementById('ta-dropdown');
    const filterToggle = document.getElementById('ta-filter-toggle');
    const filterPanel  = document.getElementById('ta-szuro-panel');
    const filterInner  = document.getElementById('ta-szuro-panel-inner');
    const filterReset  = document.getElementById('ta-szuro-reset');
    const resultCount  = document.getElementById('ta-result-count');

    if (!grid) return;

    /* ═══ ÁLLAPOT ═══ */
    let state = {
        search:   '',
        filters:  { tipus: 'all', oldhatosag: 'all', esszencialis: 'all' },
        page:     1,
        perPage:  parseInt(localStorage.getItem('ta_perpage')) || 24,
        cols:     parseInt(localStorage.getItem('ta_cols')) || 4,
        filtered: []
    };

    /* ═══ SEGÉDFÜGGVÉNYEK ═══ */
    const esc = (s) => {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    };

    const slugify = (s) => s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');

    const getTypusBadgeClass = (tipus) => {
        if (!tipus || !tipus.length) return 'egyeb';
        const t = tipus[0].toLowerCase();
        if (t.includes('vitamin')) return 'vitamin';
        if (t.includes('ásványi') || t.includes('asvany')) return 'asvany';
        if (t.includes('nyomelem')) return 'nyomelem';
        if (t.includes('aminosav')) return 'aminosav';
        if (t.includes('szénhidrát') || t.includes('szenhidrat')) return 'szenhidrat';
        if (t.includes('zsírsav') || t.includes('zsirsav')) return 'zsirsav';
        return 'egyeb';
    };

    /* ═══ SZŰRÉS ═══ */
    function applyFilters() {
        let items = [...DATA];

        // Keresés
        if (state.search) {
            const q = state.search.toLowerCase();
            items = items.filter(i =>
                i.cim.toLowerCase().includes(q) ||
                (i.kemiai_nev && i.kemiai_nev.toLowerCase().includes(q))
            );
        }

        // Típus
        if (state.filters.tipus !== 'all') {
            items = items.filter(i =>
                i.tipus.some(t => slugify(t) === state.filters.tipus)
            );
        }

        // Oldhatóság
        if (state.filters.oldhatosag !== 'all') {
            items = items.filter(i =>
                i.oldhatosag.some(o => slugify(o) === state.filters.oldhatosag)
            );
        }

        // Esszencialitás
        if (state.filters.esszencialis !== 'all') {
            items = items.filter(i =>
                i.esszencialis.some(e => slugify(e) === state.filters.esszencialis)
            );
        }

        state.filtered = items;
        state.page = 1;
        render();
    }

    /* ═══ KÁRTYA RENDERELÉS ═══ */
    function renderCard(item) {
        const typusClass = getTypusBadgeClass(item.tipus);
        const typusLabel = item.tipus.length ? esc(item.tipus[0]) : '';
        const oldhatLabel = item.oldhatosag.length ? esc(item.oldhatosag[0]) : '';

        let kepHtml;
        if (item.kep) {
            kepHtml = `<div class="ta-kartya-kep"><img src="${esc(item.kep)}" alt="${esc(item.cim)}" loading="lazy">${typusLabel ? `<span class="ta-kartya-tipus-badge ta-kartya-tipus-badge--${typusClass}">${typusLabel}</span>` : ''}${oldhatLabel ? `<span class="ta-kartya-oldhatosag-badge">${oldhatLabel}</span>` : ''}</div>`;
        } else {
            kepHtml = `<div class="ta-kartya-kep"><div class="ta-kartya-kep-placeholder">💊</div>${typusLabel ? `<span class="ta-kartya-tipus-badge ta-kartya-tipus-badge--${typusClass}">${typusLabel}</span>` : ''}${oldhatLabel ? `<span class="ta-kartya-oldhatosag-badge">${oldhatLabel}</span>` : ''}</div>`;
        }

        let chipsHtml = '';
        if (item.hatas && item.hatas.length) {
            const maxChips = 3;
            const chips = item.hatas.slice(0, maxChips).map(h =>
                `<span class="ta-kartya-chip ta-kartya-chip--hatas">${esc(h)}</span>`
            ).join('');
            chipsHtml = `<div class="ta-kartya-chips">${chips}</div>`;
        }
        if (item.esszencialis && item.esszencialis.length) {
            const eChips = item.esszencialis.map(e =>
                `<span class="ta-kartya-chip ta-kartya-chip--esszencialis">${esc(e)}</span>`
            ).join('');
            if (chipsHtml) {
                chipsHtml = chipsHtml.replace('</div>', eChips + '</div>');
            } else {
                chipsHtml = `<div class="ta-kartya-chips">${eChips}</div>`;
            }
        }

        const osszefoglalo = item.osszefoglalo
            ? `<div class="ta-kartya-osszefoglalo">${esc(item.osszefoglalo)}</div>`
            : '';

        const kemiai = item.kemiai_nev
            ? `<div class="ta-kartya-kemiai">${esc(item.kemiai_nev)}</div>`
            : '';

        return `<a href="${esc(item.url)}" class="ta-kartya">${kepHtml}<div class="ta-kartya-body"><h3 class="ta-kartya-cim">${esc(item.cim)}</h3>${kemiai}${osszefoglalo}${chipsHtml}</div></a>`;
    }

    /* ═══ RENDER ═══ */
    function render() {
        const items   = state.filtered;
        const total   = items.length;
        const start   = (state.page - 1) * state.perPage;
        const end     = Math.min(start + state.perPage, total);
        const pageItems = items.slice(start, end);

        // Grid
        if (total === 0) {
            grid.innerHTML = '<div class="ta-nincs">Nincs találat a szűrési feltételeknek.</div>';
        } else {
            grid.innerHTML = pageItems.map(renderCard).join('');
        }

        // Oszlopok
        grid.style.gridTemplateColumns = `repeat(${state.cols}, 1fr)`;

        // Eredmény szám
        if (resultCount) {
            resultCount.innerHTML = total === DATA.length
                ? `<strong>${total}</strong> tápanyag`
                : `<strong>${total}</strong> / ${DATA.length} tápanyag`;
        }

        // Pagination
        renderPagination(total);

        // Scroll to top (ha nem 1. oldal)
        if (state.page > 1) {
            const toolbarEl = document.querySelector('.ta-toolbar');
            if (toolbarEl) {
                toolbarEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    /* ═══ PAGINATION ═══ */
    function renderPagination(total) {
        const totalPages = Math.ceil(total / state.perPage);
        if (totalPages <= 1) { pagination.innerHTML = ''; return; }

        const current = state.page;
        let pages = [];

        // Ellipszises logika
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages.push(1);
            if (current > 3) pages.push('...');
            for (let i = Math.max(2, current - 1); i <= Math.min(totalPages - 1, current + 1); i++) {
                pages.push(i);
            }
            if (current < totalPages - 2) pages.push('...');
            pages.push(totalPages);
        }

        let html = '<div class="ta-pag-inner">';
        html += `<button class="ta-pag-btn${current === 1 ? ' is-disabled' : ''}" data-page="${current - 1}"${current === 1 ? ' disabled' : ''}>‹</button>`;

        pages.forEach(p => {
            if (p === '...') {
                html += '<span class="ta-pag-dots">…</span>';
            } else {
                html += `<button class="ta-pag-btn${p === current ? ' is-active' : ''}" data-page="${p}">${p}</button>`;
            }
        });

        html += `<button class="ta-pag-btn${current === totalPages ? ' is-disabled' : ''}" data-page="${current + 1}"${current === totalPages ? ' disabled' : ''}>›</button>`;
        html += '</div>';

        pagination.innerHTML = html;

        // Eseményfigyelők
        pagination.querySelectorAll('.ta-pag-btn:not(.is-disabled)').forEach(btn => {
            btn.addEventListener('click', () => {
                const p = parseInt(btn.dataset.page);
                if (p >= 1 && p <= totalPages) {
                    state.page = p;
                    render();
                }
            });
        });
    }

    /* ═══ KERESÉS + DROPDOWN ═══ */
    let searchTimeout;
    searchInput.addEventListener('input', () => {
        const val = searchInput.value.trim();
        searchClear.style.display = val ? 'flex' : 'none';

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            // Dropdown
            if (val.length >= 2) {
                const q = val.toLowerCase();
                const matches = DATA.filter(i =>
                    i.cim.toLowerCase().includes(q) ||
                    (i.kemiai_nev && i.kemiai_nev.toLowerCase().includes(q))
                ).slice(0, 8);

                if (matches.length) {
                    dropdown.innerHTML = matches.map(m => {
                        const typusClass = getTypusBadgeClass(m.tipus);
                        const imgHtml = m.kep
                            ? `<img src="${esc(m.kep)}" class="ta-dd-item-img" alt="">`
                            : `<div class="ta-dd-item-img--placeholder">💊</div>`;
                        const badges = [];
                        if (m.tipus.length) badges.push(`<span class="ta-dd-badge ta-dd-badge--${typusClass}">${esc(m.tipus[0])}</span>`);
                        if (m.oldhatosag.length) badges.push(`<span class="ta-dd-badge ta-dd-badge--oldhatosag">${esc(m.oldhatosag[0])}</span>`);
                        return `<a href="${esc(m.url)}" class="ta-dd-item">${imgHtml}<div class="ta-dd-item-body"><span class="ta-dd-item-title">${esc(m.cim)}</span>${m.kemiai_nev ? `<span class="ta-dd-item-sub">${esc(m.kemiai_nev)}</span>` : ''}<div class="ta-dd-item-badges">${badges.join('')}</div></div></a>`;
                    }).join('');
                    dropdown.classList.add('is-open');
                } else {
                    dropdown.innerHTML = '<div class="ta-dd-empty">Nincs találat</div>';
                    dropdown.classList.add('is-open');
                }
            } else {
                dropdown.classList.remove('is-open');
            }

            // Szűrés
            state.search = val;
            applyFilters();
        }, 200);
    });

    searchClear.addEventListener('click', () => {
        searchInput.value = '';
        searchClear.style.display = 'none';
        dropdown.classList.remove('is-open');
        state.search = '';
        applyFilters();
        searchInput.focus();
    });

    // Dropdown bezárás kattintásra
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.ta-search-bar')) {
            dropdown.classList.remove('is-open');
        }
    });

    /* ═══ SZŰRŐ PANEL ═══ */
    filterToggle.addEventListener('click', () => {
        const isOpen = filterPanel.classList.toggle('is-open');
        filterToggle.classList.toggle('is-active', isOpen);
        if (isOpen) {
            filterPanel.style.maxHeight = filterInner.scrollHeight + 'px';
        } else {
            filterPanel.style.maxHeight = '0';
        }
    });

    // Chip szűrők
    document.querySelectorAll('.ta-szuro-chips').forEach(group => {
        const filterKey = group.dataset.filter;
        group.querySelectorAll('.ta-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                group.querySelectorAll('.ta-chip').forEach(c => c.classList.remove('is-active'));
                chip.classList.add('is-active');
                state.filters[filterKey] = chip.dataset.value;
                updateFilterBadge();
                applyFilters();
            });
        });
    });

    // Reset
    filterReset.addEventListener('click', () => {
        state.filters = { tipus: 'all', oldhatosag: 'all', esszencialis: 'all' };
        document.querySelectorAll('.ta-szuro-chips').forEach(group => {
            group.querySelectorAll('.ta-chip').forEach(c => c.classList.remove('is-active'));
            const allChip = group.querySelector('[data-value="all"]');
            if (allChip) allChip.classList.add('is-active');
        });
        updateFilterBadge();
        applyFilters();
    });

    function updateFilterBadge() {
        const count = Object.values(state.filters).filter(v => v !== 'all').length;
        let badge = filterToggle.querySelector('.ta-filter-badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'ta-filter-badge';
                filterToggle.appendChild(badge);
            }
            badge.textContent = count;
        } else if (badge) {
            badge.remove();
        }
    }

    /* ═══ OSZLOPVÁLTÓ ═══ */
    document.querySelectorAll('.ta-cols-btn').forEach(btn => {
        if (parseInt(btn.dataset.cols) === state.cols) {
            document.querySelectorAll('.ta-cols-btn').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
        }
        btn.addEventListener('click', () => {
            document.querySelectorAll('.ta-cols-btn').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            state.cols = parseInt(btn.dataset.cols);
            localStorage.setItem('ta_cols', state.cols);
            render();
        });
    });

    /* ═══ OLDALANKÉNT ═══ */
    document.querySelectorAll('.ta-perpage-btn').forEach(btn => {
        if (parseInt(btn.dataset.pp) === state.perPage) {
            document.querySelectorAll('.ta-perpage-btn').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
        }
        btn.addEventListener('click', () => {
            document.querySelectorAll('.ta-perpage-btn').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            state.perPage = parseInt(btn.dataset.pp);
            localStorage.setItem('ta_perpage', state.perPage);
            state.page = 1;
            render();
        });
    });

    /* ═══ INICIALIZÁLÁS ═══ */
    state.filtered = [...DATA];
    render();

})();