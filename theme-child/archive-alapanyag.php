<?php
/**
 * Archive Alapanyag template – v6 FINAL
 * Child theme: archive-alapanyag.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$total_q = new WP_Query([
    'post_type'      => 'alapanyag',
    'posts_per_page' => 1,
    'post_status'    => 'publish',
]);
$total_count = $total_q->found_posts;
wp_reset_postdata();
?>

<div class="aa-archive-wrap">

    <?php // ═══════════════════════════════════════════════════ ?>
    <?php // ── HERO ──────────────────────────────────────── ?>
    <?php // ═══════════════════════════════════════════════════ ?>

    <div class="aa-hero">
        <h1 class="aa-hero-title">Alapanyag-adatbázis</h1>
        <p class="aa-hero-subtitle">
            Nyersanyag-és élelmiszer adatbázis, részletes táplértékekkel –
            <strong id="aa-total-count"><?php echo number_format( $total_count ); ?></strong> alapanyag található meg jelenleg adatbázisunkban.
        </p>

        <div class="aa-search-bar">
            <div class="aa-search-inner">
                <svg class="aa-search-svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="rgba(255,255,255,0.5)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text"
                       id="aa-search"
                       class="aa-search-input"
                       placeholder="Keresés név vagy eredeti név alapján..."
                       autocomplete="off">
                <button id="aa-search-clear" class="aa-search-clear" style="display:none;">✕</button>
            </div>
            <div id="aa-search-dropdown" class="aa-search-dropdown"></div>
        </div>
    </div>

    <?php // ═══════════════════════════════════════════════════ ?>
    <?php // ── TOOLBAR ───────────────────────────────────── ?>
    <?php // ═══════════════════════════════════════════════════ ?>

    <div class="aa-toolbar">
        <div class="aa-toolbar-left">
            <button id="aa-filter-toggle" class="aa-filter-toggle-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" y1="6" x2="20" y2="6"/>
                    <line x1="8" y1="12" x2="16" y2="12"/>
                    <line x1="11" y1="18" x2="13" y2="18"/>
                </svg>
                Szűrés
            </button>
        </div>

        <div class="aa-toolbar-right">
            <span id="aa-result-info" class="aa-toolbar-count"></span>

            <div class="aa-toolbar-group aa-toolbar-cols">
                <span class="aa-toolbar-label">Oszlopok száma:</span>
                <button class="aa-col-btn" data-cols="3">3</button>
                <button class="aa-col-btn is-active" data-cols="4">4</button>
                <button class="aa-col-btn" data-cols="5">5</button>
            </div>

            <div class="aa-toolbar-group aa-toolbar-sort">
                <span class="aa-toolbar-label">Rendezés:</span>
                <select id="aa-sort" class="aa-toolbar-select">
                    <option value="title_asc">Név (A→Z)</option>
                    <option value="title_desc">Név (Z→A)</option>
                    <optgroup label="Kalória">
                        <option value="kcal_desc">Kalória ↓ (csökkenő)</option>
                        <option value="kcal_asc">Kalória ↑ (növekvő)</option>
                    </optgroup>
                    <optgroup label="Fehérje">
                        <option value="protein_desc">Fehérje ↓ (csökkenő)</option>
                        <option value="protein_asc">Fehérje ↑ (növekvő)</option>
                    </optgroup>
                    <optgroup label="Szénhidrát">
                        <option value="carb_desc">Szénhidrát ↓ (csökkenő)</option>
                        <option value="carb_asc">Szénhidrát ↑ (növekvő)</option>
                    </optgroup>
                    <optgroup label="Zsír">
                        <option value="fat_desc">Zsír ↓ (csökkenő)</option>
                        <option value="fat_asc">Zsír ↑ (növekvő)</option>
                    </optgroup>
                    <option value="date_desc">Legújabb</option>
                </select>
            </div>

            <div class="aa-toolbar-group aa-toolbar-perpage">
                <span class="aa-toolbar-label">Elemek oldalanként:</span>
                <button class="aa-pp-btn is-active" data-pp="24">24</button>
                <button class="aa-pp-btn" data-pp="36">36</button>
                <button class="aa-pp-btn" data-pp="48">48</button>
            </div>
        </div>
    </div>

    <?php // ═══════════════════════════════════════════════════ ?>
    <?php // ── SZŰRŐ PANEL ───────────────────────────────── ?>
    <?php // ═══════════════════════════════════════════════════ ?>

    <div class="aa-szuro-panel" id="aa-szuro-panel">
        <div class="aa-szuro-panel-inner" id="aa-szuro-panel-inner">
            <div class="aa-szuro-rows">

                <div class="aa-szuro-row">
                    <span class="aa-szuro-label">Forrás</span>
                    <div class="aa-szuro-chips">
                        <button class="aa-chip is-active" data-filter="source" data-value="all">Összes</button>
                        <button class="aa-chip" data-filter="source" data-value="off">Open Food Facts</button>
                        <button class="aa-chip" data-filter="source" data-value="usda">USDA FoodData</button>
                        <button class="aa-chip" data-filter="source" data-value="both">Mindkettő</button>
                    </div>
                    <div id="aa-source-desc" class="aa-source-desc"></div>
                </div>

                <div class="aa-szuro-row-separator"></div>

                <div class="aa-szuro-row aa-szuro-row-range">
                    <span class="aa-szuro-label">Kalória</span>
                    <div class="aa-range-wrap" data-key="kcal" data-min="0" data-max="930" data-step="5" data-unit="kcal">
                        <div class="aa-range-track">
                            <div class="aa-range-fill"></div>
                            <input type="range" class="aa-range-thumb aa-range-min" min="0" max="930" step="5" value="0">
                            <input type="range" class="aa-range-thumb aa-range-max" min="0" max="930" step="5" value="930">
                        </div>
                        <div class="aa-range-inputs">
                            <div class="aa-range-input-group">
                                <input type="number" class="aa-range-num aa-range-num-min" min="0" max="930" step="5" value="0">
                                <span class="aa-range-unit">kcal</span>
                            </div>
                            <span class="aa-range-separator">–</span>
                            <div class="aa-range-input-group">
                                <input type="number" class="aa-range-num aa-range-num-max" min="0" max="930" step="5" value="930">
                                <span class="aa-range-unit">kcal</span>
                            </div>
                            <span class="aa-range-per">/ 100g</span>
                        </div>
                    </div>
                </div>

                <div class="aa-szuro-row-separator"></div>

                <div class="aa-szuro-row aa-szuro-row-range">
                    <span class="aa-szuro-label">Fehérje</span>
                    <div class="aa-range-wrap" data-key="protein" data-min="0" data-max="100" data-step="0.1" data-unit="g">
                        <div class="aa-range-track">
                            <div class="aa-range-fill"></div>
                            <input type="range" class="aa-range-thumb aa-range-min" min="0" max="100" step="0.1" value="0">
                            <input type="range" class="aa-range-thumb aa-range-max" min="0" max="100" step="0.1" value="100">
                        </div>
                        <div class="aa-range-inputs">
                            <div class="aa-range-input-group">
                                <input type="number" class="aa-range-num aa-range-num-min" min="0" max="100" step="0.1" value="0">
                                <span class="aa-range-unit">g</span>
                            </div>
                            <span class="aa-range-separator">–</span>
                            <div class="aa-range-input-group">
                                <input type="number" class="aa-range-num aa-range-num-max" min="0" max="100" step="0.1" value="100">
                                <span class="aa-range-unit">g</span>
                            </div>
                            <span class="aa-range-per">/ 100g</span>
                        </div>
                    </div>
                </div>

                <div class="aa-szuro-row-separator"></div>

                <div class="aa-szuro-row aa-szuro-row-range">
                    <span class="aa-szuro-label">Szénhidrát</span>
                    <div class="aa-range-wrap" data-key="carb" data-min="0" data-max="100" data-step="0.1" data-unit="g">
                        <div class="aa-range-track">
                            <div class="aa-range-fill"></div>
                            <input type="range" class="aa-range-thumb aa-range-min" min="0" max="100" step="0.1" value="0">
                            <input type="range" class="aa-range-thumb aa-range-max" min="0" max="100" step="0.1" value="100">
                        </div>
                        <div class="aa-range-inputs">
                            <div class="aa-range-input-group">
                                <input type="number" class="aa-range-num aa-range-num-min" min="0" max="100" step="0.1" value="0">
                                <span class="aa-range-unit">g</span>
                            </div>
                            <span class="aa-range-separator">–</span>
                            <div class="aa-range-input-group">
                                <input type="number" class="aa-range-num aa-range-num-max" min="0" max="100" step="0.1" value="100">
                                <span class="aa-range-unit">g</span>
                            </div>
                            <span class="aa-range-per">/ 100g</span>
                        </div>
                    </div>
                </div>

                <div class="aa-szuro-row-separator"></div>

                <div class="aa-szuro-row aa-szuro-row-range">
                    <span class="aa-szuro-label">Zsír</span>
                    <div class="aa-range-wrap" data-key="fat" data-min="0" data-max="100" data-step="0.1" data-unit="g">
                        <div class="aa-range-track">
                            <div class="aa-range-fill"></div>
                            <input type="range" class="aa-range-thumb aa-range-min" min="0" max="100" step="0.1" value="0">
                            <input type="range" class="aa-range-thumb aa-range-max" min="0" max="100" step="0.1" value="100">
                        </div>
                        <div class="aa-range-inputs">
                            <div class="aa-range-input-group">
                                <input type="number" class="aa-range-num aa-range-num-min" min="0" max="100" step="0.1" value="0">
                                <span class="aa-range-unit">g</span>
                            </div>
                            <span class="aa-range-separator">–</span>
                            <div class="aa-range-input-group">
                                <input type="number" class="aa-range-num aa-range-num-max" min="0" max="100" step="0.1" value="100">
                                <span class="aa-range-unit">g</span>
                            </div>
                            <span class="aa-range-per">/ 100g</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="aa-szuro-footer">
                <button id="aa-szuro-reset" class="aa-szuro-reset-btn">↺ Alaphelyzet</button>
            </div>
        </div>
    </div>

    <?php // ═══════════════════════════════════════════════════ ?>
    <?php // ── GRID ──────────────────────────────────────── ?>
    <?php // ═══════════════════════════════════════════════════ ?>

    <div id="aa-grid" class="aa-grid">
        <div class="aa-loading">
            <div class="aa-loading-spinner"></div>
            <span>Betöltés...</span>
        </div>
    </div>

    <?php // ═══════════════════════════════════════════════════ ?>
    <?php // ── LAPOZÁS ───────────────────────────────────── ?>
    <?php // ═══════════════════════════════════════════════════ ?>

    <div id="aa-pagination" class="aa-pagination"></div>

</div>

<?php get_footer(); ?>