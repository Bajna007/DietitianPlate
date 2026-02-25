<?php

/**
 * 21. – KOMBINÁLT ALAPANYAG KERESŐ (OFF + USDA)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// 22 – KOMBINÁLT ALAPANYAG KERESŐ (OFF + USDA)
//
// Admin oldal: Alapanyagok → 🔍 Kereső
// Egyszerre keres mindkét adatbázisban
// Összehasonlítja a tápértékeket egym��s mellett
// Importálható bármelyik forrásból 1 kattintással
// ============================================================


// ── 1. ADMIN MENÜ ────────────────────────────────────────────

function combined_search_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=alapanyag',
        'Kombinált Kereső',
        '🔍 Kereső',
        'manage_options',
        'combined-search',
        'combined_search_admin_page'
    );
}
add_action( 'admin_menu', 'combined_search_admin_menu' );


// ── 2. ADMIN OLDAL HTML ──────────────────────────────────────

function combined_search_admin_page() {
    ?>
    <div class="wrap">
        <h1>🔍 Kombinált Alapanyag Kereső</h1>
        <p style="font-size: 0.92rem; color: #555; max-width: 700px; line-height: 1.5;">
            Egyszerre keres az <strong style="color: #f97316;">OpenFoodFacts</strong> és
            <strong style="color: #2563eb;">USDA FoodData Central</strong> adatbázisokban.
            Összehasonlítja a tápértékeket és importálhatod bármelyiket.
        </p>

        <?php // ── KERESŐMEZŐ ── ?>
        <div style="
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 20px 0;
            max-width: 900px;
        ">
            <div style="display: flex; gap: 8px; align-items: end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-weight: 600; font-size: 0.88rem; display: block; margin-bottom: 4px;">
                        Keresőszó:
                    </label>
                    <input type="text"
                           id="cs-query"
                           placeholder="pl. csirkemell, alma, rizs, chicken breast..."
                           style="width: 100%; font-size: 14px; padding: 8px 12px;">
                </div>
                <div>
                    <label style="font-weight: 600; font-size: 0.88rem; display: block; margin-bottom: 4px;">
                        OFF ország:
                    </label>
                    <select id="cs-country" style="font-size: 14px; padding: 8px;">
                        <option value="">🌍 Összes</option>
                        <option value="hungary" selected>🇭🇺 Magyarország</option>
                        <option value="germany">🇩🇪 Németország</option>
                        <option value="austria">🇦🇹 Ausztria</option>
                        <option value="united-states">🇺🇸 USA</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight: 600; font-size: 0.88rem; display: block; margin-bottom: 4px;">
                        USDA típus:
                    </label>
                    <select id="cs-usda-type" style="font-size: 14px; padding: 8px;">
                        <option value="Foundation,SR Legacy">Foundation + SR Legacy</option>
                        <option value="Foundation">Csak Foundation</option>
                        <option value="SR Legacy">Csak SR Legacy</option>
                    </select>
                </div>
                <button id="cs-search-btn"
                        class="button button-primary"
                        style="font-size: 14px; padding: 8px 24px; height: 38px;">
                    🔍 Keresés
                </button>
            </div>
        </div>

        <?php // ── LOADING ── ?>
        <div id="cs-loading" style="
            display: none;
            max-width: 900px;
            margin: 20px 0;
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 0.92rem;
        ">
            <div style="font-size: 2rem; margin-bottom: 8px;">⏳</div>
            <div>Keresés mindkét adatbázisban...</div>
            <div style="display: flex; justify-content: center; gap: 20px; margin-top: 12px;">
                <span id="cs-status-off" style="color: #f97316;">🍊 OFF: keresés...</span>
                <span id="cs-status-usda" style="color: #2563eb;">🇺🇸 USDA: keresés...</span>
            </div>
        </div>

        <?php // ── EREDMÉNYEK ── ?>
        <div id="cs-results" style="
            display: none;
            max-width: 900px;
            margin: 20px 0;
        ">
            <?php // ── ÖSSZEFOGLALÓ ── ?>
            <div style="
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 12px;
                margin-bottom: 16px;
            ">
                <div style="text-align: center; padding: 12px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px;">
                    <div style="font-size: 1.4rem; font-weight: 700; color: #f97316;" id="cs-count-off">0</div>
                    <div style="font-size: 0.82rem; color: #666;">🍊 OFF találat</div>
                </div>
                <div style="text-align: center; padding: 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;">
                    <div style="font-size: 1.4rem; font-weight: 700; color: #2563eb;" id="cs-count-usda">0</div>
                    <div style="font-size: 0.82rem; color: #666;">🇺🇸 USDA találat</div>
                </div>
                <div style="text-align: center; padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
                    <div style="font-size: 1.4rem; font-weight: 700; color: #16a34a;" id="cs-count-existing">0</div>
                    <div style="font-size: 0.82rem; color: #666;">✅ Már importálva</div>
                </div>
            </div>

            <?php // ── SZŰRŐ TAB-OK ── ?>
            <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                <button class="button cs-tab active" data-tab="all">Összes</button>
                <button class="button cs-tab" data-tab="off" style="color: #f97316;">🍊 OFF</button>
                <button class="button cs-tab" data-tab="usda" style="color: #2563eb;">🇺🇸 USDA</button>
                <button class="button cs-tab" data-tab="both" style="color: #16a34a;">🔗 Mindkettőben</button>
            </div>

            <?php // ── LISTA ── ?>
            <div id="cs-list" style="max-width: 900px;"></div>
        </div>
    </div>

    <style>
    .cs-tab.active {
        background: #1e293b !important;
        color: #fff !important;
        border-color: #1e293b !important;
    }
    .cs-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 12px;
        overflow: hidden;
    }
    .cs-card-header {
        padding: 12px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
    }
    .cs-card-header:hover {
        background: #f8fafc;
    }
    .cs-card-body {
        display: none;
        padding: 0;
    }
    .cs-card.open .cs-card-body {
        display: block;
    }
    .cs-compare-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }
    .cs-compare-table th {
        background: #f8fafc;
        padding: 8px 12px;
        text-align: left;
        font-weight: 600;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.82rem;
    }
    .cs-compare-table td {
        padding: 6px 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .cs-compare-table tr:hover td {
        background: #fafbfc;
    }
    .cs-source-badge {
        font-size: 0.72rem;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 600;
        white-space: nowrap;
    }
    .cs-source-off {
        background: #fff7ed;
        color: #f97316;
    }
    .cs-source-usda {
        background: #eff6ff;
        color: #2563eb;
    }
    .cs-source-both {
        background: #f0fdf4;
        color: #16a34a;
    }
    .cs-better {
        font-weight: 700;
        color: #16a34a;
    }
    .cs-worse {
        color: #94a3b8;
    }
    .cs-match-indicator {
        font-size: 0.78rem;
        padding: 3px 8px;
        border-radius: 4px;
        background: #f0fdf4;
        color: #16a34a;
        font-weight: 500;
    }
    </style>
    <?php
}


// ── 3. ADMIN SCRIPTS ─────────────────────────────────────────

function combined_search_admin_scripts( $hook ) {
    if ( $hook !== 'alapanyag_page_combined-search' ) return;

    $js = <<<'JSEOF'
(function() {
    'use strict';

    let allResults = [];
    let currentTab = 'all';

    const $ = document.getElementById.bind(document);

    // ── KERESÉS ──
    $('cs-search-btn').addEventListener('click', function() {
        var query = $('cs-query').value.trim();
        if (!query) return;

        var btn = this;
        btn.disabled = true;
        btn.textContent = '⏳ Keresés...';
        allResults = [];
        currentTab = 'all';

        $('cs-loading').style.display = 'block';
        $('cs-results').style.display = 'none';
        $('cs-status-off').textContent = '🍊 OFF: keresés...';
        $('cs-status-usda').textContent = '🇺🇸 USDA: keresés...';

        var country = $('cs-country').value;
        var usdaType = $('cs-usda-type').value;

        // Párhuzamos keresés mindkét API-ban
        var offDone = false;
        var usdaDone = false;
        var offResults = [];
        var usdaResults = [];

        function checkBothDone() {
            if (!offDone || !usdaDone) return;

            btn.disabled = false;
            btn.textContent = '🔍 Keresés';
            $('cs-loading').style.display = 'none';

            // Eredmények összefésülés + smart match
            allResults = mergeResults(offResults, usdaResults);
            showResults();
        }

        // ── OFF keresés ──
        var fd1 = new FormData();
        fd1.append('action', 'cs_search_off');
        fd1.append('nonce', csData.nonce);
        fd1.append('query', query);
        fd1.append('country', country);

        fetch(csData.ajaxUrl, { method: 'POST', body: fd1 })
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            offDone = true;
            if (resp.success) {
                offResults = resp.data.foods || [];
                $('cs-status-off').innerHTML = '🍊 OFF: <strong>' + offResults.length + '</strong> találat ✅';
            } else {
                $('cs-status-off').innerHTML = '🍊 OFF: ❌ ' + (resp.data || 'Hiba');
            }
            checkBothDone();
        })
        .catch(function() {
            offDone = true;
            $('cs-status-off').innerHTML = '🍊 OFF: ❌ Hálózati hiba';
            checkBothDone();
        });

        // ── USDA keresés ──
        var fd2 = new FormData();
        fd2.append('action', 'cs_search_usda');
        fd2.append('nonce', csData.nonce);
        fd2.append('query', query);
        fd2.append('usda_type', usdaType);

        fetch(csData.ajaxUrl, { method: 'POST', body: fd2 })
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            usdaDone = true;
            if (resp.success) {
                usdaResults = resp.data.foods || [];
                $('cs-status-usda').innerHTML = '🇺🇸 USDA: <strong>' + usdaResults.length + '</strong> találat ✅';
            } else {
                $('cs-status-usda').innerHTML = '🇺🇸 USDA: ❌ ' + (resp.data || 'Hiba');
            }
            checkBothDone();
        })
        .catch(function() {
            usdaDone = true;
            $('cs-status-usda').innerHTML = '🇺🇸 USDA: ❌ Hálózati hiba';
            checkBothDone();
        });
    });

    // ── Enter keresés ──
    $('cs-query').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') $('cs-search-btn').click();
    });

    // ── Eredmények összefésülés ──
    function mergeResults(offItems, usdaItems) {
        var results = [];

        // OFF elemek hozzáadása
        offItems.forEach(function(off) {
            var matchedUsda = null;
            var bestScore = 0;

            // Keresés USDA-ban hasonló név alapján
            usdaItems.forEach(function(usda) {
                var score = similarityScore(
                    normalizeName(off.name),
                    normalizeName(usda.name)
                );
                if (score > bestScore && score >= 60) {
                    bestScore = score;
                    matchedUsda = usda;
                }
            });

            results.push({
                name: off.name_hu || off.name,
                name_off: off.name,
                name_hu: off.name_hu,
                name_usda: matchedUsda ? matchedUsda.name : null,
                source: matchedUsda ? 'both' : 'off',
                match_score: bestScore,
                barcode: off.barcode,
                fdc_id: matchedUsda ? matchedUsda.fdc_id : null,
                brands: off.brands,
                image_url: off.image_url,
                off_nutrients: {
                    kcal: off.kcal,
                    protein: off.protein,
                    carb: off.carb,
                    fat: off.fat,
                    fiber: off.fiber,
                    sugar: off.sugar,
                    saturated: off.saturated,
                    sodium: off.sodium
                },
                usda_nutrients: matchedUsda ? {
                    kcal: matchedUsda.kcal,
                    protein: matchedUsda.protein,
                    carb: matchedUsda.carb,
                    fat: matchedUsda.fat,
                    fiber: matchedUsda.fiber,
                    sugar: matchedUsda.sugar,
                    saturated: matchedUsda.saturated,
                    sodium: matchedUsda.sodium
                } : null,
                already_imported: off.already_imported,
                existing_id: off.existing_id,
                existing_name: off.existing_name,
                usda_already: matchedUsda ? matchedUsda.already_imported : false
            });

            // Matched USDA-t jelöljük hogy ne legyen dupla
            if (matchedUsda) matchedUsda._matched = true;
        });

        // Nem matchelt USDA elemek
        usdaItems.forEach(function(usda) {
            if (usda._matched) return;

            results.push({
                name: usda.name,
                name_off: null,
                name_hu: null,
                name_usda: usda.name,
                source: 'usda',
                match_score: 0,
                barcode: null,
                fdc_id: usda.fdc_id,
                brands: null,
                image_url: null,
                off_nutrients: null,
                usda_nutrients: {
                    kcal: usda.kcal,
                    protein: usda.protein,
                    carb: usda.carb,
                    fat: usda.fat,
                    fiber: usda.fiber,
                    sugar: usda.sugar,
                    saturated: usda.saturated,
                    sodium: usda.sodium
                },
                already_imported: usda.already_imported,
                existing_id: usda.existing_id,
                existing_name: '',
                usda_already: usda.already_imported
            });
        });

        return results;
    }

    // ── Név normalizálás ──
    function normalizeName(name) {
        if (!name) return '';
        return name.toLowerCase()
            .replace(/[^a-záéíóöőúüű0-9\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    // ── Hasonlóság ──
    function similarityScore(a, b) {
        if (!a || !b) return 0;
        if (a === b) return 100;
        if (a.indexOf(b) !== -1 || b.indexOf(a) !== -1) {
            return Math.round((Math.min(a.length, b.length) / Math.max(a.length, b.length)) * 100);
        }
        var words1 = a.split(' ').filter(Boolean);
        var words2 = b.split(' ').filter(Boolean);
        var common = words1.filter(function(w) { return words2.indexOf(w) !== -1; });
        return Math.round((common.length / Math.max(words1.length, words2.length)) * 100);
    }

    // ── Eredmények megjelenítés ──
    function showResults() {
        $('cs-results').style.display = 'block';

        var offCount = allResults.filter(function(r) { return r.source === 'off' || r.source === 'both'; }).length;
        var usdaCount = allResults.filter(function(r) { return r.source === 'usda' || r.source === 'both'; }).length;
        var existCount = allResults.filter(function(r) { return r.already_imported || r.usda_already; }).length;

        $('cs-count-off').textContent = offCount;
        $('cs-count-usda').textContent = usdaCount;
        $('cs-count-existing').textContent = existCount;

        renderList();
    }

    // ── Lista renderelés ──
    function renderList() {
        var filtered = allResults;
        if (currentTab === 'off') filtered = allResults.filter(function(r) { return r.source === 'off'; });
        if (currentTab === 'usda') filtered = allResults.filter(function(r) { return r.source === 'usda'; });
        if (currentTab === 'both') filtered = allResults.filter(function(r) { return r.source === 'both'; });

        if (filtered.length === 0) {
            $('cs-list').innerHTML = '<div style="padding: 20px; text-align: center; color: #94a3b8;">Nincs találat ebben a kategóriában.</div>';
            return;
        }

        var html = '';
        filtered.forEach(function(item, idx) {
            var sourceClass = 'cs-source-' + item.source;
            var sourceLabel = item.source === 'both' ? '🔗 Mindkettő' : item.source === 'off' ? '🍊 OFF' : '🇺🇸 USDA';

            html += '<div class="cs-card" data-index="' + idx + '">';

            // ── HEADER ──
            html += '<div class="cs-card-header" onclick="this.parentElement.classList.toggle(\'open\')">';
            html += '<div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0;">';
            if (item.image_url) {
                html += '<img src="' + item.image_url + '" style="width: 32px; height: 32px; border-radius: 4px; object-fit: cover;" loading="lazy">';
            }
            html += '<div style="min-width: 0;">';
            html += '<strong style="color: #1e293b;">' + escHtml(item.name) + '</strong>';
            if (item.name_hu && item.name_hu !== item.name) {
                html += ' <span style="color: #16a34a; font-size: 0.82rem;">🇭🇺 ' + escHtml(item.name_hu) + '</span>';
            }
            if (item.brands) {
                html += ' <span style="color: #94a3b8; font-size: 0.78rem;">(' + escHtml(item.brands) + ')</span>';
            }
            html += '</div>';
            html += '</div>';
            html += '<div style="display: flex; gap: 6px; align-items: center;">';
            html += '<span class="cs-source-badge ' + sourceClass + '">' + sourceLabel + '</span>';
            if (item.source === 'both') {
                html += '<span class="cs-match-indicator">Match: ' + item.match_score + '%</span>';
            }
            if (item.already_imported || item.usda_already) {
                html += '<span style="font-size: 0.78rem; color: #16a34a;">✅</span>';
            }
            html += '<span style="font-size: 1.2rem; color: #cbd5e1; transition: transform 0.2s;">▼</span>';
            html += '</div>';
            html += '</div>';

            // ── BODY: összehasonlító táblázat ──
            html += '<div class="cs-card-body">';

            // Gyors makrók
            html += '<div style="padding: 12px 16px; background: #f8fafc; display: flex; gap: 16px; flex-wrap: wrap; font-size: 0.85rem;">';
            var offN = item.off_nutrients;
            var usdaN = item.usda_nutrients;

            if (offN) {
                html += '<div>🍊 <strong>OFF</strong>: ';
                html += (offN.kcal || '?') + ' kcal | F: ' + (offN.protein || '?') + 'g | Sz: ' + (offN.carb || '?') + 'g | Zs: ' + (offN.fat || '?') + 'g';
                html += '</div>';
            }
            if (usdaN) {
                html += '<div>🇺🇸 <strong>USDA</strong>: ';
                html += (usdaN.kcal || '?') + ' kcal | F: ' + (usdaN.protein || '?') + 'g | Sz: ' + (usdaN.carb || '?') + 'g | Zs: ' + (usdaN.fat || '?') + 'g';
                html += '</div>';
            }
            html += '</div>';

            // Részletes összehasonlító táblázat (ha mindkettő)
            if (offN && usdaN) {
                html += '<table class="cs-compare-table">';
                html += '<tr><th>Tápanyag</th><th>🍊 OFF</th><th>🇺🇸 USDA</th><th>Eltérés</th></tr>';

                var nutrients = [
                    { label: 'Kalória', key: 'kcal', unit: 'kcal' },
                    { label: 'Fehérje', key: 'protein', unit: 'g' },
                    { label: 'Szénhidrát', key: 'carb', unit: 'g' },
                    { label: 'Zsír', key: 'fat', unit: 'g' },
                    { label: 'Rost', key: 'fiber', unit: 'g' },
                    { label: 'Cukor', key: 'sugar', unit: 'g' },
                    { label: 'Telített zsír', key: 'saturated', unit: 'g' },
                    { label: 'Nátrium', key: 'sodium', unit: 'mg' }
                ];

                nutrients.forEach(function(n) {
                    var offVal = offN[n.key];
                    var usdaVal = usdaN[n.key];
                    var diff = '';
                    var diffColor = '#94a3b8';

                    if (offVal !== null && offVal !== undefined && usdaVal !== null && usdaVal !== undefined) {
                        var d = Math.abs(offVal - usdaVal);
                        if (d < 0.1) {
                            diff = '≈ egyezik';
                            diffColor = '#16a34a';
                        } else {
                            var pct = offVal > 0 ? Math.round((d / offVal) * 100) : 0;
                            diff = (usdaVal > offVal ? '+' : '') + (usdaVal - offVal).toFixed(1) + ' (' + pct + '%)';
                            diffColor = pct > 20 ? '#dc2626' : '#d97706';
                        }
                    }

                    html += '<tr>';
                    html += '<td><strong>' + n.label + '</strong></td>';
                    html += '<td>' + (offVal !== null && offVal !== undefined ? offVal + ' ' + n.unit : '<span style="color:#cbd5e1">–</span>') + '</td>';
                    html += '<td>' + (usdaVal !== null && usdaVal !== undefined ? usdaVal + ' ' + n.unit : '<span style="color:#cbd5e1">–</span>') + '</td>';
                    html += '<td style="color:' + diffColor + '; font-size: 0.82rem;">' + (diff || '–') + '</td>';
                    html += '</tr>';
                });

                html += '</table>';
            } else if (offN || usdaN) {
                // Csak egy forrás
                var single = offN || usdaN;
                var srcLabel = offN ? '🍊 OFF' : '🇺🇸 USDA';
                html += '<table class="cs-compare-table">';
                html += '<tr><th>Tápanyag</th><th>' + srcLabel + '</th></tr>';
                var nutrients2 = [
                    { label: 'Kalória', key: 'kcal', unit: 'kcal' },
                    { label: 'Fehérje', key: 'protein', unit: 'g' },
                    { label: 'Szénhidrát', key: 'carb', unit: 'g' },
                    { label: 'Zsír', key: 'fat', unit: 'g' },
                    { label: 'Rost', key: 'fiber', unit: 'g' },
                    { label: 'Cukor', key: 'sugar', unit: 'g' },
                    { label: 'Telített zsír', key: 'saturated', unit: 'g' },
                    { label: 'Nátrium', key: 'sodium', unit: 'mg' }
                ];
                nutrients2.forEach(function(n) {
                    var val = single[n.key];
                    html += '<tr><td><strong>' + n.label + '</strong></td>';
                    html += '<td>' + (val !== null && val !== undefined ? val + ' ' + n.unit : '–') + '</td></tr>';
                });
                html += '</table>';
            }

            // ── Import gombok ──
            html += '<div style="padding: 12px 16px; border-top: 1px solid #e2e8f0; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">';

            if (item.already_imported) {
                html += '<span style="font-size: 0.85rem; color: #16a34a;">✅ Már importálva: #' + item.existing_id + '</span>';
            } else if (item.barcode) {
                html += '<button class="button cs-import-off" data-barcode="' + item.barcode + '" style="font-size: 0.82rem;">📥 Import OFF-ből</button>';
            }

            if (item.usda_already) {
                html += '<span style="font-size: 0.85rem; color: #16a34a;">✅ USDA már importálva</span>';
            } else if (item.fdc_id) {
                html += '<button class="button cs-import-usda" data-fdc-id="' + item.fdc_id + '" style="font-size: 0.82rem;">📥 Import USDA-ból</button>';
            }

            if (item.barcode) {
                html += '<a href="https://world.openfoodfacts.org/product/' + item.barcode + '" target="_blank" style="font-size: 0.78rem; color: #94a3b8;">OFF oldal ↗</a>';
            }
            if (item.fdc_id) {
                html += '<a href="https://fdc.nal.usda.gov/fdc-app.html#/food-details/' + item.fdc_id + '/nutrients" target="_blank" style="font-size: 0.78rem; color: #94a3b8;">USDA oldal ↗</a>';
            }

            html += '</div>';

            html += '</div>'; // card-body
            html += '</div>'; // card
        });

        $('cs-list').innerHTML = html;
        bindImportButtons();
    }

    // ── Import gombok kötés ──
    function bindImportButtons() {
        document.querySelectorAll('.cs-import-off').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var barcode = this.dataset.barcode;
                this.disabled = true;
                this.textContent = '⏳...';
                var fd = new FormData();
                fd.append('action', 'off_import_single_product');
                fd.append('nonce', csData.offNonce);
                fd.append('barcode', barcode);
                var btn2 = this;
                fetch(csData.ajaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(resp) {
                    btn2.textContent = resp.success ? '✅ Kész' : '❌ Hiba';
                    btn2.style.color = resp.success ? '#16a34a' : '#dc2626';
                    if (!resp.success) btn2.title = resp.data;
                });
            });
        });

        document.querySelectorAll('.cs-import-usda').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var fdcId = this.dataset.fdcId;
                this.disabled = true;
                this.textContent = '⏳...';
                var fd = new FormData();
                fd.append('action', 'usda_import_single');
                fd.append('nonce', csData.usdaNonce);
                fd.append('fdc_id', fdcId);
                var btn2 = this;
                fetch(csData.ajaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(resp) {
                    btn2.textContent = resp.success ? '✅ Kész' : '❌ Hiba';
                    btn2.style.color = resp.success ? '#16a34a' : '#dc2626';
                    if (!resp.success) btn2.title = resp.data;
                });
            });
        });
    }

    // ── Tab kezelés ──
    document.querySelectorAll('.cs-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.cs-tab').forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');
            currentTab = this.dataset.tab;
            renderList();
        });
    });

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }
})();
JSEOF;

    wp_register_script( 'cs-js', false, [], '1.0', true );
    wp_enqueue_script( 'cs-js' );
    wp_add_inline_script( 'cs-js', $js );

    wp_localize_script( 'cs-js', 'csData', [
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'cs_search_nonce' ),
        'offNonce'  => wp_create_nonce( 'off_import_nonce' ),
        'usdaNonce' => wp_create_nonce( 'usda_import_nonce' ),
    ] );
}
add_action( 'admin_enqueue_scripts', 'combined_search_admin_scripts' );


// ── 4. AJAX: OFF KERESÉS ────────────────────────────────────

function cs_search_off_handler() {
    check_ajax_referer( 'cs_search_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Nincs jogosultságod.' );
    }

    $query   = sanitize_text_field( $_POST['query'] ?? '' );
    $country = sanitize_text_field( $_POST['country'] ?? '' );

    if ( empty( $query ) ) {
        wp_send_json_error( 'Üres keresés.' );
    }

    $params = [
        'search_terms'  => $query,
        'search_simple' => 1,
        'action'        => 'process',
        'json'          => 'true',
        'page_size'     => 15,
        'page'          => 1,
        'fields'        => 'code,product_name,product_name_hu,nutriments,image_front_small_url,brands',
    ];

    if ( ! empty( $country ) ) {
        $params['tagtype_0']      = 'countries';
        $params['tag_contains_0'] = 'contains';
        $params['tag_0']          = $country;
    }

    $api_url = add_query_arg( $params, 'https://world.openfoodfacts.org/cgi/search.pl' );

    $response = wp_remote_get( $api_url, [
        'timeout' => 15,
        'headers' => [
            'User-Agent' => 'TapanyagLexikon/1.0 (WordPress plugin; azsupp3387@gmail.com)',
        ],
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'OFF API hiba: ' . $response->get_error_message() );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $foods = [];

    foreach ( ( $body['products'] ?? [] ) as $product ) {
        $barcode = isset( $product['code'] ) ? trim( $product['code'] ) : '';
        if ( empty( $barcode ) ) continue;

        $name    = isset( $product['product_name'] ) ? trim( $product['product_name'] ) : '';
        $name_hu = isset( $product['product_name_hu'] ) ? trim( $product['product_name_hu'] ) : '';
        if ( empty( $name ) && empty( $name_hu ) ) continue;

        $n = isset( $product['nutriments'] ) ? $product['nutriments'] : [];

        $existing = get_posts( [
            'post_type'      => 'alapanyag',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'meta_query'     => [ [ 'key' => 'off_barcode', 'value' => $barcode ] ],
        ] );

        $foods[] = [
            'barcode'          => $barcode,
            'name'             => $name,
            'name_hu'          => $name_hu,
            'brands'           => isset( $product['brands'] ) ? $product['brands'] : '',
            'image_url'        => isset( $product['image_front_small_url'] ) ? $product['image_front_small_url'] : '',
            'kcal'             => isset( $n['energy-kcal_100g'] ) ? round( $n['energy-kcal_100g'], 1 ) : null,
            'protein'          => isset( $n['proteins_100g'] ) ? round( $n['proteins_100g'], 1 ) : null,
            'carb'             => isset( $n['carbohydrates_100g'] ) ? round( $n['carbohydrates_100g'], 1 ) : null,
            'fat'              => isset( $n['fat_100g'] ) ? round( $n['fat_100g'], 1 ) : null,
            'fiber'            => isset( $n['fiber_100g'] ) ? round( $n['fiber_100g'], 1 ) : null,
            'sugar'            => isset( $n['sugars_100g'] ) ? round( $n['sugars_100g'], 1 ) : null,
            'saturated'        => isset( $n['saturated-fat_100g'] ) ? round( $n['saturated-fat_100g'], 1 ) : null,
            'sodium'           => isset( $n['sodium_100g'] ) ? round( $n['sodium_100g'] * 1000, 1 ) : null,
            'already_imported' => ! empty( $existing ),
            'existing_id'      => ! empty( $existing ) ? $existing[0]->ID : 0,
            'existing_name'    => ! empty( $existing ) ? $existing[0]->post_title : '',
        ];
    }

    wp_send_json_success( [ 'foods' => $foods ] );
}
add_action( 'wp_ajax_cs_search_off', 'cs_search_off_handler' );


// ── 5. AJAX: USDA KERESÉS ───────────────────────────────────

function cs_search_usda_handler() {
    check_ajax_referer( 'cs_search_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Nincs jogosultságod.' );
    }

    if ( ! defined( 'USDA_API_KEY' ) || empty( USDA_API_KEY ) ) {
        wp_send_json_error( 'USDA API key nincs beállítva.' );
    }

    $query     = sanitize_text_field( $_POST['query'] ?? '' );
    $usda_type = sanitize_text_field( $_POST['usda_type'] ?? 'Foundation,SR Legacy' );

    if ( empty( $query ) ) {
        wp_send_json_error( 'Üres keresés.' );
    }

    $data_types = array_map( 'trim', explode( ',', $usda_type ) );

    $api_url = 'https://api.nal.usda.gov/fdc/v1/foods/search?api_key=' . USDA_API_KEY;

    $response = wp_remote_post( $api_url, [
        'timeout' => 15,
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body'    => wp_json_encode( [
            'query'    => $query,
            'dataType' => $data_types,
            'pageSize' => 15,
        ] ),
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'USDA API hiba: ' . $response->get_error_message() );
    }

    $http_code = wp_remote_retrieve_response_code( $response );
    if ( $http_code !== 200 ) {
        wp_send_json_error( 'USDA API HTTP ' . $http_code );
    }

    $body  = json_decode( wp_remote_retrieve_body( $response ), true );
    $foods = [];

    foreach ( ( $body['foods'] ?? [] ) as $food ) {
        $fdc_id = intval( $food['fdcId'] ?? 0 );
        if ( ! $fdc_id ) continue;

        $name = isset( $food['description'] ) ? trim( $food['description'] ) : '';
        if ( empty( $name ) ) continue;

        $kcal = $prot = $carb = $fat = $fiber = $sugar = $sat = $sodium = null;

        foreach ( ( $food['foodNutrients'] ?? [] ) as $fn ) {
            $nid = intval( $fn['nutrientId'] ?? 0 );
            $val = isset( $fn['value'] ) ? round( floatval( $fn['value'] ), 1 ) : null;
            if ( $nid === 1008 ) $kcal   = $val;
            if ( $nid === 1003 ) $prot   = $val;
            if ( $nid === 1005 ) $carb   = $val;
            if ( $nid === 1004 ) $fat    = $val;
            if ( $nid === 1079 ) $fiber  = $val;
            if ( $nid === 2000 ) $sugar  = $val;
            if ( $nid === 1258 ) $sat    = $val;
            if ( $nid === 1093 ) $sodium = $val;
        }

        $existing = get_posts( [
            'post_type'      => 'alapanyag',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'meta_query'     => [ [ 'key' => 'usda_fdc_id', 'value' => $fdc_id ] ],
        ] );

        $foods[] = [
            'fdc_id'           => $fdc_id,
            'name'             => $name,
            'data_type'        => $food['dataType'] ?? '',
            'kcal'             => $kcal,
            'protein'          => $prot,
            'carb'             => $carb,
            'fat'              => $fat,
            'fiber'            => $fiber,
            'sugar'            => $sugar,
            'saturated'        => $sat,
            'sodium'           => $sodium,
            'already_imported' => ! empty( $existing ),
            'existing_id'      => ! empty( $existing ) ? $existing[0]->ID : 0,
        ];
    }

    wp_send_json_success( [ 'foods' => $foods ] );
}
add_action( 'wp_ajax_cs_search_usda', 'cs_search_usda_handler' );
