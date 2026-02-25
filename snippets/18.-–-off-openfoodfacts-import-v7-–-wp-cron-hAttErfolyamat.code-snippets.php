<?php

/**
 * 18. – OFF / OPENFOODFACTS IMPORT (v7) – WP-CRON HÁTTÉRFOLYAMAT
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// 19 – OPENFOODFACTS IMPORT (v7.1 FINAL)
//
// Motor: HIBRID
//   - Ha a böngésző nyitva van → a polling AJAX futtatja
//     a batch-eket (legmegbízhatóbb)
//   - Ha bezárod a böngészőt → WP-Cron próbálja
//     (ha a hosting engedi)
//   - Minden batch után azonnal menti a pozíciót
//   - API hiba → 3× retry → továbblép
//
// Admin: Alapanyagok → 🔄 OFF Import
//
// API LIMIT:
//   OFF: 100 kérés / perc (1 batch = 1 kérés)
//   Az admin felület NEM engedi elindítani ha túl gyors
//
// FUNKCIÓK:
//   🚀 Új import (elejétől)
//   ▶️  Folytatás (mentett pozícióból)
//   ⏹  Leállítás (pozíció mentése)
//   🔄 Szinkron (meglévők ellenőrzése)
//   🔍 Egyedi keresés + import
//   🔄 Publish box szinkron gomb
// ============================================================


// ══════════════════════════════════════════════════════════════
// KONSTANSOK
// ══════════════════════════════════════════════════════════════

define( 'OFF_IMPORT_STATE_KEY',   'off_import_state_v7' );
define( 'OFF_IMPORT_LOG_KEY',     'off_import_log_v7' );
define( 'OFF_IMPORT_LASTRUN_KEY', 'off_import_last_run_time' );
define( 'OFF_CRON_HOOK',          'off_import_cron_batch' );

define( 'OFF_API_RATE_LIMIT',     100 );   // max kérés / perc
define( 'OFF_API_MIN_DELAY',      1 );     // minimum delay (mp)
define( 'OFF_API_MAX_BATCH',      100 );   // max batch méret
define( 'OFF_API_RETRY_COUNT',    3 );     // retry szám hiba esetén
define( 'OFF_API_RETRY_DELAY',    5 );     // retry közti várakozás (mp)
define( 'OFF_API_TIMEOUT',        60 );    // API kérés timeout (mp)
define( 'OFF_EMPTY_BATCH_LIMIT',  15 );    // ennyi üres batch → leáll
define( 'OFF_LOG_MAX_ENTRIES',    200 );   // max log bejegyzések


// ══════════════════════════════════════════════════════════════
// 1. ADMIN MENÜ
// ══════════════════════════════════════════════════════════════

function off_import_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=alapanyag',
        'OpenFoodFacts Import',
        '🔄 OFF Import',
        'manage_options',
        'off-import',
        'off_import_admin_page'
    );
}
add_action( 'admin_menu', 'off_import_admin_menu' );


// ══════════════════════════════════════════════════════════════
// 2. ADMIN OLDAL HTML
// ══════════════════════════════════════════════════════════════

function off_import_admin_page() {

    // Meglévő importált alapanyagok száma
    $imported_q = new WP_Query( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => 1,
        'meta_query'     => [ [ 'key' => 'off_barcode', 'compare' => 'EXISTS' ] ],
    ] );
    $existing_count = $imported_q->found_posts;
    wp_reset_postdata();

    // Mentett állapot
    $state      = get_option( OFF_IMPORT_STATE_KEY, [] );
    $is_running = ( ( $state['status'] ?? '' ) === 'running' );
    $has_saved  = ! empty( $state['page'] )
                  && intval( $state['page'] ) > 1
                  && ! $is_running;

    ?>
    <div class="wrap">
        <h1>🍊 OpenFoodFacts – Termék Import</h1>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── ÁLLAPOT DOBOZ ───────────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div style="
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 20px 0;
            max-width: 900px;
        ">
            <h3 style="margin-top: 0;">ℹ️ Állapot</h3>

            <table class="widefat" style="max-width: 600px;">
                <tr>
                    <td><strong>Importált alapanyagok</strong></td>
                    <td>
                        <strong style="font-size: 1.2rem;">
                            <?php echo intval( $existing_count ); ?>
                        </strong> db
                    </td>
                </tr>
                <tr>
                    <td><strong>API limit</strong></td>
                    <td>
                        <span style="color: #dc2626; font-weight: 600;">
                            100 kérés / perc
                        </span>
                        (1 batch = 1 kérés)
                    </td>
                </tr>
                <tr>
                    <td><strong>Motor</strong></td>
                    <td>
                        🔄 Hibrid (böngésző polling + WP-Cron háttér)
                    </td>
                </tr>
                <tr>
                    <td><strong>Licenc</strong></td>
                    <td>ODbL (Open Database License)</td>
                </tr>

                <?php if ( $is_running ) : ?>
                    <tr>
                        <td><strong>Folyamat</strong></td>
                        <td>
                            <span style="
                                color: #16a34a;
                                font-weight: 700;
                                animation: pulse 1.5s infinite;
                            ">
                                🟢 FUT
                            </span>
                            – Oldal <?php echo intval( $state['page'] ?? 1 ); ?>
                            | +<?php echo intval( $state['stats']['created'] ?? 0 ); ?> új
                            | <?php echo esc_html( $state['country_label'] ?? '🇭🇺' ); ?>
                        </td>
                    </tr>
                <?php elseif ( $has_saved ) : ?>
                    <tr>
                        <td><strong>Mentett pozíció</strong></td>
                        <td>
                            <span style="color: #2563eb;">
                                📌 Oldal <?php echo intval( $state['page'] ); ?>
                                | <?php echo esc_html( $state['country_label'] ?? '' ); ?>
                                | +<?php echo intval( $state['stats']['created'] ?? 0 ); ?> importálva
                                | <?php echo esc_html( $state['date'] ?? '' ); ?>
                            </span>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── BEÁLLÍTÁSOK DOBOZ ───────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div id="off-settings-box" style="
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 20px 0;
            max-width: 900px;
            <?php echo $is_running ? 'opacity: 0.5; pointer-events: none;' : ''; ?>
        ">
            <h3 style="margin-top: 0;">⚙️ Beállítások</h3>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">

                <?php // ── Ország választó ── ?>
                <div>
                    <label for="off-country" style="
                        font-weight: 600;
                        font-size: 0.88rem;
                        display: block;
                        margin-bottom: 4px;
                    ">
                        Ország:
                    </label>
                    <select id="off-country" style="width: 100%;">
                        <option value="hungary" selected>🇭🇺 Magyarország</option>
                        <option value="germany">🇩🇪 Németország</option>
                        <option value="austria">🇦🇹 Ausztria</option>
                        <option value="united-kingdom">🇬🇧 Egyesült Királyság</option>
                        <option value="united-states">🇺🇸 USA</option>
                        <option value="france">🇫🇷 Franciaország</option>
                        <option value="italy">🇮🇹 Olaszország</option>
                        <option value="spain">🇪🇸 Spanyolország</option>
                        <option value="poland">🇵🇱 Lengyelország</option>
                        <option value="romania">🇷🇴 Románia</option>
                        <option value="slovakia">🇸🇰 Szlovákia</option>
                        <option value="croatia">🇭🇷 Horvátország</option>
                        <option value="serbia">🇷🇸 Szerbia</option>
                        <option value="">🌍 Összes ország</option>
                    </select>
                </div>

                <?php // ── Batch méret ── ?>
                <div>
                    <label for="off-batch-size" style="
                        font-weight: 600;
                        font-size: 0.88rem;
                        display: block;
                        margin-bottom: 4px;
                    ">
                        Batch méret:
                    </label>
                    <input type="number"
                           id="off-batch-size"
                           value="50"
                           min="10"
                           max="100"
                           step="10"
                           style="width: 100%;">
                    <span style="color: #888; font-size: 0.75rem;">
                        10–100 / kör (API max: 100)
                    </span>
                </div>

                <?php // ── Delay ── ?>
                <div>
                    <label for="off-batch-delay" style="
                        font-weight: 600;
                        font-size: 0.88rem;
                        display: block;
                        margin-bottom: 4px;
                    ">
                        Delay (mp):
                    </label>
                    <input type="number"
                           id="off-batch-delay"
                           value="3"
                           min="1"
                           max="60"
                           step="0.5"
                           style="width: 100%;">
                    <span style="color: #888; font-size: 0.75rem;">
                        min: 1 mp (ajánlott: 3)
                    </span>
                </div>

                <?php // ── Maximum ── ?>
                <div>
                    <label for="off-max" style="
                        font-weight: 600;
                        font-size: 0.88rem;
                        display: block;
                        margin-bottom: 4px;
                    ">
                        Maximum (új):
                    </label>
                    <input type="number"
                           id="off-max"
                           value="10000"
                           min="10"
                           max="50000"
                           step="10"
                           style="width: 100%;">
                    <span style="color: #888; font-size: 0.75rem;">
                        0 = nincs limit
                    </span>
                </div>
            </div>

            <?php // ── API limit figyelmeztetés ── ?>
            <div id="off-limit-warning" style="
                margin-top: 12px;
                padding: 10px 14px;
                border-radius: 6px;
                font-size: 0.85rem;
                display: none;
            "></div>

            <?php // ── Becsült idő ── ?>
            <div id="off-time-estimate-box" style="
                margin-top: 12px;
                padding: 10px 14px;
                background: #fffbeb;
                border-radius: 6px;
                font-size: 0.85rem;
                color: #92400e;
            ">
                💡 <strong>Becsült idő</strong>:
                <span id="off-time-estimate">–</span>
                (<span id="off-batch-count-est">–</span> batch
                × <span id="off-delay-est">–</span>s)
                | API kérés/perc:
                <strong><span id="off-req-per-min">–</span></strong> / 100
            </div>
        </div>

        <?php // ════════════════════════════��══════════════════ ?>
        <?php // ── HÁROM MÓD ──────────────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div style="
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            max-width: 900px;
            margin: 20px 0;
        ">

            <?php // ── ÚJ IMPORT ── ?>
            <div style="
                background: #f0fdf4;
                border: 2px solid #86efac;
                border-radius: 8px;
                padding: 18px 20px;
            ">
                <h3 style="margin-top: 0; color: #16a34a; font-size: 1rem;">
                    🚀 Új import
                </h3>
                <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                    Az <strong>elejétől</strong> indul.
                    Amíg ez az oldal nyitva van, automatikusan fut.
                    Ha bezárod, a szerver próbálja folytatni.
                </p>
                <button id="off-import-start"
                        class="button button-primary"
                        style="font-size: 13px; padding: 5px 16px;"
                        <?php echo $is_running ? 'disabled' : ''; ?>>
                    🚀 Indítás (elejétől)
                </button>
            </div>

            <?php // ── FOLYTATÁS ── ?>
            <div style="
                background: <?php echo $has_saved ? '#eff6ff' : ( $is_running ? '#f0fdf4' : '#f8fafc' ); ?>;
                border: 2px solid <?php echo $has_saved ? '#93c5fd' : ( $is_running ? '#86efac' : '#e2e8f0' ); ?>;
                border-radius: 8px;
                padding: 18px 20px;
            ">
                <h3 style="margin-top: 0; color: #2563eb; font-size: 1rem;">
                    ▶️ Folytatás
                </h3>

                <?php if ( $is_running ) : ?>
                    <p style="
                        font-size: 0.82rem;
                        color: #16a34a;
                        line-height: 1.4;
                        font-weight: 600;
                    ">
                        🟢 Jelenleg fut...
                    </p>
                    <button class="button" disabled style="font-size: 13px; padding: 5px 16px;">
                        ▶️ Fut...
                    </button>

                <?php elseif ( $has_saved ) : ?>
                    <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                        Utolsó: <strong>oldal <?php echo intval( $state['page'] ); ?></strong>
                        (+<?php echo intval( $state['stats']['created'] ?? 0 ); ?> importálva)
                        <br><?php echo esc_html( $state['date'] ?? '' ); ?>
                    </p>
                    <button id="off-import-continue"
                            class="button"
                            style="
                                font-size: 13px;
                                padding: 5px 16px;
                                background: #3b82f6;
                                color: #fff;
                                border-color: #2563eb;
                            ">
                        ▶️ Folytatás
                    </button>
                    <button id="off-import-reset"
                            class="button"
                            style="
                                font-size: 12px;
                                padding: 4px 10px;
                                color: #dc2626;
                                margin-left: 4px;
                            "
                            title="Mentett pozíció törlése">
                        🗑️
                    </button>

                <?php else : ?>
                    <p style="font-size: 0.82rem; color: #999; line-height: 1.4;">
                        Nincs mentett pozíció.
                    </p>
                    <button class="button" disabled style="font-size: 13px; padding: 5px 16px;">
                        ▶️ Nincs mentett adat
                    </button>
                <?php endif; ?>
            </div>

            <?php // ── SZINKRON ── ?>
            <div style="
                background: #fffbeb;
                border: 2px solid #fcd34d;
                border-radius: 8px;
                padding: 18px 20px;
            ">
                <h3 style="margin-top: 0; color: #d97706; font-size: 1rem;">
                    🔄 Szinkron
                </h3>
                <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                    Meglévőket ellenőrzi.
                    <strong>Eltéréseket mutat</strong>
                    és te döntöd el mit frissítesz.
                </p>
                <button id="off-sync-start"
                        class="button"
                        style="
                            font-size: 13px;
                            padding: 5px 16px;
                            background: #f59e0b;
                            color: #fff;
                            border-color: #d97706;
                        "
                        <?php echo ( $existing_count < 1 || $is_running ) ? 'disabled' : ''; ?>>
                    🔄 Szinkron
                </button>
            </div>
        </div>

        <?php // ── LEÁLLÍTÁS ── ?>
        <div style="margin: 0 0 12px;">
            <button id="off-stop"
                    class="button button-secondary"
                    style="
                        font-size: 14px;
                        padding: 6px 20px;
                        <?php echo $is_running ? '' : 'display: none;'; ?>
                    ">
                ⏹ Leállítás
            </button>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── EGYEDI KERESÉS ──────────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div style="
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 20px 0;
            max-width: 900px;
        ">
            <h3 style="margin-top: 0;">🔍 Egyedi OFF keresés</h3>
            <p style="font-size: 0.88rem; color: #555;">
                Keress rá egy termékre és importáld egyenként.
            </p>

            <div style="display: flex; gap: 8px;">
                <input type="text"
                       id="off-search-query"
                       placeholder="pl. túró rudi, müzli, csirkemell..."
                       style="flex: 1; font-size: 14px; padding: 6px 12px;">

                <select id="off-search-country" style="font-size: 14px; padding: 6px 8px;">
                    <option value="">🌍 Összes</option>
                    <option value="hungary" selected>🇭🇺 Magyarország</option>
                    <option value="germany">🇩🇪 Németország</option>
                    <option value="austria">🇦🇹 Ausztria</option>
                    <option value="united-states">🇺🇸 USA</option>
                </select>

                <button id="off-search-btn"
                        class="button"
                        style="font-size: 14px; padding: 6px 16px;">
                    🔍 Keresés
                </button>
            </div>

            <div id="off-search-results" style="margin-top: 12px;"></div>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── ÉLŐ STÁTUSZ + LOG ──────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div id="off-live-status" style="
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 20px 0;
            max-width: 900px;
            <?php echo $is_running ? '' : 'display: none;'; ?>
        ">
            <h3 style="margin-top: 0;">
                📊 Élő státusz
                <span id="off-live-indicator" style="
                    color: #16a34a;
                    animation: pulse 1.5s infinite;
                ">●</span>
            </h3>

            <?php // ── Progress bar ── ?>
            <div style="
                background: #f0f0f1;
                border-radius: 6px;
                height: 28px;
                overflow: hidden;
                margin-bottom: 12px;
            ">
                <div id="off-progress-bar" style="
                    background: linear-gradient(90deg, #F59E0B, #f97316);
                    height: 100%;
                    width: 0%;
                    transition: width 0.4s ease;
                    border-radius: 6px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #fff;
                    font-weight: 600;
                    font-size: 0.85rem;
                ">0%</div>
            </div>

            <?php // ── Statisztikák ── ?>
            <div style="
                display: grid;
                grid-template-columns: repeat(6, 1fr);
                gap: 8px;
                margin-bottom: 12px;
            ">
                <div style="text-align: center; padding: 8px; background: #f0f6fc; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700;" id="off-s-processed">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Feldolgozva</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #f0fdf4; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #16a34a;" id="off-s-created">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Új létrehozva</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #fffbeb; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #d97706;" id="off-s-skipped">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Kihagyva</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #fef2f2; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #dc2626;" id="off-s-errors">0</div>
                    <div style="font-size: 0.72rem; color: #666;">API hiba</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #f5f3ff; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #7c3aed;" id="off-s-empty">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Üres batch</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #f0f6fc; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #2563eb;" id="off-s-page">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Aktuális oldal</div>
                </div>
            </div>

            <?php // ── Batch info ── ?>
            <div id="off-live-info" style="
                font-size: 0.85rem;
                color: #666;
                margin-bottom: 8px;
                padding: 6px 10px;
                background: #f8fafc;
                border-radius: 4px;
            "></div>

            <?php // ── Log ── ?>
            <div id="off-log" style="
                background: #1e1e1e;
                color: #d4d4d4;
                font-family: monospace;
                font-size: 0.82rem;
                padding: 12px 16px;
                border-radius: 6px;
                max-height: 300px;
                overflow-y: auto;
                line-height: 1.6;
            "></div>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── SZINKRON ELTÉRÉSEK ──────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div id="off-diff-section" style="
            display: none;
            background: #fff;
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 20px 0;
            max-width: 900px;
        ">
            <h3 style="margin-top: 0; color: #d97706;">
                ⚠️ Eltérések – <span id="off-diff-count">0</span> alapanyagnál
            </h3>
            <p style="font-size: 0.88rem; color: #555;">
                Jelöld ki melyeket szeretnéd frissíteni.
            </p>

            <div style="margin-bottom: 12px;">
                <label style="font-size: 0.88rem; cursor: pointer;">
                    <input type="checkbox" id="off-diff-select-all" checked>
                    Összes kijelölése
                </label>
            </div>

            <div id="off-diff-list" style="max-height: 500px; overflow-y: auto;"></div>

            <div style="margin-top: 16px;">
                <button id="off-diff-apply"
                        class="button button-primary"
                        style="font-size: 14px; padding: 6px 20px;">
                    ✅ Kijelöltek frissítése
                </button>
                <span id="off-diff-apply-status" style="
                    margin-left: 12px;
                    font-size: 0.88rem;
                "></span>
            </div>
        </div>

    </div>

    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
    </style>
    <?php
}
// ============================================================
// 19 v7.1 – 2. RÉSZ: JS + PHP BACKEND
// Ez a fájl a resz1 UTÁN töltődik be (ugyanabba a snippetbe)
// ============================================================


// ══════════════════════════════════════════════════════════════
// 3. ADMIN SCRIPTS (JS)
// ══════════════════════════════════════════════════════════════

function off_import_admin_scripts( $hook ) {
    if ( $hook !== 'alapanyag_page_off-import' ) return;

    $state = get_option( OFF_IMPORT_STATE_KEY, [] );

    $js = <<<'JSEOF'
(function() {
    'use strict';

    var $ = document.getElementById.bind(document);
    var pollTimer = null;
    var syncAllDiffs = [];

    var countryLabels = {
        'hungary':        '🇭🇺 Magyarország',
        'germany':        '🇩🇪 Németország',
        'austria':        '🇦🇹 Ausztria',
        'united-kingdom': '🇬🇧 UK',
        'united-states':  '🇺🇸 USA',
        'france':         '🇫🇷 Franciaország',
        'italy':          '🇮🇹 Olaszország',
        'spain':          '🇪🇸 Spanyolország',
        'poland':         '🇵🇱 Lengyelország',
        'romania':        '🇷🇴 Románia',
        'slovakia':       '🇸🇰 Szlovákia',
        'croatia':        '🇭🇷 Horvátország',
        'serbia':         '🇷🇸 Szerbia',
        '':               '🌍 Összes'
    };


    // ══════════════════════════════════════════════════════
    // API LIMIT VALIDÁCIÓ
    // ══════════════════════════════════════════════════════

    function validateSettings() {
        var batchSize = parseInt($('off-batch-size').value) || 50;
        var delay     = parseFloat($('off-batch-delay').value) || 3;
        var maxItems  = parseInt($('off-max').value) || 10000;

        // Számítások
        var reqPerMin = Math.round(60 / delay);
        var batches   = Math.ceil(maxItems / batchSize);
        var totalSec  = batches * delay;
        var min       = Math.floor(totalSec / 60);
        var sec       = Math.round(totalSec % 60);
        var timeStr   = (min > 0 ? '~' + min + ' perc ' : '~') + sec + ' mp';

        // Kijelzés frissítése
        $('off-req-per-min').textContent    = reqPerMin;
        $('off-batch-count-est').textContent = batches;
        $('off-delay-est').textContent       = delay;
        $('off-time-estimate').textContent   = timeStr;

        var warn     = $('off-limit-warning');
        var startBtn = $('off-import-start');
        var contBtn  = $('off-import-continue');

        // ── PIROS: batch méret > 100 ──
        if (batchSize > 100) {
            warn.style.display    = 'block';
            warn.style.background = '#fef2f2';
            warn.style.color      = '#dc2626';
            warn.innerHTML        = '🚫 <strong>Batch méret max 100!</strong> ' +
                                    'Az OFF API nem enged nagyobb oldalméretet.';
            if (startBtn) startBtn.disabled = true;
            if (contBtn)  contBtn.disabled  = true;
            return false;
        }

        // ── PIROS: túl gyors (>100 kérés/perc) ──
        if (reqPerMin > 100) {
            var minDelay = Math.ceil(60 / 100 * 10) / 10;
            warn.style.display    = 'block';
            warn.style.background = '#fef2f2';
            warn.style.color      = '#dc2626';
            warn.innerHTML        = '🚫 <strong>Túl gyors!</strong> ' +
                                    reqPerMin + ' kérés/perc &gt; 100 (OFF limit). ' +
                                    'Növeld a delay-t legalább <strong>' + minDelay + ' mp</strong>-re.';
            if (startBtn) startBtn.disabled = true;
            if (contBtn)  contBtn.disabled  = true;
            return false;
        }

        // ── SÁRGA: közel a limithez (>80) ──
        if (reqPerMin > 80) {
            warn.style.display    = 'block';
            warn.style.background = '#fffbeb';
            warn.style.color      = '#92400e';
            warn.innerHTML        = '⚠️ <strong>Közel a limithez!</strong> ' +
                                    reqPerMin + ' kérés/perc (limit: 100). ' +
                                    'Ajánlott delay: <strong>2+ mp</strong>.';
            if (startBtn) startBtn.disabled = false;
            if (contBtn)  contBtn.disabled  = false;
            return true;
        }

        // ── ZÖLD: OK ──
        warn.style.display = 'none';
        if (startBtn) startBtn.disabled = false;
        if (contBtn)  contBtn.disabled  = false;
        return true;
    }

    // Validáció eseményfigyelők
    $('off-batch-size').addEventListener('input', validateSettings);
    $('off-batch-delay').addEventListener('input', validateSettings);
    $('off-max').addEventListener('input', validateSettings);
    validateSettings();


    // ══════════════════════════════════════════════════════
    // IMPORT INDÍTÁS (közös függvény)
    // ══════════════════════════════════════════════════════

    function startImport(fromPage) {
        if (!validateSettings()) return;

        var fd = new FormData();
        fd.append('action',      'off_cron_start');
        fd.append('nonce',       offData.nonce);
        fd.append('mode',        'import');
        fd.append('page',        fromPage);
        fd.append('country',     $('off-country').value);
        fd.append('batch_size',  $('off-batch-size').value);
        fd.append('batch_delay', $('off-batch-delay').value);
        fd.append('max_items',   $('off-max').value);

        fetch(offData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (resp.success) {
                    location.reload();
                } else {
                    alert('Hiba: ' + resp.data);
                }
            });
    }


    // ══════════════════════════════════════════════════════
    // GOMBOK
    // ══════════════════════════════════════════════════════

    // ── Új import ──
    $('off-import-start').addEventListener('click', function() {
        startImport(1);
    });

    // ── Folytatás ──
    if ($('off-import-continue')) {
        $('off-import-continue').addEventListener('click', function() {
            var savedPage = parseInt(offData.savedState.page) || 1;
            startImport(savedPage);
        });
    }

    // ── Pozíció törlés ──
    if ($('off-import-reset')) {
        $('off-import-reset').addEventListener('click', function() {
            if (!confirm('Biztosan törlöd a mentett pozíciót?')) return;

            var fd = new FormData();
            fd.append('action', 'off_cron_reset');
            fd.append('nonce',  offData.nonce);

            fetch(offData.ajaxUrl, { method: 'POST', body: fd })
                .then(function() { location.reload(); });
        });
    }

    // ── Leállítás ──
    $('off-stop').addEventListener('click', function() {
        this.disabled    = true;
        this.textContent = '⏳ Leállítás...';

        var fd = new FormData();
        fd.append('action', 'off_cron_stop');
        fd.append('nonce',  offData.nonce);

        fetch(offData.ajaxUrl, { method: 'POST', body: fd })
            .then(function() { location.reload(); });
    });

    // ── Szinkron ──
    $('off-sync-start').addEventListener('click', function() {
        if (!validateSettings()) return;

        var fd = new FormData();
        fd.append('action',      'off_cron_start');
        fd.append('nonce',       offData.nonce);
        fd.append('mode',        'sync');
        fd.append('page',        1);
        fd.append('country',     '');
        fd.append('batch_size',  $('off-batch-size').value);
        fd.append('batch_delay', $('off-batch-delay').value);
        fd.append('max_items',   99999);

        fetch(offData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (resp.success) {
                    location.reload();
                } else {
                    alert('Hiba: ' + resp.data);
                }
            });
    });


    // ══════════════════════════════════════════════════════
    // ÉLŐ POLLING (3 másodpercenként)
    //
    // FONTOS: A polling AJAX maga futtatja a batch-et!
    // Nem kell várni a WP-Cron-ra.
    // A szerver oldalon a status handler ellenőrzi a
    // delay-t és ha lejárt, lefuttatja a következő batch-et.
    // ══════════════════════════════════════════════════════

    function pollStatus() {
        var fd = new FormData();
        fd.append('action', 'off_cron_status');
        fd.append('nonce',  offData.nonce);

        fetch(offData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (!resp.success) return;

                var s = resp.data;

                // ── Statisztikák frissítése ──
                $('off-s-processed').textContent = s.processed || 0;
                $('off-s-created').textContent   = s.created || 0;
                $('off-s-skipped').textContent   = s.skipped || 0;
                $('off-s-errors').textContent    = s.errors || 0;
                $('off-s-empty').textContent     = s.empty_batches || 0;
                $('off-s-page').textContent      = s.page || 0;

                // ── Progress bar ──
                var pct = 0;
                if (s.total > 0) {
                    pct = Math.min(
                        100,
                        Math.round(((s.page * s.batch_size) / s.total) * 100)
                    );
                }
                $('off-progress-bar').style.width = pct + '%';
                $('off-progress-bar').textContent = pct + '%';

                // ── Info sor ──
                $('off-live-info').innerHTML =
                    'Ország: <strong>' + (countryLabels[s.country] || s.country) + '</strong>' +
                    ' | Batch: <strong>' + s.batch_size + '</strong>' +
                    ' | Delay: <strong>' + s.batch_delay + 's</strong>' +
                    ' | Összesen (API): <strong>' + (s.total || '?') + '</strong>';

                // ── Log frissítés ──
                if (s.recent_log && s.recent_log.length > 0) {
                    var logEl  = $('off-log');
                    var logHtml = '';

                    s.recent_log.forEach(function(entry) {
                        var colors = {
                            info:    '#60a5fa',
                            success: '#4ade80',
                            warn:    '#fbbf24',
                            error:   '#f87171',
                            skip:    '#c084fc'
                        };
                        var color = colors[entry.type] || '#d4d4d4';
                        logHtml += '<div style="color: ' + color + '">' +
                                   '[' + entry.time + '] ' + entry.msg +
                                   '</div>';
                    });

                    logEl.innerHTML   = logHtml;
                    logEl.scrollTop = logEl.scrollHeight;
                }

                // ── Ha leállt → polling leállítás ──
                if (s.status !== 'running') {
                    clearInterval(pollTimer);

                    $('off-live-indicator').textContent     = '⏹';
                    $('off-live-indicator').style.animation = 'none';
                    $('off-stop').style.display             = 'none';

                    // Szinkron eltérések
                    if (s.status === 'done_sync' && s.diffs && s.diffs.length > 0) {
                        syncAllDiffs = s.diffs;
                        showDiffs();
                    }
                }
            });
    }

    // Ha fut → polling indítása
    if (offData.isRunning) {
        $('off-live-status').style.display = 'block';
        $('off-stop').style.display        = 'inline-block';
        pollTimer = setInterval(pollStatus, 3000);
        pollStatus();
    }


    // ══════════════════════════════════════════════════════
    // SZINKRON ELTÉRÉSEK KEZELÉSE
    // ══════════════════════════════════════════════════════

    function showDiffs() {
        $('off-diff-section').style.display = 'block';
        $('off-diff-count').textContent     = syncAllDiffs.length;

        var html = '';

        syncAllDiffs.forEach(function(item, idx) {
            html += '<div style="' +
                    'border: 1px solid #e5e7eb; ' +
                    'border-radius: 6px; ' +
                    'margin-bottom: 8px; ' +
                    'padding: 12px;' +
                    '">';

            html += '<label style="display: flex; align-items: start; gap: 10px; cursor: pointer;">';

            html += '<input type="checkbox" ' +
                    'class="off-diff-cb" ' +
                    'data-index="' + idx + '" ' +
                    'checked ' +
                    'style="margin-top: 4px;">';

            html += '<div style="flex: 1;">';
            html += '<strong>' + escHtml(item.name) + '</strong>';
            html += ' <span style="color: #999; font-size: 0.82rem;">' +
                    '(#' + item.post_id + ')' +
                    '</span>';

            html += '<div style="margin-top: 6px; font-size: 0.82rem;">';

            item.changes.forEach(function(ch) {
                html += '<div style="' +
                        'display: grid; ' +
                        'grid-template-columns: 160px 100px 20px 100px; ' +
                        'gap: 4px; ' +
                        'padding: 2px 0;' +
                        '">';
                html += '<span style="color: #666;">' + ch.label + ':</span>';
                html += '<span style="color: #dc2626; text-decoration: line-through;">' +
                        ch.old_value + ' ' + ch.egyseg +
                        '</span>';
                html += '<span>→</span>';
                html += '<span style="color: #16a34a; font-weight: 600;">' +
                        ch.new_value + ' ' + ch.egyseg +
                        '</span>';
                html += '</div>';
            });

            html += '</div>'; // changes
            html += '</div>'; // flex: 1
            html += '</label>';
            html += '</div>'; // card
        });

        $('off-diff-list').innerHTML = html;
    }

    // Összes kijelölése
    $('off-diff-select-all').addEventListener('change', function() {
        var checked = this.checked;
        document.querySelectorAll('.off-diff-cb').forEach(function(cb) {
            cb.checked = checked;
        });
    });

    // Kijelöltek frissítése
    $('off-diff-apply').addEventListener('click', function() {
        var btn      = this;
        var statusEl = $('off-diff-apply-status');
        var selected = [];

        document.querySelectorAll('.off-diff-cb:checked').forEach(function(cb) {
            selected.push(syncAllDiffs[parseInt(cb.dataset.index)]);
        });

        if (selected.length === 0) {
            statusEl.textContent = '⚠️ Jelölj ki legalább egyet!';
            statusEl.style.color = '#d97706';
            return;
        }

        btn.disabled          = true;
        statusEl.textContent = '⏳ Frissítés... (' + selected.length + ' db)';
        statusEl.style.color = '#666';

        var fd = new FormData();
        fd.append('action', 'off_apply_diffs');
        fd.append('nonce',  offData.nonce);
        fd.append('diffs',  JSON.stringify(selected));

        fetch(offData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                btn.disabled          = false;
                statusEl.textContent = resp.success ? '✅ ' + resp.data : '❌ ' + resp.data;
                statusEl.style.color = resp.success ? '#16a34a' : '#dc2626';
            })
            .catch(function() {
                btn.disabled          = false;
                statusEl.textContent = '❌ Hálózati hiba';
                statusEl.style.color = '#dc2626';
            });
    });


    // ══════════════════════════════════════════════════════
    // EGYEDI OFF KERESÉS
    // ══════════════════════════════════════════════════════

    $('off-search-btn').addEventListener('click', function() {
        var query = $('off-search-query').value.trim();
        if (!query) return;

        var btn     = this;
        var country = $('off-search-country').value;

        btn.disabled    = true;
        btn.textContent = '⏳ Keresés...';

        $('off-search-results').innerHTML =
            '<div style="color: #666; font-size: 0.88rem;">' +
            'Keresés: "' + escHtml(query) + '"...' +
            '</div>';

        var fd = new FormData();
        fd.append('action',  'off_search_single');
        fd.append('nonce',   offData.nonce);
        fd.append('query',   query);
        fd.append('country', country);

        fetch(offData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                btn.disabled    = false;
                btn.textContent = '🔍 Keresés';

                if (!resp.success) {
                    $('off-search-results').innerHTML =
                        '<div style="color: #dc2626;">❌ ' + resp.data + '</div>';
                    return;
                }

                var foods = resp.data.foods;

                if (!foods || foods.length === 0) {
                    $('off-search-results').innerHTML =
                        '<div style="color: #d97706; padding: 8px 0;">' +
                        'Nincs találat. Próbálj más keresőszót.' +
                        '</div>';
                    return;
                }

                var html = '<div style="font-size: 0.85rem; color: #666; margin-bottom: 8px;">' +
                           foods.length + ' találat (max 20):' +
                           '</div>';

                foods.forEach(function(food) {
                    html += '<div style="' +
                            'border: 1px solid #e5e7eb; ' +
                            'border-radius: 6px; ' +
                            'padding: 10px 14px; ' +
                            'margin-bottom: 6px; ' +
                            'display: flex; ' +
                            'justify-content: space-between; ' +
                            'align-items: center;' +
                            '">';

                    // Bal oldal: termék info
                    html += '<div style="flex: 1; min-width: 0;">';

                    html += '<strong style="color: #1e293b;">' +
                            escHtml(food.name) +
                            '</strong>';

                    if (food.name_hu && food.name_hu !== food.name) {
                        html += ' <span style="color: #16a34a; font-size: 0.82rem;">' +
                                '🇭🇺 ' + escHtml(food.name_hu) +
                                '</span>';
                    }

                    html += ' <span style="font-size: 0.75rem; color: #94a3b8;">' +
                            escHtml(food.barcode) +
                            '</span>';

                    if (food.brands) {
                        html += '<div style="font-size: 0.78rem; color: #64748b; margin-top: 1px;">' +
                                '🏷️ ' + escHtml(food.brands) +
                                '</div>';
                    }

                    html += '<div style="font-size: 0.78rem; color: #666; margin-top: 2px;">' +
                            '🔥 ' + (food.kcal || '?') + ' kcal' +
                            ' | F: ' + (food.protein || '?') + 'g' +
                            ' | Sz: ' + (food.carb || '?') + 'g' +
                            ' | Zs: ' + (food.fat || '?') + 'g' +
                            '</div>';

                    if (food.image_url) {
                        html += '<div style="margin-top: 4px;">' +
                                '<img src="' + food.image_url + '" ' +
                                'style="max-height: 40px; border-radius: 4px;" ' +
                                'loading="lazy">' +
                                '</div>';
                    }

                    if (food.already_imported) {
                        html += '<div style="font-size: 0.78rem; color: #16a34a; margin-top: 2px;">' +
                                '✅ Már importálva (#' + food.existing_id +
                                ' – "' + escHtml(food.existing_name) + '")' +
                                '</div>';
                    }

                    html += '</div>'; // bal oldal vége

                    // Jobb oldal: import gomb
                    if (!food.already_imported) {
                        html += '<button class="button off-import-single" ' +
                                'data-barcode="' + food.barcode + '" ' +
                                'style="font-size: 0.82rem; margin-left: 8px; white-space: nowrap;">' +
                                '📥 Import' +
                                '</button>';
                    }

                    html += '</div>'; // card vége
                });

                $('off-search-results').innerHTML = html;

                // Import gombok kötése
                document.querySelectorAll('.off-import-single').forEach(function(importBtn) {
                    importBtn.addEventListener('click', function() {
                        var barcode = this.dataset.barcode;
                        var thisBtn = this;

                        thisBtn.disabled    = true;
                        thisBtn.textContent = '⏳...';

                        var fd2 = new FormData();
                        fd2.append('action',  'off_import_single_product');
                        fd2.append('nonce',   offData.nonce);
                        fd2.append('barcode', barcode);

                        fetch(offData.ajaxUrl, { method: 'POST', body: fd2 })
                            .then(function(r) { return r.json(); })
                            .then(function(resp) {
                                thisBtn.textContent = resp.success ? '✅ Kész' : '❌ Hiba';
                                thisBtn.style.color = resp.success ? '#16a34a' : '#dc2626';
                                if (!resp.success) thisBtn.title = resp.data;
                            })
                            .catch(function() {
                                thisBtn.textContent = '❌';
                                thisBtn.style.color = '#dc2626';
                            });
                    });
                });
            })
            .catch(function(err) {
                btn.disabled    = false;
                btn.textContent = '🔍 Keresés';
                $('off-search-results').innerHTML =
                    '<div style="color: #dc2626;">❌ Hálózati hiba: ' + err.message + '</div>';
            });
    });

    // Enter keresés
    $('off-search-query').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            $('off-search-btn').click();
        }
    });


    // ══════════════════════════════════════════════════════
    // SEGÉD: HTML ESCAPE
    // ══════════════════════════════════════════════════════

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

})();
JSEOF;

    wp_register_script( 'off-import-js', false, [], '7.1', true );
    wp_enqueue_script( 'off-import-js' );
    wp_add_inline_script( 'off-import-js', $js );

    wp_localize_script( 'off-import-js', 'offData', [
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'off_import_nonce' ),
        'isRunning'  => ( ( $state['status'] ?? '' ) === 'running' ),
        'savedState' => $state,
    ] );
}
add_action( 'admin_enqueue_scripts', 'off_import_admin_scripts' );


// ══════════════════════════════════════════════════════════════
// 4. AJAX: IMPORT INDÍTÁS
// ══════════════════════════════════════════════════════════════

function off_cron_start_handler() {
    check_ajax_referer( 'off_import_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Nincs jogosultságod.' );
    }

    $mode        = sanitize_key( $_POST['mode'] ?? 'import' );
    $page        = max( 1, intval( $_POST['page'] ?? 1 ) );
    $country     = sanitize_text_field( $_POST['country'] ?? 'hungary' );
    $batch_size  = min( OFF_API_MAX_BATCH, max( 10, intval( $_POST['batch_size'] ?? 50 ) ) );
    $batch_delay = max( OFF_API_MIN_DELAY, floatval( $_POST['batch_delay'] ?? 3 ) );
    $max_items   = max( 0, intval( $_POST['max_items'] ?? 10000 ) );

    // API limit validáció
    $req_per_min = round( 60 / $batch_delay );
    if ( $req_per_min > OFF_API_RATE_LIMIT ) {
        wp_send_json_error(
            'Túl gyors! ' . $req_per_min . ' kérés/perc > ' .
            OFF_API_RATE_LIMIT . ' (OFF limit). Növeld a delay-t.'
        );
    }

    // Ország címkék
    $country_labels = [
        'hungary'        => '🇭🇺 Magyarország',
        'germany'        => '🇩🇪 Németország',
        'austria'        => '🇦🇹 Ausztria',
        'united-kingdom' => '🇬🇧 UK',
        'united-states'  => '🇺🇸 USA',
        'france'         => '🇫🇷 Franciaország',
        'italy'          => '🇮🇹 Olaszország',
        'spain'          => '🇪🇸 Spanyolország',
        'poland'         => '🇵🇱 Lengyelország',
        'romania'        => '🇷🇴 Románia',
        'slovakia'       => '🇸🇰 Szlovákia',
        'croatia'        => '🇭🇷 Horvátország',
        'serbia'         => '🇷🇸 Szerbia',
        ''               => '🌍 Összes',
    ];

    // Állapot mentése
    $state = [
        'status'            => 'running',
        'mode'              => $mode,
        'page'              => $page,
        'country'           => $country,
        'country_label'     => $country_labels[ $country ] ?? $country,
        'batch_size'        => $batch_size,
        'batch_delay'       => $batch_delay,
        'max_items'         => $max_items,
        'total'             => 0,
        'stats'             => [
            'processed'     => 0,
            'created'       => 0,
            'skipped'       => 0,
            'errors'        => 0,
            'empty_batches' => 0,
            'diff'          => 0,
            'unchanged'     => 0,
        ],
        'consecutive_empty' => 0,
        'diffs'             => [],
        'date'              => current_time( 'Y-m-d H:i:s' ),
    ];

    update_option( OFF_IMPORT_STATE_KEY, $state );
    update_option( OFF_IMPORT_LOG_KEY, [] );
    update_option( OFF_IMPORT_LASTRUN_KEY, 0 );

    // WP-Cron ütemezés (háttér biztosíték)
    wp_clear_scheduled_hook( OFF_CRON_HOOK );
    wp_schedule_event( time(), 'off_every_30s', OFF_CRON_HOOK );
    spawn_cron();

    // Log
    off_import_log( 'info', '🚀 ' . ( $mode === 'sync' ? 'Szinkron' : 'Import' ) . ' indítása...' );
    off_import_log( 'info', '🌍 Ország: ' . ( $country_labels[ $country ] ?? $country ) );
    off_import_log( 'info', '⚙️ Batch: ' . $batch_size . ' | Delay: ' . $batch_delay . 's | Max: ' . $max_items );

    if ( $page > 1 ) {
        off_import_log( 'info', '📌 Folytatás a(z) ' . $page . '. oldaltól' );
    }

    wp_send_json_success( 'Elindítva.' );
}
add_action( 'wp_ajax_off_cron_start', 'off_cron_start_handler' );


// ══════════════════════════════════════════════════════════════
// 5. AJAX: LEÁLLÍTÁS
// ══════════════════════════════════════════════════════════════

function off_cron_stop_handler() {
    check_ajax_referer( 'off_import_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Nincs jogosultság.' );
    }

    $state = get_option( OFF_IMPORT_STATE_KEY, [] );
    $state['status'] = 'stopped';
    update_option( OFF_IMPORT_STATE_KEY, $state );

    wp_clear_scheduled_hook( OFF_CRON_HOOK );

    off_import_log( 'info',
        '⏹ Leállítva (felhasználó kérte). ' .
        'Pozíció mentve: oldal ' . ( $state['page'] ?? '?' )
    );

    wp_send_json_success( 'Leállítva.' );
}
add_action( 'wp_ajax_off_cron_stop', 'off_cron_stop_handler' );


// ══════════════════════════════════════════════════════════════
// 6. AJAX: POZÍCIÓ TÖRLÉS (RESET)
// ══════════════════════════════════════════════════════════════

function off_cron_reset_handler() {
    check_ajax_referer( 'off_import_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Nincs jogosultság.' );
    }

    delete_option( OFF_IMPORT_STATE_KEY );
    delete_option( OFF_IMPORT_LOG_KEY );
    delete_option( OFF_IMPORT_LASTRUN_KEY );
    wp_clear_scheduled_hook( OFF_CRON_HOOK );

    wp_send_json_success( 'Törölve.' );
}
add_action( 'wp_ajax_off_cron_reset', 'off_cron_reset_handler' );


// ══════════════════════════════════════════════════════════════
// 7. AJAX: STÁTUSZ POLLING + BATCH FUTTATÁS (HIBRID MOTOR)
//
// Ez a fő újítás: a polling AJAX maga futtatja a batch-et
// ha lejárt a delay. Így nem függ a WP-Cron-tól.
// A WP-Cron csak háttér biztosíték ha a böngésző bezáródik.
// ══════════════════════════════════════════════════════════════

function off_cron_status_handler() {
    check_ajax_referer( 'off_import_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Nincs jogosultság.' );
    }

    $state = get_option( OFF_IMPORT_STATE_KEY, [] );
    $log   = get_option( OFF_IMPORT_LOG_KEY, [] );

    // ──────────────────────────────────────────────────────
    // HIBRID MOTOR: Ha "running" → batch futtatás
    //
    // Ellenőrizzük:
    //   1. A státusz "running"?
    //   2. Lejárt a delay az utolsó futtatás óta?
    //   3. Ha igen → batch futtatás MOST
    // ──────────────────────────────────────────────────────

    if ( ( $state['status'] ?? '' ) === 'running' ) {

        $last_run = floatval( get_option( OFF_IMPORT_LASTRUN_KEY, 0 ) );
        $delay    = floatval( $state['batch_delay'] ?? 3 );
        $now      = microtime( true );

        // Lejárt a delay?
        if ( ( $now - $last_run ) >= $delay ) {

            // Jelöljük hogy most futtatunk (dupla futás védelem)
            update_option( OFF_IMPORT_LASTRUN_KEY, $now );

            // Batch futtatás
            do_action( OFF_CRON_HOOK );

            // Frissített adatok lekérése
            $state = get_option( OFF_IMPORT_STATE_KEY, [] );
            $log   = get_option( OFF_IMPORT_LOG_KEY, [] );
        }

        // WP-Cron is próbáljuk triggerelni (háttér biztosíték)
        spawn_cron();
    }

    // ── Utolsó 50 log bejegyzés ──
    $recent_log = array_slice( $log, -50 );

    // ── Válasz ──
    wp_send_json_success( [
        'status'        => $state['status'] ?? 'idle',
        'page'          => $state['page'] ?? 0,
        'country'       => $state['country'] ?? '',
        'batch_size'    => $state['batch_size'] ?? 50,
        'batch_delay'   => $state['batch_delay'] ?? 3,
        'total'         => $state['total'] ?? 0,
        'processed'     => $state['stats']['processed'] ?? 0,
        'created'       => $state['stats']['created'] ?? 0,
        'skipped'       => $state['stats']['skipped'] ?? 0,
        'errors'        => $state['stats']['errors'] ?? 0,
        'empty_batches' => $state['stats']['empty_batches'] ?? 0,
        'diff'          => $state['stats']['diff'] ?? 0,
        'unchanged'     => $state['stats']['unchanged'] ?? 0,
        'diffs'         => $state['diffs'] ?? [],
        'recent_log'    => $recent_log,
    ] );
}
add_action( 'wp_ajax_off_cron_status', 'off_cron_status_handler' );


// ══════════════════════════════════════════════════════════════
// 8. WP-CRON: BATCH FUTTATÁS (FŐ ROUTER)
//
// Ezt hívja:
//   - A polling AJAX (hibrid motor, 7-es szekció)
//   - A WP-Cron (háttér, ha a böngésző bezáródik)
// ══════════════════════════════════════════════════════════════

function off_cron_run_batch() {
    $state = get_option( OFF_IMPORT_STATE_KEY, [] );

    // Ha nem fut → semmit nem csinálunk
    if ( ( $state['status'] ?? '' ) !== 'running' ) {
        wp_clear_scheduled_hook( OFF_CRON_HOOK );
        return;
    }

    $mode      = $state['mode'] ?? 'import';
    $max_items = intval( $state['max_items'] ?? 10000 );

    // Maximum elérve?
    if ( $mode === 'import' && $max_items > 0 && ( $state['stats']['created'] ?? 0 ) >= $max_items ) {
        $state['status'] = 'done';
        update_option( OFF_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( OFF_CRON_HOOK );
        off_import_log( 'success', '🎉 Maximum elérve: ' . $state['stats']['created'] . ' új alapanyag.' );
        return;
    }

    // Üres batch limit?
    if ( ( $state['consecutive_empty'] ?? 0 ) >= OFF_EMPTY_BATCH_LIMIT ) {
        $state['status'] = 'done';
        update_option( OFF_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( OFF_CRON_HOOK );
        off_import_log( 'warn', '⏹ ' . OFF_EMPTY_BATCH_LIMIT . ' egymás utáni üres batch → leállás.' );
        return;
    }

    // Mód szerinti futtatás
    if ( $mode === 'import' ) {
        off_cron_import_batch( $state );
    } else {
        off_cron_sync_batch( $state );
    }
}
add_action( OFF_CRON_HOOK, 'off_cron_run_batch' );


// ══════════════════════════════════════════════════════════════
// 9. IMPORT BATCH (szerver oldali feldolgozás)
// ══════════════════════════════════════════════════════════════

function off_cron_import_batch( &$state ) {

    // Függőségek ellenőrzése
    if ( ! function_exists( 'update_field' ) ) {
        $state['status'] = 'error';
        update_option( OFF_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( OFF_CRON_HOOK );
        off_import_log( 'error', '❌ ACF plugin nincs betöltve.' );
        return;
    }

    if ( ! function_exists( 'tapanyag_get_alapanyag_nutrient_fields' ) ) {
        $state['status'] = 'error';
        update_option( OFF_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( OFF_CRON_HOOK );
        off_import_log( 'error', '❌ #18-as snippet (field mapping) nincs aktiválva.' );
        return;
    }

    $page       = intval( $state['page'] );
    $batch_size = intval( $state['batch_size'] );
    $country    = $state['country'];

    off_import_log( 'info', '📦 Batch: oldal ' . $page . ' (' . $batch_size . ' termék)' );

    // OFF API hívás retry-vel
    $body = off_api_request_with_retry( $page, $batch_size, $country );

    if ( $body === false ) {
        $state['stats']['errors'] = ( $state['stats']['errors'] ?? 0 ) + 1;
        off_import_log( 'error', '❌ API hiba (3× retry után) – továbblépés.' );

        $state['page'] = $page + 1;
        $state['date'] = current_time( 'Y-m-d H:i:s' );
        update_option( OFF_IMPORT_STATE_KEY, $state );
        return;
    }

    $products = $body['products'] ?? [];
    $total    = intval( $body['count'] ?? 0 );

    $state['total'] = $total;

    // Üres válasz = elértük a végét
    if ( empty( $products ) ) {
        $state['status'] = 'done';
        update_option( OFF_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( OFF_CRON_HOOK );
        off_import_log( 'success', '🏁 Minden termék feldolgozva! (Oldal ' . $page . ' üres)' );
        return;
    }

    // Termékek feldolgozása
    $created    = 0;
    $skipped    = 0;
    $fields_map = tapanyag_get_alapanyag_nutrient_fields();

    foreach ( $products as $product ) {

        // Vonalkód
        $barcode = isset( $product['code'] ) ? trim( $product['code'] ) : '';
        if ( empty( $barcode ) ) {
            $skipped++;
            continue;
        }

        // Név (magyar prioritás)
        $name = '';
        if ( ! empty( $product['product_name_hu'] ) ) {
            $name = trim( $product['product_name_hu'] );
        } elseif ( ! empty( $product['product_name'] ) ) {
            $name = trim( $product['product_name'] );
        }
        if ( empty( $name ) ) {
            $skipped++;
            continue;
        }

        // Már létezik?
        $existing = get_posts( [
            'post_type'      => 'alapanyag',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'meta_query'     => [
                [ 'key' => 'off_barcode', 'value' => $barcode ],
            ],
        ] );

        if ( ! empty( $existing ) ) {
            $skipped++;
            continue;
        }

        // Új post létrehozás
        $post_id = wp_insert_post( [
            'post_type'   => 'alapanyag',
            'post_title'  => $name,
            'post_status' => 'publish',
        ], true );

        if ( is_wp_error( $post_id ) ) {
            $skipped++;
            off_import_log( 'warn', '⚠️ Post hiba: ' . $name . ' – ' . $post_id->get_error_message() );
            continue;
        }

        $created++;

        // Tápérték mezők kitöltése
        $nutriments = $product['nutriments'] ?? [];

        foreach ( $fields_map as $acf_key => $field_info ) {
            if ( $field_info['csoport'] === 'meta' ) continue;

            $off_key = $field_info['off_key'];

            if ( isset( $nutriments[ $off_key ] ) && $nutriments[ $off_key ] !== '' ) {
                update_field(
                    $acf_key,
                    round( floatval( $nutriments[ $off_key ] ), 4 ),
                    $post_id
                );
            }
        }

        // OFF meta mezők
        update_field( 'off_barcode',   $barcode, $post_id );
        update_field( 'off_url',       $product['url'] ?? '', $post_id );
        update_field( 'off_last_sync', current_time( 'Y-m-d H:i:s' ), $post_id );

        // Kiemelt kép
        if ( ! empty( $product['image_front_url'] ) ) {
            $img_id = off_import_sideload_image(
                $product['image_front_url'],
                $post_id,
                $name
            );
            if ( $img_id && ! is_wp_error( $img_id ) ) {
                set_post_thumbnail( $post_id, $img_id );
            }
        }
    }

    // Statisztikák frissítése
    $state['stats']['processed'] = ( $state['stats']['processed'] ?? 0 ) + $created + $skipped;
    $state['stats']['created']   = ( $state['stats']['created'] ?? 0 ) + $created;
    $state['stats']['skipped']   = ( $state['stats']['skipped'] ?? 0 ) + $skipped;

    // Üres batch számláló
    if ( $created === 0 ) {
        $state['consecutive_empty']      = ( $state['consecutive_empty'] ?? 0 ) + 1;
        $state['stats']['empty_batches'] = ( $state['stats']['empty_batches'] ?? 0 ) + 1;
    } else {
        $state['consecutive_empty'] = 0;
    }

    off_import_log( 'success',
        '✅ Oldal ' . $page . ': +' . $created . ' új, ' . $skipped . ' kihagyva'
    );

    // Elértük a végét?
    if ( count( $products ) < $batch_size ) {
        $state['status'] = 'done';
        update_option( OFF_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( OFF_CRON_HOOK );
        off_import_log( 'success',
            '🏁 Import kész! Összesen: ' . ( $state['stats']['created'] ?? 0 ) . ' új alapanyag.'
        );
        return;
    }

    // Következő oldal
    $state['page'] = $page + 1;
    $state['date'] = current_time( 'Y-m-d H:i:s' );
    update_option( OFF_IMPORT_STATE_KEY, $state );
}


// ══════════════════════════════════════════════════════════════
// 10. SZINKRON BATCH (szerver oldali)
// ══════════════════════════════════════════════════════════════

function off_cron_sync_batch( &$state ) {

    // Függőségek
    if ( ! function_exists( 'update_field' ) || ! function_exists( 'get_field' ) ) {
        $state['status'] = 'error';
        update_option( OFF_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( OFF_CRON_HOOK );
        off_import_log( 'error', '❌ ACF plugin nincs betöltve.' );
        return;
    }

    if ( ! function_exists( 'tapanyag_get_alapanyag_nutrient_fields' ) ) {
        $state['status'] = 'error';
        update_option( OFF_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( OFF_CRON_HOOK );
        off_import_log( 'error', '❌ #18-as snippet nincs aktiválva.' );
        return;
    }

    $page       = intval( $state['page'] );
    $batch_size = min( 20, intval( $state['batch_size'] ) );
    $fields_map = tapanyag_get_alapanyag_nutrient_fields();

    // Meglévő importált alapanyagok lekérése
    $alapanyagok = get_posts( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => $batch_size,
        'paged'          => $page,
        'post_status'    => 'publish',
        'meta_query'     => [
            [ 'key' => 'off_barcode', 'compare' => 'EXISTS' ],
        ],
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ] );

    // Ha nincs több → kész
    if ( empty( $alapanyagok ) ) {
        $state['status'] = 'done_sync';
        update_option( OFF_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( OFF_CRON_HOOK );
        off_import_log( 'success',
            '🎉 Szinkron kész! ' . ( $state['stats']['diff'] ?? 0 ) . ' eltérés.'
        );
        return;
    }

    off_import_log( 'info',
        '📦 Szinkron batch: oldal ' . $page . ' (' . count( $alapanyagok ) . ' termék)'
    );

    $diff_count = 0;
    $unchanged  = 0;
    $errors     = 0;

    foreach ( $alapanyagok as $post ) {
        $barcode = get_field( 'off_barcode', $post->ID );
        if ( empty( $barcode ) ) continue;

        // Egyedi OFF API hívás
        $api_url  = 'https://world.openfoodfacts.org/api/v2/product/' .
                    $barcode . '.json?fields=nutriments';

        $response = wp_remote_get( $api_url, [
            'timeout' => OFF_API_TIMEOUT,
            'headers' => [
                'User-Agent' => 'TapanyagLexikon/1.0 (WordPress plugin; azsupp3387@gmail.com)',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            $errors++;
            continue;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! $body || ( $body['status'] ?? 0 ) !== 1 ) {
            $errors++;
            continue;
        }

        // Eltérések keresése
        $nutriments = $body['product']['nutriments'] ?? [];
        $changes    = [];

        foreach ( $fields_map as $acf_key => $field_info ) {
            if ( $field_info['csoport'] === 'meta' ) continue;

            $off_key   = $field_info['off_key'];
            $old_value = floatval( get_field( $acf_key, $post->ID ) );
            $new_value = isset( $nutriments[ $off_key ] )
                         ? round( floatval( $nutriments[ $off_key ] ), 4 )
                         : null;

            if ( $new_value === null ) continue;

            if ( abs( $old_value - $new_value ) > 0.01 ) {
                $changes[] = [
                    'acf_key'   => $acf_key,
                    'label'     => $field_info['label'],
                    'egyseg'    => $field_info['egyseg'],
                    'old_value' => $old_value,
                    'new_value' => $new_value,
                ];
            }
        }

        if ( ! empty( $changes ) ) {
            $diff_count++;
            $state['diffs'][] = [
                'post_id' => $post->ID,
                'name'    => $post->post_title,
                'barcode' => $barcode,
                'changes' => $changes,
            ];
        } else {
            $unchanged++;
        }

        // Rate limit: 0.6mp szünet termékenként
        usleep( 600000 );
    }

    // Statisztikák
    $state['stats']['diff']      = ( $state['stats']['diff'] ?? 0 ) + $diff_count;
    $state['stats']['unchanged'] = ( $state['stats']['unchanged'] ?? 0 ) + $unchanged;
    $state['stats']['errors']    = ( $state['stats']['errors'] ?? 0 ) + $errors;
    $state['stats']['processed'] = ( $state['stats']['processed'] ?? 0 ) + $diff_count + $unchanged + $errors;

    off_import_log( 'success',
        '✅ Szinkron oldal ' . $page . ': ' .
        $diff_count . ' eltérés, ' .
        $unchanged . ' ok' .
        ( $errors > 0 ? ', ' . $errors . ' hiba' : '' )
    );

    // Következő oldal
    $state['page'] = $page + 1;
    $state['date'] = current_time( 'Y-m-d H:i:s' );
    update_option( OFF_IMPORT_STATE_KEY, $state );
}


// ══════════════════════════════════════════════════════════════
// 11. OFF API KÉRÉS RETRY-VEL
// ══════════════════════════════════════════════════════════════

function off_api_request_with_retry( $page, $page_size, $country ) {

    // URL összeállítás
    $params = [
        'action'    => 'process',
        'page'      => $page,
        'page_size' => $page_size,
        'json'      => 'true',
        'fields'    => 'code,product_name,product_name_hu,nutriments,image_front_url,url',
    ];

    if ( ! empty( $country ) ) {
        $params['tagtype_0']      = 'countries';
        $params['tag_contains_0'] = 'contains';
        $params['tag_0']          = $country;
    }

    $api_url = add_query_arg( $params, 'https://world.openfoodfacts.org/cgi/search.pl' );

    // Retry loop
    for ( $attempt = 1; $attempt <= OFF_API_RETRY_COUNT; $attempt++ ) {

        $response = wp_remote_get( $api_url, [
            'timeout' => OFF_API_TIMEOUT,
            'headers' => [
                'User-Agent' => 'TapanyagLexikon/1.0 (WordPress plugin; azsupp3387@gmail.com)',
            ],
        ] );

        // Sikeres?
        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( $body && isset( $body['products'] ) ) {
                return $body;
            }
        }

        // Hiba → log + várakozás (nem utolsó próba)
        if ( $attempt < OFF_API_RETRY_COUNT ) {
            $error_msg = is_wp_error( $response )
                         ? $response->get_error_message()
                         : 'HTTP ' . wp_remote_retrieve_response_code( $response );

            off_import_log( 'warn',
                '⚠️ API hiba: ' . $error_msg .
                ' (próba ' . $attempt . '/' . OFF_API_RETRY_COUNT . ')' .
                ' – újra ' . OFF_API_RETRY_DELAY . 's múlva...'
            );

            sleep( OFF_API_RETRY_DELAY );
        }
    }

    // Minden retry sikertelen
    return false;
}


// ══════════════════════════════════════════════════════════════
// 12. LOG SEGÉDFÜGGVÉNY
// ══════════════════════════════════════════════════════════════

function off_import_log( $type, $msg ) {
    $log = get_option( OFF_IMPORT_LOG_KEY, [] );

    $log[] = [
        'type' => $type,
        'msg'  => $msg,
        'time' => current_time( 'H:i:s' ),
    ];

    // Maximum bejegyzések
    if ( count( $log ) > OFF_LOG_MAX_ENTRIES ) {
        $log = array_slice( $log, -OFF_LOG_MAX_ENTRIES );
    }

    update_option( OFF_IMPORT_LOG_KEY, $log );
}


// ═════════════════════════════════════════════════════════���════
// 13. KÉP SIDELOAD HELPER
// ══════════════════════════════════════════════════════════════

function off_import_sideload_image( $image_url, $post_id, $product_name ) {

    if ( ! function_exists( 'media_sideload_image' ) ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $tmp = download_url( $image_url, 15 );

    if ( is_wp_error( $tmp ) ) {
        return false;
    }

    $file_array = [
        'name'     => sanitize_file_name( $product_name ) . '.jpg',
        'tmp_name' => $tmp,
    ];

    $attachment_id = media_handle_sideload( $file_array, $post_id, $product_name );

    if ( is_wp_error( $attachment_id ) ) {
        @unlink( $tmp );
        return false;
    }

    return $attachment_id;
}


// ══════════════════════════════════════════════════════════════
// 14. AJAX: SZINKRON ELTÉRÉSEK ALKALMAZÁSA
// ══════════════════════════════════════════════════════════════

function off_apply_diffs_handler() {
    check_ajax_referer( 'off_import_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Nincs jogosultságod.' );
    }

    if ( ! function_exists( 'update_field' ) ) {
        wp_send_json_error( 'ACF plugin nincs betöltve.' );
    }

    $diffs_json = isset( $_POST['diffs'] ) ? $_POST['diffs'] : '[]';
    $diffs      = json_decode( stripslashes( $diffs_json ), true );

    if ( empty( $diffs ) || ! is_array( $diffs ) ) {
        wp_send_json_error( 'Nincs kijelölt eltérés.' );
    }

    $updated_posts  = 0;
    $updated_fields = 0;

    foreach ( $diffs as $item ) {
        $post_id = intval( $item['post_id'] ?? 0 );

        if ( ! $post_id || get_post_type( $post_id ) !== 'alapanyag' ) {
            continue;
        }

        $changes = isset( $item['changes'] ) ? $item['changes'] : [];

        foreach ( $changes as $change ) {
            $acf_key   = sanitize_key( $change['acf_key'] ?? '' );
            $new_value = round( floatval( $change['new_value'] ?? 0 ), 4 );

            if ( ! empty( $acf_key ) ) {
                update_field( $acf_key, $new_value, $post_id );
                $updated_fields++;
            }
        }

        update_field( 'off_last_sync', current_time( 'Y-m-d H:i:s' ), $post_id );
        $updated_posts++;
    }

    wp_send_json_success(
        $updated_posts . ' alapanyag frissítve (' . $updated_fields . ' mező).'
    );
}
add_action( 'wp_ajax_off_apply_diffs', 'off_apply_diffs_handler' );


// ══════════════════════════════════════════════════════════════
// 15. AJAX: EGYEDI OFF KERESÉS
// ══════════════════════════════════════════════════════════════

function off_search_single_handler() {
    check_ajax_referer( 'off_import_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Nincs jogosultságod.' );
    }

    $query   = sanitize_text_field( $_POST['query'] ?? '' );
    $country = sanitize_text_field( $_POST['country'] ?? '' );

    if ( empty( $query ) ) {
        wp_send_json_error( 'Üres keresés.' );
    }

    // OFF Search API
    $params = [
        'search_terms'  => $query,
        'search_simple' => 1,
        'action'        => 'process',
        'json'          => 'true',
        'page_size'     => 20,
        'page'          => 1,
        'fields'        => 'code,product_name,product_name_hu,nutriments,image_front_small_url,brands,countries_tags',
    ];

    if ( ! empty( $country ) ) {
        $params['tagtype_0']      = 'countries';
        $params['tag_contains_0'] = 'contains';
        $params['tag_0']          = $country;
    }

    $api_url = add_query_arg(
        $params,
        'https://world.openfoodfacts.org/cgi/search.pl'
    );

    $response = wp_remote_get( $api_url, [
        'timeout' => OFF_API_TIMEOUT,
        'headers' => [
            'User-Agent' => 'TapanyagLexikon/1.0 (WordPress plugin; azsupp3387@gmail.com)',
        ],
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'OFF API hiba: ' . $response->get_error_message() );
    }

    $body      = json_decode( wp_remote_retrieve_body( $response ), true );
    $foods_out = [];

    foreach ( ( $body['products'] ?? [] ) as $product ) {
        $barcode = isset( $product['code'] ) ? trim( $product['code'] ) : '';
        if ( empty( $barcode ) ) continue;

        $name    = isset( $product['product_name'] ) ? trim( $product['product_name'] ) : '';
        $name_hu = isset( $product['product_name_hu'] ) ? trim( $product['product_name_hu'] ) : '';
        if ( empty( $name ) && empty( $name_hu ) ) continue;

        $nutriments = isset( $product['nutriments'] ) ? $product['nutriments'] : [];

        // Már importálva?
        $existing = get_posts( [
            'post_type'      => 'alapanyag',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'meta_query'     => [
                [ 'key' => 'off_barcode', 'value' => $barcode ],
            ],
        ] );

        $foods_out[] = [
            'barcode'          => $barcode,
            'name'             => $name,
            'name_hu'          => $name_hu,
            'brands'           => isset( $product['brands'] ) ? $product['brands'] : '',
            'image_url'        => isset( $product['image_front_small_url'] ) ? $product['image_front_small_url'] : '',
            'kcal'             => isset( $nutriments['energy-kcal_100g'] )
                                  ? round( $nutriments['energy-kcal_100g'], 1 ) : null,
            'protein'          => isset( $nutriments['proteins_100g'] )
                                  ? round( $nutriments['proteins_100g'], 1 ) : null,
            'carb'             => isset( $nutriments['carbohydrates_100g'] )
                                  ? round( $nutriments['carbohydrates_100g'], 1 ) : null,
            'fat'              => isset( $nutriments['fat_100g'] )
                                  ? round( $nutriments['fat_100g'], 1 ) : null,
            'already_imported' => ! empty( $existing ),
            'existing_id'      => ! empty( $existing ) ? $existing[0]->ID : 0,
            'existing_name'    => ! empty( $existing ) ? $existing[0]->post_title : '',
        ];
    }

    wp_send_json_success( [ 'foods' => $foods_out ] );
}
add_action( 'wp_ajax_off_search_single', 'off_search_single_handler' );


// ══════════════════════════════════════════════════════════════
// 16. AJAX: EGYEDI OFF IMPORT (vonalkód alapján)
// ══════════════════════════════════════════════════════════════

function off_import_single_product_handler() {
    check_ajax_referer( 'off_import_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Nincs jogosultságod.' );
    }

    if ( ! function_exists( 'update_field' ) ) {
        wp_send_json_error( 'ACF plugin nincs betöltve.' );
    }

    if ( ! function_exists( 'tapanyag_get_alapanyag_nutrient_fields' ) ) {
        wp_send_json_error( '#18-as snippet nincs aktiválva.' );
    }

    $barcode = sanitize_text_field( $_POST['barcode'] ?? '' );

    if ( empty( $barcode ) ) {
        wp_send_json_error( 'Nincs vonalkód.' );
    }

    // Már létezik?
    $existing = get_posts( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => 1,
        'post_status'    => 'any',
        'meta_query'     => [
            [ 'key' => 'off_barcode', 'value' => $barcode ],
        ],
    ] );

    if ( ! empty( $existing ) ) {
        wp_send_json_error(
            'Már importálva: #' . $existing[0]->ID . ' – "' . $existing[0]->post_title . '"'
        );
    }

    // OFF API
    $api_url = 'https://world.openfoodfacts.org/api/v2/product/' . $barcode . '.json';

    $response = wp_remote_get( $api_url, [
        'timeout' => OFF_API_TIMEOUT,
        'headers' => [
            'User-Agent' => 'TapanyagLexikon/1.0 (WordPress plugin; azsupp3387@gmail.com)',
        ],
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'OFF API hiba: ' . $response->get_error_message() );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! $body || ( isset( $body['status'] ) && $body['status'] !== 1 ) ) {
        wp_send_json_error( 'Termék nem található az OFF-ben.' );
    }

    $product = isset( $body['product'] ) ? $body['product'] : [];

    // Név
    $name = '';
    if ( ! empty( $product['product_name_hu'] ) ) {
        $name = trim( $product['product_name_hu'] );
    } elseif ( ! empty( $product['product_name'] ) ) {
        $name = trim( $product['product_name'] );
    }

    if ( empty( $name ) ) {
        wp_send_json_error( 'A terméknek nincs neve az OFF-ben.' );
    }

    // Post létrehozás
    $post_id = wp_insert_post( [
        'post_type'   => 'alapanyag',
        'post_title'  => $name,
        'post_status' => 'publish',
    ], true );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( 'Post hiba: ' . $post_id->get_error_message() );
    }

    // Tápértékek
    $nutriments = isset( $product['nutriments'] ) ? $product['nutriments'] : [];
    $fields_map = tapanyag_get_alapanyag_nutrient_fields();
    $filled     = 0;

    foreach ( $fields_map as $acf_key => $field_info ) {
        if ( $field_info['csoport'] === 'meta' ) continue;

        $off_key = $field_info['off_key'];

        if ( isset( $nutriments[ $off_key ] ) && $nutriments[ $off_key ] !== '' ) {
            update_field(
                $acf_key,
                round( floatval( $nutriments[ $off_key ] ), 4 ),
                $post_id
            );
            $filled++;
        }
    }

    // OFF meta
    update_field( 'off_barcode',   $barcode, $post_id );
    update_field( 'off_url',       isset( $product['url'] ) ? $product['url'] : '', $post_id );
    update_field( 'off_last_sync', current_time( 'Y-m-d H:i:s' ), $post_id );

    // Kiemelt kép
    if ( ! empty( $product['image_front_url'] ) ) {
        $image_id = off_import_sideload_image(
            $product['image_front_url'],
            $post_id,
            $name
        );
        if ( $image_id && ! is_wp_error( $image_id ) ) {
            set_post_thumbnail( $post_id, $image_id );
        }
    }

    wp_send_json_success(
        'Importálva: "' . $name . '" (#' . $post_id . ', ' . $filled . ' mező)'
    );
}
add_action( 'wp_ajax_off_import_single_product', 'off_import_single_product_handler' );


// ══════════════════════════════════════════════════════════════
// 17. PUBLISH BOX SZINKRON GOMB (alapanyag szerkesztőben)
// ══════════════════════════════════════════════════════════════

function off_import_single_sync_button() {
    global $post;

    if ( ! $post || $post->post_type !== 'alapanyag' ) return;

    $barcode = function_exists( 'get_field' )
               ? get_field( 'off_barcode', $post->ID )
               : '';

    if ( empty( $barcode ) ) return;

    $last_sync = function_exists( 'get_field' )
                 ? get_field( 'off_last_sync', $post->ID )
                 : '';

    $nonce = wp_create_nonce( 'off_sync_single_' . $post->ID );

    echo '<div class="misc-pub-section" style="padding: 8px 10px;">';

    echo '<button type="button" class="button" id="off-sync-single" ';
    echo 'data-post-id="' . esc_attr( $post->ID ) . '" ';
    echo 'data-barcode="' . esc_attr( $barcode ) . '" ';
    echo 'data-nonce="' . esc_attr( $nonce ) . '">';
    echo '🔄 OFF Szinkronizálás';
    echo '</button>';

    echo '<span id="off-sync-status" style="margin-left: 8px; font-size: 0.85rem;"></span>';

    if ( $last_sync ) {
        echo '<div style="font-size: 0.78rem; color: #999; margin-top: 4px;">';
        echo 'Utolsó: ' . esc_html( $last_sync );
        echo '</div>';
    }

    echo '</div>';

    ?>
    <script>
    document.getElementById('off-sync-single').addEventListener('click', function() {
        var btn    = this;
        var status = document.getElementById('off-sync-status');

        btn.disabled       = true;
        status.textContent = '⏳ Szinkronizálás...';
        status.style.color = '#666';

        var formData = new FormData();
        formData.append('action',  'off_sync_single');
        formData.append('post_id', btn.dataset.postId);
        formData.append('barcode', btn.dataset.barcode);
        formData.append('nonce',   btn.dataset.nonce);

        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(response) {
                btn.disabled = false;

                if (response.success) {
                    status.textContent = '✅ ' + response.data;
                    status.style.color = '#16a34a';
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    status.textContent = '❌ ' + response.data;
                    status.style.color = '#dc2626';
                }
            })
            .catch(function() {
                btn.disabled       = false;
                status.textContent = '❌ Hálózati hiba';
                status.style.color = '#dc2626';
            });
    });
    </script>
    <?php
}
add_action( 'post_submitbox_misc_actions', 'off_import_single_sync_button' );


// ══════════════════════════════════════════════════════════════
// 18. AJAX: EGYEDI SZINKRON (publish box gomb)
// ══════════════════════════════════════════════════════════════

function off_sync_single_handler() {
    $post_id = intval( $_POST['post_id'] ?? 0 );
    $barcode = sanitize_text_field( $_POST['barcode'] ?? '' );

    check_ajax_referer( 'off_sync_single_' . $post_id, 'nonce' );

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( 'Nincs jogosultságod.' );
    }

    if ( ! function_exists( 'update_field' ) ) {
        wp_send_json_error( 'ACF plugin nincs betöltve.' );
    }

    if ( ! function_exists( 'tapanyag_get_alapanyag_nutrient_fields' ) ) {
        wp_send_json_error( '#18-as snippet nincs aktiválva.' );
    }

    if ( empty( $barcode ) ) {
        wp_send_json_error( 'Nincs vonalkód megadva.' );
    }

    // OFF API
    $api_url = 'https://world.openfoodfacts.org/api/v2/product/' . $barcode . '.json';

    $response = wp_remote_get( $api_url, [
        'timeout' => OFF_API_TIMEOUT,
        'headers' => [
            'User-Agent' => 'TapanyagLexikon/1.0 (WordPress plugin; azsupp3387@gmail.com)',
        ],
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'OFF API hiba: ' . $response->get_error_message() );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! $body || ( isset( $body['status'] ) && $body['status'] !== 1 ) ) {
        wp_send_json_error( 'Termék nem található az OFF-ben.' );
    }

    // Tápértékek frissítése
    $nutriments = isset( $body['product']['nutriments'] )
                  ? $body['product']['nutriments']
                  : [];

    $fields_map = tapanyag_get_alapanyag_nutrient_fields();
    $updated    = 0;

    foreach ( $fields_map as $acf_key => $field_info ) {
        if ( $field_info['csoport'] === 'meta' ) continue;

        $off_key = $field_info['off_key'];

        if ( isset( $nutriments[ $off_key ] ) && $nutriments[ $off_key ] !== '' ) {
            $value = round( floatval( $nutriments[ $off_key ] ), 4 );
            update_field( $acf_key, $value, $post_id );
            $updated++;
        }
    }

    update_field( 'off_url',
        isset( $body['product']['url'] ) ? $body['product']['url'] : '',
        $post_id
    );
    update_field( 'off_last_sync', current_time( 'Y-m-d H:i:s' ), $post_id );

    wp_send_json_success( $updated . ' mező frissítve. Oldal újratölt...' );
}
add_action( 'wp_ajax_off_sync_single', 'off_sync_single_handler' );


// ══════════════════════════════════════════════════════════════
// 19. EGYEDI CRON INTERVALLUM (30 másodperc)
// ══════════════════════════════════════════════════════════════

function off_cron_schedules( $schedules ) {
    $schedules['off_every_30s'] = [
        'interval' => 30,
        'display'  => 'OFF Import: 30 másodpercenként',
    ];
    return $schedules;
}
add_filter( 'cron_schedules', 'off_cron_schedules' );


// ══════════════════════════════════════════════════════════════
// 20. CRON BIZTOSÍTÁS: HA "RUNNING" → MINDIG LEGYEN ÜTEMEZVE
// ══════════════════════════════════════════════════════════════

function off_ensure_cron_running() {
    $state = get_option( OFF_IMPORT_STATE_KEY, [] );

    if ( ( $state['status'] ?? '' ) === 'running' ) {
        // Ha nincs ütemezve → újra ütemezzük
        if ( ! wp_next_scheduled( OFF_CRON_HOOK ) ) {
            wp_schedule_event( time(), 'off_every_30s', OFF_CRON_HOOK );
        }
    } else {
        // Ha nem fut → töröljük (takarítás)
        if ( wp_next_scheduled( OFF_CRON_HOOK ) ) {
            wp_clear_scheduled_hook( OFF_CRON_HOOK );
        }
    }
}
add_action( 'admin_init', 'off_ensure_cron_running' );
add_action( 'wp_loaded', 'off_ensure_cron_running' );
