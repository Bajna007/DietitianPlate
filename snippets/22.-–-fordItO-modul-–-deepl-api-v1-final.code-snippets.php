<?php

/**
 * 22. – FORDÍTÓ MODUL – DeepL API (v1 FINAL)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// 22 – FORDÍTÓ MODUL – DeepL API (v1 FINAL)
//
// Motor: HIBRID (polling AJAX + WP-Cron háttér)
// Admin: Alapanyagok → 🌐 Fordító
//
// MŰKÖDÉS:
//   1. Lekéri a lefordítatlan alapanyagokat (szűrhető)
//   2. Batch-enként 50 nevet küld a DeepL API-nak
//   3. Eredeti név → ACF "eredeti_nev" mezőbe (megmarad!)
//   4. Fordított név → post_title-be
//   5. Forrás + dátum mentése
//
// API LIMIT:
//   DeepL Free: 500 000 karakter / hó
//   Batch: 50 név / kérés (hatékony)
//
// SZŰRŐK:
//   - Csak USDA importált
//   - Csak OFF importált
//   - Mind (összes lefordítatlan)
//   - Csak adott forrás nélküliek
//
// FUNKCIÓK:
//   🚀 Batch fordítás
//   ▶️  Folytatás
//   🔄 Újrafordítás (már fordítottak is)
//   🔍 Egyedi fordítás
//   ✏️ Publish box gomb
// ============================================================


// ══════════════════════════════════════════════════════════════
// KONSTANSOK
// ══════════════════════════════════════════════════════════════

if ( ! defined( 'DEEPL_API_KEY' ) ) {
    define( 'DEEPL_API_KEY', 'd100065c-04c8-431c-aba1-e87aed1196ca:fx' );
}

// Az :fx végű kulcs = Free API → free-api.deepl.com
// Az :fx nélküli = Pro API → api.deepl.com
define( 'DEEPL_API_URL', str_ends_with( DEEPL_API_KEY, ':fx' )
    ? 'https://api-free.deepl.com/v2'
    : 'https://api.deepl.com/v2'
);

define( 'FORDITO_STATE_KEY',   'fordito_state_v1' );
define( 'FORDITO_LOG_KEY',     'fordito_log_v1' );
define( 'FORDITO_LASTRUN_KEY', 'fordito_last_run_time' );
define( 'FORDITO_USAGE_KEY',   'fordito_usage_v1' );
define( 'FORDITO_CRON_HOOK',   'fordito_cron_batch' );

define( 'DEEPL_MAX_TEXTS',     50 );    // max szöveg / kérés
define( 'DEEPL_MIN_DELAY',     2 );     // min delay (mp)
define( 'DEEPL_API_TIMEOUT',   30 );    // timeout mp
define( 'DEEPL_RETRY_COUNT',   3 );     // retry szám
define( 'DEEPL_RETRY_DELAY',   5 );     // retry közti mp
define( 'FORDITO_LOG_MAX',     200 );   // max log sorok


// ══════════════════════════════════════════════════════════════
// 1. ADMIN MENÜ
// ══════════════════════════════════════════════════════════════

function fordito_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=alapanyag',
        'Fordító – DeepL',
        '🌐 Fordító',
        'manage_options',
        'fordito',
        'fordito_admin_page'
    );
}
add_action( 'admin_menu', 'fordito_admin_menu' );


// ══════════════════════════════════════════════════════════════
// 2. ADMIN OLDAL HTML
// ══════════════════════════════════════════════════════════════

function fordito_admin_page() {

    // ── Statisztikák ──
    $total_q = new WP_Query( [
        'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'publish',
    ] );
    $total_count = $total_q->found_posts;
    wp_reset_postdata();

    $translated_q = new WP_Query( [
        'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'publish',
        'meta_query' => [ [ 'key' => 'forditas_forras', 'value' => 'deepl' ] ],
    ] );
    $translated_count = $translated_q->found_posts;
    wp_reset_postdata();

    $usda_count = 0;
    $usda_q = new WP_Query( [
        'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'publish',
        'meta_query' => [ [ 'key' => 'usda_fdc_id', 'compare' => 'EXISTS' ] ],
    ] );
    $usda_count = $usda_q->found_posts;
    wp_reset_postdata();

    $off_count = 0;
    $off_q = new WP_Query( [
        'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'publish',
        'meta_query' => [ [ 'key' => 'off_barcode', 'compare' => 'EXISTS' ] ],
    ] );
    $off_count = $off_q->found_posts;
    wp_reset_postdata();

    $untranslated_count = $total_count - $translated_count;

    // Mentett állapot
    $state      = get_option( FORDITO_STATE_KEY, [] );
    $is_running = ( ( $state['status'] ?? '' ) === 'running' );
    $has_saved  = ! empty( $state['offset'] )
                  && intval( $state['offset'] ) > 0
                  && ! $is_running;

    // API usage
    $usage = get_option( FORDITO_USAGE_KEY, [ 'used' => 0, 'limit' => 500000 ] );

    ?>
    <div class="wrap">
        <h1>🌐 Fordító – DeepL</h1>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── ÁLLAPOT ─────────────────────────────────── ?>
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

            <table class="widefat" style="max-width: 650px;">
                <tr>
                    <td><strong>Összes alapanyag</strong></td>
                    <td>
                        <strong style="font-size: 1.2rem;"><?php echo $total_count; ?></strong> db
                    </td>
                </tr>
                <tr>
                    <td><strong>Már lefordított</strong></td>
                    <td>
                        <strong style="font-size: 1.2rem; color: #16a34a;">
                            <?php echo $translated_count; ?>
                        </strong> db
                    </td>
                </tr>
                <tr>
                    <td><strong>Lefordítatlan</strong></td>
                    <td>
                        <strong style="font-size: 1.2rem; color: #d97706;">
                            <?php echo max( 0, $untranslated_count ); ?>
                        </strong> db
                    </td>
                </tr>
                <tr>
                    <td><strong>USDA-ból importált</strong></td>
                    <td><?php echo $usda_count; ?> db</td>
                </tr>
                <tr>
                    <td><strong>OFF-ból importált</strong></td>
                    <td><?php echo $off_count; ?> db</td>
                </tr>
                <tr>
                    <td><strong>API</strong></td>
                    <td>DeepL Free – 500 000 karakter / hó</td>
                </tr>
                <tr>
                    <td><strong>API felhasználás</strong></td>
                    <td>
                        <?php
                        $used    = intval( $usage['used'] ?? 0 );
                        $limit   = intval( $usage['limit'] ?? 500000 );
                        $pct     = $limit > 0 ? round( ( $used / $limit ) * 100, 1 ) : 0;
                        $remain  = max( 0, $limit - $used );
                        $remain_names = $remain > 0 ? round( $remain / 30 ) : 0;
                        $bar_color = $pct > 90 ? '#dc2626' : ( $pct > 70 ? '#d97706' : '#16a34a' );
                        ?>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="
                                flex: 1;
                                max-width: 200px;
                                background: #f0f0f1;
                                border-radius: 4px;
                                height: 16px;
                                overflow: hidden;
                            ">
                                <div style="
                                    width: <?php echo min( 100, $pct ); ?>%;
                                    height: 100%;
                                    background: <?php echo $bar_color; ?>;
                                    border-radius: 4px;
                                    transition: width 0.3s;
                                "></div>
                            </div>
                            <span style="font-size: 0.88rem;">
                                <strong><?php echo number_format( $used ); ?></strong>
                                / <?php echo number_format( $limit ); ?> karakter
                                (<?php echo $pct; ?>%)
                            </span>
                        </div>
                        <div style="font-size: 0.82rem; color: #666; margin-top: 4px;">
                            Maradt: ~<strong><?php echo number_format( $remain_names ); ?></strong> terméknév
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><strong>API Key</strong></td>
                    <td>
                        <?php if ( ! empty( DEEPL_API_KEY ) ) : ?>
                            <span style="color: #16a34a;">✅ Beállítva</span>
                            <span style="color: #94a3b8; font-size: 0.82rem;">
                                (Free – ...<?php echo substr( DEEPL_API_KEY, -8 ); ?>)
                            </span>
                        <?php else : ?>
                            <span style="color: #dc2626;">❌ Hiányzik!</span>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php if ( $is_running ) : ?>
                    <tr>
                        <td><strong>Folyamat</strong></td>
                        <td>
                            <span style="color: #16a34a; font-weight: 700; animation: forditoPulse 1.5s infinite;">
                                🟢 FUT
                            </span>
                            – <?php echo intval( $state['stats']['translated'] ?? 0 ); ?> lefordítva
                            | <?php echo esc_html( $state['filter_label'] ?? '' ); ?>
                        </td>
                    </tr>
                <?php elseif ( $has_saved ) : ?>
                    <tr>
                        <td><strong>Mentett pozíció</strong></td>
                        <td>
                            <span style="color: #2563eb;">
                                📌 <?php echo intval( $state['stats']['translated'] ?? 0 ); ?> lefordítva
                                | <?php echo esc_html( $state['filter_label'] ?? '' ); ?>
                                | <?php echo esc_html( $state['date'] ?? '' ); ?>
                            </span>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── BEÁLLÍTÁSOK ─────────────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div id="fordito-settings-box" style="
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

                <?php // ── Szűrő ── ?>
                <div>
                    <label for="fordito-filter" style="
                        font-weight: 600;
                        font-size: 0.88rem;
                        display: block;
                        margin-bottom: 4px;
                    ">
                        Szűrés (forrás):
                    </label>
                    <select id="fordito-filter" style="width: 100%;">
                        <option value="all" selected>📋 Összes lefordítatlan</option>
                        <option value="usda">🇺🇸 Csak USDA importált</option>
                        <option value="off">🍊 Csak OFF importált</option>
                        <option value="no_source">❓ Forrás nélküliek</option>
                    </select>
                </div>

                <?php // ── Batch méret ── ?>
                <div>
                    <label for="fordito-batch-size" style="
                        font-weight: 600;
                        font-size: 0.88rem;
                        display: block;
                        margin-bottom: 4px;
                    ">
                        Batch méret:
                    </label>
                    <input type="number"
                           id="fordito-batch-size"
                           value="50"
                           min="5"
                           max="50"
                           step="5"
                           style="width: 100%;">
                    <span style="color: #888; font-size: 0.75rem;">
                        5–50 név / kérés (DeepL max: 50)
                    </span>
                </div>

                <?php // ── Delay ── ?>
                <div>
                    <label for="fordito-batch-delay" style="
                        font-weight: 600;
                        font-size: 0.88rem;
                        display: block;
                        margin-bottom: 4px;
                    ">
                        Delay (mp):
                    </label>
                    <input type="number"
                           id="fordito-batch-delay"
                           value="2"
                           min="2"
                           max="30"
                           step="1"
                           style="width: 100%;">
                    <span style="color: #888; font-size: 0.75rem;">
                        min: 2 mp
                    </span>
                </div>

                <?php // ── Maximum ── ?>
                <div>
                    <label for="fordito-max" style="
                        font-weight: 600;
                        font-size: 0.88rem;
                        display: block;
                        margin-bottom: 4px;
                    ">
                        Maximum:
                    </label>
                    <input type="number"
                           id="fordito-max"
                           value="500"
                           min="10"
                           max="50000"
                           step="10"
                           style="width: 100%;">
                    <span style="color: #888; font-size: 0.75rem;">
                        0 = nincs limit
                    </span>
                </div>
            </div>

            <?php // ── Figyelmeztetés ── ?>
            <div id="fordito-limit-warning" style="
                margin-top: 12px;
                padding: 10px 14px;
                border-radius: 6px;
                font-size: 0.85rem;
                display: none;
            "></div>

            <?php // ── Becsült idő + karakter ── ?>
            <div id="fordito-estimate-box" style="
                margin-top: 12px;
                padding: 10px 14px;
                background: #fffbeb;
                border-radius: 6px;
                font-size: 0.85rem;
                color: #92400e;
            ">
                💡 <strong>Becsült</strong>:
                <span id="fordito-time-estimate">–</span>
                | ~<span id="fordito-char-estimate">–</span> karakter
                (marad utána: <span id="fordito-char-remain">–</span>)
            </div>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── HÁROM MÓD ──────────────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div style="
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            max-width: 900px;
            margin: 20px 0;
        ">

            <?php // ── BATCH FORDÍTÁS ── ?>
            <div style="
                background: #f0fdf4;
                border: 2px solid #86efac;
                border-radius: 8px;
                padding: 18px 20px;
            ">
                <h3 style="margin-top: 0; color: #16a34a; font-size: 1rem;">
                    🚀 Batch fordítás
                </h3>
                <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                    Összes <strong>lefordítatlan</strong> alapanyag
                    neve a szűrő szerint.
                </p>
                <button id="fordito-start"
                        class="button button-primary"
                        style="font-size: 13px; padding: 5px 16px;"
                        <?php echo $is_running ? 'disabled' : ''; ?>>
                    🚀 Fordítás indítása
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
                    <p style="font-size: 0.82rem; color: #16a34a; line-height: 1.4; font-weight: 600;">
                        🟢 Jelenleg fut...
                    </p>
                    <button class="button" disabled>▶️ Fut...</button>
                <?php elseif ( $has_saved ) : ?>
                    <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                        <?php echo intval( $state['stats']['translated'] ?? 0 ); ?> lefordítva
                        <br><?php echo esc_html( $state['date'] ?? '' ); ?>
                    </p>
                    <button id="fordito-continue" class="button"
                            style="background: #3b82f6; color: #fff; border-color: #2563eb;">
                        ▶️ Folytatás
                    </button>
                    <button id="fordito-reset" class="button"
                            style="font-size: 12px; padding: 4px 10px; color: #dc2626; margin-left: 4px;"
                            title="Pozíció törlése">🗑️</button>
                <?php else : ?>
                    <p style="font-size: 0.82rem; color: #999;">Nincs mentett pozíció.</p>
                    <button class="button" disabled>▶️ Nincs adat</button>
                <?php endif; ?>
            </div>

            <?php // ── ÚJRAFORDÍTÁS ── ?>
            <div style="
                background: #fffbeb;
                border: 2px solid #fcd34d;
                border-radius: 8px;
                padding: 18px 20px;
            ">
                <h3 style="margin-top: 0; color: #d97706; font-size: 1rem;">
                    🔄 Újrafordítás
                </h3>
                <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                    Már fordított neveket is <strong>újrafordítja</strong>
                    (ha javult a DeepL).
                </p>
                <button id="fordito-retranslate" class="button"
                        style="background: #f59e0b; color: #fff; border-color: #d97706;"
                        <?php echo ( $translated_count < 1 || $is_running ) ? 'disabled' : ''; ?>>
                    🔄 Újrafordítás
                </button>
            </div>
        </div>

        <?php // ── LEÁLLÍTÁS ── ?>
        <div style="margin: 0 0 12px;">
            <button id="fordito-stop" class="button button-secondary"
                    style="font-size: 14px; padding: 6px 20px; <?php echo $is_running ? '' : 'display: none;'; ?>">
                ⏹ Leállítás
            </button>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── EGYEDI FORDÍTÁS ─────────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div style="
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 20px 0;
            max-width: 900px;
        ">
            <h3 style="margin-top: 0;">🔍 Egyedi fordítás</h3>
            <p style="font-size: 0.88rem; color: #555;">
                Írj be egy angol (vagy más nyelvű) terméknevet és nézd meg a fordítást.
            </p>

            <div style="display: flex; gap: 8px;">
                <input type="text"
                       id="fordito-search-query"
                       placeholder="pl. Chicken breast, raw, skinless..."
                       style="flex: 1; font-size: 14px; padding: 6px 12px;">
                <button id="fordito-search-btn" class="button"
                        style="font-size: 14px; padding: 6px 16px;">
                    🌐 Fordítás
                </button>
            </div>

            <div id="fordito-search-result" style="margin-top: 12px;"></div>

            <?php // ── Lefordítatlan lista keresés ── ?>
            <div style="margin-top: 16px; border-top: 1px solid #e5e7eb; padding-top: 16px;">
                <h4 style="margin-top: 0; font-size: 0.95rem;">
                    📋 Lefordítatlan alapanyagok
                </h4>
                <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                    <select id="fordito-list-filter" style="font-size: 14px; padding: 6px 8px;">
                        <option value="all">📋 Összes</option>
                        <option value="usda">🇺🇸 USDA</option>
                        <option value="off">🍊 OFF</option>
                    </select>
                    <button id="fordito-list-btn" class="button"
                            style="font-size: 14px; padding: 6px 16px;">
                        📋 Mutasd (max 20)
                    </button>
                </div>
                <div id="fordito-list-results" style=""></div>
            </div>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── ÉLŐ STÁTUSZ + LOG ──────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div id="fordito-live-status" style="
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
                <span id="fordito-live-indicator" style="
                    color: #16a34a;
                    animation: forditoPulse 1.5s infinite;
                ">●</span>
            </h3>

            <div style="
                background: #f0f0f1;
                border-radius: 6px;
                height: 28px;
                overflow: hidden;
                margin-bottom: 12px;
            ">
                <div id="fordito-progress-bar" style="
                    background: linear-gradient(90deg, #8b5cf6, #6366f1);
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

            <div style="
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 8px;
                margin-bottom: 12px;
            ">
                <div style="text-align: center; padding: 8px; background: #f0fdf4; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #16a34a;" id="fordito-s-translated">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Lefordítva</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #fffbeb; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #d97706;" id="fordito-s-skipped">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Kihagyva (magyar)</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #fef2f2; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #dc2626;" id="fordito-s-errors">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Hiba</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #f5f3ff; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #7c3aed;" id="fordito-s-chars">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Karakter (session)</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #f0f6fc; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #2563eb;" id="fordito-s-batch">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Batch #</div>
                </div>
            </div>

            <div id="fordito-live-info" style="
                font-size: 0.85rem;
                color: #666;
                margin-bottom: 8px;
                padding: 6px 10px;
                background: #f8fafc;
                border-radius: 4px;
            "></div>

            <div id="fordito-log" style="
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
    </div>

    <style>
        @keyframes forditoPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
    </style>
    <?php
}
// ============================================================
// 22 v1 – 2. RÉSZ: JS + PHP BACKEND
// ============================================================


// ══════════════════════════════════════════════════════════════
// 3. ADMIN SCRIPTS (JS)
// ══════════════════════════════════════════════════════════════

function fordito_admin_scripts( $hook ) {
    if ( $hook !== 'alapanyag_page_fordito' ) return;

    $state = get_option( FORDITO_STATE_KEY, [] );
    $usage = get_option( FORDITO_USAGE_KEY, [ 'used' => 0, 'limit' => 500000 ] );

    $js = <<<'JSEOF'
(function() {
    'use strict';

    var $ = document.getElementById.bind(document);
    var pollTimer = null;

    // ══════════════════════════════════════════════════════
    // VALIDÁCIÓ + BECSLÉS
    // ══════════════════════════════════════════════════════

    function validateSettings() {
        var batchSize = parseInt($('fordito-batch-size').value) || 50;
        var delay     = parseFloat($('fordito-batch-delay').value) || 2;
        var maxItems  = parseInt($('fordito-max').value) || 500;

        var batches   = Math.ceil(maxItems / batchSize);
        var totalSec  = batches * delay;
        var min       = Math.floor(totalSec / 60);
        var sec       = Math.round(totalSec % 60);
        var timeStr   = (min > 0 ? '~' + min + ' perc ' : '~') + sec + ' mp';
        var estChars  = maxItems * 30;
        var apiUsed   = parseInt(forditoData.apiUsed) || 0;
        var apiLimit  = parseInt(forditoData.apiLimit) || 500000;
        var charRemain = Math.max(0, apiLimit - apiUsed - estChars);

        $('fordito-time-estimate').textContent = timeStr;
        $('fordito-char-estimate').textContent = estChars.toLocaleString();
        $('fordito-char-remain').textContent   = charRemain.toLocaleString();

        var warn     = $('fordito-limit-warning');
        var startBtn = $('fordito-start');

        if (batchSize > 50) {
            warn.style.display = 'block'; warn.style.background = '#fef2f2'; warn.style.color = '#dc2626';
            warn.innerHTML = '🚫 <strong>Max 50 név / batch!</strong> DeepL korlátozás.';
            if (startBtn) startBtn.disabled = true;
            return false;
        }

        if (estChars > (apiLimit - apiUsed)) {
            warn.style.display = 'block'; warn.style.background = '#fef2f2'; warn.style.color = '#dc2626';
            warn.innerHTML = '🚫 <strong>Nem elég karakter!</strong> Becsült: ' + estChars.toLocaleString() +
                             ' | Elérhető: ' + (apiLimit - apiUsed).toLocaleString() + '. Csökkentsd a maximumot.';
            if (startBtn) startBtn.disabled = true;
            return false;
        }

        if (estChars > (apiLimit - apiUsed) * 0.8) {
            warn.style.display = 'block'; warn.style.background = '#fffbeb'; warn.style.color = '#92400e';
            warn.innerHTML = '⚠️ <strong>Közel a limithez!</strong> Becsült: ' + estChars.toLocaleString() +
                             ' karakter (havi limit 80%-a fölött).';
            if (startBtn) startBtn.disabled = false;
            return true;
        }

        warn.style.display = 'none';
        if (startBtn) startBtn.disabled = false;
        return true;
    }

    $('fordito-batch-size').addEventListener('input', validateSettings);
    $('fordito-batch-delay').addEventListener('input', validateSettings);
    $('fordito-max').addEventListener('input', validateSettings);
    validateSettings();

    // ══════════════════════════════════════════════════════
    // INDÍTÁS
    // ══════════════════════════════════════════════════════

    function startTranslation(mode, offset) {
        if (!validateSettings()) return;
        var fd = new FormData();
        fd.append('action',      'fordito_cron_start');
        fd.append('nonce',       forditoData.nonce);
        fd.append('mode',        mode);
        fd.append('offset',      offset);
        fd.append('filter',      $('fordito-filter').value);
        fd.append('batch_size',  $('fordito-batch-size').value);
        fd.append('batch_delay', $('fordito-batch-delay').value);
        fd.append('max_items',   $('fordito-max').value);
        fetch(forditoData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (resp.success) location.reload();
                else alert('Hiba: ' + resp.data);
            });
    }

    $('fordito-start').addEventListener('click', function() { startTranslation('translate', 0); });
    $('fordito-retranslate').addEventListener('click', function() { startTranslation('retranslate', 0); });
    if ($('fordito-continue')) {
        $('fordito-continue').addEventListener('click', function() {
            var saved = parseInt(forditoData.savedState.offset) || 0;
            startTranslation(forditoData.savedState.mode || 'translate', saved);
        });
    }
    if ($('fordito-reset')) {
        $('fordito-reset').addEventListener('click', function() {
            if (!confirm('Mentett pozíció törlése?')) return;
            var fd = new FormData();
            fd.append('action', 'fordito_cron_reset'); fd.append('nonce', forditoData.nonce);
            fetch(forditoData.ajaxUrl, { method: 'POST', body: fd }).then(function() { location.reload(); });
        });
    }

    $('fordito-stop').addEventListener('click', function() {
        this.disabled = true; this.textContent = '⏳ Leállítás...';
        var fd = new FormData();
        fd.append('action', 'fordito_cron_stop'); fd.append('nonce', forditoData.nonce);
        fetch(forditoData.ajaxUrl, { method: 'POST', body: fd }).then(function() { location.reload(); });
    });

    // ══════════════════════════════════════════════════════
    // POLLING (3mp)
    // ══════════════════════════════════════════════════════

    function pollStatus() {
        var fd = new FormData();
        fd.append('action', 'fordito_cron_status'); fd.append('nonce', forditoData.nonce);
        fetch(forditoData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (!resp.success) return;
                var s = resp.data;

                $('fordito-s-translated').textContent = s.translated || 0;
                $('fordito-s-skipped').textContent    = s.skipped || 0;
                $('fordito-s-errors').textContent     = s.errors || 0;
                $('fordito-s-chars').textContent      = (s.chars_used || 0).toLocaleString();
                $('fordito-s-batch').textContent       = s.batch_num || 0;

                var pct = s.max_items > 0 ? Math.min(100, Math.round((s.translated / s.max_items) * 100)) : 0;
                $('fordito-progress-bar').style.width = pct + '%';
                $('fordito-progress-bar').textContent = pct + '%';

                var filterLabels = { all:'📋 Összes', usda:'🇺🇸 USDA', off:'🍊 OFF', no_source:'❓ Forrás nélküli' };
                $('fordito-live-info').innerHTML =
                    'Szűrő: <strong>' + (filterLabels[s.filter] || s.filter) + '</strong>' +
                    ' | Batch: <strong>' + s.batch_size + '</strong>' +
                    ' | Delay: <strong>' + s.batch_delay + 's</strong>' +
                    ' | Session karakter: <strong>' + (s.chars_used || 0).toLocaleString() + '</strong>';

                if (s.recent_log && s.recent_log.length > 0) {
                    var logEl = $('fordito-log'), logHtml = '';
                    s.recent_log.forEach(function(entry) {
                        var colors = { info:'#60a5fa', success:'#4ade80', warn:'#fbbf24', error:'#f87171', skip:'#c084fc' };
                        logHtml += '<div style="color:' + (colors[entry.type]||'#d4d4d4') + '">[' + entry.time + '] ' + entry.msg + '</div>';
                    });
                    logEl.innerHTML = logHtml;
                    logEl.scrollTop = logEl.scrollHeight;
                }

                if (s.status !== 'running') {
                    clearInterval(pollTimer);
                    $('fordito-live-indicator').textContent = '⏹';
                    $('fordito-live-indicator').style.animation = 'none';
                    $('fordito-stop').style.display = 'none';
                }
            });
    }

    if (forditoData.isRunning) {
        $('fordito-live-status').style.display = 'block';
        $('fordito-stop').style.display = 'inline-block';
        pollTimer = setInterval(pollStatus, 3000);
        pollStatus();
    }

    // ══════════════════════════════════════════════════════
    // EGYEDI FORDÍTÁS
    // ══════════════════════════════════════════════════════

    $('fordito-search-btn').addEventListener('click', function() {
        var query = $('fordito-search-query').value.trim();
        if (!query) return;
        var btn = this; btn.disabled = true; btn.textContent = '⏳...';
        $('fordito-search-result').innerHTML = '<div style="color:#666;">Fordítás...</div>';
        var fd = new FormData();
        fd.append('action', 'fordito_translate_single'); fd.append('nonce', forditoData.nonce);
        fd.append('text', query);
        fetch(forditoData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                btn.disabled = false; btn.textContent = '🌐 Fordítás';
                if (resp.success) {
                    var d = resp.data;
                    $('fordito-search-result').innerHTML =
                        '<div style="padding:10px 14px;background:#f0fdf4;border:1px solid #86efac;border-radius:6px;margin-top:8px;">' +
                        '<div style="font-size:0.82rem;color:#666;">Eredeti (' + escHtml(d.source_lang) + '):</div>' +
                        '<div style="font-weight:600;color:#374151;">' + escHtml(d.original) + '</div>' +
                        '<div style="font-size:0.82rem;color:#666;margin-top:6px;">Magyar fordítás:</div>' +
                        '<div style="font-weight:700;color:#16a34a;font-size:1.1rem;">🇭🇺 ' + escHtml(d.translated) + '</div>' +
                        '<div style="font-size:0.78rem;color:#94a3b8;margin-top:4px;">' + d.chars + ' karakter</div>' +
                        '</div>';
                } else {
                    $('fordito-search-result').innerHTML = '<div style="color:#dc2626;">❌ ' + resp.data + '</div>';
                }
            });
    });
    $('fordito-search-query').addEventListener('keypress', function(e) { if (e.key === 'Enter') $('fordito-search-btn').click(); });

    // ══════════════════════════════════════════════════════
    // LEFORDÍTATLAN LISTA
    // ══════════════════════════════════════════════════════

    $('fordito-list-btn').addEventListener('click', function() {
        var btn = this, filter = $('fordito-list-filter').value;
        btn.disabled = true; btn.textContent = '⏳...';
        var fd = new FormData();
        fd.append('action', 'fordito_list_untranslated'); fd.append('nonce', forditoData.nonce);
        fd.append('filter', filter);
        fetch(forditoData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                btn.disabled = false; btn.textContent = '📋 Mutasd (max 20)';
                if (!resp.success) { $('fordito-list-results').innerHTML = '<div style="color:#dc2626;">❌ ' + resp.data + '</div>'; return; }
                var items = resp.data.items;
                if (!items.length) { $('fordito-list-results').innerHTML = '<div style="color:#16a34a;">✅ Minden le van fordítva!</div>'; return; }
                var html = '<div style="font-size:0.85rem;color:#666;margin-bottom:6px;">' + resp.data.total + ' lefordítatlan (' + items.length + ' mutatva):</div>';
                items.forEach(function(item) {
                    html += '<div style="border:1px solid #e5e7eb;border-radius:6px;padding:8px 12px;margin-bottom:4px;display:flex;justify-content:space-between;align-items:center;">';
                    html += '<div style="flex:1;"><strong>' + escHtml(item.name) + '</strong>';
                    html += ' <span style="font-size:0.75rem;color:#94a3b8;">#' + item.id + '</span>';
                    if (item.source) html += ' <span style="font-size:0.72rem;padding:1px 6px;background:#eff6ff;color:#2563eb;border-radius:3px;">' + escHtml(item.source) + '</span>';
                    html += '</div>';
                    html += '<button class="button fordito-single-btn" data-post-id="' + item.id + '" style="font-size:0.82rem;white-space:nowrap;">🌐 Fordítás</button>';
                    html += '</div>';
                });
                $('fordito-list-results').innerHTML = html;
                document.querySelectorAll('.fordito-single-btn').forEach(function(b) {
                    b.addEventListener('click', function() {
                        var postId = this.dataset.postId, tb = this;
                        tb.disabled = true; tb.textContent = '⏳...';
                        var fd2 = new FormData();
                        fd2.append('action', 'fordito_translate_post'); fd2.append('nonce', forditoData.nonce);
                        fd2.append('post_id', postId);
                        fetch(forditoData.ajaxUrl, { method: 'POST', body: fd2 })
                            .then(function(r) { return r.json(); })
                            .then(function(rr) {
                                tb.textContent = rr.success ? '✅ ' + rr.data.translated_name : '❌';
                                tb.style.color = rr.success ? '#16a34a' : '#dc2626';
                            });
                    });
                });
            });
    });

    function escHtml(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
})();
JSEOF;

    wp_register_script( 'fordito-js', false, [], '1.0', true );
    wp_enqueue_script( 'fordito-js' );
    wp_add_inline_script( 'fordito-js', $js );

    wp_localize_script( 'fordito-js', 'forditoData', [
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'fordito_nonce' ),
        'isRunning'  => ( ( $state['status'] ?? '' ) === 'running' ),
        'savedState' => $state,
        'apiUsed'    => $usage['used'] ?? 0,
        'apiLimit'   => $usage['limit'] ?? 500000,
    ] );
}
add_action( 'admin_enqueue_scripts', 'fordito_admin_scripts' );


// ══════════════════════════════════════════════════════════════
// 4. AJAX: INDÍTÁS
// ══════════════════════════════════════════════════════════════

function fordito_cron_start_handler() {
    check_ajax_referer( 'fordito_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $mode        = sanitize_key( $_POST['mode'] ?? 'translate' );
    $offset      = max( 0, intval( $_POST['offset'] ?? 0 ) );
    $filter      = sanitize_key( $_POST['filter'] ?? 'all' );
    $batch_size  = min( DEEPL_MAX_TEXTS, max( 5, intval( $_POST['batch_size'] ?? 50 ) ) );
    $batch_delay = max( DEEPL_MIN_DELAY, floatval( $_POST['batch_delay'] ?? 2 ) );
    $max_items   = max( 0, intval( $_POST['max_items'] ?? 500 ) );

    $filter_labels = [
        'all'       => '📋 Összes lefordítatlan',
        'usda'      => '🇺🇸 Csak USDA',
        'off'       => '🍊 Csak OFF',
        'no_source' => '❓ Forrás nélküli',
    ];

    $state = [
        'status'       => 'running',
        'mode'         => $mode,
        'offset'       => $offset,
        'filter'       => $filter,
        'filter_label' => $filter_labels[ $filter ] ?? $filter,
        'batch_size'   => $batch_size,
        'batch_delay'  => $batch_delay,
        'max_items'    => $max_items,
        'batch_num'    => 0,
        'stats'        => [
            'translated' => 0,
            'skipped'    => 0,
            'errors'     => 0,
            'chars_used' => 0,
        ],
        'date' => current_time( 'Y-m-d H:i:s' ),
    ];

    update_option( FORDITO_STATE_KEY, $state );
    update_option( FORDITO_LOG_KEY, [] );
    update_option( FORDITO_LASTRUN_KEY, 0 );

    wp_clear_scheduled_hook( FORDITO_CRON_HOOK );
    wp_schedule_event( time(), 'fordito_every_30s', FORDITO_CRON_HOOK );
    spawn_cron();

    fordito_log( 'info', '🚀 ' . ( $mode === 'retranslate' ? 'Újrafordítás' : 'Fordítás' ) . ' indítása...' );
    fordito_log( 'info', '🔍 Szűrő: ' . ( $filter_labels[ $filter ] ?? $filter ) );
    fordito_log( 'info', '⚙️ Batch: ' . $batch_size . ' | Delay: ' . $batch_delay . 's | Max: ' . $max_items );

    wp_send_json_success( 'Elindítva.' );
}
add_action( 'wp_ajax_fordito_cron_start', 'fordito_cron_start_handler' );


// ══════════════════════════════════════════════════════════════
// 5. AJAX: LEÁLLÍTÁS
// ══════════════════════════════════════════════════════════════

function fordito_cron_stop_handler() {
    check_ajax_referer( 'fordito_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $state = get_option( FORDITO_STATE_KEY, [] );
    $state['status'] = 'stopped';
    update_option( FORDITO_STATE_KEY, $state );
    wp_clear_scheduled_hook( FORDITO_CRON_HOOK );

    fordito_log( 'info', '⏹ Leállítva. ' . ( $state['stats']['translated'] ?? 0 ) . ' lefordítva.' );
    wp_send_json_success( 'Leállítva.' );
}
add_action( 'wp_ajax_fordito_cron_stop', 'fordito_cron_stop_handler' );


// ══════════════════════════════════════════════════════════════
// 6. AJAX: RESET
// ══════════════════════════════════════════════════════════════

function fordito_cron_reset_handler() {
    check_ajax_referer( 'fordito_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    delete_option( FORDITO_STATE_KEY );
    delete_option( FORDITO_LOG_KEY );
    delete_option( FORDITO_LASTRUN_KEY );
    wp_clear_scheduled_hook( FORDITO_CRON_HOOK );

    wp_send_json_success( 'Törölve.' );
}
add_action( 'wp_ajax_fordito_cron_reset', 'fordito_cron_reset_handler' );


// ══════════════════════════════════════════════════════════════
// 7. AJAX: POLLING + HIBRID MOTOR
// ══════════════════════════════════════════════════════════════

function fordito_cron_status_handler() {
    check_ajax_referer( 'fordito_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $state = get_option( FORDITO_STATE_KEY, [] );
    $log   = get_option( FORDITO_LOG_KEY, [] );

    if ( ( $state['status'] ?? '' ) === 'running' ) {
        $last_run = floatval( get_option( FORDITO_LASTRUN_KEY, 0 ) );
        $delay    = floatval( $state['batch_delay'] ?? 2 );
        $now      = microtime( true );

        if ( ( $now - $last_run ) >= $delay ) {
            update_option( FORDITO_LASTRUN_KEY, $now );
            do_action( FORDITO_CRON_HOOK );
            $state = get_option( FORDITO_STATE_KEY, [] );
            $log   = get_option( FORDITO_LOG_KEY, [] );
        }
        spawn_cron();
    }

    wp_send_json_success( [
        'status'      => $state['status'] ?? 'idle',
        'filter'      => $state['filter'] ?? 'all',
        'batch_size'  => $state['batch_size'] ?? 50,
        'batch_delay' => $state['batch_delay'] ?? 2,
        'max_items'   => $state['max_items'] ?? 500,
        'batch_num'   => $state['batch_num'] ?? 0,
        'translated'  => $state['stats']['translated'] ?? 0,
        'skipped'     => $state['stats']['skipped'] ?? 0,
        'errors'      => $state['stats']['errors'] ?? 0,
        'chars_used'  => $state['stats']['chars_used'] ?? 0,
        'recent_log'  => array_slice( $log, -50 ),
    ] );
}
add_action( 'wp_ajax_fordito_cron_status', 'fordito_cron_status_handler' );


// ══════════════════════════════════════════════════════════════
// 8. WP-CRON: BATCH ROUTER
// ══════════════════════════════════════════════════════════════

function fordito_cron_run_batch() {
    $state = get_option( FORDITO_STATE_KEY, [] );
    if ( ( $state['status'] ?? '' ) !== 'running' ) {
        wp_clear_scheduled_hook( FORDITO_CRON_HOOK );
        return;
    }

    $max_items = intval( $state['max_items'] ?? 500 );
    if ( $max_items > 0 && ( $state['stats']['translated'] ?? 0 ) >= $max_items ) {
        $state['status'] = 'done';
        update_option( FORDITO_STATE_KEY, $state );
        wp_clear_scheduled_hook( FORDITO_CRON_HOOK );
        fordito_log( 'success', '🎉 Maximum elérve: ' . $state['stats']['translated'] . ' lefordítva.' );
        return;
    }

    fordito_run_translate_batch( $state );
}
add_action( FORDITO_CRON_HOOK, 'fordito_cron_run_batch' );


// ══════════════════════════════════════════════════════════════
// 9. FORDÍTÁS BATCH
// ══════════════════════════════════════════════════════════════

function fordito_run_translate_batch( &$state ) {
    if ( ! function_exists( 'update_field' ) ) {
        $state['status'] = 'error';
        update_option( FORDITO_STATE_KEY, $state );
        wp_clear_scheduled_hook( FORDITO_CRON_HOOK );
        fordito_log( 'error', '❌ ACF nincs betöltve.' );
        return;
    }

    $batch_size = intval( $state['batch_size'] ?? 50 );
    $offset     = intval( $state['offset'] ?? 0 );
    $filter     = $state['filter'] ?? 'all';
    $mode       = $state['mode'] ?? 'translate';

    // ── Lekérdezés ──
    $meta_query = [];

    if ( $mode === 'retranslate' ) {
        // Újrafordítás: amiknek VAN eredeti_nev
        $meta_query[] = [ 'key' => 'eredeti_nev', 'compare' => 'EXISTS' ];
        $meta_query[] = [ 'key' => 'eredeti_nev', 'value' => '', 'compare' => '!=' ];
    } else {
        // Csak lefordítatlanok
        $meta_query['relation'] = 'OR';
        $meta_query[] = [ 'key' => 'forditas_forras', 'compare' => 'NOT EXISTS' ];
        $meta_query[] = [ 'key' => 'forditas_forras', 'value' => '' ];
    }

    // Szűrő
    if ( $filter === 'usda' ) {
        $meta_query = [
            'relation' => 'AND',
            $meta_query,
            [ 'key' => 'usda_fdc_id', 'compare' => 'EXISTS' ],
        ];
    } elseif ( $filter === 'off' ) {
        $meta_query = [
            'relation' => 'AND',
            $meta_query,
            [ 'key' => 'off_barcode', 'compare' => 'EXISTS' ],
        ];
    } elseif ( $filter === 'no_source' ) {
        $meta_query = [
            'relation' => 'AND',
            $meta_query,
            [ 'key' => 'usda_fdc_id', 'compare' => 'NOT EXISTS' ],
            [ 'key' => 'off_barcode', 'compare' => 'NOT EXISTS' ],
        ];
    }

    $posts = get_posts( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => $batch_size,
        'offset'         => $offset,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ] );

    if ( empty( $posts ) ) {
        $state['status'] = 'done';
        update_option( FORDITO_STATE_KEY, $state );
        wp_clear_scheduled_hook( FORDITO_CRON_HOOK );
        fordito_log( 'success', '🏁 Kész! ' . ( $state['stats']['translated'] ?? 0 ) . ' lefordítva.' );
        return;
    }

    $state['batch_num'] = ( $state['batch_num'] ?? 0 ) + 1;
    fordito_log( 'info', '📦 Batch #' . $state['batch_num'] . ': ' . count( $posts ) . ' név' );

    // ── Nevek összegyűjtése ──
    $texts   = [];
    $post_map = [];

    foreach ( $posts as $post ) {
        $name = $mode === 'retranslate'
                ? get_field( 'eredeti_nev', $post->ID )
                : $post->post_title;

        if ( empty( $name ) ) {
            $name = $post->post_title;
        }

        $texts[]    = $name;
        $post_map[] = [
            'post_id'       => $post->ID,
            'original_name' => $name,
            'current_title' => $post->post_title,
        ];
    }

    // ── DeepL API hívás ──
    $result = fordito_deepl_translate_batch( $texts );

    if ( $result === false ) {
        $state['stats']['errors'] = ( $state['stats']['errors'] ?? 0 ) + 1;
        fordito_log( 'error', '❌ DeepL API hiba – továbblépés.' );
        $state['offset'] = $offset + $batch_size;
        $state['date']   = current_time( 'Y-m-d H:i:s' );
        update_option( FORDITO_STATE_KEY, $state );
        return;
    }

    $translations = $result['translations'];
    $chars_batch  = $result['chars_used'];

    // ── Eredmények alkalmazása ──
    $translated = 0;
    $skipped    = 0;

    foreach ( $translations as $i => $tr ) {
        if ( ! isset( $post_map[ $i ] ) ) continue;

        $post_id       = $post_map[ $i ]['post_id'];
        $original_name = $post_map[ $i ]['original_name'];
        $translated_text = trim( $tr['text'] ?? '' );
        $source_lang     = $tr['detected_source_language'] ?? 'EN';

        // Ha forrás nyelv magyar → kihagyjuk
        if ( strtoupper( $source_lang ) === 'HU' ) {
            $skipped++;

            // Eredeti nevet is mentsük (ha nincs)
            $existing_original = get_field( 'eredeti_nev', $post_id );
            if ( empty( $existing_original ) ) {
                update_field( 'eredeti_nev', $original_name, $post_id );
            }
            update_field( 'forditas_forras', 'magyar_eredeti', $post_id );
            update_field( 'forditas_datum', current_time( 'Y-m-d H:i:s' ), $post_id );
            continue;
        }

        if ( empty( $translated_text ) ) {
            $skipped++;
            continue;
        }

        // Eredeti név mentése
        update_field( 'eredeti_nev', $original_name, $post_id );

        // Post title → fordított név
        wp_update_post( [
            'ID'         => $post_id,
            'post_title' => $translated_text,
        ] );

        // Meta mezők
        update_field( 'forditas_forras', 'deepl', $post_id );
        update_field( 'forditas_datum', current_time( 'Y-m-d H:i:s' ), $post_id );

        $translated++;
    }

    // ── Statisztikák ──
    $state['stats']['translated'] = ( $state['stats']['translated'] ?? 0 ) + $translated;
    $state['stats']['skipped']    = ( $state['stats']['skipped'] ?? 0 ) + $skipped;
    $state['stats']['chars_used'] = ( $state['stats']['chars_used'] ?? 0 ) + $chars_batch;
    $state['offset']              = $offset + $batch_size;
    $state['date']                = current_time( 'Y-m-d H:i:s' );

    update_option( FORDITO_STATE_KEY, $state );

    // API usage tracking
    $usage = get_option( FORDITO_USAGE_KEY, [ 'used' => 0, 'limit' => 500000 ] );
    $usage['used'] = ( $usage['used'] ?? 0 ) + $chars_batch;
    update_option( FORDITO_USAGE_KEY, $usage );

    fordito_log( 'success',
        '✅ Batch #' . $state['batch_num'] . ': ' .
        $translated . ' lefordítva, ' .
        $skipped . ' kihagyva (' .
        $chars_batch . ' karakter)'
    );
}


// ══════════════════════════════════════════════════════════════
// 10. DEEPL API: BATCH FORDÍTÁS
// ══════════════════════════════════════════════════════════════

function fordito_deepl_translate_batch( $texts ) {
    if ( empty( $texts ) ) return false;

    $chars_used = 0;
    foreach ( $texts as $t ) {
        $chars_used += mb_strlen( $t, 'UTF-8' );
    }

    // Body összeállítás (többszörös text paraméter)
    $body_parts = [];
    foreach ( $texts as $text ) {
        $body_parts[] = 'text=' . urlencode( $text );
    }
    $body_parts[] = 'target_lang=HU';

    $body_string = implode( '&', $body_parts );

    for ( $attempt = 1; $attempt <= DEEPL_RETRY_COUNT; $attempt++ ) {
        $response = wp_remote_post( DEEPL_API_URL . '/translate', [
            'timeout' => DEEPL_API_TIMEOUT,
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key ' . DEEPL_API_KEY,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body' => $body_string,
        ] );

        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( $body && isset( $body['translations'] ) ) {
                return [
                    'translations' => $body['translations'],
                    'chars_used'   => $chars_used,
                ];
            }
        }

        if ( $attempt < DEEPL_RETRY_COUNT ) {
            $err = is_wp_error( $response )
                   ? $response->get_error_message()
                   : 'HTTP ' . wp_remote_retrieve_response_code( $response );
            fordito_log( 'warn', '⚠️ DeepL hiba: ' . $err . ' (próba ' . $attempt . '/' . DEEPL_RETRY_COUNT . ')' );
            sleep( DEEPL_RETRY_DELAY );
        }
    }

    return false;
}


// ══════════════════════════════════════════════════════════════
// 11. DEEPL USAGE LEKÉRÉS
// ══════════════════════════════════════════════════════════════

function fordito_refresh_deepl_usage() {
    $response = wp_remote_get( DEEPL_API_URL . '/usage', [
        'timeout' => 10,
        'headers' => [ 'Authorization' => 'DeepL-Auth-Key ' . DEEPL_API_KEY ],
    ] );

    if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $body && isset( $body['character_count'] ) ) {
            $usage = [
                'used'  => intval( $body['character_count'] ),
                'limit' => intval( $body['character_limit'] ),
            ];
            update_option( FORDITO_USAGE_KEY, $usage );
            return $usage;
        }
    }
    return false;
}

// Indításkor frissítjük
function fordito_refresh_usage_on_load( $hook ) {
    if ( $hook === 'alapanyag_page_fordito' ) {
        fordito_refresh_deepl_usage();
    }
}
add_action( 'admin_enqueue_scripts', 'fordito_refresh_usage_on_load', 5 );


// ══════════════════════════════════════════════════════════════
// 12. LOG
// ══════════════════════════════════════════════════════════════

function fordito_log( $type, $msg ) {
    $log   = get_option( FORDITO_LOG_KEY, [] );
    $log[] = [ 'type' => $type, 'msg' => $msg, 'time' => current_time( 'H:i:s' ) ];
    if ( count( $log ) > FORDITO_LOG_MAX ) $log = array_slice( $log, -FORDITO_LOG_MAX );
    update_option( FORDITO_LOG_KEY, $log );
}


// ══════════════════════════════════════════════════════════════
// 13. AJAX: EGYEDI SZÖVEG FORDÍTÁS (teszt)
// ══════════════════════════════════════════════════════════════

function fordito_translate_single_handler() {
    check_ajax_referer( 'fordito_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $text = sanitize_text_field( $_POST['text'] ?? '' );
    if ( empty( $text ) ) wp_send_json_error( 'Üres szöveg.' );

    $result = fordito_deepl_translate_batch( [ $text ] );
    if ( ! $result ) wp_send_json_error( 'DeepL API hiba.' );

    $tr = $result['translations'][0] ?? null;
    if ( ! $tr ) wp_send_json_error( 'Nincs eredmény.' );

    wp_send_json_success( [
        'original'    => $text,
        'translated'  => $tr['text'],
        'source_lang' => $tr['detected_source_language'] ?? '?',
        'chars'       => $result['chars_used'],
    ] );
}
add_action( 'wp_ajax_fordito_translate_single', 'fordito_translate_single_handler' );


// ══════════════════════════════════════════════════════════════
// 14. AJAX: EGY POST FORDÍTÁSA
// ══════════════════════════════════════════════════════════════

function fordito_translate_post_handler() {
    check_ajax_referer( 'fordito_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );
    if ( ! function_exists( 'update_field' ) ) wp_send_json_error( 'ACF nincs.' );

    $post_id = intval( $_POST['post_id'] ?? 0 );
    if ( ! $post_id || get_post_type( $post_id ) !== 'alapanyag' ) wp_send_json_error( 'Érvénytelen.' );

    $post = get_post( $post_id );
    $name = $post->post_title;

    $result = fordito_deepl_translate_batch( [ $name ] );
    if ( ! $result ) wp_send_json_error( 'DeepL hiba.' );

    $tr          = $result['translations'][0];
    $source_lang = strtoupper( $tr['detected_source_language'] ?? 'EN' );
    $translated  = trim( $tr['text'] ?? '' );

    if ( $source_lang === 'HU' ) {
        update_field( 'eredeti_nev', $name, $post_id );
        update_field( 'forditas_forras', 'magyar_eredeti', $post_id );
        update_field( 'forditas_datum', current_time( 'Y-m-d H:i:s' ), $post_id );
        wp_send_json_success( [ 'translated_name' => $name . ' (már magyar)' ] );
    }

    update_field( 'eredeti_nev', $name, $post_id );
    wp_update_post( [ 'ID' => $post_id, 'post_title' => $translated ] );
    update_field( 'forditas_forras', 'deepl', $post_id );
    update_field( 'forditas_datum', current_time( 'Y-m-d H:i:s' ), $post_id );

    wp_send_json_success( [ 'translated_name' => $translated ] );
}
add_action( 'wp_ajax_fordito_translate_post', 'fordito_translate_post_handler' );


// ══════════════════════════════════════════════════════════════
// 15. AJAX: LEFORDÍTATLAN LISTA
// ══════════════════════════════════════════════════════════════

function fordito_list_untranslated_handler() {
    check_ajax_referer( 'fordito_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $filter = sanitize_key( $_POST['filter'] ?? 'all' );

    $meta_query = [
        'relation' => 'OR',
        [ 'key' => 'forditas_forras', 'compare' => 'NOT EXISTS' ],
        [ 'key' => 'forditas_forras', 'value' => '' ],
    ];

    if ( $filter === 'usda' ) {
        $meta_query = [ 'relation' => 'AND', $meta_query, [ 'key' => 'usda_fdc_id', 'compare' => 'EXISTS' ] ];
    } elseif ( $filter === 'off' ) {
        $meta_query = [ 'relation' => 'AND', $meta_query, [ 'key' => 'off_barcode', 'compare' => 'EXISTS' ] ];
    }

    $q = new WP_Query( [
        'post_type' => 'alapanyag', 'posts_per_page' => 20, 'post_status' => 'publish',
        'meta_query' => $meta_query, 'orderby' => 'ID', 'order' => 'ASC',
    ] );

    $items = [];
    foreach ( $q->posts as $p ) {
        $source = '';
        if ( get_post_meta( $p->ID, 'usda_fdc_id', true ) ) $source = 'USDA';
        elseif ( get_post_meta( $p->ID, 'off_barcode', true ) ) $source = 'OFF';
        $items[] = [ 'id' => $p->ID, 'name' => $p->post_title, 'source' => $source ];
    }
    wp_reset_postdata();

    wp_send_json_success( [ 'items' => $items, 'total' => $q->found_posts ] );
}
add_action( 'wp_ajax_fordito_list_untranslated', 'fordito_list_untranslated_handler' );


// ══════════════════════════════════════════════════════════════
// 16. PUBLISH BOX FORDÍTÁS GOMB
// ══════════════════════════════════════════════════════════════

function fordito_publish_box_button() {
    global $post;
    if ( ! $post || $post->post_type !== 'alapanyag' ) return;

    $eredeti_nev = function_exists( 'get_field' ) ? get_field( 'eredeti_nev', $post->ID ) : '';
    $forras      = function_exists( 'get_field' ) ? get_field( 'forditas_forras', $post->ID ) : '';
    $datum       = function_exists( 'get_field' ) ? get_field( 'forditas_datum', $post->ID ) : '';
    $nonce       = wp_create_nonce( 'fordito_nonce' );

    echo '<div class="misc-pub-section" style="padding: 8px 10px;">';

    if ( ! empty( $eredeti_nev ) && $eredeti_nev !== $post->post_title ) {
        echo '<div style="font-size: 0.82rem; color: #666; margin-bottom: 6px;">';
        echo '🌐 Eredeti név: <strong style="color: #374151;">' . esc_html( $eredeti_nev ) . '</strong>';
        echo '</div>';
    }

    if ( $forras ) {
        echo '<div style="font-size: 0.78rem; color: #94a3b8; margin-bottom: 6px;">';
        echo 'Forrás: ' . esc_html( $forras );
        if ( $datum ) echo ' | ' . esc_html( $datum );
        echo '</div>';
    }

    echo '<button type="button" class="button" id="fordito-publish-btn" ';
    echo 'data-post-id="' . esc_attr( $post->ID ) . '" ';
    echo 'data-nonce="' . esc_attr( $nonce ) . '">';
    echo '🌐 Fordítás (DeepL)';
    echo '</button>';
    echo '<span id="fordito-publish-status" style="margin-left: 8px; font-size: 0.85rem;"></span>';
    echo '</div>';

    ?>
    <script>
    document.getElementById('fordito-publish-btn').addEventListener('click', function() {
        var btn = this, st = document.getElementById('fordito-publish-status');
        btn.disabled = true; st.textContent = '⏳...'; st.style.color = '#666';
        var fd = new FormData();
        fd.append('action', 'fordito_translate_post');
        fd.append('nonce', btn.dataset.nonce);
        fd.append('post_id', btn.dataset.postId);
        fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                btn.disabled = false;
                st.textContent = resp.success ? '✅ ' + resp.data.translated_name : '❌ ' + resp.data;
                st.style.color = resp.success ? '#16a34a' : '#dc2626';
                if (resp.success) setTimeout(function() { location.reload(); }, 1500);
            })
            .catch(function() { btn.disabled = false; st.textContent = '❌ Hiba'; st.style.color = '#dc2626'; });
    });
    </script>
    <?php
}
add_action( 'post_submitbox_misc_actions', 'fordito_publish_box_button' );


// ══════════════════════════════════════════════════════════════
// 17. CRON SCHEDULE + BIZTOSÍTÁS
// ══════════════════════════════════════════════════════════════

function fordito_cron_schedules( $schedules ) {
    $schedules['fordito_every_30s'] = [
        'interval' => 30,
        'display'  => 'Fordító: 30 másodpercenként',
    ];
    return $schedules;
}
add_filter( 'cron_schedules', 'fordito_cron_schedules' );

function fordito_ensure_cron_running() {
    $state = get_option( FORDITO_STATE_KEY, [] );
    if ( ( $state['status'] ?? '' ) === 'running' ) {
        if ( ! wp_next_scheduled( FORDITO_CRON_HOOK ) ) {
            wp_schedule_event( time(), 'fordito_every_30s', FORDITO_CRON_HOOK );
        }
    } else {
        if ( wp_next_scheduled( FORDITO_CRON_HOOK ) ) {
            wp_clear_scheduled_hook( FORDITO_CRON_HOOK );
        }
    }
}
add_action( 'admin_init', 'fordito_ensure_cron_running' );
add_action( 'wp_loaded', 'fordito_ensure_cron_running' );
