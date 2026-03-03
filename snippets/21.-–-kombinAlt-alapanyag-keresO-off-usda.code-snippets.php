<?php

/**
 * 21. – KOMBINÁLT ALAPANYAG KERESŐ (OFF + USDA)
 */
/**
 * 21 – KOMBINÁLT ALAPANYAG KERESŐ (OFF + USDA) v10.1 FINAL FIXED
 *
 * Admin: Alapanyagok → 🔍 Kereső
 * Párhuzamos keresés OFF + USDA
 * Vonalkód + szöveg keresés
 * Összehasonlítás, batch import, mikrotápanyagok
 * Nutri-Score, NOVA, allergének, összetevők
 * Pagination, rendezés, cache, keresési előzmények
 * Duplikátum figyelmeztetés, adatminőség jelző
 * AbortController, nonce refresh, rate limit
 * Responsive design (mobil + desktop)
 *
 * v10.1 FIX: NOWDOC PHP echo fix, portion modal, responsive CSS
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ══════════════════════════════════════════════════════════════
// KONSTANSOK
// ══════════════════════════════════════════════════════════════

if ( ! defined( 'CS_VERSION' ) )          define( 'CS_VERSION', '10.1.0' );
if ( ! defined( 'CS_OFF_PAGE_SIZE' ) )    define( 'CS_OFF_PAGE_SIZE', 30 );
if ( ! defined( 'CS_USDA_PAGE_SIZE' ) )   define( 'CS_USDA_PAGE_SIZE', 30 );
if ( ! defined( 'CS_CACHE_TTL' ) )        define( 'CS_CACHE_TTL', 300 );
if ( ! defined( 'CS_RATE_LIMIT_SEC' ) )   define( 'CS_RATE_LIMIT_SEC', 2 );
if ( ! defined( 'CS_HISTORY_KEY' ) )      define( 'CS_HISTORY_KEY', 'cs_search_history' );
if ( ! defined( 'CS_HISTORY_MAX' ) )      define( 'CS_HISTORY_MAX', 20 );

// ══════════════════════════════════════════════════════════════
// ADMIN MENÜ
// ═══════════════════════════════════════════════════════��══════

add_action( 'admin_menu', function(): void {
    add_submenu_page(
        'edit.php?post_type=alapanyag',
        'Kombinált Kereső v10', '🔍 Kereső',
        'manage_options', 'combined-search', 'cs_admin_page'
    );
} );

// ══════════════════════════════════════════════════════════════
// ADMIN PAGE HTML
// ══════════════════════════════════════════════════════════════

function cs_admin_page(): void {
    $history = get_option( CS_HISTORY_KEY, [] );
    if ( ! is_array( $history ) ) $history = [];
    ?>
    <div class="wrap cs-wrap">
    <h1 class="cs-main-title">🔍 Kombinált Alapanyag Kereső <span class="cs-version">v<?php echo esc_html( CS_VERSION ); ?></span></h1>

    <!-- INFO PANEL -->
    <div class="cs-panel">
        <details>
            <summary class="cs-details-sum">📚 Adatbázisok magyarázata <span class="cs-hint">(kattints)</span></summary>
            <div class="cs-db-grid">
                <div class="cs-db-card cs-db-off">
                    <h4>🍊 OpenFoodFacts (OFF)</h4>
                    <p>
                        <strong>Közösségi adatbázis</strong> – bárki töltheti fel. Vonalkódos bolti termékek.
                        Magyar nevek gyakran elérhetők. Tartalmaz képet, Nutri-Score-t, NOVA csoportot, allergéneket, összetevőket.
                    </p>
                    <div class="cs-db-rating">⭐⭐⭐ Jó termékekre, de hibás adat előfordul</div>
                </div>
                <div class="cs-db-card cs-db-usda">
                    <h4>🇺🇸 USDA FoodData Central</h4>
                    <p>
                        Az <strong>USA Mezőgazdasági Minisztérium</strong> hivatalos adatbázisa. Laborban mért értékek.
                        Teljes mikrotápanyag profil (30+ vitamin/ásványi anyag).
                    </p>
                    <ul class="cs-db-types">
                        <li><strong>Foundation</strong> ⭐⭐⭐⭐⭐ – Saját labor, nyers alapanyagok. Arany standard.</li>
                        <li><strong>SR Legacy</strong> ⭐⭐⭐⭐ – Régi, hatalmas (7000+). Megbízható, nem frissül.</li>
                        <li><strong>Branded</strong> ⭐⭐⭐ – Gyártói adatok, vonalkóddal. Csak USA.</li>
                        <li><strong>Survey (FNDDS)</strong> ⭐⭐⭐ – Összetett ételek, receptek. Átlagolt.</li>
                    </ul>
                </div>
            </div>
            <div class="cs-tip">
                💡 <strong>Tipp:</strong> Nyers alapanyagokhoz <strong>USDA Foundation/SR Legacy</strong> a legjobb.
                Bolti termékekhez <strong>OFF</strong> (vonalkóddal). Próbálj angolul is keresni jobb USDA eredményekhez!
            </div>
        </details>
    </div>

    <!-- KERESŐ -->
    <div class="cs-panel cs-search-panel">
        <div class="cs-search-row">
            <div class="cs-search-input-wrap">
                <label class="cs-label">Keresőszó / Vonalkód:</label>
                <input type="text" id="cs-query" class="cs-input" placeholder="pl. csirkemell, chicken breast, 5997675312158..." list="cs-history-list">
                <datalist id="cs-history-list">
                    <?php foreach ( array_reverse( $history ) as $h ) : ?>
                        <option value="<?php echo esc_attr( $h ); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="cs-search-select-wrap">
                <label class="cs-label">OFF ország:</label>
                <select id="cs-country" class="cs-select">
                    <option value="">🌍 Összes</option>
                    <option value="hungary" selected>🇭🇺 Magyarország</option>
                    <option value="germany">🇩🇪 Németország</option>
                    <option value="austria">🇦🇹 Ausztria</option>
                    <option value="united-kingdom">🇬🇧 UK</option>
                    <option value="france">🇫🇷 Francia</option>
                    <option value="italy">🇮🇹 Olasz</option>
                    <option value="spain">🇪🇸 Spanyol</option>
                    <option value="poland">🇵🇱 Lengyel</option>
                    <option value="romania">🇷🇴 Román</option>
                    <option value="slovakia">🇸🇰 Szlovák</option>
                    <option value="czech-republic">🇨🇿 Cseh</option>
                    <option value="croatia">🇭🇷 Horvát</option>
                    <option value="serbia">🇷🇸 Szerb</option>
                    <option value="united-states">🇺🇸 USA</option>
                </select>
            </div>
            <div class="cs-search-select-wrap">
                <label class="cs-label">USDA típus:</label>
                <select id="cs-usda-type" class="cs-select">
                    <option value="Foundation,SR Legacy">Foundation + SR Legacy</option>
                    <option value="Foundation">Foundation ⭐⭐⭐⭐⭐</option>
                    <option value="SR Legacy">SR Legacy ⭐⭐⭐⭐</option>
                    <option value="Branded">Branded (USA) ⭐⭐⭐</option>
                    <option value="Survey (FNDDS)">Survey/FNDDS ⭐⭐⭐</option>
                    <option value="Foundation,SR Legacy,Branded,Survey (FNDDS)">Minden típus</option>
                </select>
            </div>
            <div class="cs-search-select-wrap">
                <label class="cs-label">Rendezés:</label>
                <select id="cs-sort" class="cs-select">
                    <option value="relevance">Relevancia</option>
                    <option value="kcal_asc">Kcal ↑</option>
                    <option value="kcal_desc">Kcal ↓</option>
                    <option value="protein_desc">Fehérje ↓</option>
                    <option value="protein_asc">Fehérje ↑</option>
                    <option value="name_asc">Név A→Z</option>
                </select>
            </div>
            <button id="cs-search-btn" class="button button-primary cs-search-btn">🔍 Keresés</button>
        </div>
        <div class="cs-options-row">
            <label class="cs-opt-label"><input type="checkbox" id="cs-show-micro"> Mikrotápanyagok</label>
            <label class="cs-opt-label"><input type="checkbox" id="cs-show-ingredients"> Összetevők</label>
            <label class="cs-opt-label"><input type="checkbox" id="cs-show-allergens"> Allergének</label>
            <?php if ( ! empty( $history ) ) : ?>
                <button id="cs-clear-history" class="button cs-btn-tiny">🗑️ Előzmények törlése</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- LOADING -->
    <div id="cs-loading" class="cs-loading">
        <div class="cs-loading-icon">⏳</div>
        <div>Keresés mindkét adatbázisban...</div>
        <div class="cs-loading-status">
            <span id="cs-status-off">🍊 OFF: keresés...</span>
            <span id="cs-status-usda">🇺🇸 USDA: keresés...</span>
        </div>
    </div>

    <!-- EREDMÉNYEK -->
    <div id="cs-results" class="cs-results">
        <!-- STAT CARDS -->
        <div class="cs-stat-grid">
            <div class="cs-stat cs-stat-off">
                <div class="cs-stat-num" id="cs-count-off">0</div>
                <div class="cs-stat-label">🍊 OFF</div>
            </div>
            <div class="cs-stat cs-stat-usda">
                <div class="cs-stat-num" id="cs-count-usda">0</div>
                <div class="cs-stat-label">🇺🇸 USDA</div>
            </div>
            <div class="cs-stat cs-stat-existing">
                <div class="cs-stat-num" id="cs-count-existing">0</div>
                <div class="cs-stat-label">✅ Importálva</div>
            </div>
            <div class="cs-stat cs-stat-both">
                <div class="cs-stat-num" id="cs-count-both">0</div>
                <div class="cs-stat-label">🔗 Mindkettő</div>
            </div>
        </div>

        <!-- TABS + BATCH -->
        <div class="cs-toolbar">
            <div class="cs-tabs">
                <button class="button cs-tab active" data-tab="all">Összes</button>
                <button class="button cs-tab" data-tab="off">🍊 OFF</button>
                <button class="button cs-tab" data-tab="usda">🇺🇸 USDA</button>
                <button class="button cs-tab" data-tab="both">🔗 Mindkettő</button>
                <button class="button cs-tab" data-tab="imported">✅ Importálva</button>
            </div>
            <div class="cs-batch-row">
                <label class="cs-opt-label"><input type="checkbox" id="cs-select-all"> Mind</label>
                <button id="cs-batch-import" class="button cs-btn-import">📥 Import kijelöltek</button>
                <button id="cs-export-csv" class="button cs-btn-tiny">📥 CSV</button>
                <span id="cs-batch-status" class="cs-batch-status"></span>
            </div>
        </div>

        <!-- ÖSSZEHASONLÍTÁS BAR -->
        <div id="cs-compare-bar" class="cs-compare-bar">
            <span class="cs-compare-label">🔬 Összehasonlítás: <strong id="cs-compare-count">0</strong>/3</span>
            <div class="cs-compare-btns">
                <button id="cs-compare-btn" class="button cs-btn-compare" disabled>Összehasonlít</button>
                <button id="cs-compare-clear" class="button cs-btn-tiny">Törlés</button>
            </div>
        </div>

        <!-- COMPARE MODAL -->
        <div id="cs-compare-modal" class="cs-compare-modal">
            <div class="cs-compare-header">
                <h3>🔬 Összehasonlítás</h3>
                <button id="cs-compare-close" class="button cs-btn-tiny">✖ Bezárás</button>
            </div>
            <div id="cs-compare-content" class="cs-compare-content"></div>
        </div>

        <!-- LISTA -->
        <div id="cs-list"></div>

        <!-- PAGINATION -->
        <div id="cs-pagination" class="cs-pagination">
            <button id="cs-load-more-off" class="button">🍊 Több OFF találat</button>
            <button id="cs-load-more-usda" class="button">🇺🇸 Több USDA találat</button>
        </div>
    </div>

    <!-- ADAG MODAL -->
    <div id="cs-portion-modal" class="cs-modal-overlay">
        <div class="cs-modal-box">
            <h3 id="cs-portion-title">⚖️ Adag átszámolás</h3>
            <div class="cs-portion-input-row">
                <label class="cs-label">Adag méret (gramm):</label>
                <input type="number" id="cs-portion-grams" value="100" min="1" max="9999" class="cs-input">
            </div>
            <div class="cs-portion-presets">
                <strong>Gyakori adagok:</strong>
                <div class="cs-preset-btns">
                    <button class="button cs-portion-preset" data-g="25">25g</button>
                    <button class="button cs-portion-preset" data-g="50">50g</button>
                    <button class="button cs-portion-preset" data-g="100">100g</button>
                    <button class="button cs-portion-preset" data-g="150">150g</button>
                    <button class="button cs-portion-preset" data-g="200">200g</button>
                    <button class="button cs-portion-preset" data-g="250">250g</button>
                    <button class="button cs-portion-preset" data-g="300">300g</button>
                </div>
            </div>
            <div id="cs-portion-result" class="cs-portion-result"></div>
            <div class="cs-modal-footer">
                <button id="cs-portion-close" class="button">Bezárás</button>
            </div>
        </div>
    </div>

    </div>

    <style>
    /* ═══ GLOBAL ═══ */
    .cs-wrap{max-width:960px;padding-bottom:40px;}
    .cs-main-title{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;}
    .cs-version{font-size:.72rem;color:#94a3b8;font-weight:400;}
    .cs-panel{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 22px;margin:14px 0;box-shadow:0 1px 3px rgba(0,0,0,.04);}
    .cs-label{font-weight:600;font-size:.82rem;display:block;margin-bottom:4px;color:#334155;}
    .cs-input{width:100%;font-size:14px;padding:9px 12px;border:1px solid #cbd5e1;border-radius:8px;transition:border-color .2s,box-shadow .2s;}
    .cs-input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12);outline:none;}
    .cs-select{font-size:13px;padding:9px 8px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;min-width:0;}
    .cs-hint{font-weight:400;color:#94a3b8;font-size:.82rem;}
    .cs-btn-tiny{font-size:.75rem!important;padding:3px 10px!important;}

    /* ═══ DB INFO ═══ */
    .cs-details-sum{cursor:pointer;font-weight:700;font-size:.95rem;padding:4px 0;user-select:none;}
    .cs-db-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px;}
    .cs-db-card{padding:14px 16px;border-radius:10px;}
    .cs-db-card h4{margin:0 0 6px;}
    .cs-db-card p{font-size:.82rem;color:#555;margin:0;line-height:1.55;}
    .cs-db-off{background:#fff7ed;border:1px solid #fed7aa;}
    .cs-db-off h4{color:#f97316;}
    .cs-db-usda{background:#eff6ff;border:1px solid #bfdbfe;}
    .cs-db-usda h4{color:#2563eb;}
    .cs-db-rating{font-size:.78rem;color:#d97706;margin-top:6px;}
    .cs-db-types{font-size:.78rem;color:#555;margin:6px 0 0 16px;line-height:1.6;padding:0;}
    .cs-tip{margin-top:10px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:.82rem;color:#555;}

    /* ═══ SEARCH ═══ */
    .cs-search-panel{padding:18px 22px 14px;}
    .cs-search-row{display:flex;gap:10px;align-items:end;flex-wrap:wrap;}
    .cs-search-input-wrap{flex:1;min-width:180px;}
    .cs-search-select-wrap{min-width:0;flex-shrink:1;}
    .cs-search-btn{font-size:14px!important;padding:9px 24px!important;height:auto!important;border-radius:8px!important;white-space:nowrap;}
    .cs-options-row{margin-top:10px;display:flex;gap:14px;align-items:center;flex-wrap:wrap;}
    .cs-opt-label{font-size:.82rem;cursor:pointer;display:flex;align-items:center;gap:4px;white-space:nowrap;}

    /* ═══ LOADING ═══ */
    .cs-loading{display:none;margin:20px 0;text-align:center;padding:30px 20px;color:#666;font-size:.92rem;background:#fff;border-radius:12px;border:1px solid #e2e8f0;}
    .cs-loading-icon{font-size:2.2rem;margin-bottom:8px;animation:csPulse 1.5s infinite;}
    .cs-loading-status{display:flex;justify-content:center;gap:20px;margin-top:12px;flex-wrap:wrap;}

    /* ═══ RESULTS ═══ */
    .cs-results{display:none;margin:20px 0;}
    .cs-stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px;}
    .cs-stat{text-align:center;padding:14px 8px;border-radius:10px;border:1px solid;}
    .cs-stat-num{font-size:1.5rem;font-weight:700;}
    .cs-stat-label{font-size:.75rem;color:#666;margin-top:2px;}
    .cs-stat-off{background:#fff7ed;border-color:#fed7aa;}.cs-stat-off .cs-stat-num{color:#f97316;}
    .cs-stat-usda{background:#eff6ff;border-color:#bfdbfe;}.cs-stat-usda .cs-stat-num{color:#2563eb;}
    .cs-stat-existing{background:#f0fdf4;border-color:#bbf7d0;}.cs-stat-existing .cs-stat-num{color:#16a34a;}
    .cs-stat-both{background:#f5f3ff;border-color:#c4b5fd;}.cs-stat-both .cs-stat-num{color:#7c3aed;}

    /* ═══ TOOLBAR ═══ */
    .cs-toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px;}
    .cs-tabs{display:flex;gap:6px;flex-wrap:wrap;}
    .cs-tab{font-size:.82rem!important;padding:5px 12px!important;border-radius:6px!important;transition:all .15s;}
    .cs-tab.active{background:#1e293b!important;color:#fff!important;border-color:#1e293b!important;}
    .cs-batch-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
    .cs-btn-import{font-size:.82rem!important;padding:5px 14px!important;background:#16a34a!important;color:#fff!important;border-color:#15803d!important;border-radius:6px!important;}
    .cs-batch-status{font-size:.82rem;}

    /* ═══ COMPARE ═══ */
    .cs-compare-bar{display:none;background:#f5f3ff;border:2px solid #c4b5fd;border-radius:10px;padding:12px 16px;margin-bottom:14px;}
    .cs-compare-bar{display:none;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
    .cs-compare-label{font-weight:600;color:#7c3aed;font-size:.88rem;}
    .cs-compare-btns{display:flex;gap:6px;}
    .cs-btn-compare{font-size:.82rem!important;background:#7c3aed!important;color:#fff!important;border-color:#6d28d9!important;border-radius:6px!important;}
    .cs-compare-modal{display:none;background:#fff;border:2px solid #c4b5fd;border-radius:14px;padding:20px 22px;margin-bottom:16px;max-height:600px;overflow-y:auto;}
    .cs-compare-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;}
    .cs-compare-header h3{margin:0;color:#7c3aed;}
    .cs-compare-content{overflow-x:auto;}

    /* ═══ CARDS ═══ */
    .cs-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:10px;overflow:hidden;transition:box-shadow .2s,border-color .2s;}
    .cs-card:hover{box-shadow:0 2px 10px rgba(0,0,0,.06);}
    .cs-card.cs-selected{border-color:#7c3aed;box-shadow:0 0 0 2px rgba(124,58,237,.15);}
    .cs-card-header{padding:12px 16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f1f5f9;cursor:pointer;gap:8px;min-height:52px;}
    .cs-card-header:hover{background:#f8fafc;}
    .cs-card-body{display:none;padding:0;}
    .cs-card.open .cs-card-body{display:block;}
    .cs-card-left{display:flex;align-items:center;gap:10px;flex:1;min-width:0;}
    .cs-card-img{width:36px;height:36px;border-radius:6px;object-fit:cover;flex-shrink:0;}
    .cs-card-info{min-width:0;}
    .cs-card-name{font-weight:600;color:#1e293b;font-size:.9rem;word-break:break-word;}
    .cs-card-hu{color:#16a34a;font-size:.78rem;}
    .cs-card-brands{color:#94a3b8;font-size:.72rem;}
    .cs-card-dtype{font-size:.68rem;padding:1px 5px;background:#eff6ff;border-radius:3px;color:#2563eb;white-space:nowrap;}
    .cs-card-right{display:flex;gap:5px;align-items:center;flex-shrink:0;flex-wrap:wrap;justify-content:flex-end;}
    .cs-card-arrow{font-size:1rem;color:#cbd5e1;transition:transform .2s;}
    .cs-card.open .cs-card-arrow{transform:rotate(180deg);}

    /* ═══ TABLE ═══ */
    .cs-ct{width:100%;border-collapse:collapse;font-size:.82rem;}
    .cs-ct th{background:#f8fafc;padding:7px 12px;text-align:left;font-weight:600;border-bottom:1px solid #e2e8f0;font-size:.78rem;white-space:nowrap;}
    .cs-ct td{padding:6px 12px;border-bottom:1px solid #f1f5f9;}
    .cs-ct tr:hover td{background:#fafbfc;}

    /* ═══ BADGES ═══ */
    .cs-sb{font-size:.68rem;padding:2px 7px;border-radius:5px;font-weight:600;white-space:nowrap;}
    .cs-sb-off{background:#fff7ed;color:#f97316;}
    .cs-sb-usda{background:#eff6ff;color:#2563eb;}
    .cs-sb-both{background:#f0fdf4;color:#16a34a;}
    .cs-nutri{display:inline-block;width:22px;height:22px;border-radius:50%;text-align:center;line-height:22px;font-weight:700;font-size:.7rem;color:#fff;}
    .cs-nutri-a{background:#038141;}.cs-nutri-b{background:#85bb2f;}.cs-nutri-c{background:#fecb02;color:#333;}.cs-nutri-d{background:#ee8100;}.cs-nutri-e{background:#e63e11;}
    .cs-nova{display:inline-block;padding:2px 6px;border-radius:4px;font-size:.68rem;font-weight:600;}
    .cs-nova-1{background:#038141;color:#fff;}.cs-nova-2{background:#85bb2f;color:#fff;}.cs-nova-3{background:#fecb02;color:#333;}.cs-nova-4{background:#e63e11;color:#fff;}
    .cs-quality{display:inline-flex;gap:1px;font-size:.7rem;}
    .cs-star-on{color:#fbbf24;}.cs-star-off{color:#e2e8f0;}
    .cs-match-badge{font-size:.7rem;padding:2px 6px;background:#f0fdf4;color:#16a34a;border-radius:4px;}
    .cs-btn-cmp{font-size:.68rem!important;padding:1px 6px!important;border-radius:4px!important;min-width:28px;}
    .cs-btn-cmp.active{background:#7c3aed!important;color:#fff!important;border-color:#7c3aed!important;}

    /* ═══ CARD BODY SECTIONS ═══ */
    .cs-dup-warn{background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:.82rem;color:#dc2626;margin:10px 16px;}
    .cs-macro-bar{padding:12px 16px;background:#f8fafc;display:flex;gap:16px;flex-wrap:wrap;font-size:.82rem;align-items:center;border-bottom:1px solid #f1f5f9;}
    .cs-import-row{padding:12px 16px;border-top:1px solid #e2e8f0;display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
    .cs-allergen-tag{display:inline-block;padding:2px 7px;background:#fef2f2;border:1px solid #fca5a5;border-radius:5px;margin:2px;font-size:.75rem;color:#dc2626;}
    .cs-ingredients-box{padding:10px 16px;font-size:.82rem;color:#555;border-top:1px solid #f1f5f9;line-height:1.5;}
    .cs-allergens-box{padding:10px 16px;font-size:.82rem;border-top:1px solid #f1f5f9;}

    /* ═══ PAGINATION ═══ */
    .cs-pagination{display:none;text-align:center;margin-top:16px;padding-bottom:20px;}
    .cs-pagination button{margin:0 4px;border-radius:8px!important;}

    /* ═══ MODAL ═══ */
    .cs-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:100000;align-items:center;justify-content:center;padding:20px;}
    .cs-modal-overlay.open{display:flex;}
    .cs-modal-box{background:#fff;border-radius:14px;padding:24px;max-width:440px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);}
    .cs-modal-box h3{margin:0 0 16px;font-size:1.05rem;}
    .cs-portion-input-row{margin-bottom:14px;}
    .cs-portion-presets{margin-bottom:14px;font-size:.82rem;color:#555;}
    .cs-preset-btns{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;}
    .cs-preset-btns button{border-radius:6px!important;}
    .cs-portion-result{background:#f8fafc;border-radius:8px;padding:14px;font-size:.85rem;line-height:1.7;margin-bottom:14px;}
    .cs-modal-footer{display:flex;justify-content:flex-end;}

    /* ═══ RESPONSIVE ═══ */
    @media(max-width:782px){
        .cs-wrap{padding:0 4px 30px;}
        .cs-panel{padding:14px 14px;border-radius:10px;margin:10px 0;}
        .cs-db-grid{grid-template-columns:1fr;}
        .cs-search-row{flex-direction:column;gap:8px;}
        .cs-search-input-wrap,.cs-search-select-wrap{width:100%;}
        .cs-select{width:100%;}
        .cs-search-btn{width:100%;}
        .cs-stat-grid{grid-template-columns:repeat(2,1fr);gap:8px;}
        .cs-stat{padding:10px 6px;}
        .cs-stat-num{font-size:1.2rem;}
        .cs-toolbar{flex-direction:column;align-items:stretch;}
        .cs-tabs{justify-content:center;}
        .cs-tab{font-size:.75rem!important;padding:4px 8px!important;}
        .cs-batch-row{justify-content:center;}
        .cs-card-header{padding:10px 12px;}
        .cs-card-right{gap:3px;}
        .cs-card-name{font-size:.84rem;}
        .cs-macro-bar{flex-direction:column;gap:6px;font-size:.78rem;}
        .cs-ct{font-size:.76rem;}
        .cs-ct th,.cs-ct td{padding:5px 8px;}
        .cs-import-row{flex-direction:column;align-items:flex-start;gap:6px;}
        .cs-compare-bar{flex-direction:column;align-items:flex-start;}
        .cs-compare-modal{padding:14px;max-height:80vh;}
        .cs-modal-box{padding:18px;max-width:95vw;}
        .cs-options-row{gap:8px;}
        .cs-pagination button{font-size:.82rem!important;}
    }
    @media(max-width:480px){
        .cs-stat-grid{grid-template-columns:repeat(2,1fr);}
        .cs-tabs{gap:4px;}
        .cs-tab{font-size:.7rem!important;padding:3px 6px!important;}
        .cs-card-img{width:28px;height:28px;}
    }

    /* ═══ ANIMATIONS ═══ */
    @keyframes csPulse{0%,100%{opacity:1;}50%{opacity:.4;}}
    </style>
    <?php
}


// ══════════════════════════════════════════════════════════════
// ADMIN JS
// ══════════════════════════════════════════════════════════════

add_action( 'admin_enqueue_scripts', function( string $hook ): void {
    if ( $hook !== 'alapanyag_page_combined-search' ) return;

    $js = <<<'JSEOF'
(function(){
'use strict';
var cfg=Object.assign({},window.csData||{});
try{delete window.csData;}catch(e){}

var $=function(id){return document.getElementById(id);};
var AR=[],CT='all',offPage=1,usdaPage=1,offTotal=0,usdaTotal=0,offItems=[],usdaItems=[];
var activeCtrl=null,lastSearchTime=0,compareList=[];
var RL=cfg.rateLimit||2;

function abortPrev(){if(activeCtrl){try{activeCtrl.abort();}catch(e){}activeCtrl=null;}}
function newCtrl(){abortPrev();activeCtrl=new AbortController();return activeCtrl;}

setInterval(function(){
    var fd=new FormData();fd.append('action','cs_refresh_nonce');fd.append('nonce',cfg.nonce);
    fetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
        if(r&&r.success&&r.data){if(r.data.nonce)cfg.nonce=r.data.nonce;}
    }).catch(function(){});
},300000);

$('cs-search-btn').addEventListener('click',doSearch);
$('cs-query').addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();doSearch();}});
$('cs-sort').addEventListener('change',function(){sortAndRender();});

function doSearch(){
    var now=Date.now();
    if(now-lastSearchTime<RL*1000)return;
    lastSearchTime=now;
    var q=$('cs-query').value.trim();if(!q)return;
    var btn=$('cs-search-btn');btn.disabled=true;btn.textContent='⏳...';
    offPage=1;usdaPage=1;offItems=[];usdaItems=[];AR=[];CT='all';compareList=[];
    document.querySelectorAll('.cs-tab').forEach(function(t){t.classList.remove('active');});
    document.querySelector('.cs-tab[data-tab="all"]').classList.add('active');
    $('cs-loading').style.display='block';$('cs-results').style.display='none';
    $('cs-status-off').textContent='🍊 OFF: keresés...';$('cs-status-usda').textContent='🇺🇸 USDA: keresés...';
    $('cs-compare-modal').style.display='none';updCmpBar();

    var country=$('cs-country').value,ut=$('cs-usda-type').value,isBC=/^\d{8,14}$/.test(q);
    var ctrl=newCtrl(),sig=ctrl.signal,od=false,ud=false;

    function chk(){if(!od||!ud)return;btn.disabled=false;btn.textContent='🔍 Keresés';$('cs-loading').style.display='none';AR=mergeResults(offItems,usdaItems);sortAndRender();}

    var fd1=new FormData();fd1.append('action','cs_search_off');fd1.append('nonce',cfg.nonce);fd1.append('query',q);fd1.append('country',country);fd1.append('page','1');fd1.append('is_barcode',isBC?'1':'0');
    fetch(cfg.ajaxUrl,{method:'POST',body:fd1,signal:sig}).then(function(r){return r.json();}).then(function(r){
        od=true;
        if(r&&r.success){offItems=r.data.foods||[];offTotal=r.data.total||0;$('cs-status-off').innerHTML='🍊 OFF: <strong>'+offItems.length+'</strong>/'+offTotal+' ✅';}
        else $('cs-status-off').innerHTML='🍊 OFF: ❌ '+(r?r.data:'');
        chk();
    }).catch(function(e){od=true;if(e&&e.name!=='AbortError')$('cs-status-off').innerHTML='🍊 OFF: ❌';chk();});

    var fd2=new FormData();fd2.append('action','cs_search_usda');fd2.append('nonce',cfg.nonce);fd2.append('query',q);fd2.append('usda_type',ut);fd2.append('page','1');
    fetch(cfg.ajaxUrl,{method:'POST',body:fd2,signal:sig}).then(function(r){return r.json();}).then(function(r){
        ud=true;
        if(r&&r.success){usdaItems=r.data.foods||[];usdaTotal=r.data.total||0;$('cs-status-usda').innerHTML='🇺🇸 USDA: <strong>'+usdaItems.length+'</strong>/'+usdaTotal+' ✅';}
        else $('cs-status-usda').innerHTML='🇺🇸 USDA: ❌ '+(r?r.data:'');
        chk();
    }).catch(function(e){ud=true;if(e&&e.name!=='AbortError')$('cs-status-usda').innerHTML='🇺🇸 USDA: ❌';chk();});
}

function mergeResults(oi,ui){
    var res=[],usedU={};
    oi.forEach(function(o){
        var bu=null,bs=0;
        ui.forEach(function(u,idx){if(usedU[idx])return;var s=simSc(normN(o.name),normN(u.name));if(s>bs&&s>=55){bs=s;bu=u;bu._idx=idx;}});
        if(bu)usedU[bu._idx]=true;
        res.push(mkIt(o,bu,bs));
    });
    ui.forEach(function(u,idx){if(usedU[idx])return;res.push(mkIt(null,u,0));});
    return res;
}

function mkIt(o,u,ms){
    return{
        name:o?(o.name_hu||o.name):(u?u.name:'?'),
        name_off:o?o.name:null,name_hu:o?o.name_hu:null,name_usda:u?u.name:null,
        source:o&&u?'both':o?'off':'usda',match_score:ms,
        barcode:o?o.barcode:null,fdc_id:u?u.fdc_id:null,
        brands:o?o.brands:null,image_url:o?o.image_url:null,
        off_n:o?o.nutrients:null,usda_n:u?u.nutrients:null,
        usda_micro:u?u.micro:null,data_type:u?u.data_type:null,
        nutri_score:o?o.nutri_score:null,nova:o?o.nova:null,
        allergens:o?o.allergens:null,ingredients:o?o.ingredients:null,
        completeness:o?o.completeness:null,
        already_imported:o?o.already_imported:(u?u.already_imported:false),
        existing_id:o?o.existing_id:(u?u.existing_id:0),
        existing_name:o?o.existing_name:'',
        usda_already:u?u.already_imported:false,
        dup_warning:o?o.dup_warning:(u?u.dup_warning:null),
        quality:calcQ(o,u)
    };
}

function calcQ(o,u){
    if(u&&u.data_type==='Foundation')return 5;
    if(u&&u.data_type==='SR Legacy')return 4;
    if(o&&o.completeness&&o.completeness>0.7)return 3;
    if(u)return 3;if(o)return 2;return 1;
}

function normN(n){return(n||'').toLowerCase().replace(/[^a-záéíóöőúüű0-9\s]/g,' ').replace(/\s+/g,' ').trim();}
function simSc(a,b){
    if(!a||!b)return 0;if(a===b)return 100;
    if(a.indexOf(b)!==-1||b.indexOf(a)!==-1)return Math.round(Math.min(a.length,b.length)/Math.max(a.length,b.length)*100);
    var w1=a.split(' ').filter(Boolean),w2=b.split(' ').filter(Boolean),c=w1.filter(function(w){return w2.indexOf(w)!==-1;});
    return Math.round(c.length/Math.max(w1.length,w2.length)*100);
}

function sortAndRender(){
    var s=$('cs-sort').value;
    AR.sort(function(a,b){
        var an=a.off_n||a.usda_n||{},bn=b.off_n||b.usda_n||{};
        if(s==='kcal_asc')return(an.kcal||9999)-(bn.kcal||9999);
        if(s==='kcal_desc')return(bn.kcal||0)-(an.kcal||0);
        if(s==='protein_desc')return(bn.protein||0)-(an.protein||0);
        if(s==='protein_asc')return(an.protein||9999)-(bn.protein||9999);
        if(s==='name_asc')return(a.name||'').localeCompare(b.name||'','hu');
        return 0;
    });
    showRes();
}

function showRes(){
    $('cs-results').style.display='block';
    $('cs-count-off').textContent=AR.filter(function(r){return r.source==='off'||r.source==='both';}).length;
    $('cs-count-usda').textContent=AR.filter(function(r){return r.source==='usda'||r.source==='both';}).length;
    $('cs-count-existing').textContent=AR.filter(function(r){return r.already_imported||r.usda_already;}).length;
    $('cs-count-both').textContent=AR.filter(function(r){return r.source==='both';}).length;
    $('cs-pagination').style.display=(offItems.length<offTotal||usdaItems.length<usdaTotal)?'block':'none';
    if($('cs-load-more-off'))$('cs-load-more-off').disabled=(offItems.length>=offTotal);
    if($('cs-load-more-usda'))$('cs-load-more-usda').disabled=(usdaItems.length>=usdaTotal);
    renderList();
}

function renderList(){
    var f=AR;
    if(CT==='off')f=AR.filter(function(r){return r.source==='off';});
    if(CT==='usda')f=AR.filter(function(r){return r.source==='usda';});
    if(CT==='both')f=AR.filter(function(r){return r.source==='both';});
    if(CT==='imported')f=AR.filter(function(r){return r.already_imported||r.usda_already;});

    if(!f.length){$('cs-list').innerHTML='<div style="padding:30px;text-align:center;color:#94a3b8;font-size:.92rem;">Nincs találat ebben a kategóriában.</div>';return;}

    var sm=$('cs-show-micro').checked,si=$('cs-show-ingredients').checked,sa=$('cs-show-allergens').checked;
    var h='';
    f.forEach(function(it){
        var sc='cs-sb-'+it.source,sl=it.source==='both'?'🔗 Both':it.source==='off'?'🍊 OFF':'🇺🇸 USDA';
        var gix=AR.indexOf(it),isSel=compareList.indexOf(gix)!==-1;

        h+='<div class="cs-card'+(isSel?' cs-selected':'')+'" data-gix="'+gix+'">';

        // HEADER
        h+='<div class="cs-card-header" onclick="this.parentElement.classList.toggle(\'open\')">';
        h+='<div class="cs-card-left">';
        h+='<input type="checkbox" class="cs-item-cb" data-gix="'+gix+'"'+(it.already_imported||it.usda_already?' disabled':'')+' onclick="event.stopPropagation()">';
        if(it.image_url)h+='<img src="'+it.image_url+'" class="cs-card-img" loading="lazy" onerror="this.style.display=\'none\'">';
        h+='<div class="cs-card-info">';
        h+='<div class="cs-card-name">'+eh(it.name)+'</div>';
        var sub=[];
        if(it.name_hu&&it.name_hu!==it.name)sub.push('<span class="cs-card-hu">🇭🇺 '+eh(it.name_hu)+'</span>');
        if(it.brands)sub.push('<span class="cs-card-brands">('+eh(it.brands)+')</span>');
        if(it.data_type)sub.push('<span class="cs-card-dtype">'+eh(it.data_type)+'</span>');
        if(sub.length)h+='<div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin-top:2px;">'+sub.join('')+'</div>';
        h+='</div></div>';

        h+='<div class="cs-card-right">';
        // Quality stars
        h+='<span class="cs-quality">';for(var qi=0;qi<5;qi++)h+='<span class="'+(qi<it.quality?'cs-star-on':'cs-star-off')+'">★</span>';h+='</span>';
        // Nutri-Score
        if(it.nutri_score){var ns=it.nutri_score.toLowerCase();if('abcde'.indexOf(ns)!==-1)h+='<span class="cs-nutri cs-nutri-'+ns+'">'+ns.toUpperCase()+'</span>';}
        // NOVA
        if(it.nova){var nv=parseInt(it.nova);if(nv>=1&&nv<=4)h+='<span class="cs-nova cs-nova-'+nv+'">NOVA'+nv+'</span>';}
        h+='<span class="cs-sb '+sc+'">'+sl+'</span>';
        if(it.source==='both')h+='<span class="cs-match-badge">'+it.match_score+'%</span>';
        if(it.already_imported||it.usda_already)h+='<span style="font-size:.82rem;color:#16a34a;">✅</span>';
        h+='<button class="button cs-btn-cmp'+(isSel?' active':'')+'" data-gix="'+gix+'" onclick="event.stopPropagation()">🔬</button>';
        h+='<span class="cs-card-arrow">▼</span>';
        h+='</div></div>';

        // BODY
        h+='<div class="cs-card-body">';

        if(it.dup_warning)h+='<div class="cs-dup-warn">⚠️ Hasonló már létezik: <strong>'+eh(it.dup_warning.name)+'</strong> (#'+it.dup_warning.id+') – '+it.dup_warning.similarity+'%</div>';

        // Macro bar
        h+='<div class="cs-macro-bar">';
        if(it.off_n)h+='<div>🍊 <strong>OFF</strong>: '+(it.off_n.kcal!=null?it.off_n.kcal:'?')+' kcal | F:'+(it.off_n.protein!=null?it.off_n.protein:'?')+'g | CH:'+(it.off_n.carb!=null?it.off_n.carb:'?')+'g | Zs:'+(it.off_n.fat!=null?it.off_n.fat:'?')+'g</div>';
        if(it.usda_n)h+='<div>🇺🇸 <strong>USDA</strong>: '+(it.usda_n.kcal!=null?it.usda_n.kcal:'?')+' kcal | F:'+(it.usda_n.protein!=null?it.usda_n.protein:'?')+'g | CH:'+(it.usda_n.carb!=null?it.usda_n.carb:'?')+'g | Zs:'+(it.usda_n.fat!=null?it.usda_n.fat:'?')+'g</div>';
        h+='<button class="button cs-btn-tiny cs-portion-btn" data-gix="'+gix+'" onclick="event.stopPropagation()">⚖️ Adag</button>';
        h+='</div>';

        // Table
        if(it.off_n&&it.usda_n){
            h+=cmpTbl(it.off_n,it.usda_n,sm,it.usda_micro);
        } else {
            var sn=it.off_n||it.usda_n;
            if(sn){
                var snl=it.off_n?'🍊 OFF':'🇺🇸 USDA';
                h+='<table class="cs-ct"><tr><th>Tápanyag</th><th>'+snl+'</th></tr>';
                ML().forEach(function(m){h+='<tr><td><strong>'+m.l+'</strong></td><td>'+(sn[m.k]!=null?sn[m.k]+' '+m.u:'–')+'</td></tr>';});
                if(sm&&it.usda_micro){Object.keys(it.usda_micro).forEach(function(mk){h+='<tr><td>'+mk+'</td><td>'+(it.usda_micro[mk].value!=null?it.usda_micro[mk].value:'–')+' '+(it.usda_micro[mk].unit||'')+'</td></tr>';});}
                h+='</table>';
            }
        }

        if(si&&it.ingredients)h+='<div class="cs-ingredients-box"><strong>Összetevők:</strong> '+eh(it.ingredients)+'</div>';
        if(sa&&it.allergens&&it.allergens.length)h+='<div class="cs-allergens-box"><strong>Allergének:</strong> '+it.allergens.map(function(a){return'<span class="cs-allergen-tag">'+eh(a)+'</span>';}).join('')+'</div>';

        // Import
        h+='<div class="cs-import-row">';
        if(it.already_imported)h+='<a href="'+cfg.editUrl+it.existing_id+'" target="_blank" style="color:#16a34a;font-size:.85rem;">✅ Importálva: #'+it.existing_id+' '+eh(it.existing_name)+' ↗</a>';
        else if(it.barcode)h+='<button class="button cs-import-off" data-barcode="'+it.barcode+'" style="font-size:.82rem;">📥 Import OFF</button>';
        if(it.usda_already)h+='<span style="font-size:.85rem;color:#16a34a;">✅ USDA importálva</span>';
        else if(it.fdc_id)h+='<button class="button cs-import-usda" data-fdc-id="'+it.fdc_id+'" style="font-size:.82rem;">📥 Import USDA</button>';
        if(it.source==='both'){var rec=it.quality>=4?'USDA':'OFF';h+='<span style="font-size:.78rem;color:#7c3aed;">💡 Ajánlott: <strong>'+rec+'</strong></span>';}
        if(it.barcode)h+='<a href="https://world.openfoodfacts.org/product/'+it.barcode+'" target="_blank" style="font-size:.72rem;color:#94a3b8;">OFF ↗</a>';
        if(it.fdc_id)h+='<a href="https://fdc.nal.usda.gov/fdc-app.html#/food-details/'+it.fdc_id+'/nutrients" target="_blank" style="font-size:.72rem;color:#94a3b8;">USDA ↗</a>';
        h+='</div>';

        h+='</div></div>';
    });
    $('cs-list').innerHTML=h;
    bindAll();
}

function cmpTbl(on,un,sm,micro){
    var h='<div style="overflow-x:auto;"><table class="cs-ct"><tr><th>Tápanyag</th><th>🍊 OFF</th><th>🇺🇸 USDA</th><th>Eltérés</th></tr>';
    ML().forEach(function(m){
        var ov=on[m.k],uv=un[m.k],d='',dc='#94a3b8';
        if(ov!=null&&uv!=null){var dd=Math.abs(ov-uv);if(dd<0.1){d='≈';dc='#16a34a';}else{var pct=ov>0?Math.round(dd/ov*100):0;d=(uv>ov?'+':'')+(uv-ov).toFixed(1)+' ('+pct+'%)';dc=pct>20?'#dc2626':'#d97706';}}
        h+='<tr><td><strong>'+m.l+'</strong></td><td>'+(ov!=null?ov+' '+m.u:'–')+'</td><td>'+(uv!=null?uv+' '+m.u:'–')+'</td><td style="color:'+dc+';font-size:.78rem;">'+d+'</td></tr>';
    });
    if(sm&&micro){Object.keys(micro).forEach(function(mk){h+='<tr><td>'+mk+'</td><td style="color:#cbd5e1;">–</td><td>'+(micro[mk].value!=null?micro[mk].value:'–')+' '+(micro[mk].unit||'')+'</td><td></td></tr>';});}
    h+='</table></div>';return h;
}

function ML(){return[{l:'Kalória',k:'kcal',u:'kcal'},{l:'Fehérje',k:'protein',u:'g'},{l:'Szénhidrát',k:'carb',u:'g'},{l:'Zsír',k:'fat',u:'g'},{l:'Rost',k:'fiber',u:'g'},{l:'Cukor',k:'sugar',u:'g'},{l:'Telített zsír',k:'saturated',u:'g'},{l:'Nátrium',k:'sodium',u:'mg'}];}

function bindAll(){
    document.querySelectorAll('.cs-import-off').forEach(function(b){b.addEventListener('click',function(e){e.stopPropagation();impOff(this);});});
    document.querySelectorAll('.cs-import-usda').forEach(function(b){b.addEventListener('click',function(e){e.stopPropagation();impUsda(this);});});
    document.querySelectorAll('.cs-btn-cmp').forEach(function(b){b.addEventListener('click',function(){togCmp(parseInt(this.dataset.gix));});});
    document.querySelectorAll('.cs-portion-btn').forEach(function(b){b.addEventListener('click',function(e){e.stopPropagation();openPortion(parseInt(this.dataset.gix));});});
}

function impOff(btn){
    btn.disabled=true;btn.textContent='⏳...';
    var fd=new FormData();fd.append('action','cs_import_off');fd.append('nonce',cfg.nonce);fd.append('barcode',btn.dataset.barcode);
    fetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
        btn.textContent=r.success?'✅ Kész':'❌';btn.style.color=r.success?'#16a34a':'#dc2626';if(!r.success)btn.title=r.data||'';
    }).catch(function(){btn.textContent='❌';btn.style.color='#dc2626';});
}

function impUsda(btn){
    btn.disabled=true;btn.textContent='⏳...';
    var fd=new FormData();fd.append('action','cs_import_usda');fd.append('nonce',cfg.nonce);fd.append('fdc_id',btn.dataset.fdcId);
    fetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
        btn.textContent=r.success?'✅ Kész':'❌';btn.style.color=r.success?'#16a34a':'#dc2626';if(!r.success)btn.title=r.data||'';
    }).catch(function(){btn.textContent='❌';btn.style.color='#dc2626';});
}

function togCmp(gix){
    var i=compareList.indexOf(gix);
    if(i>=0)compareList.splice(i,1);else if(compareList.length<3)compareList.push(gix);
    updCmpBar();renderList();
}
function updCmpBar(){
    var el=$('cs-compare-bar');
    el.style.display=compareList.length>0?'flex':'none';
    $('cs-compare-count').textContent=compareList.length;
    $('cs-compare-btn').disabled=compareList.length<2;
}

$('cs-compare-btn').addEventListener('click',function(){
    if(compareList.length<2)return;
    var items=compareList.map(function(i){return AR[i];}).filter(Boolean);
    var h='<div style="overflow-x:auto;"><table class="cs-ct"><tr><th>Tápanyag</th>';
    items.forEach(function(it){h+='<th style="min-width:120px;">'+eh(it.name).substring(0,25)+'<br><span style="font-size:.68rem;color:#94a3b8;">'+it.source+'</span></th>';});
    h+='</tr>';
    ML().forEach(function(m){
        h+='<tr><td><strong>'+m.l+'</strong></td>';
        var vals=items.map(function(it){var n=it.off_n||it.usda_n||{};return n[m.k];});
        var best=null;if(m.k==='kcal'){best=Math.min.apply(null,vals.filter(function(v){return v!=null;}));}
        else if(m.k==='protein'||m.k==='fiber'){best=Math.max.apply(null,vals.filter(function(v){return v!=null;}));}
        items.forEach(function(it,idx){
            var n=it.off_n||it.usda_n||{},v=n[m.k];
            var style='';
            if(v!=null&&v===best&&vals.filter(function(x){return x!=null;}).length>1)style=' style="color:#16a34a;font-weight:600;"';
            h+='<td'+style+'>'+(v!=null?v+' '+m.u:'–')+'</td>';
        });
        h+='</tr>';
    });
    h+='</table></div>';
    $('cs-compare-content').innerHTML=h;
    $('cs-compare-modal').style.display='block';
    $('cs-compare-modal').scrollIntoView({behavior:'smooth'});
});
$('cs-compare-clear').addEventListener('click',function(){compareList=[];updCmpBar();renderList();});
$('cs-compare-close').addEventListener('click',function(){$('cs-compare-modal').style.display='none';});

// Portion modal
var portionItem=null;
function openPortion(gix){
    portionItem=AR[gix];if(!portionItem)return;
    $('cs-portion-title').textContent='⚖️ '+portionItem.name;
    $('cs-portion-grams').value=100;
    calcPortion();
    $('cs-portion-modal').classList.add('open');
}
$('cs-portion-grams').addEventListener('input',calcPortion);
document.querySelectorAll('.cs-portion-preset').forEach(function(b){b.addEventListener('click',function(){$('cs-portion-grams').value=this.dataset.g;calcPortion();});});
$('cs-portion-close').addEventListener('click',function(){$('cs-portion-modal').classList.remove('open');});
$('cs-portion-modal').addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');});

function calcPortion(){
    if(!portionItem)return;
    var g=parseFloat($('cs-portion-grams').value)||100,fac=g/100;
    var n=portionItem.off_n||portionItem.usda_n||{};
    var src=portionItem.off_n?'OFF':'USDA';
    var h='<div style="margin-bottom:6px;"><strong>'+g+'g adag</strong> <span style="color:#94a3b8;font-size:.82rem;">('+src+')</span></div>';
    h+='<div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 16px;">';
    ML().forEach(function(m){
        if(n[m.k]!=null)h+='<div style="display:flex;justify-content:space-between;"><span>'+m.l+':</span><strong>'+(n[m.k]*fac).toFixed(1)+' '+m.u+'</strong></div>';
    });
    h+='</div>';
    $('cs-portion-result').innerHTML=h;
}

// Tabs
document.querySelectorAll('.cs-tab').forEach(function(t){t.addEventListener('click',function(){
    document.querySelectorAll('.cs-tab').forEach(function(x){x.classList.remove('active');});this.classList.add('active');CT=this.dataset.tab;renderList();
});});

// Select all + Batch
$('cs-select-all').addEventListener('change',function(){var c=this.checked;document.querySelectorAll('.cs-item-cb:not(:disabled)').forEach(function(cb){cb.checked=c;});});

$('cs-batch-import').addEventListener('click',function(){
    var sel=[];document.querySelectorAll('.cs-item-cb:checked').forEach(function(cb){var gix=parseInt(cb.dataset.gix);var it=AR[gix];if(it)sel.push(it);});
    if(!sel.length){$('cs-batch-status').textContent='⚠️ Jelölj ki!';return;}
    if(!confirm('📥 '+sel.length+' elem importálása?'))return;
    this.disabled=true;$('cs-batch-status').textContent='⏳ '+sel.length+'...';
    var fd=new FormData();fd.append('action','cs_batch_import');fd.append('nonce',cfg.nonce);
    fd.append('items',JSON.stringify(sel.map(function(it){return{barcode:it.barcode||null,fdc_id:it.fdc_id||null,source:it.source,quality:it.quality};})));
    var btn=this;
    fetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
        btn.disabled=false;$('cs-batch-status').innerHTML=r.success?'<span style="color:#16a34a;">✅ '+r.data+'</span>':'<span style="color:#dc2626;">❌ '+(r.data||'')+'</span>';
    }).catch(function(){btn.disabled=false;$('cs-batch-status').textContent='❌ Hiba';});
});

// CSV
$('cs-export-csv').addEventListener('click',function(){
    if(!AR.length)return;
    var csv='Név;Forrás;Kcal;Fehérje;CH;Zsír;Rost;Cukor;Vonalkód;FDC_ID;Minőség\n';
    AR.forEach(function(it){var n=it.off_n||it.usda_n||{};
        csv+='"'+ec(it.name)+'";"'+it.source+'";"'+(n.kcal!=null?n.kcal:'')+'";"'+(n.protein!=null?n.protein:'')+'";"'+(n.carb!=null?n.carb:'')+'";"'+(n.fat!=null?n.fat:'')+'";"'+(n.fiber!=null?n.fiber:'')+'";"'+(n.sugar!=null?n.sugar:'')+'";"'+(it.barcode||'')+'";"'+(it.fdc_id||'')+'";"'+it.quality+'"\n';
    });
    var b=new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'}),u=URL.createObjectURL(b),a=document.createElement('a');a.href=u;a.download='kereses_'+new Date().toISOString().slice(0,10)+'.csv';a.click();URL.revokeObjectURL(u);
});

// Pagination
if($('cs-load-more-off'))$('cs-load-more-off').addEventListener('click',function(){
    this.disabled=true;this.textContent='⏳...';offPage++;
    var fd=new FormData();fd.append('action','cs_search_off');fd.append('nonce',cfg.nonce);fd.append('query',$('cs-query').value.trim());fd.append('country',$('cs-country').value);fd.append('page',String(offPage));fd.append('is_barcode','0');
    var btn=this;
    fetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
        btn.disabled=false;btn.textContent='🍊 Több OFF';
        if(r&&r.success){offItems=offItems.concat(r.data.foods||[]);AR=mergeResults(offItems,usdaItems);sortAndRender();}
    }).catch(function(){btn.disabled=false;btn.textContent='🍊 Több OFF';});
});
if($('cs-load-more-usda'))$('cs-load-more-usda').addEventListener('click',function(){
    this.disabled=true;this.textContent='⏳...';usdaPage++;
    var fd=new FormData();fd.append('action','cs_search_usda');fd.append('nonce',cfg.nonce);fd.append('query',$('cs-query').value.trim());fd.append('usda_type',$('cs-usda-type').value);fd.append('page',String(usdaPage));
    var btn=this;
    fetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
        btn.disabled=false;btn.textContent='🇺🇸 Több USDA';
        if(r&&r.success){usdaItems=usdaItems.concat(r.data.foods||[]);AR=mergeResults(offItems,usdaItems);sortAndRender();}
    }).catch(function(){btn.disabled=false;btn.textContent='🇺🇸 Több USDA';});
});

if($('cs-clear-history'))$('cs-clear-history').addEventListener('click',function(){
    var fd=new FormData();fd.append('action','cs_clear_history');fd.append('nonce',cfg.nonce);
    fetch(cfg.ajaxUrl,{method:'POST',body:fd});
    var dl=$('cs-history-list');if(dl)dl.innerHTML='';this.style.display='none';
});

['cs-show-micro','cs-show-ingredients','cs-show-allergens'].forEach(function(id){
    $(id).addEventListener('change',function(){if(AR.length)renderList();});
});

// ESC close modals
document.addEventListener('keydown',function(e){
    if(e.key==='Escape'){
        $('cs-portion-modal').classList.remove('open');
        $('cs-compare-modal').style.display='none';
    }
});

function eh(s){var d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
function ec(s){return String(s||'').replace(/"/g,'""');}
})();
JSEOF;

    wp_register_script( 'cs-js', false, [], CS_VERSION, true );
    wp_enqueue_script( 'cs-js' );
    wp_add_inline_script( 'cs-js', $js );
    wp_localize_script( 'cs-js', 'csData', [
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'cs_nonce' ),
        'offNonce'  => wp_create_nonce( 'off_import_nonce' ),
        'usdaNonce' => wp_create_nonce( 'usda_import_nonce' ),
        'editUrl'   => admin_url( 'post.php?action=edit&post=' ),
        'rateLimit' => CS_RATE_LIMIT_SEC,
    ] );
} );


// ══════════════════════════════════════════════════════════════
// AJAX HANDLERS
// ══════════════════════════════════════════════════════════════

add_action( 'wp_ajax_cs_refresh_nonce', function(): void {
    check_ajax_referer( 'cs_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'X' );
    wp_send_json_success( [ 'nonce' => wp_create_nonce( 'cs_nonce' ) ] );
} );

add_action( 'wp_ajax_cs_clear_history', function(): void {
    check_ajax_referer( 'cs_nonce', 'nonce' );
    if ( current_user_can( 'manage_options' ) ) delete_option( CS_HISTORY_KEY );
    wp_send_json_success( 'OK' );
} );

function cs_save_history( string $q ): void {
    $h = get_option( CS_HISTORY_KEY, [] );
    if ( ! is_array( $h ) ) $h = [];
    $h = array_values( array_filter( $h, function( $v ) use ( $q ) { return $v !== $q; } ) );
    $h[] = $q;
    if ( count( $h ) > CS_HISTORY_MAX ) $h = array_slice( $h, -CS_HISTORY_MAX );
    update_option( CS_HISTORY_KEY, $h, 'no' );
}

function cs_batch_check_imported( array $vals, string $mk ): array {
    if ( empty( $vals ) ) return [];
    global $wpdb;
    $phs = implode( ',', array_fill( 0, count( $vals ), '%s' ) );
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT pm.meta_value, p.ID, p.post_title FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID=pm.post_id AND p.post_type='alapanyag' WHERE pm.meta_key=%s AND pm.meta_value IN ($phs)",
        array_merge( [ $mk ], $vals )
    ) );
    $map = [];
    foreach ( $rows as $r ) $map[ $r->meta_value ] = [ 'id' => (int) $r->ID, 'title' => $r->post_title ];
    return $map;
}

function cs_check_duplicate( string $name ): ?array {
    if ( mb_strlen( $name, 'UTF-8' ) < 3 ) return null;
    $clean = mb_strtolower( trim( $name ), 'UTF-8' );
    global $wpdb;
    $like = '%' . $wpdb->esc_like( mb_substr( $clean, 0, 8, 'UTF-8' ) ) . '%';
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT ID,post_title FROM {$wpdb->posts} WHERE post_type='alapanyag' AND post_status IN('publish','draft') AND LOWER(post_title) LIKE %s LIMIT 10", $like
    ) );
    foreach ( $rows as $r ) {
        similar_text( $clean, mb_strtolower( $r->post_title, 'UTF-8' ), $pct );
        if ( $pct >= 80 ) return [ 'id' => (int) $r->ID, 'name' => $r->post_title, 'similarity' => round( $pct ) ];
    }
    return null;
}


// ═════════════════���════════════════════════════════════════════
// AJAX: OFF KERESÉS
// ══════════════════════════════════════════════════════════════

add_action( 'wp_ajax_cs_search_off', function(): void {
    check_ajax_referer( 'cs_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'X' );

    $query   = sanitize_text_field( $_POST['query'] ?? '' );
    $country = sanitize_text_field( $_POST['country'] ?? '' );
    $page    = max( 1, (int) ( $_POST['page'] ?? 1 ) );
    $isBC    = ( $_POST['is_barcode'] ?? '0' ) === '1';

    if ( $query === '' ) wp_send_json_error( 'Üres.' );
    cs_save_history( $query );

    $ck = 'cs_off_' . md5( $query . $country . $page . ( $isBC ? 'b' : 't' ) );
    $cached = get_transient( $ck );
    if ( $cached !== false ) { wp_send_json_success( $cached ); }

    $fields = 'code,product_name,product_name_hu,nutriments,image_front_small_url,image_front_url,brands,nutriscore_grade,nova_group,allergens_tags,ingredients_text_hu,ingredients_text,completeness';

    if ( $isBC ) {
        $api = 'https://world.openfoodfacts.org/api/v2/product/' . urlencode( $query ) . '.json?fields=' . $fields;
    } else {
        $params = [
            'search_terms' => $query, 'search_simple' => 1, 'action' => 'process',
            'json' => 'true', 'page_size' => CS_OFF_PAGE_SIZE, 'page' => $page, 'fields' => $fields,
        ];
        if ( $country !== '' ) { $params['tagtype_0'] = 'countries'; $params['tag_contains_0'] = 'contains'; $params['tag_0'] = $country; }
        $api = add_query_arg( $params, 'https://world.openfoodfacts.org/cgi/search.pl' );
    }

    $resp = wp_remote_get( $api, [ 'timeout' => 15, 'headers' => [ 'User-Agent' => 'TapanyagLexikon/2.0 (WordPress plugin)' ] ] );
    if ( is_wp_error( $resp ) ) wp_send_json_error( 'OFF: ' . $resp->get_error_message() );

    $body = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( ! is_array( $body ) ) wp_send_json_error( 'OFF: invalid' );

    $products = $isBC ? ( ! empty( $body['product'] ) ? [ $body['product'] ] : [] ) : ( $body['products'] ?? [] );
    $total    = $isBC ? count( $products ) : (int) ( $body['count'] ?? 0 );

    $bcs = [];
    foreach ( $products as $p ) { $bc = trim( (string) ( $p['code'] ?? '' ) ); if ( $bc !== '' ) $bcs[] = $bc; }
    $imap = cs_batch_check_imported( $bcs, 'off_barcode' );

    $foods = [];
    foreach ( $products as $p ) {
        $bc = trim( (string) ( $p['code'] ?? '' ) );
        if ( $bc === '' ) continue;
        $name    = trim( (string) ( $p['product_name'] ?? '' ) );
        $name_hu = trim( (string) ( $p['product_name_hu'] ?? '' ) );
        if ( $name === '' && $name_hu === '' ) continue;
        $n   = (array) ( $p['nutriments'] ?? [] );
        $dn  = $name_hu !== '' ? $name_hu : $name;
        $alg = [];
        foreach ( (array) ( $p['allergens_tags'] ?? [] ) as $at ) $alg[] = str_replace( 'en:', '', (string) $at );
        $imp = $imap[ $bc ] ?? null;
        $dup = ( ! $imp ) ? cs_check_duplicate( $dn ) : null;

        $foods[] = [
            'barcode' => $bc, 'name' => $name, 'name_hu' => $name_hu,
            'brands' => (string) ( $p['brands'] ?? '' ),
            'image_url' => (string) ( $p['image_front_small_url'] ?? '' ),
            'nutri_score' => (string) ( $p['nutriscore_grade'] ?? '' ),
            'nova' => (string) ( $p['nova_group'] ?? '' ),
            'allergens' => $alg,
            'ingredients' => trim( (string) ( $p['ingredients_text_hu'] ?? $p['ingredients_text'] ?? '' ) ),
            'completeness' => isset( $p['completeness'] ) ? round( (float) $p['completeness'], 2 ) : null,
            'nutrients' => [
                'kcal' => isset( $n['energy-kcal_100g'] ) ? round( (float) $n['energy-kcal_100g'], 1 ) : null,
                'protein' => isset( $n['proteins_100g'] ) ? round( (float) $n['proteins_100g'], 1 ) : null,
                'carb' => isset( $n['carbohydrates_100g'] ) ? round( (float) $n['carbohydrates_100g'], 1 ) : null,
                'fat' => isset( $n['fat_100g'] ) ? round( (float) $n['fat_100g'], 1 ) : null,
                'fiber' => isset( $n['fiber_100g'] ) ? round( (float) $n['fiber_100g'], 1 ) : null,
                'sugar' => isset( $n['sugars_100g'] ) ? round( (float) $n['sugars_100g'], 1 ) : null,
                'saturated' => isset( $n['saturated-fat_100g'] ) ? round( (float) $n['saturated-fat_100g'], 1 ) : null,
                'sodium' => isset( $n['sodium_100g'] ) ? round( (float) $n['sodium_100g'] * 1000, 1 ) : null,
            ],
            'already_imported' => $imp !== null,
            'existing_id' => $imp ? $imp['id'] : 0,
            'existing_name' => $imp ? $imp['title'] : '',
            'dup_warning' => $dup,
        ];
    }

    $result = [ 'foods' => $foods, 'total' => $total, 'page' => $page ];
    set_transient( $ck, $result, CS_CACHE_TTL );
    wp_send_json_success( $result );
} );


// ═══════════════════════════���══════════════════════════════════
// AJAX: USDA KERESÉS
// ══════════════════════════════════════════════════════════════

add_action( 'wp_ajax_cs_search_usda', function(): void {
    check_ajax_referer( 'cs_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'X' );
    if ( ! defined( 'USDA_API_KEY' ) || USDA_API_KEY === '' ) wp_send_json_error( 'USDA API key hiányzik.' );

    $query = sanitize_text_field( $_POST['query'] ?? '' );
    $utype = sanitize_text_field( $_POST['usda_type'] ?? 'Foundation,SR Legacy' );
    $page  = max( 1, (int) ( $_POST['page'] ?? 1 ) );

    if ( $query === '' ) wp_send_json_error( 'Üres.' );
    cs_save_history( $query );

    $ck = 'cs_usda_' . md5( $query . $utype . $page );
    $cached = get_transient( $ck );
    if ( $cached !== false ) { wp_send_json_success( $cached ); }

    $dts = array_map( 'trim', explode( ',', $utype ) );
    $api = 'https://api.nal.usda.gov/fdc/v1/foods/search?api_key=' . USDA_API_KEY;
    $resp = wp_remote_post( $api, [
        'timeout' => 15, 'headers' => [ 'Content-Type' => 'application/json' ],
        'body' => wp_json_encode( [ 'query' => $query, 'dataType' => $dts, 'pageSize' => CS_USDA_PAGE_SIZE, 'pageNumber' => $page, 'sortBy' => 'dataType.keyword', 'sortOrder' => 'asc' ] ),
    ] );
    if ( is_wp_error( $resp ) ) wp_send_json_error( 'USDA: ' . $resp->get_error_message() );
    if ( wp_remote_retrieve_response_code( $resp ) !== 200 ) wp_send_json_error( 'USDA HTTP ' . wp_remote_retrieve_response_code( $resp ) );

    $body  = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( ! is_array( $body ) ) wp_send_json_error( 'USDA: invalid' );
    $total = (int) ( $body['totalHits'] ?? 0 );

    $fids = [];
    foreach ( ( $body['foods'] ?? [] ) as $f ) { $fid = (int) ( $f['fdcId'] ?? 0 ); if ( $fid ) $fids[] = (string) $fid; }
    $imap = cs_batch_check_imported( $fids, 'usda_fdc_id' );

    $mmap = [
        1087=>['n'=>'Kalcium','u'=>'mg'],1089=>['n'=>'Vas','u'=>'mg'],1090=>['n'=>'Magnézium','u'=>'mg'],
        1091=>['n'=>'Foszfor','u'=>'mg'],1092=>['n'=>'Kálium','u'=>'mg'],1095=>['n'=>'Cink','u'=>'mg'],
        1098=>['n'=>'Réz','u'=>'mg'],1101=>['n'=>'Mangán','u'=>'mg'],1103=>['n'=>'Szelén','u'=>'µg'],
        1104=>['n'=>'A-vitamin(RAE)','u'=>'µg'],1109=>['n'=>'E-vitamin','u'=>'mg'],1114=>['n'=>'D-vitamin','u'=>'µg'],
        1162=>['n'=>'C-vitamin','u'=>'mg'],1165=>['n'=>'B1','u'=>'mg'],1166=>['n'=>'B2','u'=>'mg'],
        1167=>['n'=>'B3','u'=>'mg'],1170=>['n'=>'B5','u'=>'mg'],1175=>['n'=>'B6','u'=>'mg'],
        1177=>['n'=>'B9(Folát)','u'=>'µg'],1178=>['n'=>'B12','u'=>'µg'],1180=>['n'=>'K-vitamin','u'=>'µg'],
        1253=>['n'=>'Koleszterin','u'=>'mg'],
    ];

    $foods = [];
    foreach ( ( $body['foods'] ?? [] ) as $f ) {
        $fid = (int) ( $f['fdcId'] ?? 0 );
        if ( ! $fid ) continue;
        $name = trim( (string) ( $f['description'] ?? '' ) );
        if ( $name === '' ) continue;

        $kcal=$prot=$carb=$fat=$fiber=$sugar=$sat=$sodium=null;$micro=[];
        foreach ( (array) ( $f['foodNutrients'] ?? [] ) as $fn ) {
            $nid = (int) ( $fn['nutrientId'] ?? 0 );
            $val = isset( $fn['value'] ) ? round( (float) $fn['value'], 2 ) : null;
            if ( $val === null ) continue;
            if ( $nid === 1008 ) $kcal = round( $val, 1 );
            elseif ( $nid === 1003 ) $prot = round( $val, 1 );
            elseif ( $nid === 1005 ) $carb = round( $val, 1 );
            elseif ( $nid === 1004 ) $fat = round( $val, 1 );
            elseif ( $nid === 1079 ) $fiber = round( $val, 1 );
            elseif ( $nid === 2000 ) $sugar = round( $val, 1 );
            elseif ( $nid === 1258 ) $sat = round( $val, 1 );
            elseif ( $nid === 1093 ) $sodium = round( $val, 1 );
            elseif ( isset( $mmap[ $nid ] ) ) $micro[ $mmap[$nid]['n'] ] = [ 'value' => $val, 'unit' => $mmap[$nid]['u'] ];
        }

        $imp = $imap[ (string) $fid ] ?? null;
        $dup = ( ! $imp ) ? cs_check_duplicate( $name ) : null;

        $foods[] = [
            'fdc_id' => $fid, 'name' => $name, 'data_type' => (string) ( $f['dataType'] ?? '' ),
            'nutrients' => [ 'kcal'=>$kcal,'protein'=>$prot,'carb'=>$carb,'fat'=>$fat,'fiber'=>$fiber,'sugar'=>$sugar,'saturated'=>$sat,'sodium'=>$sodium ],
            'micro' => $micro,
            'already_imported' => $imp !== null, 'existing_id' => $imp ? $imp['id'] : 0, 'existing_name' => $imp ? $imp['title'] : '',
            'dup_warning' => $dup,
        ];
    }

    $result = [ 'foods' => $foods, 'total' => $total, 'page' => $page ];
    set_transient( $ck, $result, CS_CACHE_TTL );
    wp_send_json_success( $result );
} );


// ══════════════════════════════════════════════════════════════
// AJAX: IMPORT OFF
// ══════════════════════════════════════════════════════════════

add_action( 'wp_ajax_cs_import_off', function(): void {
    check_ajax_referer( 'cs_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'X' );
    $bc = sanitize_text_field( $_POST['barcode'] ?? '' );
    if ( $bc === '' ) wp_send_json_error( 'Nincs vonalkód.' );
    if ( function_exists( 'off_import_single_product_handler' ) ) { $_POST['nonce'] = wp_create_nonce( 'off_import_nonce' ); off_import_single_product_handler(); return; }
    $r = cs_do_off_import( $bc );
    if ( $r ) wp_send_json_success( 'OK' ); else wp_send_json_error( 'Import hiba.' );
} );

// ══════════════════════════════════════════════════════════════
// AJAX: IMPORT USDA
// ══════════════════════════════════════════════════════════════

add_action( 'wp_ajax_cs_import_usda', function(): void {
    check_ajax_referer( 'cs_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'X' );
    if ( ! defined( 'USDA_API_KEY' ) || USDA_API_KEY === '' ) wp_send_json_error( 'USDA API key hiányzik.' );
    $fid = (int) ( $_POST['fdc_id'] ?? 0 );
    if ( ! $fid ) wp_send_json_error( 'Nincs FDC ID.' );
    if ( function_exists( 'usda_import_single_handler' ) ) { $_POST['nonce'] = wp_create_nonce( 'usda_import_nonce' ); usda_import_single_handler(); return; }
    $r = cs_do_usda_import( $fid );
    if ( $r ) wp_send_json_success( 'OK' ); else wp_send_json_error( 'Import hiba.' );
} );

// ══════════════════════════════════════════════════════════════
// AJAX: BATCH IMPORT
// ══════════════════════════════════════════════════════════════

add_action( 'wp_ajax_cs_batch_import', function(): void {
    check_ajax_referer( 'cs_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'X' );
    @set_time_limit( 300 );

    $items = json_decode( stripslashes( $_POST['items'] ?? '[]' ), true );
    if ( empty( $items ) || ! is_array( $items ) ) wp_send_json_error( 'Üres.' );

    $ok = 0; $err = 0; $skip = 0;
    foreach ( $items as $it ) {
        $bc  = sanitize_text_field( $it['barcode'] ?? '' );
        $fid = (int) ( $it['fdc_id'] ?? 0 );
        $src = sanitize_key( $it['source'] ?? '' );
        $q   = (int) ( $it['quality'] ?? 0 );

        if ( $src === 'both' ) {
            if ( $q >= 4 && $fid ) {
                $ex = get_posts( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'any', 'meta_query' => [['key'=>'usda_fdc_id','value'=>$fid]] ] );
                if ( ! empty( $ex ) ) { $skip++; continue; }
                cs_do_usda_import( $fid ) ? $ok++ : $err++;
            } elseif ( $bc ) {
                $ex = get_posts( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'any', 'meta_query' => [['key'=>'off_barcode','value'=>$bc]] ] );
                if ( ! empty( $ex ) ) { $skip++; continue; }
                cs_do_off_import( $bc ) ? $ok++ : $err++;
            } else { $err++; }
        } elseif ( $bc && $src !== 'usda' ) {
            $ex = get_posts( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'any', 'meta_query' => [['key'=>'off_barcode','value'=>$bc]] ] );
            if ( ! empty( $ex ) ) { $skip++; continue; }
            cs_do_off_import( $bc ) ? $ok++ : $err++;
        } elseif ( $fid ) {
            $ex = get_posts( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'any', 'meta_query' => [['key'=>'usda_fdc_id','value'=>$fid]] ] );
            if ( ! empty( $ex ) ) { $skip++; continue; }
            cs_do_usda_import( $fid ) ? $ok++ : $err++;
        } else { $err++; }
        usleep( 100000 );
    }

    wp_send_json_success( $ok . ' importálva' . ( $skip ? ', ' . $skip . ' kihagyva' : '' ) . ( $err ? ', ' . $err . ' hiba' : '' ) );
} );


// ══════════════════════════════════════════════════════════════
// IMPORT HELPERS
// ══════════════════════════════════════════════════════════════

function cs_do_off_import( string $bc ): bool {
    $existing = get_posts( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'any', 'meta_query' => [['key'=>'off_barcode','value'=>$bc]] ] );
    if ( ! empty( $existing ) ) return false;

    $resp = wp_remote_get( 'https://world.openfoodfacts.org/api/v2/product/' . urlencode( $bc ) . '.json', [ 'timeout' => 15, 'headers' => [ 'User-Agent' => 'TapanyagLexikon/2.0' ] ] );
    if ( is_wp_error( $resp ) ) return false;
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( empty( $body['product'] ) ) return false;

    $p  = $body['product'];
    $nh = trim( (string) ( $p['product_name_hu'] ?? '' ) );
    $ne = trim( (string) ( $p['product_name'] ?? '' ) );
    $title = $nh !== '' ? $nh : $ne;
    if ( $title === '' ) return false;
    $title = mb_strtoupper( mb_substr( $title, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $title, 1, null, 'UTF-8' );

    $n  = (array) ( $p['nutriments'] ?? [] );
    $uf = function_exists( 'update_field' );
    $pid = wp_insert_post( [ 'post_type' => 'alapanyag', 'post_title' => $title, 'post_status' => 'publish', 'post_name' => sanitize_title( $title ) ] );
    if ( is_wp_error( $pid ) || ! $pid ) return false;

    $meta = [
        'off_barcode' => $bc, 'off_url' => 'https://world.openfoodfacts.org/product/' . $bc,
        'kaloria' => isset( $n['energy-kcal_100g'] ) ? round( (float) $n['energy-kcal_100g'], 1 ) : 0,
        'feherje' => isset( $n['proteins_100g'] ) ? round( (float) $n['proteins_100g'], 1 ) : 0,
        'szenhidrat' => isset( $n['carbohydrates_100g'] ) ? round( (float) $n['carbohydrates_100g'], 1 ) : 0,
        'zsir' => isset( $n['fat_100g'] ) ? round( (float) $n['fat_100g'], 1 ) : 0,
        'rost' => isset( $n['fiber_100g'] ) ? round( (float) $n['fiber_100g'], 1 ) : 0,
        'cukor' => isset( $n['sugars_100g'] ) ? round( (float) $n['sugars_100g'], 1 ) : 0,
        'telitett_zsir' => isset( $n['saturated-fat_100g'] ) ? round( (float) $n['saturated-fat_100g'], 1 ) : 0,
        'natrium' => isset( $n['sodium_100g'] ) ? round( (float) $n['sodium_100g'] * 1000, 1 ) : 0,
    ];
    foreach ( $meta as $k => $v ) { $uf ? update_field( $k, $v, $pid ) : update_post_meta( $pid, $k, $v ); }

    $img = (string) ( $p['image_front_url'] ?? $p['image_front_small_url'] ?? '' );
    if ( $img !== '' ) cs_sideload_image( $img, $pid, $title );
    return true;
}

function cs_do_usda_import( int $fid ): bool {
    if ( ! defined( 'USDA_API_KEY' ) || USDA_API_KEY === '' ) return false;
    $existing = get_posts( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'any', 'meta_query' => [['key'=>'usda_fdc_id','value'=>$fid]] ] );
    if ( ! empty( $existing ) ) return false;

    $resp = wp_remote_get( 'https://api.nal.usda.gov/fdc/v1/food/' . $fid . '?api_key=' . USDA_API_KEY . '&format=full', [ 'timeout' => 15 ] );
    if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) return false;
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( ! is_array( $body ) || empty( $body['description'] ) ) return false;

    $title = trim( (string) $body['description'] );
    $title = mb_strtoupper( mb_substr( $title, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $title, 1, null, 'UTF-8' );
    $uf = function_exists( 'update_field' );
    $pid = wp_insert_post( [ 'post_type' => 'alapanyag', 'post_title' => $title, 'post_status' => 'publish', 'post_name' => sanitize_title( $title ) ] );
    if ( is_wp_error( $pid ) || ! $pid ) return false;

    $nmap = [
        1008=>'kaloria',1003=>'feherje',1005=>'szenhidrat',1004=>'zsir',1079=>'rost',2000=>'cukor',
        1258=>'telitett_zsir',1093=>'natrium',1087=>'kalcium',1089=>'vas',1092=>'kalium',1090=>'magnezium',
        1091=>'foszfor',1095=>'cink',1098=>'rez',1101=>'mangan',1103=>'szelen',
        1162=>'vitamin_c',1104=>'vitamin_a',1114=>'vitamin_d',1109=>'vitamin_e',1180=>'vitamin_k',
        1165=>'vitamin_b1',1166=>'vitamin_b2',1167=>'vitamin_b3',1170=>'vitamin_b5',
        1175=>'vitamin_b6',1177=>'vitamin_b9',1178=>'vitamin_b12',1253=>'koleszterin',
    ];
    $meta = [ 'usda_fdc_id' => $fid ];
    foreach ( (array) ( $body['foodNutrients'] ?? [] ) as $fn ) {
        $nid = (int) ( $fn['nutrient']['id'] ?? $fn['nutrientId'] ?? 0 );
        $val = (float) ( $fn['amount'] ?? $fn['value'] ?? 0 );
        if ( isset( $nmap[ $nid ] ) && $val > 0 ) $meta[ $nmap[$nid] ] = round( $val, 2 );
    }
    foreach ( $meta as $k => $v ) { $uf ? update_field( $k, $v, $pid ) : update_post_meta( $pid, $k, $v ); }
    return true;
}

function cs_sideload_image( string $url, int $pid, string $desc ): void {
    if ( ! function_exists( 'media_sideload_image' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }
    $att = media_sideload_image( $url, $pid, $desc, 'id' );
    if ( ! is_wp_error( $att ) && is_int( $att ) ) set_post_thumbnail( $pid, $att );
}
