<?php

/**
 * 20. – USDA FOODDATA CENTRAL IMPORT (v1)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// 21 – USDA FOODDATA CENTRAL IMPORT (v4 FIXED)
//
// JAVÍTÁSOK a v3-hoz képest:
//   1. MUTEX LOCK – csak 1 batch futhat egyszerre
//   2. Page szám BATCH ELEJÉN nő (nem végén)
//   3. $wpdb direct query duplikáció ellenőrzéshez
//   4. FDC ID string-ként tárolva (konzisztencia)
//   5. Leállítás VALÓBAN leállít (lock + state + cron)
//   6. Polling NEM futtat batch-et – csak olvas
//   7. Bezárás/újranyitás után NEM indul újra magától
//
// Motor: TISZTA POLLING
//   - Böngésző nyitva → polling AJAX futtatja a batch-eket
//   - Böngésző bezárva → WP-Cron backup (30mp)
//   - Mutex lock védi a párhuzamos futást
//
// Admin: Alapanyagok → 🇺🇸 USDA Import
// ============================================================


// ══════════════════════════════════════════════════════════════
// KONSTANSOK
// ══════════════════════════════════════════════════════════════

if ( ! defined( 'USDA_API_KEY' ) ) {
    define( 'USDA_API_KEY', 'lm5jXaulLW6h7sqHHCce0foTzFUHt4tSmQJU7Yf0' );
}

define( 'USDA_IMPORT_STATE_KEY',   'usda_import_state_v4' );
define( 'USDA_IMPORT_LOG_KEY',     'usda_import_log_v4' );
define( 'USDA_IMPORT_LASTRUN_KEY', 'usda_import_last_run_time_v4' );
define( 'USDA_IMPORT_LOCK_KEY',    'usda_import_lock_v4' );
define( 'USDA_CRON_HOOK',          'usda_import_cron_batch_v4' );

define( 'USDA_API_RATE_LIMIT',     1000 );
define( 'USDA_API_RATE_PER_MIN',   16 );
define( 'USDA_API_MIN_DELAY',      4 );
define( 'USDA_API_MAX_BATCH',      200 );
define( 'USDA_API_RETRY_COUNT',    3 );
define( 'USDA_API_RETRY_DELAY',    10 );
define( 'USDA_API_TIMEOUT',        60 );
define( 'USDA_EMPTY_BATCH_LIMIT',  10 );
define( 'USDA_LOG_MAX_ENTRIES',    200 );
define( 'USDA_LOCK_TIMEOUT',       120 ); // lock lejárat (mp) – ha crash, 2 perc után felszabadul


// ══════════════════════════════════════════════════════════════
// MUTEX LOCK – CSAK 1 BATCH FUTHAT EGYSZERRE
// ══════════════════════════════════════════════════════════════

function usda_acquire_lock() {
    global $wpdb;
    $now  = time();
    $lock = get_option( USDA_IMPORT_LOCK_KEY, 0 );

    // Ha van lock és nem járt le → nem kapjuk meg
    if ( $lock > 0 && ( $now - $lock ) < USDA_LOCK_TIMEOUT ) {
        return false;
    }

    // Atomi lock szerzés – csak ha a régi érték megegyezik
    $updated = $wpdb->update(
        $wpdb->options,
        [ 'option_value' => $now ],
        [ 'option_name' => USDA_IMPORT_LOCK_KEY, 'option_value' => $lock ],
        [ '%d' ],
        [ '%s', '%s' ]
    );

    if ( $updated === 0 ) {
        // Lehet, hogy az option nem létezik még
        if ( get_option( USDA_IMPORT_LOCK_KEY ) === false ) {
            add_option( USDA_IMPORT_LOCK_KEY, $now, '', 'no' );
            return true;
        }
        return false; // Valaki más szerezte meg közben
    }

    // Töröljük a WP cache-t hogy friss legyen
    wp_cache_delete( USDA_IMPORT_LOCK_KEY, 'options' );

    return true;
}

function usda_release_lock() {
    update_option( USDA_IMPORT_LOCK_KEY, 0 );
    wp_cache_delete( USDA_IMPORT_LOCK_KEY, 'options' );
}


// ══════════════════════════════════════════════════════════════
// DUPLIKÁCIÓ ELLENŐRZÉS – $wpdb DIRECT QUERY
// ══════════════════════════════════════════════════════════════

function usda_fdc_id_exists( $fdc_id ) {
    global $wpdb;
    $fdc_id_str = strval( $fdc_id );

    $result = $wpdb->get_var( $wpdb->prepare(
        "SELECT pm.post_id
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = 'usda_fdc_id'
           AND pm.meta_value = %s
           AND p.post_type = 'alapanyag'
           AND p.post_status IN ('publish', 'draft', 'pending', 'private')
         LIMIT 1",
        $fdc_id_str
    ) );

    return ! empty( $result );
}


// ══════════════════════════════════════════════════════════════
// USDA NUTRIENT ID → ACF MEZŐ MAPPING
// ══════════════════════════════════════════════════════════════

function usda_get_nutrient_mapping() {
    return [
        1008 => [ 'acf_key' => 'kaloria',                      'label' => 'Kalória',                       'egyseg' => 'kcal' ],
        1003 => [ 'acf_key' => 'feherje',                      'label' => 'Fehérje',                       'egyseg' => 'g' ],
        1005 => [ 'acf_key' => 'szenhidrat',                   'label' => 'Szénhidrát',                    'egyseg' => 'g' ],
        1004 => [ 'acf_key' => 'zsir',                         'label' => 'Zsír',                          'egyseg' => 'g' ],
        1079 => [ 'acf_key' => 'rost',                         'label' => 'Rost',                          'egyseg' => 'g' ],
        2000 => [ 'acf_key' => 'cukor',                        'label' => 'Cukor',                         'egyseg' => 'g' ],
        1258 => [ 'acf_key' => 'telitett',                     'label' => 'Telített zsír',                 'egyseg' => 'g' ],
        1292 => [ 'acf_key' => 'egyszeresen_telitetlen_zsir',  'label' => 'Egyszeresen telítetlen zsír',   'egyseg' => 'g' ],
        1293 => [ 'acf_key' => 'tobbszorosen_telitetlen_zsir', 'label' => 'Többszörösen telítetlen zsír',  'egyseg' => 'g' ],
        1253 => [ 'acf_key' => 'koleszterin',                  'label' => 'Koleszterin',                   'egyseg' => 'mg' ],
        1093 => [ 'acf_key' => 'natrium',                      'label' => 'Nátrium',                       'egyseg' => 'mg' ],
        1092 => [ 'acf_key' => 'kalium',                       'label' => 'Kálium',                        'egyseg' => 'mg' ],
        1087 => [ 'acf_key' => 'kalcium',                      'label' => 'Kalcium',                       'egyseg' => 'mg' ],
        1089 => [ 'acf_key' => 'vas',                          'label' => 'Vas',                           'egyseg' => 'mg' ],
        1090 => [ 'acf_key' => 'magnezium',                    'label' => 'Magnézium',                     'egyseg' => 'mg' ],
        1091 => [ 'acf_key' => 'foszfor',                      'label' => 'Foszfor',                       'egyseg' => 'mg' ],
        1095 => [ 'acf_key' => 'cink',                         'label' => 'Cink',                          'egyseg' => 'mg' ],
        1098 => [ 'acf_key' => 'rez',                          'label' => 'Réz',                           'egyseg' => 'mg' ],
        1101 => [ 'acf_key' => 'mangan',                       'label' => 'Mangán',                        'egyseg' => 'mg' ],
        1103 => [ 'acf_key' => 'szelen',                       'label' => 'Szelén',                        'egyseg' => 'µg' ],
        1106 => [ 'acf_key' => 'vitamin_a',                    'label' => 'A-vitamin',                     'egyseg' => 'µg' ],
        1162 => [ 'acf_key' => 'vitamin_c',                    'label' => 'C-vitamin',                     'egyseg' => 'mg' ],
        1114 => [ 'acf_key' => 'vitamin_d',                    'label' => 'D-vitamin',                     'egyseg' => 'µg' ],
        1109 => [ 'acf_key' => 'vitamin_e',                    'label' => 'E-vitamin',                     'egyseg' => 'mg' ],
        1185 => [ 'acf_key' => 'vitamin_k',                    'label' => 'K-vitamin',                     'egyseg' => 'µg' ],
        1165 => [ 'acf_key' => 'vitamin_b1',                   'label' => 'B1-vitamin (Tiamin)',           'egyseg' => 'mg' ],
        1166 => [ 'acf_key' => 'vitamin_b2',                   'label' => 'B2-vitamin (Riboflavin)',       'egyseg' => 'mg' ],
        1167 => [ 'acf_key' => 'vitamin_b3',                   'label' => 'B3-vitamin (Niacin)',           'egyseg' => 'mg' ],
        1170 => [ 'acf_key' => 'vitamin_b5',                   'label' => 'B5-vitamin (Pantoténsav)',      'egyseg' => 'mg' ],
        1175 => [ 'acf_key' => 'vitamin_b6',                   'label' => 'B6-vitamin',                    'egyseg' => 'mg' ],
        1177 => [ 'acf_key' => 'vitamin_b9',                   'label' => 'B9-vitamin (Folát)',            'egyseg' => 'µg' ],
        1178 => [ 'acf_key' => 'vitamin_b12',                  'label' => 'B12-vitamin',                   'egyseg' => 'µg' ],
    ];
}


// ══════════════════════════════════════════════════════════════
// USDA ADATKÉSZLET (KATEGÓRIA) LEÍRÁSOK
// ══════════════════════════════════════════════════════════════

function usda_get_dataset_descriptions() {
    return [
        'Foundation' => [
            'hu'    => 'Foundation Foods – Laboratóriumi alapanyagok',
            'db'    => '~287',
            'desc'  => 'Az USA Mezőgazdasági Minisztériuma (USDA) által laboratóriumban elemzett, nyers alapanyagok. Minden tápérték közvetlen kémiai analízisből származik – nem csomagolásról leolvasott, nem becsült adat. A legpontosabb élelmiszer-összetételi adatbázis a világon.',
            'mikor' => 'Folyamatosan frissül (évente 2×), utolsó frissítés: 2024',
            'hol'   => 'USDA Agricultural Research Service (ARS), Beltsville, Maryland, USA',
            'mivel' => 'Kromatográfia, spektrometria, Kjeldahl-módszer (fehérje), Soxhlet-extrakció (zsír), bomb-kalorimetria (kalória). Minden mintát akkreditált laboratóriumban elemeztek.',
        ],
        'SR Legacy' => [
            'hu'    => 'Standard Reference Legacy – Átfogó referencia adatbázis',
            'db'    => '~7 793',
            'desc'  => 'Az USDA korábbi Standard Reference adatbázisa (SR28). Több ezer általános élelmiszer tápértékét tartalmazza. Vegyesen tartalmaz labor-elemzett és számított/becsült adatokat. Már nem frissül, de a legátfogóbb nyers alapanyag adatbázis.',
            'mikor' => 'Utolsó kiadás: 2018 (SR28) – azóta nem frissül, historikus referencia',
            'hol'   => 'USDA Nutrient Data Laboratory, Beltsville, Maryland, USA',
            'mivel' => 'Vegyes: laboratóriumi analízis + számított értékek + irodalmi adatok. A régebbi bejegyzéseknél az analitikai módszer nem mindig dokumentált.',
        ],
        'Survey (FNDDS)' => [
            'hu'    => 'FNDDS – Táplálkozási felmérés adatbázis',
            'db'    => '~10 000',
            'desc'  => 'Food and Nutrient Database for Dietary Studies. Az amerikaiak által ténylegesen fogyasztott ételeket tartalmazza – beleértve kész ételeket, éttermi fogásokat, összetett recepteket. A nemzeti táplálkozási felmérésekhez (NHANES) készült.',
            'mikor' => 'Rendszeresen frissül a NHANES felmérési ciklusokkal (2 évente)',
            'hol'   => 'USDA Food Surveys Research Group, Beltsville, Maryland, USA',
            'mivel' => 'Vegyes: labor-analízis + receptúra alapú számítás + gyártói adatok. Az összetett ételek tápértékét az összetevők alapján kalkulálják.',
        ],
        'Branded' => [
            'hu'    => 'Branded Foods – Márkás/bolti termékek',
            'db'    => '~400 000+',
            'desc'  => 'Boltokban kapható, márkás termékek tápérték adatai – a gyártók által megadott címke információk alapján. Hasonló az OpenFoodFacts-hoz. GTIN/UPC vonalkódokat is tartalmaz.',
            'mikor' => 'Havonta frissül (gyártói beküldés alapján)',
            'hol'   => 'USDA FoodData Central + Label Insight (gyártói adatszolgáltatás)',
            'mivel' => 'Csomagolási címke adatok – a gyártó által deklarált értékek. Nem laboratóriumi analízis, hanem a termék tápérték jelölése (Nutrition Facts label).',
        ],
        'Foundation,SR Legacy' => [
            'hu'    => 'Foundation + SR Legacy – Mindkét referencia adatbázis',
            'db'    => '~8 080',
            'desc'  => 'A két legfontosabb nyers alapanyag adatbázis együtt. A Foundation a legpontosabb (labor), az SR Legacy a legátfogóbb (7 793 élelmiszer). Együtt lefedik az összes jelentős nyers alapanyagot.',
            'mikor' => 'Foundation: 2024, SR Legacy: 2018',
            'hol'   => 'USDA Agricultural Research Service, Beltsville, Maryland, USA',
            'mivel' => 'Foundation: kizárólag labor-analízis. SR Legacy: vegyes (labor + számított). Az import során a Foundation adatok prioritást élveznek ha mindkettőben megvan.',
        ],
    ];
}


// ══════════════════════════════════════════════════════════════
// 1. ADMIN MENÜ
// ══════════════════════════════════════════════════════════════

function usda_import_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=alapanyag',
        'USDA Import',
        '🇺🇸 USDA Import',
        'manage_options',
        'usda-import',
        'usda_import_admin_page'
    );
}
add_action( 'admin_menu', 'usda_import_admin_menu' );


// ══════════════════════════════════════════════════════════════
// 2. ADMIN OLDAL HTML
// ══════════════════════════════════════════════════════════════

function usda_import_admin_page() {

    // Meglévő USDA alapanyagok száma – $wpdb direct
    global $wpdb;
    $existing_count = intval( $wpdb->get_var(
        "SELECT COUNT(DISTINCT pm.post_id)
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = 'usda_fdc_id'
           AND pm.meta_value != ''
           AND p.post_type = 'alapanyag'
           AND p.post_status = 'publish'"
    ) );

    // Összes alapanyag
    $total       = wp_count_posts( 'alapanyag' );
    $total_count = intval( $total->publish ?? 0 );

    // Mentett állapot
    $state      = get_option( USDA_IMPORT_STATE_KEY, [] );
    $is_running = ( ( $state['status'] ?? '' ) === 'running' );
    $has_saved  = ! empty( $state['page'] )
                  && intval( $state['page'] ) > 1
                  && ! $is_running;

    // API key check
    $has_api_key = defined( 'USDA_API_KEY' ) && ! empty( USDA_API_KEY );

    // Kategória leírások JSON-ként a JS-nek
    $datasets = usda_get_dataset_descriptions();

    ?>
    <div class="wrap">
        <h1>🇺🇸 USDA FoodData Central – Import</h1>

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
                    <td><strong>Összes alapanyag</strong></td>
                    <td>
                        <strong style="font-size: 1.2rem;"><?php echo $total_count; ?></strong> db
                    </td>
                </tr>
                <tr>
                    <td><strong>USDA-ból importált</strong></td>
                    <td>
                        <strong style="font-size: 1.2rem; color: #2563eb;">
                            <?php echo $existing_count; ?>
                        </strong> db
                    </td>
                </tr>
                <tr>
                    <td><strong>API limit</strong></td>
                    <td>
                        <span style="color: #dc2626; font-weight: 600;">
                            1 000 kérés / óra
                        </span>
                        (~16 / perc, 1 batch = 1 kérés)
                    </td>
                </tr>
                <tr>
                    <td><strong>Motor</strong></td>
                    <td>🔄 Hibrid (böngésző polling + WP-Cron háttér) + Mutex lock</td>
                </tr>
                <tr>
                    <td><strong>Licenc</strong></td>
                    <td>CC0 Public Domain (ingyenes, korlátlan)</td>
                </tr>
                <tr>
                    <td><strong>API Key</strong></td>
                    <td>
                        <?php if ( $has_api_key ) : ?>
                            <span style="color: #16a34a;">✅ Beállítva</span>
                            <span style="color: #94a3b8; font-size: 0.82rem;">
                                (...<?php echo substr( USDA_API_KEY, -6 ); ?>)
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
                            <span style="
                                color: #16a34a;
                                font-weight: 700;
                                animation: usdaPulse 1.5s infinite;
                            ">
                                🟢 FUT
                            </span>
                            – Oldal <?php echo intval( $state['page'] ?? 1 ); ?>
                            | +<?php echo intval( $state['stats']['created'] ?? 0 ); ?> új
                            | <?php echo esc_html( $state['dataset_label'] ?? '' ); ?>
                        </td>
                    </tr>
                <?php elseif ( $has_saved ) : ?>
                    <tr>
                        <td><strong>Mentett pozíció</strong></td>
                        <td>
                            <span style="color: #2563eb;">
                                📌 Oldal <?php echo intval( $state['page'] ); ?>
                                | <?php echo esc_html( $state['dataset_label'] ?? '' ); ?>
                                | +<?php echo intval( $state['stats']['created'] ?? 0 ); ?> importálva
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

        <div id="usda-settings-box" style="
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

                <div>
                    <label for="usda-dataset" style="font-weight: 600; font-size: 0.88rem; display: block; margin-bottom: 4px;">
                        Adatkészlet:
                    </label>
                    <select id="usda-dataset" style="width: 100%;">
                        <?php foreach ( $datasets as $key => $info ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>"
                                    <?php echo $key === 'Foundation' ? 'selected' : ''; ?>>
                                <?php echo esc_html( $info['hu'] ); ?>
                                (<?php echo esc_html( $info['db'] ); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="usda-batch-size" style="font-weight: 600; font-size: 0.88rem; display: block; margin-bottom: 4px;">
                        Batch méret:
                    </label>
                    <input type="number" id="usda-batch-size" value="50" min="10" max="200" step="10" style="width: 100%;">
                    <span style="color: #888; font-size: 0.75rem;">10–200 / kör (API max: 200)</span>
                </div>

                <div>
                    <label for="usda-batch-delay" style="font-weight: 600; font-size: 0.88rem; display: block; margin-bottom: 4px;">
                        Delay (mp):
                    </label>
                    <input type="number" id="usda-batch-delay" value="5" min="4" max="60" step="1" style="width: 100%;">
                    <span style="color: #888; font-size: 0.75rem;">min: 4 mp (ajánlott: 5)</span>
                </div>

                <div>
                    <label for="usda-max" style="font-weight: 600; font-size: 0.88rem; display: block; margin-bottom: 4px;">
                        Maximum (új):
                    </label>
                    <input type="number" id="usda-max" value="500" min="10" max="50000" step="10" style="width: 100%;">
                    <span style="color: #888; font-size: 0.75rem;">0 = nincs limit</span>
                </div>
            </div>

            <div id="usda-dataset-info" style="
                margin-top: 12px;
                padding: 14px 16px;
                background: #eff6ff;
                border: 1px solid #bfdbfe;
                border-radius: 6px;
                font-size: 0.85rem;
                color: #1e3a5f;
                line-height: 1.6;
            "></div>

            <div id="usda-limit-warning" style="
                margin-top: 12px;
                padding: 10px 14px;
                border-radius: 6px;
                font-size: 0.85rem;
                display: none;
            "></div>

            <div id="usda-time-estimate-box" style="
                margin-top: 12px;
                padding: 10px 14px;
                background: #fffbeb;
                border-radius: 6px;
                font-size: 0.85rem;
                color: #92400e;
            ">
                💡 <strong>Becsült idő</strong>:
                <span id="usda-time-estimate">–</span>
                (<span id="usda-batch-count-est">–</span> batch
                × <span id="usda-delay-est">–</span>s)
                | API kérés/perc:
                <strong><span id="usda-req-per-min">–</span></strong> / 16
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

            <div style="
                background: #f0fdf4;
                border: 2px solid #86efac;
                border-radius: 8px;
                padding: 18px 20px;
            ">
                <h3 style="margin-top: 0; color: #16a34a; font-size: 1rem;">🚀 Új import</h3>
                <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                    Az <strong>elejétől</strong> indul. Amíg ez az oldal nyitva van, automatikusan fut.
                </p>
                <button id="usda-import-start" class="button button-primary"
                        style="font-size: 13px; padding: 5px 16px;"
                        <?php echo ( $is_running || ! $has_api_key ) ? 'disabled' : ''; ?>>
                    🚀 Indítás (elejétől)
                </button>
            </div>

            <div style="
                background: <?php echo $has_saved ? '#eff6ff' : ( $is_running ? '#f0fdf4' : '#f8fafc' ); ?>;
                border: 2px solid <?php echo $has_saved ? '#93c5fd' : ( $is_running ? '#86efac' : '#e2e8f0' ); ?>;
                border-radius: 8px;
                padding: 18px 20px;
            ">
                <h3 style="margin-top: 0; color: #2563eb; font-size: 1rem;">▶️ Folytatás</h3>

                <?php if ( $is_running ) : ?>
                    <p style="font-size: 0.82rem; color: #16a34a; line-height: 1.4; font-weight: 600;">
                        🟢 Jelenleg fut...
                    </p>
                    <button class="button" disabled style="font-size: 13px; padding: 5px 16px;">▶️ Fut...</button>
                <?php elseif ( $has_saved ) : ?>
                    <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                        Utolsó: <strong>oldal <?php echo intval( $state['page'] ); ?></strong>
                        (+<?php echo intval( $state['stats']['created'] ?? 0 ); ?> importálva)
                        <br><?php echo esc_html( $state['date'] ?? '' ); ?>
                    </p>
                    <button id="usda-import-continue" class="button"
                            style="font-size: 13px; padding: 5px 16px; background: #3b82f6; color: #fff; border-color: #2563eb;">
                        ▶️ Folytatás
                    </button>
                    <button id="usda-import-reset" class="button"
                            style="font-size: 12px; padding: 4px 10px; color: #dc2626; margin-left: 4px;"
                            title="Mentett pozíció törlése">
                        🗑️
                    </button>
                <?php else : ?>
                    <p style="font-size: 0.82rem; color: #999; line-height: 1.4;">Nincs mentett pozíció.</p>
                    <button class="button" disabled style="font-size: 13px; padding: 5px 16px;">▶️ Nincs mentett adat</button>
                <?php endif; ?>
            </div>

            <div style="
                background: #fffbeb;
                border: 2px solid #fcd34d;
                border-radius: 8px;
                padding: 18px 20px;
            ">
                <h3 style="margin-top: 0; color: #d97706; font-size: 1rem;">🔄 Szinkron</h3>
                <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                    Meglévő USDA alapanyagokat ellenőrzi. <strong>Eltéréseket mutat.</strong>
                </p>
                <button id="usda-sync-start" class="button"
                        style="font-size: 13px; padding: 5px 16px; background: #f59e0b; color: #fff; border-color: #d97706;"
                        <?php echo ( $existing_count < 1 || $is_running ) ? 'disabled' : ''; ?>>
                    🔄 Szinkron
                </button>
            </div>
        </div>

        <div style="margin: 0 0 12px;">
            <button id="usda-stop" class="button button-secondary"
                    style="font-size: 14px; padding: 6px 20px; <?php echo $is_running ? '' : 'display: none;'; ?>">
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
            <h3 style="margin-top: 0;">🔍 Egyedi USDA keresés</h3>
            <p style="font-size: 0.88rem; color: #555;">
                Keress rá egy alapanyagra az USDA-ban és importáld egyenként.
            </p>

            <div style="display: flex; gap: 8px;">
                <input type="text" id="usda-search-query"
                       placeholder="pl. chicken breast, apple, rice, beef..."
                       style="flex: 1; font-size: 14px; padding: 6px 12px;">

                <select id="usda-search-type" style="font-size: 14px; padding: 6px 8px;">
                    <option value="Foundation,SR Legacy">Foundation + SR Legacy</option>
                    <option value="Foundation">Csak Foundation</option>
                    <option value="SR Legacy">Csak SR Legacy</option>
                    <option value="Branded">Branded (bolti)</option>
                </select>

                <button id="usda-search-btn" class="button" style="font-size: 14px; padding: 6px 16px;">
                    🔍 Keresés
                </button>
            </div>

            <div id="usda-search-results" style="margin-top: 12px;"></div>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── ÉLŐ STÁTUSZ + LOG ──────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div id="usda-live-status" style="
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
                <span id="usda-live-indicator" style="color: #16a34a; animation: usdaPulse 1.5s infinite;">●</span>
            </h3>

            <div style="background: #f0f0f1; border-radius: 6px; height: 28px; overflow: hidden; margin-bottom: 12px;">
                <div id="usda-progress-bar" style="
                    background: linear-gradient(90deg, #3b82f6, #6366f1);
                    height: 100%; width: 0%; transition: width 0.4s ease;
                    border-radius: 6px; display: flex; align-items: center;
                    justify-content: center; color: #fff; font-weight: 600; font-size: 0.85rem;
                ">0%</div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; margin-bottom: 12px;">
                <div style="text-align: center; padding: 8px; background: #f0f6fc; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700;" id="usda-s-processed">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Feldolgozva</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #f0fdf4; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #16a34a;" id="usda-s-created">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Új létrehozva</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #fffbeb; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #d97706;" id="usda-s-skipped">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Kihagyva</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #fef2f2; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #dc2626;" id="usda-s-errors">0</div>
                    <div style="font-size: 0.72rem; color: #666;">API hiba</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #f5f3ff; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #7c3aed;" id="usda-s-empty">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Üres batch</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #f0f6fc; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #2563eb;" id="usda-s-page">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Aktuális oldal</div>
                </div>
            </div>

            <div id="usda-live-info" style="
                font-size: 0.85rem; color: #666; margin-bottom: 8px;
                padding: 6px 10px; background: #f8fafc; border-radius: 4px;
            "></div>

            <div id="usda-log" style="
                background: #1e1e1e; color: #d4d4d4; font-family: monospace;
                font-size: 0.82rem; padding: 12px 16px; border-radius: 6px;
                max-height: 300px; overflow-y: auto; line-height: 1.6;
            "></div>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── SZINKRON ELTÉRÉSEK ──────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div id="usda-diff-section" style="
            display: none; background: #fff; border: 2px solid #3b82f6;
            border-radius: 8px; padding: 20px 24px; margin: 20px 0; max-width: 900px;
        ">
            <h3 style="margin-top: 0; color: #2563eb;">
                ⚠️ Eltérések – <span id="usda-diff-count">0</span> alapanyagnál
            </h3>
            <div style="margin-bottom: 12px;">
                <label style="font-size: 0.88rem; cursor: pointer;">
                    <input type="checkbox" id="usda-diff-select-all" checked> Összes kijelölése
                </label>
            </div>
            <div id="usda-diff-list" style="max-height: 500px; overflow-y: auto;"></div>
            <div style="margin-top: 16px;">
                <button id="usda-diff-apply" class="button button-primary"
                        style="font-size: 14px; padding: 6px 20px;">
                    ✅ Kijelöltek frissítése
                </button>
                <span id="usda-diff-apply-status" style="margin-left: 12px; font-size: 0.88rem;"></span>
            </div>
        </div>

    </div>

    <style>
        @keyframes usdaPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
    </style>
    <?php
}


// ══════════════════════════════════════════════════════════════
// 3. ADMIN SCRIPTS (JS) – v4 FIXED
// ══════════════════════════════════════════════════════════════

function usda_import_admin_scripts( $hook ) {
    if ( $hook !== 'alapanyag_page_usda-import' ) return;

    $state         = get_option( USDA_IMPORT_STATE_KEY, [] );
    $datasets_json = wp_json_encode( usda_get_dataset_descriptions() );

    $js = <<<JSEOF
(function() {
    'use strict';

    var \$ = document.getElementById.bind(document);
    var pollTimer = null;
    var isStopping = false;
    var syncAllDiffs = [];
    var datasetDescriptions = {$datasets_json};

    function updateDatasetInfo() {
        var val  = \$('usda-dataset').value;
        var info = datasetDescriptions[val];
        if (!info) { \$('usda-dataset-info').innerHTML = ''; return; }
        \$('usda-dataset-info').innerHTML =
            '<div style="margin-bottom:6px;"><strong>📚 ' + escHtml(info.hu) + '</strong> <span style="color:#64748b;">(' + escHtml(info.db) + ' db)</span></div>' +
            '<div style="margin-bottom:4px;">' + escHtml(info.desc) + '</div>' +
            '<div style="display:grid;grid-template-columns:auto 1fr;gap:2px 10px;margin-top:8px;font-size:0.82rem;color:#475569;">' +
                '<strong>📅 Mikor:</strong><span>' + escHtml(info.mikor) + '</span>' +
                '<strong>📍 Hol:</strong><span>' + escHtml(info.hol) + '</span>' +
                '<strong>🔬 Mivel mérték:</strong><span>' + escHtml(info.mivel) + '</span>' +
            '</div>';
    }
    \$('usda-dataset').addEventListener('change', updateDatasetInfo);
    updateDatasetInfo();

    function validateSettings() {
        var batchSize = parseInt(\$('usda-batch-size').value) || 50;
        var delay     = parseFloat(\$('usda-batch-delay').value) || 5;
        var maxItems  = parseInt(\$('usda-max').value) || 500;
        var reqPerMin = Math.round(60 / delay);
        var batches   = Math.ceil(maxItems / batchSize);
        var totalSec  = batches * delay;
        var min = Math.floor(totalSec / 60);
        var sec = Math.round(totalSec % 60);
        var timeStr = (min > 0 ? '~' + min + ' perc ' : '~') + sec + ' mp';

        \$('usda-req-per-min').textContent     = reqPerMin;
        \$('usda-batch-count-est').textContent  = batches;
        \$('usda-delay-est').textContent        = delay;
        \$('usda-time-estimate').textContent    = timeStr;

        var warn = \$('usda-limit-warning');
        var startBtn = \$('usda-import-start');
        var contBtn  = \$('usda-import-continue');

        if (batchSize > 200) {
            warn.style.display = 'block'; warn.style.background = '#fef2f2'; warn.style.color = '#dc2626';
            warn.innerHTML = '🚫 <strong>Batch méret max 200!</strong>';
            if (startBtn) startBtn.disabled = true;
            if (contBtn)  contBtn.disabled  = true;
            return false;
        }
        if (reqPerMin > 16) {
            warn.style.display = 'block'; warn.style.background = '#fef2f2'; warn.style.color = '#dc2626';
            warn.innerHTML = '🚫 <strong>Túl gyors!</strong> ' + reqPerMin + '/perc &gt; 16. Növeld a delay-t.';
            if (startBtn) startBtn.disabled = true;
            if (contBtn)  contBtn.disabled  = true;
            return false;
        }
        if (reqPerMin > 12) {
            warn.style.display = 'block'; warn.style.background = '#fffbeb'; warn.style.color = '#92400e';
            warn.innerHTML = '⚠️ <strong>Közel a limithez!</strong> ' + reqPerMin + '/perc. Ajánlott: 5+ mp.';
            if (startBtn) startBtn.disabled = false;
            if (contBtn)  contBtn.disabled  = false;
            return true;
        }
        warn.style.display = 'none';
        if (startBtn) startBtn.disabled = false;
        if (contBtn)  contBtn.disabled  = false;
        return true;
    }
    \$('usda-batch-size').addEventListener('input', validateSettings);
    \$('usda-batch-delay').addEventListener('input', validateSettings);
    \$('usda-max').addEventListener('input', validateSettings);
    validateSettings();

    function startImport(fromPage) {
        if (!validateSettings()) return;
        isStopping = false;
        var fd = new FormData();
        fd.append('action', 'usda_cron_start'); fd.append('nonce', usdaData.nonce);
        fd.append('mode', 'import'); fd.append('page', fromPage);
        fd.append('dataset', \$('usda-dataset').value);
        fd.append('batch_size', \$('usda-batch-size').value);
        fd.append('batch_delay', \$('usda-batch-delay').value);
        fd.append('max_items', \$('usda-max').value);
        fetch(usdaData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) { if (resp.success) location.reload(); else alert('Hiba: ' + resp.data); });
    }

    \$('usda-import-start').addEventListener('click', function() { startImport(1); });
    if (\$('usda-import-continue')) {
        \$('usda-import-continue').addEventListener('click', function() {
            startImport(parseInt(usdaData.savedState.page) || 1);
        });
    }
    if (\$('usda-import-reset')) {
        \$('usda-import-reset').addEventListener('click', function() {
            if (!confirm('Biztosan törlöd a mentett pozíciót?')) return;
            var fd = new FormData();
            fd.append('action', 'usda_cron_reset'); fd.append('nonce', usdaData.nonce);
            fetch(usdaData.ajaxUrl, { method: 'POST', body: fd }).then(function() { location.reload(); });
        });
    }

    // LEÁLLÍTÁS – JAVÍTOTT
    \$('usda-stop').addEventListener('click', function() {
        isStopping = true;
        this.disabled = true;
        this.textContent = '⏳ Leállítás...';
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        var fd = new FormData();
        fd.append('action', 'usda_cron_stop'); fd.append('nonce', usdaData.nonce);
        fetch(usdaData.ajaxUrl, { method: 'POST', body: fd })
            .then(function() { setTimeout(function() { location.reload(); }, 1000); })
            .catch(function() { setTimeout(function() { location.reload(); }, 1000); });
    });

    // SZINKRON
    \$('usda-sync-start').addEventListener('click', function() {
        if (!validateSettings()) return;
        isStopping = false;
        var fd = new FormData();
        fd.append('action', 'usda_cron_start'); fd.append('nonce', usdaData.nonce);
        fd.append('mode', 'sync'); fd.append('page', 1); fd.append('dataset', '');
        fd.append('batch_size', \$('usda-batch-size').value);
        fd.append('batch_delay', \$('usda-batch-delay').value);
        fd.append('max_items', 99999);
        fetch(usdaData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) { if (resp.success) location.reload(); else alert('Hiba: ' + resp.data); });
    });

    // POLLING – JAVÍTOTT: run_batch flag + isStopping védi
    function pollStatus() {
        if (isStopping) return;
        var fd = new FormData();
        fd.append('action', 'usda_cron_status'); fd.append('nonce', usdaData.nonce);
        fd.append('run_batch', '1');
        fetch(usdaData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (!resp.success || isStopping) return;
                var s = resp.data;
                \$('usda-s-processed').textContent = s.processed || 0;
                \$('usda-s-created').textContent   = s.created || 0;
                \$('usda-s-skipped').textContent   = s.skipped || 0;
                \$('usda-s-errors').textContent    = s.errors || 0;
                \$('usda-s-empty').textContent     = s.empty_batches || 0;
                \$('usda-s-page').textContent      = s.page || 0;
                var pct = s.total > 0 ? Math.min(100, Math.round(((s.page * s.batch_size) / s.total) * 100)) : 0;
                \$('usda-progress-bar').style.width = pct + '%';
                \$('usda-progress-bar').textContent = pct + '%';
                \$('usda-live-info').innerHTML =
                    'Adatkészlet: <strong>' + escHtml(s.dataset_label || s.dataset || '?') + '</strong>' +
                    ' | Batch: <strong>' + s.batch_size + '</strong>' +
                    ' | Delay: <strong>' + s.batch_delay + 's</strong>' +
                    ' | Összesen (API): <strong>' + (s.total || '?') + '</strong>';
                if (s.recent_log && s.recent_log.length > 0) {
                    var logEl = \$('usda-log'), logHtml = '';
                    s.recent_log.forEach(function(entry) {
                        var colors = {info:'#60a5fa',success:'#4ade80',warn:'#fbbf24',error:'#f87171',skip:'#c084fc'};
                        logHtml += '<div style="color:' + (colors[entry.type]||'#d4d4d4') + '">[' + entry.time + '] ' + entry.msg + '</div>';
                    });
                    logEl.innerHTML = logHtml;
                    logEl.scrollTop = logEl.scrollHeight;
                }
                if (s.status !== 'running') {
                    clearInterval(pollTimer); pollTimer = null;
                    \$('usda-live-indicator').textContent = '⏹';
                    \$('usda-live-indicator').style.animation = 'none';
                    \$('usda-stop').style.display = 'none';
                    if (s.status === 'done_sync' && s.diffs && s.diffs.length > 0) {
                        syncAllDiffs = s.diffs; showDiffs();
                    }
                }
            }).catch(function() {});
    }
    if (usdaData.isRunning) {
        \$('usda-live-status').style.display = 'block';
        \$('usda-stop').style.display = 'inline-block';
        pollTimer = setInterval(pollStatus, 3000);
        pollStatus();
    }

    // SZINKRON ELTÉRÉSEK
    function showDiffs() {
        \$('usda-diff-section').style.display = 'block';
        \$('usda-diff-count').textContent = syncAllDiffs.length;
        var html = '';
        syncAllDiffs.forEach(function(item, idx) {
            html += '<div style="border:1px solid #e5e7eb;border-radius:6px;margin-bottom:8px;padding:12px;">';
            html += '<label style="display:flex;align-items:start;gap:10px;cursor:pointer;">';
            html += '<input type="checkbox" class="usda-diff-cb" data-index="' + idx + '" checked style="margin-top:4px;">';
            html += '<div style="flex:1;"><strong>' + escHtml(item.name) + '</strong>';
            html += ' <span style="color:#999;font-size:0.82rem;">(#' + item.post_id + ' | FDC: ' + item.fdc_id + ')</span>';
            html += '<div style="margin-top:6px;font-size:0.82rem;">';
            item.changes.forEach(function(ch) {
                html += '<div style="display:grid;grid-template-columns:160px 100px 20px 100px;gap:4px;padding:2px 0;">';
                html += '<span style="color:#666;">' + ch.label + ':</span>';
                html += '<span style="color:#dc2626;text-decoration:line-through;">' + ch.old_value + ' ' + ch.egyseg + '</span>';
                html += '<span>→</span>';
                html += '<span style="color:#16a34a;font-weight:600;">' + ch.new_value + ' ' + ch.egyseg + '</span></div>';
            });
            html += '</div></div></label></div>';
        });
        \$('usda-diff-list').innerHTML = html;
    }

    \$('usda-diff-select-all').addEventListener('change', function() {
        var c = this.checked;
        document.querySelectorAll('.usda-diff-cb').forEach(function(cb) { cb.checked = c; });
    });
    \$('usda-diff-apply').addEventListener('click', function() {
        var btn = this, st = \$('usda-diff-apply-status'), sel = [];
        document.querySelectorAll('.usda-diff-cb:checked').forEach(function(cb) { sel.push(syncAllDiffs[parseInt(cb.dataset.index)]); });
        if (!sel.length) { st.textContent = '⚠️ Jelölj ki!'; st.style.color = '#d97706'; return; }
        btn.disabled = true; st.textContent = '⏳ Frissítés...'; st.style.color = '#666';
        var fd = new FormData();
        fd.append('action', 'usda_apply_diffs'); fd.append('nonce', usdaData.nonce); fd.append('diffs', JSON.stringify(sel));
        fetch(usdaData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                btn.disabled = false;
                st.textContent = resp.success ? '✅ ' + resp.data : '❌ ' + resp.data;
                st.style.color = resp.success ? '#16a34a' : '#dc2626';
            });
    });

    // EGYEDI KERESÉS
    \$('usda-search-btn').addEventListener('click', function() {
        var query = \$('usda-search-query').value.trim();
        if (!query) return;
        var btn = this, searchType = \$('usda-search-type').value;
        btn.disabled = true; btn.textContent = '⏳ Keresés...';
        \$('usda-search-results').innerHTML = '<div style="color:#666;font-size:0.88rem;">Keresés...</div>';
        var fd = new FormData();
        fd.append('action', 'usda_search_single'); fd.append('nonce', usdaData.nonce);
        fd.append('query', query); fd.append('usda_type', searchType);
        fetch(usdaData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                btn.disabled = false; btn.textContent = '🔍 Keresés';
                if (!resp.success) { \$('usda-search-results').innerHTML = '<div style="color:#dc2626;">❌ ' + resp.data + '</div>'; return; }
                var foods = resp.data.foods;
                if (!foods || !foods.length) { \$('usda-search-results').innerHTML = '<div style="color:#d97706;">Nincs találat.</div>'; return; }
                var html = '<div style="font-size:0.85rem;color:#666;margin-bottom:8px;">' + foods.length + ' találat:</div>';
                foods.forEach(function(f) {
                    html += '<div style="border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;margin-bottom:6px;display:flex;justify-content:space-between;align-items:center;">';
                    html += '<div style="flex:1;min-width:0;">';
                    html += '<strong style="color:#1e293b;">' + escHtml(f.name) + '</strong>';
                    html += ' <span style="font-size:0.75rem;color:#94a3b8;">FDC: ' + f.fdc_id + ' | ' + escHtml(f.data_type) + '</span>';
                    if (f.kcal !== null) html += '<div style="font-size:0.78rem;color:#666;margin-top:2px;">🔥 ' + f.kcal + ' kcal | F: ' + (f.protein||'?') + 'g | Sz: ' + (f.carb||'?') + 'g | Zs: ' + (f.fat||'?') + 'g</div>';
                    if (f.already_imported) html += '<div style="font-size:0.78rem;color:#16a34a;margin-top:2px;">✅ Már importálva (#' + f.existing_id + ')</div>';
                    html += '</div>';
                    if (!f.already_imported) html += '<button class="button usda-import-single" data-fdc-id="' + f.fdc_id + '" style="font-size:0.82rem;margin-left:8px;white-space:nowrap;">📥 Import</button>';
                    html += '</div>';
                });
                \$('usda-search-results').innerHTML = html;
                document.querySelectorAll('.usda-import-single').forEach(function(b) {
                    b.addEventListener('click', function() {
                        var fdcId = this.dataset.fdcId, tb = this;
                        tb.disabled = true; tb.textContent = '⏳...';
                        var fd2 = new FormData();
                        fd2.append('action', 'usda_import_single'); fd2.append('nonce', usdaData.nonce); fd2.append('fdc_id', fdcId);
                        fetch(usdaData.ajaxUrl, { method: 'POST', body: fd2 })
                            .then(function(r) { return r.json(); })
                            .then(function(rr) { tb.textContent = rr.success ? '✅ Kész' : '❌ Hiba'; tb.style.color = rr.success ? '#16a34a' : '#dc2626'; });
                    });
                });
            }).catch(function() { btn.disabled = false; btn.textContent = '🔍 Keresés'; });
    });
    \$('usda-search-query').addEventListener('keypress', function(e) { if (e.key === 'Enter') \$('usda-search-btn').click(); });

    function escHtml(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
})();
JSEOF;

    wp_register_script( 'usda-import-js', false, [], '4.0', true );
    wp_enqueue_script( 'usda-import-js' );
    wp_add_inline_script( 'usda-import-js', $js );
    wp_localize_script( 'usda-import-js', 'usdaData', [
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'usda_import_nonce' ),
        'isRunning'  => ( ( $state['status'] ?? '' ) === 'running' ),
        'savedState' => $state,
		
    ] );
}
add_action( 'admin_enqueue_scripts', 'usda_import_admin_scripts' );
// ══════════════════════════════════════════════════════════════
// 4. AJAX: INDÍTÁS – v4 FIXED
// ══════════════════════════════════════════════════════════════

function usda_cron_start_handler() {
    check_ajax_referer( 'usda_import_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultságod.' );
    if ( ! defined( 'USDA_API_KEY' ) || empty( USDA_API_KEY ) ) wp_send_json_error( 'USDA API key hiányzik!' );

    $mode        = sanitize_key( $_POST['mode'] ?? 'import' );
    $page        = max( 1, intval( $_POST['page'] ?? 1 ) );
    $dataset     = sanitize_text_field( $_POST['dataset'] ?? 'Foundation' );
    $batch_size  = min( USDA_API_MAX_BATCH, max( 10, intval( $_POST['batch_size'] ?? 50 ) ) );
    $batch_delay = max( USDA_API_MIN_DELAY, floatval( $_POST['batch_delay'] ?? 5 ) );
    $max_items   = max( 0, intval( $_POST['max_items'] ?? 500 ) );

    $req_per_min = round( 60 / $batch_delay );
    if ( $req_per_min > USDA_API_RATE_PER_MIN ) {
        wp_send_json_error( 'Túl gyors! ' . $req_per_min . '/perc > ' . USDA_API_RATE_PER_MIN . '. Növeld a delay-t.' );
    }

    $datasets      = usda_get_dataset_descriptions();
    $dataset_label = isset( $datasets[ $dataset ] ) ? $datasets[ $dataset ]['hu'] : $dataset;

    // Lock felszabadítás ha maradt régi
    usda_release_lock();

    // Régi cron törlése
    wp_clear_scheduled_hook( USDA_CRON_HOOK );

    $state = [
        'status'            => 'running',
        'mode'              => $mode,
        'page'              => $page,
        'dataset'           => $dataset,
        'dataset_label'     => $dataset_label,
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

    update_option( USDA_IMPORT_STATE_KEY, $state );
    update_option( USDA_IMPORT_LOG_KEY, [] );
    update_option( USDA_IMPORT_LASTRUN_KEY, 0 );

    // Cron backup ütemezés
    wp_schedule_event( time() + 30, 'usda_every_30s', USDA_CRON_HOOK );

    usda_import_log( 'info', '🚀 ' . ( $mode === 'sync' ? 'Szinkron' : 'Import' ) . ' indítása...' );
    usda_import_log( 'info', '📚 Adatkészlet: ' . $dataset_label );
    usda_import_log( 'info', '⚙️ Batch: ' . $batch_size . ' | Delay: ' . $batch_delay . 's | Max: ' . $max_items );
    if ( $page > 1 ) usda_import_log( 'info', '📌 Folytatás a(z) ' . $page . '. oldaltól' );

    wp_send_json_success( 'Elindítva.' );
}
add_action( 'wp_ajax_usda_cron_start', 'usda_cron_start_handler' );


// ══════════════════════════════════════════════════════════════
// 5. AJAX: LEÁLLÍTÁS – v4 FIXED (atomi stop)
// ══════════════════════════════════════════════════════════════

function usda_cron_stop_handler() {
    check_ajax_referer( 'usda_import_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    // 1. Cron törlése ELŐSZÖR
    wp_clear_scheduled_hook( USDA_CRON_HOOK );

    // 2. State → stopped
    $state = get_option( USDA_IMPORT_STATE_KEY, [] );
    $state['status'] = 'stopped';
    update_option( USDA_IMPORT_STATE_KEY, $state );

    // 3. Lock felszabadítás
    usda_release_lock();

    // 4. Dupla check – cron tényleg törölve
    wp_clear_scheduled_hook( USDA_CRON_HOOK );

    usda_import_log( 'info', '⏹ Leállítva. Pozíció mentve: oldal ' . ( $state['page'] ?? '?' ) );
    wp_send_json_success( 'Leállítva.' );
}
add_action( 'wp_ajax_usda_cron_stop', 'usda_cron_stop_handler' );


// ══════════════════════════════════════════════════════════════
// 6. AJAX: RESET
// ══════════════════════════════════════════════════════════════

function usda_cron_reset_handler() {
    check_ajax_referer( 'usda_import_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    wp_clear_scheduled_hook( USDA_CRON_HOOK );
    usda_release_lock();
    delete_option( USDA_IMPORT_STATE_KEY );
    delete_option( USDA_IMPORT_LOG_KEY );
    delete_option( USDA_IMPORT_LASTRUN_KEY );

    wp_send_json_success( 'Törölve.' );
}
add_action( 'wp_ajax_usda_cron_reset', 'usda_cron_reset_handler' );


// ══════════════════════════════════════════════════════════════
// 7. AJAX: POLLING + BATCH TRIGGERELÉS – v4 FIXED
//
// KRITIKUS JAVÍTÁS:
// - A polling CSAK AKKOR futtat batch-et, ha run_batch=1 ÉS elég idő telt el
// - Status check MINDIG FRISS (wp_cache törlés)
// - Ha status !== running → NEM futtat semmit
// ══════════════════════════════════════════════════════════════

function usda_cron_status_handler() {
    check_ajax_referer( 'usda_import_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    // Friss state – cache nélkül
    wp_cache_delete( USDA_IMPORT_STATE_KEY, 'options' );
    $state = get_option( USDA_IMPORT_STATE_KEY, [] );

    wp_cache_delete( USDA_IMPORT_LOG_KEY, 'options' );
    $log = get_option( USDA_IMPORT_LOG_KEY, [] );

    $run_batch = ( $_POST['run_batch'] ?? '0' ) === '1';

    // CSAK ha running ÉS a kliens kéri ÉS elég idő telt el
    if ( $run_batch && ( $state['status'] ?? '' ) === 'running' ) {
        wp_cache_delete( USDA_IMPORT_LASTRUN_KEY, 'options' );
        $last_run = floatval( get_option( USDA_IMPORT_LASTRUN_KEY, 0 ) );
        $delay    = floatval( $state['batch_delay'] ?? 5 );
        $now      = microtime( true );

        if ( ( $now - $last_run ) >= $delay ) {
            // Próbáljuk megszerezni a lockot
            if ( usda_acquire_lock() ) {
                // Frissítsük a last_run IDŐT ELŐBB – hogy másik poll ne indítson újat
                update_option( USDA_IMPORT_LASTRUN_KEY, $now );

                // State ÚJRAOLVASÁS lock megszerzése után
                wp_cache_delete( USDA_IMPORT_STATE_KEY, 'options' );
                $state = get_option( USDA_IMPORT_STATE_KEY, [] );

                // DUPLA CHECK: még mindig running?
                if ( ( $state['status'] ?? '' ) === 'running' ) {
                    usda_execute_batch( $state );
                }

                usda_release_lock();

                // Friss state a válaszhoz
                wp_cache_delete( USDA_IMPORT_STATE_KEY, 'options' );
                $state = get_option( USDA_IMPORT_STATE_KEY, [] );
                wp_cache_delete( USDA_IMPORT_LOG_KEY, 'options' );
                $log = get_option( USDA_IMPORT_LOG_KEY, [] );
            }
        }
    }

    $recent_log = array_slice( $log, -50 );

    wp_send_json_success( [
        'status'        => $state['status'] ?? 'idle',
        'page'          => $state['page'] ?? 0,
        'dataset'       => $state['dataset'] ?? '',
        'dataset_label' => $state['dataset_label'] ?? '',
        'batch_size'    => $state['batch_size'] ?? 50,
        'batch_delay'   => $state['batch_delay'] ?? 5,
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
add_action( 'wp_ajax_usda_cron_status', 'usda_cron_status_handler' );


// ══════════════════════════════════════════════════════════════
// 8. WP-CRON HANDLER (backup – ha böngésző bezárul)
// ══════════════════════════════════════════════════════════════

function usda_cron_run_batch() {
    // Friss state
    wp_cache_delete( USDA_IMPORT_STATE_KEY, 'options' );
    $state = get_option( USDA_IMPORT_STATE_KEY, [] );

    if ( ( $state['status'] ?? '' ) !== 'running' ) {
        wp_clear_scheduled_hook( USDA_CRON_HOOK );
        return;
    }

    // Lock – ha nem kapjuk meg, kihagyjuk (polling majd csinálja)
    if ( ! usda_acquire_lock() ) {
        return;
    }

    // Dupla check lock után
    wp_cache_delete( USDA_IMPORT_STATE_KEY, 'options' );
    $state = get_option( USDA_IMPORT_STATE_KEY, [] );
    if ( ( $state['status'] ?? '' ) !== 'running' ) {
        usda_release_lock();
        wp_clear_scheduled_hook( USDA_CRON_HOOK );
        return;
    }

    update_option( USDA_IMPORT_LASTRUN_KEY, microtime( true ) );
    usda_execute_batch( $state );
    usda_release_lock();
}
add_action( USDA_CRON_HOOK, 'usda_cron_run_batch' );


// ══════════════════════════════════════════════════════════════
// 9. BATCH VÉGREHAJTÓ (közös logika – polling + cron használja)
// ══════════════════════════════════════════════════════════════

function usda_execute_batch( $state ) {
    $mode      = $state['mode'] ?? 'import';
    $max_items = intval( $state['max_items'] ?? 500 );

    // Maximum elérve?
    if ( $mode === 'import' && $max_items > 0 && ( $state['stats']['created'] ?? 0 ) >= $max_items ) {
        $state['status'] = 'done';
        update_option( USDA_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( USDA_CRON_HOOK );
        usda_import_log( 'success', '🎉 Maximum elérve: ' . $state['stats']['created'] . ' új.' );
        return;
    }

    // Üres batch limit elérve?
    if ( ( $state['consecutive_empty'] ?? 0 ) >= USDA_EMPTY_BATCH_LIMIT ) {
        $state['status'] = 'done';
        update_option( USDA_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( USDA_CRON_HOOK );
        usda_import_log( 'warn', '⏹ ' . USDA_EMPTY_BATCH_LIMIT . ' üres batch → leállás.' );
        return;
    }

    if ( $mode === 'import' ) {
        usda_execute_import_batch( $state );
    } else {
        usda_execute_sync_batch( $state );
    }
}


// ══════════════════════════════════════════════════════════════
// 10. IMPORT BATCH – v4 FIXED
//
// KRITIKUS JAVÍTÁSOK:
// - Page szám BATCH ELEJÉN nő (nem végén) → párhuzamos futás nem dolgozza fel kétszer
// - $wpdb direct query duplikáció ellenőrzés (nem get_posts + meta_query)
// - FDC ID string-ként tárolva
// - State MINDIG frissen olvasva
// ══════════════════════════════════════════════════════════════

function usda_execute_import_batch( $state ) {
    if ( ! function_exists( 'update_field' ) ) {
        $state['status'] = 'error';
        update_option( USDA_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( USDA_CRON_HOOK );
        usda_import_log( 'error', '❌ ACF nincs betöltve.' );
        return;
    }

    $page       = intval( $state['page'] );
    $batch_size = intval( $state['batch_size'] );
    $dataset    = $state['dataset'] ?? 'Foundation';
    $data_types = array_map( 'trim', explode( ',', $dataset ) );

    // ★ PAGE SZÁM AZONNAL NŐ – mielőtt bármit csinálnánk
    // Ez megakadályozza, hogy párhuzamos futás ugyanazt az oldalt dolgozza fel
    $state['page'] = $page + 1;
    $state['date'] = current_time( 'Y-m-d H:i:s' );
    update_option( USDA_IMPORT_STATE_KEY, $state );

    usda_import_log( 'info', '📦 Batch: oldal ' . $page . ' (' . $batch_size . ' termék)' );

    // API kérés
    $body = usda_api_request_with_retry( $data_types, $page, $batch_size );

    if ( $body === false ) {
        $state['stats']['errors'] = ( $state['stats']['errors'] ?? 0 ) + 1;
        update_option( USDA_IMPORT_STATE_KEY, $state );
        usda_import_log( 'error', '❌ API hiba (3× retry) – továbblépés oldal ' . ( $page + 1 ) );
        return;
    }

    $foods = $body['foods'] ?? [];
    $total = intval( $body['totalHits'] ?? 0 );
    $state['total'] = $total;

    // Üres válasz → vége
    if ( empty( $foods ) ) {
        $state['status'] = 'done';
        update_option( USDA_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( USDA_CRON_HOOK );
        usda_import_log( 'success', '🏁 Minden feldolgozva! (Oldal ' . $page . ' üres)' );
        return;
    }

    $created      = 0;
    $skipped      = 0;
    $nutrient_map = usda_get_nutrient_mapping();

    foreach ( $foods as $food ) {
        // Státusz ellenőrzés MINDEN terméknél – ha közben leállították, kilépünk
        if ( $created > 0 && $created % 10 === 0 ) {
            wp_cache_delete( USDA_IMPORT_STATE_KEY, 'options' );
            $fresh_state = get_option( USDA_IMPORT_STATE_KEY, [] );
            if ( ( $fresh_state['status'] ?? '' ) !== 'running' ) {
                usda_import_log( 'info', '⏹ Leállítás érzékelve batch közben.' );
                return;
            }
        }

        $fdc_id = intval( $food['fdcId'] ?? 0 );
        $name   = isset( $food['description'] ) ? trim( $food['description'] ) : '';
        if ( ! $fdc_id || empty( $name ) ) {
            $skipped++;
            continue;
        }

        // ★ DUPLIKÁCIÓ ELLENŐRZÉS – $wpdb direct query (NEM get_posts!)
        // String-ként hasonlítjuk össze (ACF/update_field string-ként menti)
        if ( usda_fdc_id_exists( $fdc_id ) ) {
            $skipped++;
            continue;
        }

        $clean_name = usda_proper_case( usda_clean_description( $name ) );
        $post_id = wp_insert_post( [
            'post_type'   => 'alapanyag',
            'post_title'  => $clean_name,
            'post_status' => 'publish',
        ], true );

        if ( is_wp_error( $post_id ) ) {
            $skipped++;
            continue;
        }

        $created++;

        // Tápértékek mentése
        if ( isset( $food['foodNutrients'] ) && is_array( $food['foodNutrients'] ) ) {
            foreach ( $food['foodNutrients'] as $fn ) {
                $nid = intval( $fn['nutrientId'] ?? 0 );
                if ( isset( $nutrient_map[ $nid ] ) && isset( $fn['value'] ) ) {
                    update_field( $nutrient_map[ $nid ]['acf_key'], round( floatval( $fn['value'] ), 4 ), $post_id );
                }
            }
        }

        // Meta adatok – FDC ID STRING-KÉNT
        update_field( 'usda_fdc_id',       strval( $fdc_id ), $post_id );
        update_field( 'usda_last_sync',    current_time( 'Y-m-d H:i:s' ), $post_id );
        update_field( 'elsodleges_forras', 'usda', $post_id );
        update_field( 'eredeti_nev',       $name, $post_id );
    }

    // Statisztikák frissítése – FRISS state olvasás
    wp_cache_delete( USDA_IMPORT_STATE_KEY, 'options' );
    $state = get_option( USDA_IMPORT_STATE_KEY, [] );

    $state['stats']['processed'] = ( $state['stats']['processed'] ?? 0 ) + $created + $skipped;
    $state['stats']['created']   = ( $state['stats']['created'] ?? 0 ) + $created;
    $state['stats']['skipped']   = ( $state['stats']['skipped'] ?? 0 ) + $skipped;

    if ( $created === 0 ) {
        $state['consecutive_empty']      = ( $state['consecutive_empty'] ?? 0 ) + 1;
        $state['stats']['empty_batches'] = ( $state['stats']['empty_batches'] ?? 0 ) + 1;
    } else {
        $state['consecutive_empty'] = 0;
    }

    usda_import_log( 'success', '✅ Oldal ' . $page . ': +' . $created . ' új, ' . $skipped . ' kihagyva' );

    // Ha kevesebb jött mint a batch méret → utolsó oldal volt
    if ( count( $foods ) < $batch_size ) {
        $state['status'] = 'done';
        update_option( USDA_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( USDA_CRON_HOOK );
        usda_import_log( 'success', '🏁 Import kész! Összesen: ' . ( $state['stats']['created'] ?? 0 ) . ' új.' );
        return;
    }

    update_option( USDA_IMPORT_STATE_KEY, $state );
}


// ══════════════════════════════════════════════════════════════
// 11. SZINKRON BATCH
// ══════════════════════════════════════════════════════════════

function usda_execute_sync_batch( $state ) {
    if ( ! function_exists( 'update_field' ) || ! function_exists( 'get_field' ) ) {
        $state['status'] = 'error';
        update_option( USDA_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( USDA_CRON_HOOK );
        usda_import_log( 'error', '❌ ACF nincs betöltve.' );
        return;
    }

    $page         = intval( $state['page'] );
    $batch_size   = min( 10, intval( $state['batch_size'] ) );
    $nutrient_map = usda_get_nutrient_mapping();

    // Page szám azonnal nő
    $state['page'] = $page + 1;
    $state['date'] = current_time( 'Y-m-d H:i:s' );
    update_option( USDA_IMPORT_STATE_KEY, $state );

    $alapanyagok = get_posts( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => $batch_size,
        'paged'          => $page,
        'post_status'    => 'publish',
        'meta_query'     => [ [ 'key' => 'usda_fdc_id', 'compare' => 'EXISTS' ] ],
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ] );

    if ( empty( $alapanyagok ) ) {
        $state['status'] = 'done_sync';
        update_option( USDA_IMPORT_STATE_KEY, $state );
        wp_clear_scheduled_hook( USDA_CRON_HOOK );
        usda_import_log( 'success', '🎉 Szinkron kész! ' . ( $state['stats']['diff'] ?? 0 ) . ' eltérés.' );
        return;
    }

    usda_import_log( 'info', '📦 Szinkron batch: oldal ' . $page . ' (' . count( $alapanyagok ) . ' termék)' );

    $diff_count = 0;
    $unchanged  = 0;
    $errors     = 0;

    foreach ( $alapanyagok as $post ) {
        $fdc_id = get_field( 'usda_fdc_id', $post->ID );
        if ( empty( $fdc_id ) ) continue;

        $api_url  = 'https://api.nal.usda.gov/fdc/v1/food/' . intval( $fdc_id ) . '?api_key=' . USDA_API_KEY;
        $response = wp_remote_get( $api_url, [ 'timeout' => USDA_API_TIMEOUT ] );
        if ( is_wp_error( $response ) ) { $errors++; continue; }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! $body || ! isset( $body['foodNutrients'] ) ) { $errors++; continue; }

        $changes = [];
        foreach ( $body['foodNutrients'] as $fn ) {
            $nid = intval( $fn['nutrient']['id'] ?? $fn['nutrientId'] ?? 0 );
            if ( ! isset( $nutrient_map[ $nid ] ) ) continue;
            $new_value = isset( $fn['amount'] ) ? round( floatval( $fn['amount'] ), 4 ) : ( isset( $fn['value'] ) ? round( floatval( $fn['value'] ), 4 ) : null );
            if ( $new_value === null ) continue;
            $acf_key   = $nutrient_map[ $nid ]['acf_key'];
            $old_value = floatval( get_field( $acf_key, $post->ID ) );
            if ( abs( $old_value - $new_value ) > 0.01 ) {
                $changes[] = [
                    'acf_key'   => $acf_key,
                    'label'     => $nutrient_map[ $nid ]['label'],
                    'egyseg'    => $nutrient_map[ $nid ]['egyseg'],
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
                'fdc_id'  => $fdc_id,
                'changes' => $changes,
            ];
        } else {
            $unchanged++;
        }

        usleep( 400000 );
    }

    // Friss state olvasás
    wp_cache_delete( USDA_IMPORT_STATE_KEY, 'options' );
    $fresh = get_option( USDA_IMPORT_STATE_KEY, [] );

    // Diffs mentése – merge az eddigiekkel
    $fresh['diffs']              = $state['diffs'];
    $fresh['stats']['diff']      = ( $fresh['stats']['diff'] ?? 0 ) + $diff_count;
    $fresh['stats']['unchanged'] = ( $fresh['stats']['unchanged'] ?? 0 ) + $unchanged;
    $fresh['stats']['errors']    = ( $fresh['stats']['errors'] ?? 0 ) + $errors;
    $fresh['stats']['processed'] = ( $fresh['stats']['processed'] ?? 0 ) + $diff_count + $unchanged + $errors;

    usda_import_log( 'success', '✅ Szinkron oldal ' . $page . ': ' . $diff_count . ' eltérés, ' . $unchanged . ' ok' );

    update_option( USDA_IMPORT_STATE_KEY, $fresh );
}


// ══════════════════════════════════════════════════════════════
// 12. USDA API KÉRÉS RETRY-VEL
// ══════════════════════════════════════════════════════════════

function usda_api_request_with_retry( $data_types, $page, $page_size ) {
    $api_url = 'https://api.nal.usda.gov/fdc/v1/foods/search?api_key=' . USDA_API_KEY;

    for ( $attempt = 1; $attempt <= USDA_API_RETRY_COUNT; $attempt++ ) {
        $response = wp_remote_post( $api_url, [
            'timeout' => USDA_API_TIMEOUT,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( [
                'query'      => '',
                'dataType'   => $data_types,
                'pageSize'   => $page_size,
                'pageNumber' => $page,
            ] ),
        ] );

        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( $body && isset( $body['foods'] ) ) return $body;
        }

        if ( $attempt < USDA_API_RETRY_COUNT ) {
            $err = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $response );
            usda_import_log( 'warn', '⚠️ API hiba: ' . $err . ' (próba ' . $attempt . '/' . USDA_API_RETRY_COUNT . ')' );
            sleep( USDA_API_RETRY_DELAY );
        }
    }
    return false;
}


// ══════════════════════════════════════════════════════════════
// 13. LOG
// ══════════════════════════════════════════════════════════════

function usda_import_log( $type, $msg ) {
    $log   = get_option( USDA_IMPORT_LOG_KEY, [] );
    $log[] = [ 'type' => $type, 'msg' => $msg, 'time' => current_time( 'H:i:s' ) ];
    if ( count( $log ) > USDA_LOG_MAX_ENTRIES ) {
        $log = array_slice( $log, -USDA_LOG_MAX_ENTRIES );
    }
    update_option( USDA_IMPORT_LOG_KEY, $log );
}


// ══════════════════════════════════════════════════════════════
// 14. AJAX: DIFF ALKALMAZÁS
// ══════════════════════════════════════════════════════════════

function usda_apply_diffs_handler() {
    check_ajax_referer( 'usda_import_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );
    if ( ! function_exists( 'update_field' ) ) wp_send_json_error( 'ACF nincs betöltve.' );

    $diffs = json_decode( stripslashes( $_POST['diffs'] ?? '[]' ), true );
    if ( empty( $diffs ) ) wp_send_json_error( 'Nincs kijelölve.' );

    $updated_posts  = 0;
    $updated_fields = 0;

    foreach ( $diffs as $item ) {
        $post_id = intval( $item['post_id'] ?? 0 );
        if ( ! $post_id || get_post_type( $post_id ) !== 'alapanyag' ) continue;
        foreach ( $item['changes'] ?? [] as $change ) {
            $acf_key = sanitize_key( $change['acf_key'] ?? '' );
            if ( $acf_key ) {
                update_field( $acf_key, round( floatval( $change['new_value'] ?? 0 ), 4 ), $post_id );
                $updated_fields++;
            }
        }
        update_field( 'usda_last_sync', current_time( 'Y-m-d H:i:s' ), $post_id );
        $updated_posts++;
    }

    wp_send_json_success( $updated_posts . ' frissítve (' . $updated_fields . ' mező).' );
}
add_action( 'wp_ajax_usda_apply_diffs', 'usda_apply_diffs_handler' );


// ══════════════════════════════════════════════════════════════
// 15. AJAX: EGYEDI KERESÉS
// ══════════════════════════════════════════════════════════════

function usda_search_single_handler() {
    check_ajax_referer( 'usda_import_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultságod.' );
    if ( ! defined( 'USDA_API_KEY' ) || empty( USDA_API_KEY ) ) wp_send_json_error( 'API key hiányzik.' );

    $query     = sanitize_text_field( $_POST['query'] ?? '' );
    $usda_type = sanitize_text_field( $_POST['usda_type'] ?? 'Foundation,SR Legacy' );
    if ( empty( $query ) ) wp_send_json_error( 'Üres keresés.' );

    $data_types = array_map( 'trim', explode( ',', $usda_type ) );
    $api_url    = 'https://api.nal.usda.gov/fdc/v1/foods/search?api_key=' . USDA_API_KEY;
    $response   = wp_remote_post( $api_url, [
        'timeout' => USDA_API_TIMEOUT,
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body'    => wp_json_encode( [ 'query' => $query, 'dataType' => $data_types, 'pageSize' => 15 ] ),
    ] );
    if ( is_wp_error( $response ) ) wp_send_json_error( 'API hiba: ' . $response->get_error_message() );
    if ( wp_remote_retrieve_response_code( $response ) !== 200 ) wp_send_json_error( 'USDA HTTP ' . wp_remote_retrieve_response_code( $response ) );

    $body  = json_decode( wp_remote_retrieve_body( $response ), true );
    $foods = [];

    foreach ( ( $body['foods'] ?? [] ) as $food ) {
        $fdc_id = intval( $food['fdcId'] ?? 0 );
        if ( ! $fdc_id ) continue;
        $kcal = $prot = $carb = $fat = null;
        foreach ( ( $food['foodNutrients'] ?? [] ) as $fn ) {
            $nid = intval( $fn['nutrientId'] ?? 0 );
            $val = isset( $fn['value'] ) ? round( floatval( $fn['value'] ), 1 ) : null;
            if ( $nid === 1008 ) $kcal = $val;
            if ( $nid === 1003 ) $prot = $val;
            if ( $nid === 1005 ) $carb = $val;
            if ( $nid === 1004 ) $fat  = $val;
        }
        $already = usda_fdc_id_exists( $fdc_id );
        $existing_id = 0;
        if ( $already ) {
            global $wpdb;
            $existing_id = intval( $wpdb->get_var( $wpdb->prepare(
                "SELECT pm.post_id FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = 'usda_fdc_id' AND pm.meta_value = %s
                   AND p.post_type = 'alapanyag' LIMIT 1",
                strval( $fdc_id )
            ) ) );
        }
        $foods[] = [
            'fdc_id'           => $fdc_id,
            'name'             => $food['description'] ?? '',
            'data_type'        => $food['dataType'] ?? '',
            'kcal'             => $kcal,
            'protein'          => $prot,
            'carb'             => $carb,
            'fat'              => $fat,
            'already_imported' => $already,
            'existing_id'      => $existing_id,
        ];
    }

    wp_send_json_success( [ 'foods' => $foods ] );
}
add_action( 'wp_ajax_usda_search_single', 'usda_search_single_handler' );


// ══════════════════════════════════════════════════════════════
// 16. AJAX: EGYEDI IMPORT
// ══════════════════════════════════════════════════════════════

function usda_import_single_handler() {
    check_ajax_referer( 'usda_import_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultságod.' );
    if ( ! function_exists( 'update_field' ) ) wp_send_json_error( 'ACF nincs betöltve.' );

    $fdc_id = intval( $_POST['fdc_id'] ?? 0 );
    if ( ! $fdc_id ) wp_send_json_error( 'Nincs FDC ID.' );

    // Duplikáció check – $wpdb direct
    if ( usda_fdc_id_exists( $fdc_id ) ) {
        global $wpdb;
        $ex_id = intval( $wpdb->get_var( $wpdb->prepare(
            "SELECT pm.post_id FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = 'usda_fdc_id' AND pm.meta_value = %s
               AND p.post_type = 'alapanyag' LIMIT 1",
            strval( $fdc_id )
        ) ) );
        wp_send_json_error( 'Már importálva: #' . $ex_id );
    }

    $api_url  = 'https://api.nal.usda.gov/fdc/v1/food/' . $fdc_id . '?api_key=' . USDA_API_KEY;
    $response = wp_remote_get( $api_url, [ 'timeout' => USDA_API_TIMEOUT ] );
    if ( is_wp_error( $response ) ) wp_send_json_error( 'API hiba.' );

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( ! $body ) wp_send_json_error( 'Érvénytelen válasz.' );

    $name    = usda_proper_case( usda_clean_description( $body['description'] ?? 'Ismeretlen' ) );
    $post_id = wp_insert_post( [
        'post_type'   => 'alapanyag',
        'post_title'  => $name,
        'post_status' => 'publish',
    ], true );
    if ( is_wp_error( $post_id ) ) wp_send_json_error( 'Post hiba.' );

    $nutrient_map = usda_get_nutrient_mapping();
    foreach ( ( $body['foodNutrients'] ?? [] ) as $fn ) {
        $nid = intval( $fn['nutrient']['id'] ?? $fn['nutrientId'] ?? 0 );
        if ( isset( $nutrient_map[ $nid ] ) ) {
            $value = round( floatval( $fn['amount'] ?? $fn['value'] ?? 0 ), 4 );
            update_field( $nutrient_map[ $nid ]['acf_key'], $value, $post_id );
        }
    }

    update_field( 'usda_fdc_id',       strval( $fdc_id ), $post_id );
    update_field( 'usda_last_sync',    current_time( 'Y-m-d H:i:s' ), $post_id );
    update_field( 'elsodleges_forras', 'usda', $post_id );
    update_field( 'eredeti_nev',       $body['description'] ?? '', $post_id );

    wp_send_json_success( 'Importálva: ' . $name . ' (#' . $post_id . ')' );
}
add_action( 'wp_ajax_usda_import_single', 'usda_import_single_handler' );


// ══════════════════════════════════════════════════════════════
// 17. SEGÉDFÜGGVÉNYEK
// ══════════════════════════════════════════════════════════════

function usda_clean_description( $name ) {
    $remove = [ 'raw', 'fresh', 'whole', 'skinless', 'boneless', 'with skin', 'without skin',
                'cooked', 'uncooked', 'NFS', 'UPC:', 'GTIN:' ];
    foreach ( $remove as $word ) {
        $name = preg_replace( '/\b' . preg_quote( $word, '/' ) . '\b/i', '', $name );
    }
    $name = str_replace( ',', ' ', $name );
    $name = preg_replace( '/\s+/', ' ', $name );
    return trim( $name );
}

function usda_proper_case( $name ) {
    $words  = explode( ' ', $name );
    $result = [];
    $small  = [ 'and', 'or', 'of', 'in', 'with', 'the', 'for', 'a', 'an' ];
    foreach ( $words as $i => $word ) {
        if ( empty( $word ) ) continue;
        if ( $i > 0 && in_array( mb_strtolower( $word, 'UTF-8' ), $small, true ) ) {
            $result[] = mb_strtolower( $word, 'UTF-8' );
        } else {
            $result[] = mb_strtoupper( mb_substr( $word, 0, 1, 'UTF-8' ), 'UTF-8' )
                      . mb_strtolower( mb_substr( $word, 1, null, 'UTF-8' ), 'UTF-8' );
        }
    }
    return implode( ' ', $result );
}


// ══════════════════════════════════════════════════════════════
// 18. EGYEDI CRON INTERVALLUM (30 mp)
// ══════════════════════════════════════════════════════════════

function usda_cron_schedules( $schedules ) {
    $schedules['usda_every_30s'] = [
        'interval' => 30,
        'display'  => 'USDA Import: 30 másodpercenként',
    ];
    return $schedules;
}
add_filter( 'cron_schedules', 'usda_cron_schedules' );


// ══════════════════════════════════════════════════════════════
// 19. CRON BIZTOSÍTÁS – v4 FIXED
// NEM indít újra semmit magától – csak takarít ha kell
// ══════════════════════════════════════════════════════════════

function usda_ensure_cron_running() {
    $state = get_option( USDA_IMPORT_STATE_KEY, [] );
    $status = $state['status'] ?? '';

    if ( $status === 'running' ) {
        // Ha running DE nincs cron ütemezve → ütemezzük (backup)
        if ( ! wp_next_scheduled( USDA_CRON_HOOK ) ) {
            wp_schedule_event( time() + 30, 'usda_every_30s', USDA_CRON_HOOK );
        }
    } else {
        // Ha NEM running → cron törlése (takarítás)
        if ( wp_next_scheduled( USDA_CRON_HOOK ) ) {
            wp_clear_scheduled_hook( USDA_CRON_HOOK );
        }
        // Lock takarítás ha maradt
        $lock = get_option( USDA_IMPORT_LOCK_KEY, 0 );
        if ( $lock > 0 ) {
            usda_release_lock();
        }
    }
}
add_action( 'admin_init', 'usda_ensure_cron_running' );
