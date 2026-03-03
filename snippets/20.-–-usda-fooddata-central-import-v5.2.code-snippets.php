<?php

/**
 * 20. – USDA FOODDATA CENTRAL IMPORT (v5.2)
 */
/**
 * 20. – USDA FOODDATA CENTRAL IMPORT v5.4 FINAL
 *
 * JAVÍTÁSOK v5.3 → v5.4:
 *  1. SMART SKIP: NEM állítja le az importot – consecutive_empty CSAK ténylegesen üres API válasznál nő
 *  2. DONE LOGIKA: totalHits-alapú befejezés – nem consecutive_empty limit
 *  3. JS FIX: bitwise OR (|) → logical OR (||) a startProc-ban
 *  4. MIGRÁCIÓ MENTÉSE: usda5_maybe_migrate_state utáni auto-save
 *  5. LOCK FIX: expiry egyetlen row-ban, JSON-ben tárolt uuid+expiry
 *  6. DEACTIVATION: Code Snippets kompatibilis shutdown hook
 *  7. STALE CHECK: cron-ban is fut, nem csak admin_init-ben
 *  8. LOG ARRAY GUARD: get_option visszatérés mindig tömb
 *  9. CONSECUTIVE_EMPTY: csak TÉNYLEGESEN üres API válasznál nő (smart skip NEM növeli)
 *  10. PAGE OVERFLOW GUARD: totalHits alapú végfelismerés
 *
 * MEGTARTOTT v5.3 FUNKCIÓK:
 *  - STOP-SAFE: 5 rétegű stop védelem
 *  - DUPLIKÁTUM-BIZTOS: batch cache + atomi DB check
 *  - CONTINUE ADAPTIVE: response_times, timeouts, time_saved megőrzése
 *  - CLEAN_DESCRIPTION: raw/cooked/fresh megmarad
 *  - ACF FALLBACK: usda_fdc_id update_field bukásánál update_post_meta
 *  - FORCE_STATUS: cron cleanup automatikus
 *  - SMART SKIP: turbo skip oldal ugrás (DE NEM ÁLLÍT LE!)
 *  - META_INPUT: ACF mezők CSAK update_field-del mentve
 *  - DIFFS LIMIT: max 200 sync diff
 *  - ETA: created-alapú számítás
 *  - DISABLE_WP_CRON: admin_init backup trigger
 *  - XSS: log escape server-side + JS-ben
 *  - POLL: stop után readonly endpoint
 *  - ISRUNNING: stopping NEM számít running-nak
 *  - CRON: strict running check minden belépési ponton
 *  - START GUARD: nem indulhat ha már fut/leáll
 *  - ATOMI LOCK: DB INSERT IGNORE
 *  - VERZIÓ CHECK: state-ben tárolt plugin_version, auto-migráció
 *  - STALE CHECK: adaptív timeout (delay-függő)
 *  - PHP 8.x COMPAT: mb_substr explicit length
 *  - BATCH_SIZE SYNC: adaptív méret visszaírása state-be
 *  - SERVER-SIDE LOG SANITIZE
 *  - JS GUARD: usda5Data undefined védelem
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'USDA5_PLUGIN_VERSION', '5.4' );

// API KEY: wp-config.php-ban kell legyen!
if ( ! defined( 'USDA_API_KEY' ) || empty( USDA_API_KEY ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>❌ <strong>USDA Import:</strong> Állítsd be a <code>USDA_API_KEY</code>-t a <code>wp-config.php</code>-ban! <code>define( \'USDA_API_KEY\', \'TE_KULCSOD\' );</code></p></div>';
    } );
    return;
}

/* ═══════════════════════════════════════════════════════════
   KONSTANSOK
   ═══════════════════════════════════════════════════════════ */

define( 'USDA5_STATE_KEY',       'usda5_import_state' );
define( 'USDA5_LOG_KEY',         'usda5_import_log' );
define( 'USDA5_LASTRUN_KEY',     'usda5_last_run_time' );
define( 'USDA5_LOCK_KEY',        'usda5_batch_lock' );
define( 'USDA5_CRON_HOOK',       'usda5_cron_batch' );
define( 'USDA5_RATE_BUCKET_KEY', 'usda5_rate_bucket' );

define( 'USDA5_RATE_LIMIT',   1000 );
define( 'USDA5_RATE_PER_MIN', 16 );
define( 'USDA5_MAX_BATCH',    200 );
define( 'USDA5_MAX_RETRY',    5 );
define( 'USDA5_TIMEOUT',      60 );
define( 'USDA5_LOG_MAX',      500 );
define( 'USDA5_LOCK_TTL',     120 );
define( 'USDA5_DIFFS_MAX',    200 );
define( 'USDA5_SKIP_MAX_JUMP', 20 );

// v5.4: consecutive_empty CSAK ténylegesen üres API válasznál – magasabb limit mert ritkábban nő
define( 'USDA5_EMPTY_LIMIT',  20 );

define( 'USDA5_ADAPTIVE_MIN_DELAY',     4 );
define( 'USDA5_ADAPTIVE_MAX_DELAY',     60 );
define( 'USDA5_ADAPTIVE_MIN_BATCH',     10 );
define( 'USDA5_ADAPTIVE_MAX_BATCH',     200 );
define( 'USDA5_ADAPTIVE_SWEET_SPOT',    3.0 );
define( 'USDA5_ADAPTIVE_TIMEOUT_CEIL',  10.0 );
define( 'USDA5_CURL28_CONSECUTIVE_MAX', 3 );

/* ═══════════════════════════════════════════════════════════
   SHUTDOWN HOOK (Code Snippets kompatibilis)
   ═══════════════════════════════════════════════════════════ */

/**
 * v5.4: register_deactivation_hook NEM működik Code Snippets-ben (__FILE__ nem plugin fájl).
 * Helyette: shutdown action-ben ellenőrizzük, hogy a plugin még aktív-e,
 * ÉS register_deactivation_hook-ot is meghívjuk ha rendes plugin fájlként fut.
 */
if ( function_exists( 'register_deactivation_hook' ) && strpos( __FILE__, WP_PLUGIN_DIR ) !== false ) {
    register_deactivation_hook( __FILE__, 'usda5_deactivation_cleanup' );
}

function usda5_deactivation_cleanup() {
    wp_clear_scheduled_hook( USDA5_CRON_HOOK );
    usda5_force_unlock();

    $state = get_option( USDA5_STATE_KEY, [] );
    if ( ! empty( $state ) && isset( $state['status'] ) && $state['status'] === 'running' ) {
        $state['status']   = 'stopped';
        $state['_updated'] = microtime( true );
        update_option( USDA5_STATE_KEY, $state, false );
    }
}

/* ═══════════════════════════════════════════════════════════
   ADATKÉSZLET LEÍRÁSOK
   ═══════════════════════════════════════════════════════════ */

function usda5_get_dataset_descriptions() {
    return [
        'Foundation' => [
            'hu'    => 'Foundation Foods – Laboratóriumi alapanyagok',
            'db'    => '~287',
            'desc'  => 'Az USDA által laboratóriumban elemzett nyers alapanyagok.',
            'mikor' => 'Évente 2× frissül, utolsó: 2024',
            'hol'   => 'USDA Agricultural Research Service (ARS), Beltsville, Maryland',
            'mivel' => 'Kromatográfia, spektrometria, Kjeldahl-módszer, Soxhlet-extrakció, bomb-kalorimetria.',
        ],
        'SR Legacy' => [
            'hu'    => 'Standard Reference Legacy – Átfogó referencia',
            'db'    => '~7 793',
            'desc'  => 'Az USDA korábbi Standard Reference adatbázisa (SR28).',
            'mikor' => 'Utolsó kiadás: 2018 (SR28) – nem frissül',
            'hol'   => 'USDA Nutrient Data Laboratory, Beltsville, Maryland',
            'mivel' => 'Vegyes: labor + számított + irodalmi adatok.',
        ],
        'Survey (FNDDS)' => [
            'hu'    => 'FNDDS – Táplálkozási felmérés',
            'db'    => '~10 000',
            'desc'  => 'Az amerikaiak által ténylegesen fogyasztott ételek.',
            'mikor' => '2 évente frissül a NHANES ciklusokkal',
            'hol'   => 'USDA Food Surveys Research Group, Beltsville, Maryland',
            'mivel' => 'Vegyes: labor + receptúra alapú számítás + gyártói adatok.',
        ],
        'Branded' => [
            'hu'    => 'Branded Foods – Márkás/bolti termékek',
            'db'    => '~400 000+',
            'desc'  => 'Boltokban kapható márkás termékek – gyártói címke adatok.',
            'mikor' => 'Havonta frissül',
            'hol'   => 'USDA FoodData Central + Label Insight',
            'mivel' => 'Csomagolási címke adatok (Nutrition Facts label).',
        ],
        'Foundation,SR Legacy' => [
            'hu'    => 'Foundation + SR Legacy – Mindkét referencia',
            'db'    => '~8 080',
            'desc'  => 'A két legfontosabb nyers alapanyag adatbázis együtt.',
            'mikor' => 'Foundation: 2024, SR Legacy: 2018',
            'hol'   => 'USDA ARS, Beltsville, Maryland',
            'mivel' => 'Foundation: kizárólag labor. SR Legacy: vegyes.',
        ],
    ];
}

/* ═══════════════════════════════════════════════════════════
   NUTRIENT MAPPING
   ═══════════════════════════════════════════════════════════ */

function usda5_get_nutrient_mapping() {
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

/* ═══════════════════════════════════════════════════════════
   MUTEX LOCK (ATOMI – DB INSERT IGNORE, egyetlen row expiry)
   v5.4: uuid+expiry egyetlen JSON row-ban – nincs _expiry külön option
   ═══════════════════════════════════════════════════════════ */

function usda5_acquire_lock() {
    global $wpdb;

    $uuid      = wp_generate_uuid4();
    $lock_key  = '_usda5_lock';
    $now       = time();
    $lock_data = wp_json_encode( [ 'uuid' => $uuid, 'expires' => $now + USDA5_LOCK_TTL ] );

    // Próbáljuk atomi INSERT-tel
    $inserted = $wpdb->query( $wpdb->prepare(
        "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
        $lock_key,
        $lock_data
    ) );

    if ( $inserted ) {
        return $uuid;
    }

    // Nem sikerült – ellenőrizzük hogy lejárt-e
    $existing_raw = $wpdb->get_var( $wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
        $lock_key
    ) );

    if ( $existing_raw ) {
        $existing = json_decode( $existing_raw, true );
        $expires  = intval( $existing['expires'] ?? 0 );

        if ( $expires > 0 && $now > $expires ) {
            // Lejárt – atomi UPDATE: csak akkor sikerül ha az érték nem változott közben
            $updated = $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
                $lock_data,
                $lock_key,
                $existing_raw
            ) );

            if ( $updated ) {
                return $uuid;
            }
        }
    }

    return false;
}

function usda5_release_lock( $uuid ) {
    global $wpdb;
    $lock_key = '_usda5_lock';

    $existing_raw = $wpdb->get_var( $wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
        $lock_key
    ) );

    if ( $existing_raw ) {
        $existing = json_decode( $existing_raw, true );
        if ( isset( $existing['uuid'] ) && $existing['uuid'] === $uuid ) {
            $wpdb->delete( $wpdb->options, [ 'option_name' => $lock_key ] );
            return true;
        }
    }
    return false;
}

function usda5_force_unlock() {
    global $wpdb;
    $wpdb->delete( $wpdb->options, [ 'option_name' => '_usda5_lock' ] );
    // v5.3 kompatibilitás – régi _expiry row törlése
    $wpdb->delete( $wpdb->options, [ 'option_name' => '_usda5_lock_expiry' ] );
    // Régi transient is törlés (v5.2 kompatibilitás)
    delete_transient( USDA5_LOCK_KEY );
}

/* ═══════════════════════════════════════════════════════════
   RATE LIMITER (pontosabb burst-kezelés)
   ═══════════════════════════════════════════════════════════ */

function usda5_rate_consume( $tokens = 1 ) {
    $bucket = get_option( USDA5_RATE_BUCKET_KEY, [] );
    $now    = microtime( true );
    if ( empty( $bucket ) || ! isset( $bucket['tokens'] ) ) {
        $bucket = [ 'tokens' => USDA5_RATE_LIMIT - $tokens, 'last_time' => $now ];
        update_option( USDA5_RATE_BUCKET_KEY, $bucket, false );
        return true;
    }
    $elapsed     = $now - $bucket['last_time'];
    $refill_rate = USDA5_RATE_LIMIT / 3600.0;
    $new_tokens  = min( USDA5_RATE_LIMIT, $bucket['tokens'] + $elapsed * $refill_rate );
    if ( $new_tokens < $tokens ) return false;
    $bucket['tokens']    = $new_tokens - $tokens;
    $bucket['last_time'] = $now;
    update_option( USDA5_RATE_BUCKET_KEY, $bucket, false );
    return true;
}

/* ═══════════════════════════════════════════════════════════
   STATE MANAGEMENT (STOP-SAFE + VERZIÓ CHECK)
   ═══════════════════════════════════════════════════════════ */

function usda5_default_state() {
    return [
        'plugin_version'      => USDA5_PLUGIN_VERSION,
        'status'              => 'idle',
        'mode'                => 'import',
        'page'                => 1,
        'current_processing_page' => 0,
        'dataset'             => 'Foundation',
        'dataset_label'       => 'Foundation Foods',
        'batch_size'          => 50,
        'batch_delay'         => 5,
        'max_items'           => 0,
        'total'               => 0,
        'sync_total'          => 0,
        'started_at'          => '',
        'stats'               => [
            'processed'     => 0,
            'created'       => 0,
            'updated'       => 0,
            'skipped'       => 0,
            'errors'        => 0,
            'empty_batches' => 0,
            'api_calls'     => 0,
            'diff'          => 0,
            'unchanged'     => 0,
            'smart_skipped' => 0,
            'curl28_count'  => 0,
            'dup_prevented' => 0,
        ],
        'consecutive_empty'     => 0,
        'consecutive_full_skip' => 0,
        'last_known_page'       => 0,
        'diffs'                 => [],
        'date'                  => '',
        '_updated'              => microtime( true ),
        'adaptive' => [
            'enabled'              => true,
            'phase'                => 'skip',
            'current_delay'        => 5.0,
            'current_batch_size'   => 50,
            'avg_response_time'    => 0,
            'response_times'       => [],
            'consecutive_timeouts' => 0,
            'consecutive_fast'     => 0,
            'last_adjustment'      => '',
            'cooldown_until'       => 0,
            'total_time_saved'     => 0,
        ],
    ];
}

/**
 * Verzió migráció – automatikus state frissítés plugin update-nél.
 * v5.4: mentés is történik ha migráció volt
 */
function usda5_maybe_migrate_state( &$state, $auto_save = false ) {
    $state_version = $state['plugin_version'] ?? '5.2';
    if ( version_compare( $state_version, USDA5_PLUGIN_VERSION, '>=' ) ) return false;

    // v5.2 → v5.3 migráció
    if ( version_compare( $state_version, '5.3', '<' ) ) {
        if ( ! isset( $state['stats']['empty_batches'] ) )  $state['stats']['empty_batches'] = 0;
        if ( ! isset( $state['stats']['smart_skipped'] ) )  $state['stats']['smart_skipped'] = 0;
        if ( ! isset( $state['stats']['curl28_count'] ) )   $state['stats']['curl28_count'] = 0;
        if ( ! isset( $state['stats']['dup_prevented'] ) )  $state['stats']['dup_prevented'] = 0;
        if ( ! isset( $state['consecutive_empty'] ) )       $state['consecutive_empty'] = 0;
        if ( ! isset( $state['consecutive_full_skip'] ) )   $state['consecutive_full_skip'] = 0;
    }

    // v5.3 → v5.4 migráció
    if ( version_compare( $state_version, '5.4', '<' ) ) {
        // consecutive_empty reset – régi logika miatt lehet magas érték ami block-ol
        // v5.4-ben ez CSAK ténylegesen üres API válasznál nő
        // Ha korábban done-ba került emiatt, resetteljük
        if ( $state['status'] === 'done' && ( $state['consecutive_empty'] ?? 0 ) >= 10 ) {
            $state['consecutive_empty'] = 0;
            $state['status'] = 'stopped'; // Lehetővé teszi a folytatást
        }
    }

    $old_version = $state_version;
    $state['plugin_version'] = USDA5_PLUGIN_VERSION;

    // v5.4: auto-save ha kérve van (get_state-ből híváskor)
    if ( $auto_save ) {
        $state['_updated'] = microtime( true );
        update_option( USDA5_STATE_KEY, $state, false );
        usda5_log( 'info', '🔀 State migráció: v' . $old_version . ' → v' . USDA5_PLUGIN_VERSION . ' (mentve)' );
    }

    return true;
}

function usda5_get_state() {
    wp_cache_delete( USDA5_STATE_KEY, 'options' );
    $state = get_option( USDA5_STATE_KEY, [] );
    if ( empty( $state ) ) return usda5_default_state();

    $default = usda5_default_state();
    $state   = wp_parse_args( $state, $default );
    $state['stats']    = wp_parse_args( $state['stats'] ?? [], $default['stats'] );
    $state['adaptive'] = wp_parse_args( $state['adaptive'] ?? [], $default['adaptive'] );

    // v5.4: migráció + auto-save
    usda5_maybe_migrate_state( $state, true );

    return $state;
}

/**
 * STOP-SAFE state mentés.
 * Ha DB-ben stopping/stopped van, a batch NEM írhatja felül running-gal.
 */
function usda5_save_state( $state ) {
    wp_cache_delete( USDA5_STATE_KEY, 'options' );
    $db_state  = get_option( USDA5_STATE_KEY, [] );
    $db_status = $db_state['status'] ?? 'idle';

    if ( in_array( $db_status, [ 'stopping', 'stopped' ], true ) && $state['status'] === 'running' ) {
        $state['status'] = $db_status;
        usda5_log( 'debug', '🛡️ State védelem: "' . $db_status . '" megőrizve' );
    }

    $state['_updated'] = microtime( true );
    update_option( USDA5_STATE_KEY, $state, false );
}

/**
 * Közvetlen status írás – CSAK stop/start handler használja.
 */
function usda5_force_status( $new_status ) {
    wp_cache_delete( USDA5_STATE_KEY, 'options' );
    $state = get_option( USDA5_STATE_KEY, [] );
    if ( empty( $state ) ) $state = usda5_default_state();
    $state['status']   = $new_status;
    $state['_updated'] = microtime( true );
    update_option( USDA5_STATE_KEY, $state, false );

    if ( in_array( $new_status, [ 'stopping', 'stopped', 'done', 'done_sync', 'error', 'idle' ], true ) ) {
        wp_clear_scheduled_hook( USDA5_CRON_HOOK );
    }
}

function usda5_advance_page( &$state, $new_page ) {
    $state['page'] = $new_page;
    $state['current_processing_page'] = $new_page;
    if ( $new_page > ( $state['last_known_page'] ?? 0 ) ) {
        $state['last_known_page'] = $new_page;
    }
    $state['date'] = current_time( 'Y-m-d H:i:s' );
}

function usda5_should_stop() {
    wp_cache_delete( USDA5_STATE_KEY, 'options' );
    $state  = get_option( USDA5_STATE_KEY, [] );
    $status = $state['status'] ?? 'idle';
    return in_array( $status, [ 'stopping', 'stopped', 'idle' ], true );
}

/**
 * Strict running check – CSAK 'running' számít aktívnak.
 */
function usda5_is_strictly_running() {
    wp_cache_delete( USDA5_STATE_KEY, 'options' );
    $state = get_option( USDA5_STATE_KEY, [] );
    return ( $state['status'] ?? 'idle' ) === 'running';
}

/**
 * v5.4: totalHits alapú – tényleg elértük-e az utolsó oldalt?
 */
function usda5_is_past_last_page( $state, $batch_size ) {
    $total = intval( $state['total'] ?? 0 );
    if ( $total <= 0 ) return false; // nem tudjuk → NE állítsuk le
    $page = intval( $state['page'] ?? 1 );
    $max_page = ceil( $total / $batch_size );
    return ( $page > $max_page );
}

/* ═══════════════════════════════════════════════════════════
   LOG (server-side escape)
   ═══════════════════════════════════════════════════════════ */

function usda5_log( $type, $msg ) {
    $msg   = sanitize_text_field( $msg );
    $type  = sanitize_key( $type );
    $log   = get_option( USDA5_LOG_KEY, [] );
    if ( ! is_array( $log ) ) $log = [];
    $log[] = [ 'type' => $type, 'msg' => $msg, 'time' => current_time( 'H:i:s' ) ];
    if ( count( $log ) > USDA5_LOG_MAX ) $log = array_slice( $log, -USDA5_LOG_MAX );
    update_option( USDA5_LOG_KEY, $log, false );
}

/* ═══════════════════════════════════════════════════════════
   ADAPTÍV SZABÁLYOZÓ
   ═══════════════════════════════════════════════════════════ */

function usda5_adaptive_adjust( &$state, $response_time, $was_error = false, $error_type = '' ) {
    $a = &$state['adaptive'];
    if ( ! $a['enabled'] ) return;

    $a['response_times'][] = $response_time;
    if ( count( $a['response_times'] ) > 10 ) $a['response_times'] = array_slice( $a['response_times'], -10 );
    $a['avg_response_time'] = round( array_sum( $a['response_times'] ) / count( $a['response_times'] ), 2 );

    if ( $was_error || $error_type === 'curl28' ) {
        $a['consecutive_timeouts']++;
        $a['consecutive_fast'] = 0;
        $state['stats']['curl28_count']++;

        if ( $a['consecutive_timeouts'] >= USDA5_CURL28_CONSECUTIVE_MAX ) {
            $a['cooldown_until']     = microtime( true ) + 90;
            $a['current_delay']      = min( USDA5_ADAPTIVE_MAX_DELAY, $a['current_delay'] * 3 );
            $a['current_batch_size'] = USDA5_ADAPTIVE_MIN_BATCH;
            $a['last_adjustment']    = '🛑 Cooldown 90s (3× timeout)';
            usda5_log( 'warn', '🛑 3× timeout! Cooldown 90s. Delay: ' . $a['current_delay'] . 's, Batch: ' . $a['current_batch_size'] );
            return;
        }

        $a['current_delay'] = min( USDA5_ADAPTIVE_MAX_DELAY, $a['current_delay'] * 2 );
        $a['current_batch_size'] = max( USDA5_ADAPTIVE_MIN_BATCH, intval( round( $a['current_batch_size'] * 0.6 / 10 ) * 10 ) );
        $a['last_adjustment'] = '🔻 Lassítás (timeout): delay=' . $a['current_delay'] . 's, batch=' . $a['current_batch_size'];
        usda5_log( 'warn', $a['last_adjustment'] );
        return;
    }

    $a['consecutive_timeouts'] = 0;
    $avg = $a['avg_response_time'];

    if ( $avg < USDA5_ADAPTIVE_SWEET_SPOT ) {
        $a['consecutive_fast']++;
        if ( $a['consecutive_fast'] >= 3 ) {
            $new_delay = max( USDA5_ADAPTIVE_MIN_DELAY, round( $a['current_delay'] * 0.8, 1 ) );
            $new_batch = min( USDA5_ADAPTIVE_MAX_BATCH, $a['current_batch_size'] + 10 );
            if ( $new_delay !== $a['current_delay'] || $new_batch !== $a['current_batch_size'] ) {
                $a['current_delay']      = $new_delay;
                $a['current_batch_size'] = $new_batch;
                $a['last_adjustment']    = '🔺 Gyorsítás: delay=' . $a['current_delay'] . 's, batch=' . $a['current_batch_size'] . ' (avg: ' . $avg . 's)';
                usda5_log( 'info', $a['last_adjustment'] );
            }
            $a['consecutive_fast'] = 0;
        }
        return;
    }

    if ( $avg > USDA5_ADAPTIVE_TIMEOUT_CEIL ) {
        $a['consecutive_fast'] = 0;
        $a['current_delay']      = min( USDA5_ADAPTIVE_MAX_DELAY, round( $a['current_delay'] * 1.5, 1 ) );
        $a['current_batch_size'] = max( USDA5_ADAPTIVE_MIN_BATCH, $a['current_batch_size'] - 10 );
        $a['last_adjustment']    = '🔻 Lassítás (avg ' . $avg . 's): delay=' . $a['current_delay'] . 's, batch=' . $a['current_batch_size'];
        usda5_log( 'info', $a['last_adjustment'] );
        return;
    }

    $a['consecutive_fast'] = 0;
}

function usda5_adaptive_in_cooldown( $state ) {
    $cd = $state['adaptive']['cooldown_until'] ?? 0;
    if ( $cd <= 0 ) return false;
    if ( microtime( true ) < $cd ) {
        usda5_log( 'debug', '⏳ Cooldown: még ' . round( $cd - microtime( true ) ) . 'mp' );
        return true;
    }
    return false;
}

function usda5_adaptive_detect_phase( &$state, $new_count, $total_count ) {
    $a = &$state['adaptive'];
    if ( $total_count === 0 ) { $a['phase'] = 'skip'; return; }
    $ratio = $new_count / $total_count;
    if ( $ratio === 0.0 ) {
        if ( $a['phase'] !== 'skip' ) { $a['phase'] = 'skip'; usda5_log( 'info', '📊 Fázis: SKIP' ); }
    } elseif ( $ratio < 0.2 ) {
        if ( $a['phase'] !== 'tail' ) { $a['phase'] = 'tail'; usda5_log( 'info', '📊 Fázis: TAIL (' . round( $ratio * 100 ) . '% új)' ); }
    } else {
        if ( $a['phase'] !== 'import' ) { $a['phase'] = 'import'; usda5_log( 'info', '📊 Fázis: IMPORT (' . round( $ratio * 100 ) . '% új)' ); }
    }
}

/* ═══════════════════════════════════════════════════════════
   FDC EXISTS CHECK (atomi)
   ═══════════════════════════════════════════════════════════ */

function usda5_fdc_exists( $fdc_id_str ) {
    global $wpdb;
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT p.ID FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE pm.meta_key = 'usda_fdc_id' AND pm.meta_value = %s
           AND p.post_type = 'alapanyag' AND p.post_status IN ('publish','draft')
         LIMIT 1",
        $fdc_id_str
    ) );
    return $exists ? intval( $exists ) : false;
}

/* ═══════════════════════════════════════════════════════════
   BATCH FDC EXISTS CHECK (tömeges, üres tömb biztos)
   ═══════════════════════════════════════════════════════════ */

function usda5_fdc_exists_batch( $fdc_ids ) {
    if ( empty( $fdc_ids ) ) return [];
    global $wpdb;
    $ph   = implode( ',', array_fill( 0, count( $fdc_ids ), '%s' ) );
    $rows = $wpdb->get_col( $wpdb->prepare(
        "SELECT pm.meta_value FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE pm.meta_key = 'usda_fdc_id' AND pm.meta_value IN ($ph)
           AND p.post_type = 'alapanyag' AND p.post_status IN ('publish','draft')",
        ...$fdc_ids
    ) );
    return array_flip( $rows );
}

/**
 * Tömeges FDC→post_id map (search handler-hez)
 */
function usda5_fdc_to_post_id_map( $fdc_ids ) {
    if ( empty( $fdc_ids ) ) return [];
    global $wpdb;
    $ph   = implode( ',', array_fill( 0, count( $fdc_ids ), '%s' ) );
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT pm.meta_value AS fdc_id, p.ID FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE pm.meta_key = 'usda_fdc_id' AND pm.meta_value IN ($ph)
           AND p.post_type = 'alapanyag' AND p.post_status IN ('publish','draft')",
        ...$fdc_ids
    ) );
    $map = [];
    foreach ( $rows as $row ) $map[ $row->fdc_id ] = intval( $row->ID );
    return $map;
}

/* ═══════════════════════════════════════════════════════════
   ACF SAVE HELPER – fallback update_post_meta
   ═══════════════════════════════════════════════════════════ */

function usda5_save_acf_field( $key, $value, $post_id, $critical = false ) {
    if ( function_exists( 'update_field' ) ) {
        $result = update_field( $key, $value, $post_id );
        if ( $result ) return true;
    }
    if ( $critical ) {
        update_post_meta( $post_id, $key, $value );
        usda5_log( 'warn', '⚠️ ACF fallback: ' . $key . ' → meta (post #' . $post_id . ')' );
        return true;
    }
    return false;
}

/* ════════════════════════════════��══════════════════════════
   SEGÉDFÜGGVÉNYEK
   ═══════════════════════════════════════════════════════════ */

/**
 * Csak felesleges jelzők eltávolítása.
 * raw/cooked/fresh/skinless MEGMARAD – tápértékileg megkülönböztető!
 * v5.3: Regex fix – kettőspont biztos eltávolítás
 */
function usda5_clean_description( $name ) {
    $name = preg_replace( '/\bNFS\b/i', '', $name );
    $name = preg_replace( '/\bUPC:\s*\S*/i', '', $name );
    $name = preg_replace( '/\bGTIN:\s*\S*/i', '', $name );
    $name = preg_replace( '/,\s*,/', ',', $name );
    $name = preg_replace( '/,\s*$/', '', $name );
    return trim( preg_replace( '/\s+/', ' ', $name ) );
}

/**
 * PHP <8.0 kompatibilis mb_substr – explicit length
 */
function usda5_proper_case( $name ) {
    $words  = explode( ' ', $name );
    $result = [];
    $small  = [ 'and', 'or', 'of', 'in', 'with', 'the', 'for', 'a', 'an' ];
    foreach ( $words as $i => $word ) {
        if ( empty( $word ) ) continue;
        $word_len = mb_strlen( $word, 'UTF-8' );
        if ( $i > 0 && in_array( mb_strtolower( $word, 'UTF-8' ), $small, true ) ) {
            $result[] = mb_strtolower( $word, 'UTF-8' );
        } else {
            $first = mb_strtoupper( mb_substr( $word, 0, 1, 'UTF-8' ), 'UTF-8' );
            $rest  = ( $word_len > 1 ) ? mb_strtolower( mb_substr( $word, 1, $word_len - 1, 'UTF-8' ), 'UTF-8' ) : '';
            $result[] = $first . $rest;
        }
    }
    return implode( ' ', $result );
}

/* ═══════════════════════════════════════════════════════════
   USDA API: SEARCH
   ═══════════════════════════════════════════════════════════ */

function usda5_api_search( $data_types, $page, $page_size, &$state = null ) {
    $api_url     = 'https://api.nal.usda.gov/fdc/v1/foods/search?api_key=' . USDA_API_KEY;
    $retry_delay = 1;
    $has_state   = ( $state !== null && is_array( $state ) );

    for ( $attempt = 1; $attempt <= USDA5_MAX_RETRY; $attempt++ ) {
        if ( $attempt > 1 && usda5_should_stop() ) return false;

        $t_start  = microtime( true );
        $response = wp_remote_post( $api_url, [
            'timeout' => USDA5_TIMEOUT,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( [
                'query'      => '',
                'dataType'   => $data_types,
                'pageSize'   => $page_size,
                'pageNumber' => $page,
            ] ),
        ] );
        $t_elapsed = round( microtime( true ) - $t_start, 2 );

        if ( is_wp_error( $response ) ) {
            $err    = $response->get_error_message();
            $is_c28 = ( strpos( $err, 'cURL error 28' ) !== false || strpos( $err, 'timed out' ) !== false );
            if ( $has_state ) { usda5_adaptive_adjust( $state, $t_elapsed, true, $is_c28 ? 'curl28' : 'error' ); usda5_save_state( $state ); }
            usda5_log( 'warn', '⚠️ ' . ( $is_c28 ? 'TIMEOUT' : 'HTTP hiba' ) . ' #' . $attempt . '/' . USDA5_MAX_RETRY . ' (' . $t_elapsed . 's): ' . $err );
            if ( $attempt < USDA5_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( $code === 429 ) {
            if ( $has_state ) { usda5_adaptive_adjust( $state, $t_elapsed, true, 'rate_limit' ); usda5_save_state( $state ); }
            usda5_log( 'warn', '🚦 429 Rate limited! retry ' . $attempt );
            if ( $attempt < USDA5_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        if ( $code !== 200 ) {
            usda5_log( 'warn', '⚠️ HTTP ' . $code . ' #' . $attempt . ' (' . $t_elapsed . 's)' );
            if ( $attempt < USDA5_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) || strlen( $body ) < 10 ) {
            usda5_log( 'warn', '⚠️ Üres válasz #' . $attempt );
            if ( $attempt < USDA5_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        $data = json_decode( $body, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            usda5_log( 'warn', '⚠️ JSON hiba #' . $attempt . ': ' . json_last_error_msg() );
            if ( $attempt < USDA5_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        if ( $has_state ) { usda5_adaptive_adjust( $state, $t_elapsed, false ); }
        usda5_log( 'debug', '📡 API: ' . $t_elapsed . 's, ' . count( $data['foods'] ?? [] ) . ' termék' );
        return $data;
    }

    usda5_log( 'error', '❌ API: ' . USDA5_MAX_RETRY . ' sikertelen. Oldal: ' . $page );
    return false;
}

/* ═══════════════════════════════════════════════════════════
   USDA API: PRODUCT
   ═══════════════════════════════════════════════════════════ */

function usda5_api_get_product( $fdc_id ) {
    $url         = 'https://api.nal.usda.gov/fdc/v1/food/' . intval( $fdc_id ) . '?api_key=' . USDA_API_KEY;
    $retry_delay = 1;

    for ( $attempt = 1; $attempt <= USDA5_MAX_RETRY; $attempt++ ) {
        if ( $attempt > 1 && usda5_should_stop() ) return false;

        $response = wp_remote_get( $url, [ 'timeout' => USDA5_TIMEOUT ] );
        if ( is_wp_error( $response ) ) {
            if ( $attempt < USDA5_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code === 429 ) {
            usda5_log( 'warn', '🚦 429 (FDC ' . $fdc_id . ') retry ' . $attempt );
            if ( $attempt < USDA5_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }
        if ( $code !== 200 ) {
            if ( $attempt < USDA5_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) || strlen( $body ) < 10 ) {
            if ( $attempt < USDA5_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        $data = json_decode( $body, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            if ( $attempt < USDA5_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        return $data;
    }
    return false;
}

/* ═══════════════════════════════════════════════════════════
   BATCH ROUTER (STOP-SAFE)
   v5.4: NEM áll meg consecutive_empty-re – totalHits alapú befejezés
   ═══════════════════════════════════════════════════════════ */

function usda5_run_batch() {
    if ( usda5_should_stop() ) return;
    $state = usda5_get_state();
    if ( $state['status'] !== 'running' ) return;

    // Max items check
    if ( $state['mode'] === 'import' && $state['max_items'] > 0 ) {
        if ( ( $state['stats']['created'] ?? 0 ) >= $state['max_items'] ) {
            $state['status'] = 'done';
            usda5_save_state( $state );
            usda5_log( 'success', '🎉 Maximum elérve: ' . $state['max_items'] );
            wp_clear_scheduled_hook( USDA5_CRON_HOOK );
            return;
        }
    }

    // v5.4: totalHits alapú page overflow check – ha tudjuk a totalt ÉS túlléptük
    $batch_size = intval( $state['adaptive']['current_batch_size'] ?? $state['batch_size'] ?? 50 );
    if ( usda5_is_past_last_page( $state, $batch_size ) ) {
        $state['status'] = 'done';
        usda5_save_state( $state );
        usda5_log( 'success', '🏁 Minden feldolgozva! (totalHits: ' . $state['total'] . ', utolsó oldal túllépve)' );
        wp_clear_scheduled_hook( USDA5_CRON_HOOK );
        return;
    }

    // v5.4: consecutive_empty CSAK figyelmeztetés – NEM állítja le
    // (ténylegesen üres API válasz = lehet átmeneti API hiba)
    if ( ( $state['consecutive_empty'] ?? 0 ) >= USDA5_EMPTY_LIMIT ) {
        // Logoljuk de NEM állítjuk le – lehet hogy az API átmenetileg nem ad vissza adatot
        // DE ha totalHits ismert és túlmentünk rajta, a fenti check már leállította
        usda5_log( 'warn', '⚠️ ' . $state['consecutive_empty'] . ' egymás utáni üres/hibás batch – folytatás...' );
        // Reset hogy ne logoljon minden batch-nél
        $state['consecutive_empty'] = 0;
        usda5_save_state( $state );
    }

    if ( ! usda5_rate_consume() ) {
        usda5_log( 'warn', '⏳ Rate limit – várakozás...' );
        return;
    }

    if ( $state['mode'] === 'sync' ) {
        usda5_batch_sync( $state );
    } else {
        usda5_batch_import( $state );
    }
}

/* ═══════════════════════════════════════════════════════════
   BATCH IMPORT (STOP-SAFE + DUPLIKÁTUM-BIZTOS)
   v5.4: Smart skip NEM növeli consecutive_empty-t
         consecutive_empty CSAK ténylegesen üres API válasznál nő
         totalHits alapú befejezés
   ═══════════════════════════════════════════════════════════ */

function usda5_batch_import( $state ) {
    if ( ! function_exists( 'update_field' ) ) {
        $state['status'] = 'error';
        usda5_save_state( $state );
        usda5_log( 'error', '❌ ACF nincs betöltve.' );
        wp_clear_scheduled_hook( USDA5_CRON_HOOK );
        return;
    }

    if ( usda5_should_stop() ) {
        usda5_log( 'info', '⏹ Batch elején leállítva.' );
        return;
    }

    $page    = intval( $state['page'] ?? 1 );
    $dataset = $state['dataset'] ?? 'Foundation';
    $data_types = array_map( 'trim', explode( ',', $dataset ) );

    $batch_size = intval( $state['batch_size'] ?? 50 );
    if ( $state['adaptive']['enabled'] ?? false ) {
        $batch_size = intval( $state['adaptive']['current_batch_size'] ?? $batch_size );
    }

    // Adaptív batch_size visszaszinkronizálása a state-be
    $state['batch_size'] = $batch_size;

    if ( usda5_adaptive_in_cooldown( $state ) ) {
        usda5_save_state( $state );
        return;
    }

    $state['current_processing_page'] = $page;
    usda5_save_state( $state );

    usda5_log( 'info', '📦 Batch oldal ' . $page . ' (méret: ' . $batch_size . ', delay: ' . round( $state['adaptive']['current_delay'] ?? $state['batch_delay'], 1 ) . 's)' );

    $result = usda5_api_search( $data_types, $page, $batch_size, $state );

    if ( $result === false ) {
        $state['stats']['errors']++;
        // v5.4: API hiba → consecutive_empty nő (ténylegesen nem kaptunk adatot)
        $state['consecutive_empty']++;
        $state['stats']['empty_batches']++;
        usda5_advance_page( $state, $page + 1 );
        usda5_save_state( $state );
        usda5_log( 'warn', '📭 API hiba, továbbhaladás (egymás után: ' . $state['consecutive_empty'] . ')' );
        return;
    }

    if ( usda5_should_stop() ) {
        usda5_log( 'info', '⏹ API hívás után leállítva.' );
        return;
    }

    if ( isset( $result['totalHits'] ) && $result['totalHits'] > 0 ) {
        $state['total'] = intval( $result['totalHits'] );
    }

    $state['stats']['api_calls']++;
    $foods = $result['foods'] ?? [];

    if ( empty( $foods ) ) {
        // v5.4: TÉNYLEGESEN üres API válasz → consecutive_empty nő
        $state['consecutive_empty']++;
        $state['stats']['empty_batches']++;

        // totalHits alapú befejezés – ha tudjuk hogy túlmentünk
        if ( usda5_is_past_last_page( $state, $batch_size ) ) {
            $state['status'] = 'done';
            usda5_save_state( $state );
            usda5_log( 'success', '🏁 Minden feldolgozva! Oldal ' . $page . ' üres. (total: ' . $state['total'] . ')' );
            wp_clear_scheduled_hook( USDA5_CRON_HOOK );
            return;
        }

        // Nem tudjuk biztosan hogy vége → tovább lépünk
        usda5_advance_page( $state, $page + 1 );
        usda5_save_state( $state );
        usda5_log( 'info', '📭 Üres oldal ' . $page . ' – továbbhaladás (egymás után: ' . $state['consecutive_empty'] . ')' );
        return;
    }

    // v5.4: Van adat → reset consecutive_empty (CSAK itt, smart skip-nél NEM reseteljük)
    $state['consecutive_empty'] = 0;

    // Batch deduplikáció – gyors előszűrés
    $fdc_ids = [];
    foreach ( $foods as $f ) {
        $fid = intval( $f['fdcId'] ?? 0 );
        if ( $fid > 0 ) $fdc_ids[] = strval( $fid );
    }

    $existing_fdc = usda5_fdc_exists_batch( $fdc_ids );

    $new_in_batch = 0;
    foreach ( $foods as $f ) {
        $fid = strval( intval( $f['fdcId'] ?? 0 ) );
        if ( $fid !== '0' && ! isset( $existing_fdc[ $fid ] ) ) {
            $name = trim( $f['description'] ?? '' );
            if ( ! empty( $name ) ) $new_in_batch++;
        }
    }

    usda5_adaptive_detect_phase( $state, $new_in_batch, count( $foods ) );

    // Smart skip – v5.4: NEM növeli consecutive_empty-t, NEM állítja le az importot
    if ( $new_in_batch === 0 ) {
        $state['consecutive_full_skip']++;
        $state['stats']['smart_skipped']++;
        $state['stats']['skipped']   += count( $foods );
        $state['stats']['processed'] += count( $foods );

        $cfs  = $state['consecutive_full_skip'];
        $skip = 1;
        if ( $cfs >= 10 )     $skip = min( USDA5_SKIP_MAX_JUMP, 20 );
        elseif ( $cfs >= 5 )  $skip = 10;
        elseif ( $cfs >= 3 )  $skip = 5;

        $new_page = $page + $skip;

        // v5.4: totalHits overflow guard – ne ugorjunk a végtelenbe
        $total = intval( $state['total'] ?? 0 );
        if ( $total > 0 ) {
            $max_page = ceil( $total / $batch_size );
            if ( $new_page > $max_page + 2 ) {
                // Túlmentünk – de NEM állítjuk le, hanem visszaállunk a max_page-re
                // Lehet hogy közben új adatok jöttek
                $state['status'] = 'done';
                usda5_save_state( $state );
                usda5_log( 'success', '🏁 Smart skip túllépte a totalHits-t (' . $total . '). Import kész!' );
                wp_clear_scheduled_hook( USDA5_CRON_HOOK );
                return;
            }
        }

        usda5_advance_page( $state, $new_page );

        $saved = $skip * floatval( $state['adaptive']['current_delay'] ?? $state['batch_delay'] );
        $state['adaptive']['total_time_saved'] += $saved;

        if ( $skip > 1 ) {
            usda5_log( 'info', '⚡ Turbo skip: +' . $skip . ' oldal → ' . $new_page . ' (~' . round( $saved ) . 's megtakarítva)' );
        } else {
            usda5_log( 'info', '⏭ Skip: oldal ' . $page . ' – mind létezik → ' . $new_page );
        }

        usda5_save_state( $state );
        return;
    }

    $state['consecutive_full_skip'] = 0;

    $nutrient_map  = usda5_get_nutrient_mapping();
    $batch_created = 0;
    $batch_skipped = 0;
    $batch_errors  = 0;
    $batch_dup_prevented = 0;

    foreach ( $foods as $food ) {
        if ( usda5_should_stop() ) {
            usda5_log( 'info', '⏹ Leállítva batch közben. Oldal: ' . $page . ', +' . $batch_created . ' új' );
            usda5_save_state( $state );
            wp_clear_scheduled_hook( USDA5_CRON_HOOK );
            return;
        }

        $fdc_id = intval( $food['fdcId'] ?? 0 );
        $name   = trim( $food['description'] ?? '' );
        if ( ! $fdc_id || empty( $name ) ) { $batch_skipped++; $state['stats']['skipped']++; $state['stats']['processed']++; continue; }

        $fdc_str = strval( $fdc_id );

        // 1. szűrő: batch cache (gyors)
        if ( isset( $existing_fdc[ $fdc_str ] ) ) { $batch_skipped++; $state['stats']['skipped']++; $state['stats']['processed']++; continue; }

        if ( $state['max_items'] > 0 && $state['stats']['created'] >= $state['max_items'] ) {
            $state['status'] = 'done';
            usda5_save_state( $state );
            usda5_log( 'success', '🎉 Maximum elérve: ' . $state['max_items'] );
            wp_clear_scheduled_hook( USDA5_CRON_HOOK );
            return;
        }

        // 2. szűrő: atomi DB check (duplikátum-biztos)
        $existing_post_id = usda5_fdc_exists( $fdc_str );
        if ( $existing_post_id !== false ) {
            $batch_dup_prevented++;
            $state['stats']['dup_prevented']++;
            $state['stats']['skipped']++;
            $state['stats']['processed']++;
            $existing_fdc[ $fdc_str ] = true;
            usda5_log( 'debug', '🛡️ Dup megakadályozva: FDC ' . $fdc_str . ' (#' . $existing_post_id . ')' );
            continue;
        }

        $clean_name = usda5_proper_case( usda5_clean_description( $name ) );

        $post_id = wp_insert_post( [
            'post_type'   => 'alapanyag',
            'post_title'  => $clean_name,
            'post_status' => 'publish',
            'meta_input'  => [
                'usda_import_version' => USDA5_PLUGIN_VERSION,
            ],
        ], true );

        if ( is_wp_error( $post_id ) ) {
            $batch_errors++;
            $state['stats']['errors']++;
            $state['stats']['processed']++;
            usda5_log( 'error', '❌ WP: ' . $post_id->get_error_message() );
            continue;
        }

        // Tápanyagok mentése
        if ( isset( $food['foodNutrients'] ) && is_array( $food['foodNutrients'] ) ) {
            foreach ( $food['foodNutrients'] as $fn ) {
                $nid = intval( $fn['nutrientId'] ?? 0 );
                if ( isset( $nutrient_map[ $nid ] ) && isset( $fn['value'] ) ) {
                    usda5_save_acf_field( $nutrient_map[ $nid ]['acf_key'], round( floatval( $fn['value'] ), 4 ), $post_id );
                }
            }
        }

        // Azonosító mezők – usda_fdc_id KRITIKUS
        usda5_save_acf_field( 'usda_fdc_id', $fdc_str, $post_id, true );
        usda5_save_acf_field( 'usda_last_sync', current_time( 'Y-m-d H:i:s' ), $post_id );
        usda5_save_acf_field( 'elsodleges_forras', 'usda', $post_id );
        usda5_save_acf_field( 'eredeti_nev', $name, $post_id );

        $batch_created++;
        $state['stats']['created']++;
        $state['stats']['processed']++;
        $existing_fdc[ $fdc_str ] = true;
    }

    usda5_advance_page( $state, $page + 1 );

    // Ha kevesebb termék jött mint kértünk → utolsó oldal
    if ( count( $foods ) < $batch_size ) {
        $state['status'] = 'done';
        usda5_save_state( $state );
        usda5_log( 'success', '🏁 Import kész! (utolsó oldal) Össz: ' . $state['stats']['created'] . ' új.' );
        wp_clear_scheduled_hook( USDA5_CRON_HOOK );
        return;
    }

    // Batch végén is stop check
    if ( usda5_should_stop() ) {
        usda5_log( 'info', '⏹ Batch végén leállítva. Oldal: ' . $page );
        usda5_save_state( $state );
        wp_clear_scheduled_hook( USDA5_CRON_HOOK );
        return;
    }

    usda5_save_state( $state );

    $dup_info = $batch_dup_prevented > 0 ? ', 🛡️' . $batch_dup_prevented . ' dup' : '';
    usda5_log( 'success', '✅ Oldal ' . $page . ': +' . $batch_created . ' új, ' . $batch_skipped . ' skip' . $dup_info . ' | Össz: ' . $state['stats']['created'] . ' | Köv: ' . $state['page'] . ' | avg: ' . ( $state['adaptive']['avg_response_time'] ?? '?' ) . 's' );
}

/* ═══════════════════════════════════════════════════════════
   BATCH SYNC
   ═══════════════════════════════════════════════════════════ */

function usda5_batch_sync( $state ) {
    if ( ! function_exists( 'update_field' ) || ! function_exists( 'get_field' ) ) {
        $state['status'] = 'error';
        usda5_save_state( $state );
        usda5_log( 'error', '❌ ACF nincs betöltve.' );
        wp_clear_scheduled_hook( USDA5_CRON_HOOK );
        return;
    }

    if ( usda5_should_stop() ) {
        usda5_log( 'info', '⏹ Sync batch elején leállítva.' );
        return;
    }

    $page     = intval( $state['page'] ?? 1 );
    $per_page = 10;

    $state['current_processing_page'] = $page;
    usda5_save_state( $state );

    usda5_log( 'info', '🔄 Szinkron batch #' . $page );

    $post_ids = get_posts( [
        'post_type'      => 'alapanyag',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'meta_query'     => [ [ 'key' => 'usda_fdc_id', 'value' => '', 'compare' => '!=' ] ],
        'fields'         => 'ids',
    ] );

    if ( empty( $post_ids ) ) {
        $state['status'] = 'done_sync';
        usda5_save_state( $state );
        $dc = count( $state['diffs'] ?? [] );
        usda5_log( 'success', '🎉 Szinkron kész! ' . $dc . ' eltérés.' );
        wp_clear_scheduled_hook( USDA5_CRON_HOOK );
        return;
    }

    $nutrient_map = usda5_get_nutrient_mapping();

    foreach ( $post_ids as $pid ) {
        if ( usda5_should_stop() ) {
            usda5_log( 'info', '⏹ Szinkron leállítva. Oldal: ' . $page );
            usda5_save_state( $state );
            wp_clear_scheduled_hook( USDA5_CRON_HOOK );
            return;
        }

        $fdc_id = get_field( 'usda_fdc_id', $pid );
        if ( empty( $fdc_id ) ) { $state['stats']['skipped']++; $state['stats']['processed']++; continue; }

        if ( ! usda5_rate_consume() ) {
            usda5_log( 'warn', '⏳ Rate limit – szinkron szünetel' );
            usda5_save_state( $state );
            return;
        }

        $product = usda5_api_get_product( $fdc_id );
        $state['stats']['api_calls']++;

        if ( $product === false ) {
            $state['stats']['errors']++;
            $state['stats']['processed']++;
            usda5_log( 'error', '❌ API: FDC ' . $fdc_id );
            continue;
        }

        if ( empty( $product['foodNutrients'] ) ) {
            $state['stats']['skipped']++;
            $state['stats']['processed']++;
            continue;
        }

        $changes = [];
        foreach ( $product['foodNutrients'] as $fn ) {
            $nid = intval( $fn['nutrient']['id'] ?? $fn['nutrientId'] ?? 0 );
            if ( ! isset( $nutrient_map[ $nid ] ) ) continue;
            $new = isset( $fn['amount'] ) ? round( floatval( $fn['amount'] ), 4 ) : ( isset( $fn['value'] ) ? round( floatval( $fn['value'] ), 4 ) : null );
            if ( $new === null ) continue;
            $acf_key = $nutrient_map[ $nid ]['acf_key'];
            $old     = floatval( get_field( $acf_key, $pid ) );
            if ( abs( $new - $old ) > 0.01 ) {
                $changes[] = [
                    'acf_key'   => $acf_key,
                    'label'     => $nutrient_map[ $nid ]['label'],
                    'egyseg'    => $nutrient_map[ $nid ]['egyseg'],
                    'old_value' => round( $old, 2 ),
                    'new_value' => round( $new, 2 ),
                ];
            }
        }

        if ( ! empty( $changes ) ) {
            $state['stats']['diff']++;
            if ( count( $state['diffs'] ) < USDA5_DIFFS_MAX ) {
                $state['diffs'][] = [
                    'post_id' => $pid,
                    'name'    => get_the_title( $pid ),
                    'fdc_id'  => $fdc_id,
                    'changes' => $changes,
                ];
            }
            usda5_log( 'warn', '⚠️ Eltérés: ' . get_the_title( $pid ) . ' (' . count( $changes ) . ' mező)' );
        } else {
            $state['stats']['unchanged']++;
        }

        $state['stats']['processed']++;
        usleep( 600000 );
    }

    usda5_advance_page( $state, $page + 1 );

    if ( usda5_should_stop() ) {
        usda5_log( 'info', '⏹ Sync végén leállítva.' );
        usda5_save_state( $state );
        wp_clear_scheduled_hook( USDA5_CRON_HOOK );
        return;
    }

    usda5_save_state( $state );
    usda5_log( 'info', '🔄 Szinkron oldal ' . $page . ' kész. Eltérések: ' . ( $state['stats']['diff'] ?? 0 ) );
}

/* ═══════════════════════════════════════════════════════════
   ADMIN MENÜ
   ═══════════════════════════════════════════════════════════ */

function usda5_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=alapanyag',
        'USDA Import v5.4 Final',
        '🇺🇸 USDA Import',
        'manage_options',
        'usda-import',
        'usda5_admin_page'
    );
}
add_action( 'admin_menu', 'usda5_admin_menu' );

/* ═══════════════════════════════════════════════════════════
   ADMIN OLDAL HTML
   ═══════════════════════════════════════════════════════════ */

function usda5_admin_page() {
    global $wpdb;

    $existing_count = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'alapanyag' AND p.post_status IN ('publish','draft')
           AND pm.meta_key = 'usda_fdc_id' AND pm.meta_value != ''"
    );

    $state       = usda5_get_state();
    $status      = $state['status'] ?? 'idle';
    $is_running  = ( $status === 'running' );
    $is_stopping = ( $status === 'stopping' );
    $has_saved   = ! $is_running && ! $is_stopping && ! empty( $state['page'] ) && intval( $state['page'] ) > 1 && in_array( $status, [ 'stopped', 'error' ], true );
    $has_api_key = defined( 'USDA_API_KEY' ) && ! empty( USDA_API_KEY );
    $datasets    = usda5_get_dataset_descriptions();
    $last_known  = intval( $state['last_known_page'] ?? 0 );

    $saved_dataset    = $state['dataset'] ?? 'Foundation';
    $saved_batch_size = intval( $state['adaptive']['current_batch_size'] ?? $state['batch_size'] ?? 50 );
    $saved_delay      = floatval( $state['adaptive']['current_delay'] ?? $state['batch_delay'] ?? 5 );
    $saved_max        = intval( $state['max_items'] ?? 0 );

    ?>
    <div class="wrap">
        <h1>🇺🇸 USDA FoodData Central – Import <small style="font-size:0.6em;color:#999;">v5.4 Final</small></h1>

        <?php if ( $is_stopping ) : ?>
        <div style="background:#fef3c7;border:2px solid #f59e0b;border-radius:8px;padding:16px 20px;margin:20px 0;max-width:900px;">
            <h3 style="margin:0;color:#92400e;">⏳ Leállítás folyamatban...</h3>
            <p style="margin:8px 0 0;color:#92400e;">Az aktuális batch befejezése után megáll.</p>
            <script>setTimeout(function(){location.reload();},3000);</script>
        </div>
        <?php endif; ?>

        <div class="usda5-card">
            <h3 style="margin-top:0;">ℹ️ Állapot</h3>
            <table class="widefat" style="max-width:600px;">
                <tr><td><strong>USDA-ból importált</strong></td><td><strong style="font-size:1.2rem;color:#2563eb;"><?php echo $existing_count; ?></strong> db</td></tr>
                <tr><td><strong>API limit</strong></td><td><span style="color:#dc2626;font-weight:600;">1 000 kérés/óra</span> (~16/perc)</td></tr>
                <tr><td><strong>Motor</strong></td><td>🔄 Hibrid Adaptív v5.4 Final (totalHits-done, never-stop-skip)</td></tr>
                <tr><td><strong>API Key</strong></td><td><?php if($has_api_key):?><span style="color:#16a34a;">✅</span> (...<?php echo esc_html(substr(USDA_API_KEY,-6));?>)<?php else:?><span style="color:#dc2626;">❌ Hiányzik!</span><?php endif;?></td></tr>
                <?php if ( $is_running ) : ?>
                <tr><td><strong>Folyamat</strong></td><td><span class="usda5-pulse" style="color:#16a34a;font-weight:700;">🟢 FUT</span> – Oldal <?php echo intval($state['current_processing_page'] ?: $state['page']);?> | +<?php echo intval($state['stats']['created']);?> | <?php echo esc_html($state['dataset_label']);?></td></tr>
                <?php elseif ( $has_saved ) : ?>
                <tr><td><strong>Mentett pozíció</strong></td><td><span style="color:#2563eb;">📌 Oldal <?php echo intval($state['page']);?> | <?php echo esc_html($state['dataset_label']);?> | +<?php echo intval($state['stats']['created']);?> | <?php echo esc_html($state['date']);?></span></td></tr>
                <?php endif; ?>
            </table>
        </div>

        <div id="usda5-settings-box" class="usda5-card" style="background:#f8fafc;border-color:#e2e8f0;<?php echo ($is_running||$is_stopping)?'opacity:0.5;pointer-events:none;':'';?>">
            <h3 style="margin-top:0;">⚙️ Beállítások</h3>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
                <div>
                    <label class="usda5-label" for="usda5-dataset">Adatkészlet:</label>
                    <select id="usda5-dataset" style="width:100%;">
                        <?php foreach($datasets as $key=>$info):?>
                            <option value="<?php echo esc_attr($key);?>"<?php selected($key,$saved_dataset);?>><?php echo esc_html($info['hu']);?> (<?php echo esc_html($info['db']);?>)</option>
                        <?php endforeach;?>
                    </select>
                </div>
                <div>
                    <label class="usda5-label" for="usda5-batch-size">Induló batch:</label>
                    <input type="number" id="usda5-batch-size" value="<?php echo $saved_batch_size; ?>" min="10" max="200" step="10" style="width:100%;">
                    <span class="usda5-hint">Adaptív: 10–200 auto</span>
                </div>
                <div>
                    <label class="usda5-label" for="usda5-batch-delay">Induló delay:</label>
                    <input type="number" id="usda5-batch-delay" value="<?php echo $saved_delay; ?>" min="4" max="60" step="1" style="width:100%;">
                    <span class="usda5-hint">Adaptív: 4–60s auto</span>
                </div>
                <div>
                    <label class="usda5-label" for="usda5-max">Maximum:</label>
                    <input type="number" id="usda5-max" value="<?php echo $saved_max; ?>" min="0" max="999999" step="10" style="width:100%;">
                    <span class="usda5-hint">0 = korlátlan</span>
                </div>
            </div>
            <div id="usda5-dataset-info" style="margin-top:12px;padding:14px 16px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;font-size:0.85rem;color:#1e3a5f;line-height:1.6;"></div>
            <div id="usda5-limit-warning" class="usda5-warning" style="display:none;"></div>
            <div id="usda5-time-estimate-box" class="usda5-estimate">💡 <strong>Becsült idő</strong>: <span id="usda5-time-estimate">–</span> (<span id="usda5-batch-count-est">–</span> batch × <span id="usda5-delay-est">–</span>s) | API kérés/perc: <strong><span id="usda5-req-per-min">–</span></strong>/16</div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;max-width:900px;margin:20px 0;">
            <div class="usda5-mode-card" style="background:#f0fdf4;border-color:#86efac;">
                <h3 style="margin-top:0;color:#16a34a;font-size:1rem;">🚀 Új import</h3>
                <?php if($last_known>1&&$existing_count>0&&!$is_running&&!$is_stopping):?>
                    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;padding:8px 12px;margin-bottom:10px;font-size:0.82rem;color:#92400e;">⚠️ Már van <strong><?php echo $existing_count;?></strong> USDA (oldal <?php echo $last_known;?>-ig).</div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <button id="usda5-import-start" class="button button-primary" style="font-size:13px;padding:5px 16px;" <?php echo(!$has_api_key?'disabled':'');?>>🚀 Elejétől</button>
                        <button id="usda5-import-smart" class="button" style="font-size:13px;padding:5px 16px;background:#f59e0b;color:#fff;border-color:#d97706;">⚡ Smart (<?php echo $last_known;?>. oldaltól)</button>
                    </div>
                    <span class="usda5-hint">⚡ = átugorja az ismert <?php echo $last_known-1;?> oldalt</span>
                <?php else:?>
                    <p style="font-size:0.82rem;color:#555;">Az elejétől indul. Adaptív motor hangolja.</p>
                    <button id="usda5-import-start" class="button button-primary" style="font-size:13px;padding:5px 16px;" <?php echo($is_running||$is_stopping||!$has_api_key?'disabled':'');?>>🚀 Indítás</button>
                <?php endif;?>
            </div>

            <div class="usda5-mode-card" style="background:<?php echo $has_saved?'#eff6ff':($is_running?'#f0fdf4':'#f8fafc');?>;border-color:<?php echo $has_saved?'#93c5fd':($is_running?'#86efac':'#e2e8f0');?>;">
                <h3 style="margin-top:0;color:#2563eb;font-size:1rem;">▶️ Folytatás</h3>
                <?php if($is_running||$is_stopping):?>
                    <p style="font-size:0.82rem;color:#16a34a;font-weight:600;">🟢 <?php echo $is_stopping?'Leáll...':'Fut...';?></p>
                    <button class="button" disabled>▶️ <?php echo $is_stopping?'Leáll...':'Fut...';?></button>
                <?php elseif($has_saved):?>
                    <p style="font-size:0.82rem;color:#555;">Utolsó: <strong>oldal <?php echo intval($state['page']);?></strong> (+<?php echo intval($state['stats']['created']);?>)<br><?php echo esc_html($state['date']);?></p>
                    <button id="usda5-import-continue" class="button" style="font-size:13px;padding:5px 16px;background:#3b82f6;color:#fff;border-color:#2563eb;">▶️ Folytatás</button>
                    <button id="usda5-import-reset" class="button" style="font-size:12px;padding:4px 10px;color:#dc2626;margin-left:4px;">🗑️</button>
                <?php else:?>
                    <p style="font-size:0.82rem;color:#999;">Nincs mentett pozíció.</p>
                    <button class="button" disabled>▶️ Nincs adat</button>
                <?php endif;?>
            </div>

            <div class="usda5-mode-card" style="background:#fffbeb;border-color:#fcd34d;">
                <h3 style="margin-top:0;color:#d97706;font-size:1rem;">🔄 Szinkron</h3>
                <p style="font-size:0.82rem;color:#555;">Meglévő USDA alapanyagok ellenőrzése.</p>
                <button id="usda5-sync-start" class="button" style="font-size:13px;padding:5px 16px;background:#f59e0b;color:#fff;border-color:#d97706;" <?php echo($existing_count<1||$is_running||$is_stopping?'disabled':'');?>>🔄 Szinkron</button>
            </div>
        </div>

        <div style="margin:0 0 12px;">
            <button id="usda5-stop" class="button button-secondary" style="font-size:14px;padding:6px 20px;<?php echo $is_running?'':'display:none;';?>">⏹ Leállítás</button>
        </div>

        <div class="usda5-card">
            <h3 style="margin-top:0;">🔍 Egyedi USDA keresés</h3>
            <div style="display:flex;gap:8px;">
                <input type="text" id="usda5-search-query" placeholder="pl. chicken breast, apple, rice..." style="flex:1;font-size:14px;padding:6px 12px;">
                <select id="usda5-search-type" style="font-size:14px;padding:6px 8px;">
                    <option value="Foundation,SR Legacy">Foundation + SR Legacy</option>
                    <option value="Foundation">Csak Foundation</option>
                    <option value="SR Legacy">Csak SR Legacy</option>
                    <option value="Branded">Branded (bolti)</option>
                </select>
                <button id="usda5-search-btn" class="button" style="font-size:14px;padding:6px 16px;">🔍 Keresés</button>
            </div>
            <div id="usda5-search-results" style="margin-top:12px;"></div>
        </div>

        <div id="usda5-live-status" class="usda5-card" style="<?php echo $is_running?'':'display:none;';?>">
            <h3 style="margin-top:0;">📊 Élő státusz <span id="usda5-live-indicator" class="usda5-pulse" style="color:#16a34a;">●</span></h3>
            <div class="usda5-progress-wrap"><div id="usda5-progress-bar" class="usda5-progress-fill">0%</div></div>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:8px;">
                <div style="text-align:center;padding:8px;background:#f0f6fc;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;" id="usda5-s-processed">0</div><div style="font-size:0.72rem;color:#666;">Feldolgozva</div></div>
                <div style="text-align:center;padding:8px;background:#f0fdf4;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#16a34a;" id="usda5-s-created">0</div><div style="font-size:0.72rem;color:#666;">Új</div></div>
                <div style="text-align:center;padding:8px;background:#fffbeb;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#d97706;" id="usda5-s-skipped">0</div><div style="font-size:0.72rem;color:#666;">Kihagyva</div></div>
                <div style="text-align:center;padding:8px;background:#fef2f2;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#dc2626;" id="usda5-s-errors">0</div><div style="font-size:0.72rem;color:#666;">Hiba</div></div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:12px;">
                <div style="text-align:center;padding:8px;background:#f5f3ff;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#7c3aed;" id="usda5-s-empty">0</div><div style="font-size:0.72rem;color:#666;">Üres batch</div></div>
                <div style="text-align:center;padding:8px;background:#f0f6fc;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#2563eb;" id="usda5-s-page">0</div><div style="font-size:0.72rem;color:#666;">Oldal</div></div>
                <div style="text-align:center;padding:8px;background:#fef3c7;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#d97706;" id="usda5-s-smartskip">0</div><div style="font-size:0.72rem;color:#666;">Smart skip</div></div>
                <div style="text-align:center;padding:8px;background:#f0f6fc;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#0891b2;" id="usda5-s-api">0</div><div style="font-size:0.72rem;color:#666;">API hívás</div></div>
                <div style="text-align:center;padding:8px;background:#ecfdf5;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#059669;" id="usda5-s-dupblocked">0</div><div style="font-size:0.72rem;color:#666;">🛡️ Dup</div></div>
            </div>

            <div id="usda5-adaptive-info" style="font-size:0.82rem;color:#555;margin-bottom:8px;padding:8px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;"></div>
            <div id="usda5-eta" style="font-size:0.85rem;color:#666;margin-bottom:8px;padding:6px 10px;background:#f8fafc;border-radius:4px;"></div>
            <div id="usda5-live-info" style="font-size:0.85rem;color:#666;margin-bottom:8px;padding:6px 10px;background:#f8fafc;border-radius:4px;"></div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                <span style="font-size:0.85rem;font-weight:600;">📋 Log</span>
                <button id="usda5-log-export" class="button" style="font-size:11px;padding:2px 8px;">📥 Export</button>
            </div>
            <div id="usda5-log" style="background:#1e1e1e;color:#d4d4d4;font-family:monospace;font-size:0.82rem;padding:12px 16px;border-radius:6px;max-height:300px;overflow-y:auto;line-height:1.6;"></div>
        </div>

        <div id="usda5-diff-section" style="display:none;background:#fff;border:2px solid #3b82f6;border-radius:8px;padding:20px 24px;margin:20px 0;max-width:900px;">
            <h3 style="margin-top:0;color:#2563eb;">⚠️ Eltérések – <span id="usda5-diff-count">0</span> alapanyagnál</h3>
            <div style="margin-bottom:12px;"><label style="font-size:0.88rem;cursor:pointer;"><input type="checkbox" id="usda5-diff-select-all" checked> Összes</label></div>
            <div id="usda5-diff-list" style="max-height:500px;overflow-y:auto;"></div>
            <div style="margin-top:16px;">
                <button id="usda5-diff-apply" class="button button-primary" style="font-size:14px;padding:6px 20px;">✅ Frissítés</button>
                <span id="usda5-diff-apply-status" style="margin-left:12px;font-size:0.88rem;"></span>
            </div>
        </div>
    </div>

    <style>
        .usda5-card{background:#fff;border:1px solid #ccd0d4;border-radius:8px;padding:20px 24px;margin:20px 0;max-width:900px;}
        .usda5-mode-card{border:2px solid;border-radius:8px;padding:18px 20px;}
        .usda5-label{font-weight:600;font-size:0.88rem;display:block;margin-bottom:4px;}
        .usda5-hint{color:#888;font-size:0.75rem;}
        .usda5-warning{margin-top:12px;padding:10px 14px;border-radius:6px;font-size:0.85rem;}
        .usda5-estimate{margin-top:12px;padding:10px 14px;background:#fffbeb;border-radius:6px;font-size:0.85rem;color:#92400e;}
        .usda5-progress-wrap{background:#f0f0f1;border-radius:6px;height:28px;overflow:hidden;margin-bottom:12px;}
        .usda5-progress-fill{background:linear-gradient(90deg,#3b82f6,#6366f1);height:100%;width:0%;transition:width 0.4s ease;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;font-size:0.85rem;}
        @keyframes usda5pulse{0%,100%{opacity:1;}50%{opacity:0.4;}}
        .usda5-pulse{animation:usda5pulse 1.5s infinite;}
    </style>
    <?php
}

/* ═══════════════════════════════════════════════════════════
   ADMIN SCRIPTS (STOP-SAFE POLLING)
   v5.4: JS bitwise OR fix (| → ||), guard minden DOM műveletnél
   ═══════════════════════════════════════════════════════════ */

function usda5_admin_scripts( $hook ) {
    if ( $hook !== 'alapanyag_page_usda-import' ) return;
    $state = usda5_get_state();
    $datasets_json = wp_json_encode( usda5_get_dataset_descriptions() );

    $js = <<<JSEOF
(function(){
'use strict';
if(typeof usda5Data==='undefined'){console.error('USDA5: usda5Data not loaded');return;}
var \$=function(id){return document.getElementById(id);};
var pollTimer=null,syncAllDiffs=[],startTime=0,startCreated=0;
var stopRequested=false;
var DS={$datasets_json};

function updateDSInfo(){
    var el=\$('usda5-dataset');if(!el)return;
    var v=el.value,i=DS[v];if(!i){var di=\$('usda5-dataset-info');if(di)di.innerHTML='';return;}
    var di=\$('usda5-dataset-info');if(di)di.innerHTML='<div style="margin-bottom:6px;"><strong>📚 '+esc(i.hu)+'</strong> <span style="color:#64748b;">('+esc(i.db)+')</span></div><div style="margin-bottom:4px;">'+esc(i.desc)+'</div><div style="display:grid;grid-template-columns:auto 1fr;gap:2px 10px;margin-top:8px;font-size:0.82rem;color:#475569;"><strong>📅</strong><span>'+esc(i.mikor)+'</span><strong>📍</strong><span>'+esc(i.hol)+'</span><strong>🔬</strong><span>'+esc(i.mivel)+'</span></div>';
}
if(\$('usda5-dataset')){\$('usda5-dataset').addEventListener('change',updateDSInfo);updateDSInfo();}

function validate(){
    var bsEl=\$('usda5-batch-size'),dlEl=\$('usda5-batch-delay'),mxEl=\$('usda5-max');
    if(!bsEl||!dlEl||!mxEl)return true;
    var bs=parseInt(bsEl.value)||50;
    var dl=parseFloat(dlEl.value)||5;
    var mx=parseInt(mxEl.value);
    if(isNaN(mx)||mx<0) mx=0;
    var rpm=Math.round(60/dl);
    var batches=mx>0?Math.ceil(mx/bs):0;
    var tsec=batches*dl,mn=Math.floor(tsec/60),sc=Math.round(tsec%60);
    var rpmEl=\$('usda5-req-per-min'),bceEl=\$('usda5-batch-count-est'),deEl=\$('usda5-delay-est'),teEl=\$('usda5-time-estimate');
    if(rpmEl)rpmEl.textContent=rpm;
    if(bceEl)bceEl.textContent=mx>0?batches:'∞';
    if(deEl)deEl.textContent=dl;
    if(teEl)teEl.textContent=mx>0?((mn>0?'~'+mn+'p ':'')+sc+'s'):'∞ (korlátlan)';
    var w=\$('usda5-limit-warning'),sb=\$('usda5-import-start'),cb=\$('usda5-import-continue');
    if(rpm>16){if(w){w.style.display='block';w.style.background='#fef2f2';w.style.color='#dc2626';w.innerHTML='🚫 Túl gyors! '+rpm+'/perc > 16.';}if(sb)sb.disabled=true;if(cb)cb.disabled=true;return false;}
    if(rpm>12){if(w){w.style.display='block';w.style.background='#fffbeb';w.style.color='#92400e';w.innerHTML='⚠️ Közel a limithez! '+rpm+'/perc.';}if(sb)sb.disabled=false;if(cb)cb.disabled=false;return true;}
    if(w)w.style.display='none';if(sb)sb.disabled=false;if(cb)cb.disabled=false;return true;
}
if(\$('usda5-batch-size'))\$('usda5-batch-size').addEventListener('input',validate);
if(\$('usda5-batch-delay'))\$('usda5-batch-delay').addEventListener('input',validate);
if(\$('usda5-max'))\$('usda5-max').addEventListener('input',validate);
validate();

function ajax(action,data,cb){
    var fd=new FormData();fd.append('action',action);fd.append('nonce',usda5Data.nonce);
    if(data){for(var k in data)fd.append(k,data[k]);}
    fetch(usda5Data.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){if(cb)cb(r);}).catch(function(e){if(cb)cb({success:false,data:'Hálózat: '+e.message});});
}

function startProc(mode,page){
    if(!validate())return;
    var mxEl=\$('usda5-max');
    var mx=mxEl?parseInt(mxEl.value):0;
    if(isNaN(mx)||mx<0) mx=0;
    var d={
        mode:mode,
        page:page,
        dataset:(\$('usda5-dataset')||{}).value||'Foundation',
        batch_size:(\$('usda5-batch-size')||{}).value||50,
        batch_delay:(\$('usda5-batch-delay')||{}).value||5,
        max_items:mx
    };
    ajax('usda5_start',d,function(r){if(r.success)location.reload();else alert('Hiba: '+(r.data||'Ismeretlen'));});
}

if(\$('usda5-import-start'))\$('usda5-import-start').addEventListener('click',function(){startProc('import',1);});
if(\$('usda5-import-smart'))\$('usda5-import-smart').addEventListener('click',function(){startProc('import',usda5Data.lastKnownPage||1);});
if(\$('usda5-import-continue'))\$('usda5-import-continue').addEventListener('click',function(){startProc('continue',usda5Data.savedPage);});
if(\$('usda5-import-reset'))\$('usda5-import-reset').addEventListener('click',function(){if(!confirm('Biztosan törlöd?'))return;ajax('usda5_reset',{},function(){location.reload();});});

if(\$('usda5-stop'))\$('usda5-stop').addEventListener('click',function(){
    this.disabled=true;this.textContent='⏳ Leállítás...';stopRequested=true;
    ajax('usda5_stop',{},function(){});
});

if(\$('usda5-sync-start'))\$('usda5-sync-start').addEventListener('click',function(){startProc('sync',1);});

function poll(){
    if(stopRequested){
        ajax('usda5_status_readonly',{},function(r){
            if(!r.success)return;var s=r.data;
            updateUI(s);
            if(s.status!=='running'&&s.status!=='stopping'){
                clearInterval(pollTimer);setTimeout(function(){location.reload();},500);
            }
        });
        return;
    }
    ajax('usda5_status',{},function(r){
        if(!r.success)return;var s=r.data;
        updateUI(s);
        if(s.status!=='running'){
            clearInterval(pollTimer);
            var li=\$('usda5-live-indicator');if(li){li.textContent='⏹';li.style.animation='none';}
            var stopBtn=\$('usda5-stop');if(stopBtn)stopBtn.style.display='none';
            if(s.status==='done_sync'&&s.diffs&&s.diffs.length>0){syncAllDiffs=s.diffs;showDiffs();}
            setTimeout(function(){location.reload();},2000);
        }
    });
}

function updateUI(s){
    var setTxt=function(id,v){var e=\$(id);if(e)e.textContent=v;};
    setTxt('usda5-s-processed',s.processed||0);
    setTxt('usda5-s-created',s.created||0);
    setTxt('usda5-s-skipped',s.skipped||0);
    setTxt('usda5-s-errors',s.errors||0);
    setTxt('usda5-s-empty',s.empty_batches||0);
    setTxt('usda5-s-page',s.current_processing_page||s.page||0);
    setTxt('usda5-s-smartskip',s.smart_skipped||0);
    setTxt('usda5-s-api',s.api_calls||0);
    setTxt('usda5-s-dupblocked',s.dup_prevented||0);

    var pct=0;
    if(s.mode==='sync'&&s.sync_total>0)pct=Math.min(100,Math.round((s.processed/s.sync_total)*100));
    else if(s.total>0)pct=Math.min(100,Math.round(((s.page*s.batch_size)/s.total)*100));
    else if(s.max_items>0&&s.created>0)pct=Math.min(100,Math.round((s.created/s.max_items)*100));
    var pb=\$('usda5-progress-bar');if(pb){pb.style.width=pct+'%';pb.textContent=pct+'%';}

    if(startTime===0){startTime=Date.now();startCreated=s.created||0;}
    var elapsed=(Date.now()-startTime)/1000,done=(s.created||0)-startCreated,etaEl=\$('usda5-eta');
    if(etaEl&&done>0&&elapsed>10){var rate=done/elapsed,rem=0;
        if(s.max_items>0)rem=Math.max(0,s.max_items-s.created);
        else if(s.total>0){var estTotal=s.total*(s.created/(s.processed||1));rem=Math.max(0,estTotal-s.created);}
        if(rem>0){var es=Math.round(rem/rate),em=Math.floor(es/60),eh=Math.floor(em/60);
        etaEl.innerHTML='⏱ ETA: <strong>'+(eh>0?eh+'ó ':'')+(em%60>0?em%60+'p ':'')+es%60+'s</strong> | '+rate.toFixed(2)+' új/s';}
        else{etaEl.textContent='';}
    }

    var liEl=\$('usda5-live-info');
    if(liEl)liEl.innerHTML='Adatkészlet: <strong>'+esc(s.dataset_label||s.dataset||'?')+'</strong> | Batch: <strong>'+s.batch_size+'</strong> | Delay: <strong>'+s.batch_delay+'s</strong> | Total: <strong>'+(s.total||'?')+'</strong> | Max: <strong>'+(s.max_items>0?s.max_items:'∞')+'</strong>';

    var ai=\$('usda5-adaptive-info');
    if(ai&&s.adaptive&&s.adaptive.enabled){ai.style.display='block';ai.innerHTML='🤖 <strong>Adaptív</strong>: Fázis=<strong>'+esc(s.adaptive.phase)+'</strong> | avg: <strong>'+s.adaptive.avg_response+'s</strong> | Timeouts: <strong>'+s.adaptive.timeouts+'</strong> | Saved: <strong>~'+s.adaptive.time_saved+'s</strong>'+(s.adaptive.adjustment?' | '+esc(s.adaptive.adjustment):'');}else if(ai){ai.style.display='none';}

    if(s.recent_log&&s.recent_log.length>0){var le=\$('usda5-log');if(le){var lh='';var colors={info:'#60a5fa',success:'#4ade80',warn:'#fbbf24',error:'#f87171',skip:'#c084fc',debug:'#94a3b8'};
    s.recent_log.forEach(function(e){lh+='<div style="color:'+(colors[e.type]||'#d4d4d4')+'">['+esc(e.time)+'] '+esc(e.msg)+'</div>';});le.innerHTML=lh;le.scrollTop=le.scrollHeight;}}
}

if(usda5Data.isRunning){var lsEl=\$('usda5-live-status');if(lsEl)lsEl.style.display='block';var stBtn=\$('usda5-stop');if(stBtn)stBtn.style.display='inline-block';pollTimer=setInterval(poll,3000);poll();}

function showDiffs(){
    var dsEl=\$('usda5-diff-section'),dcEl=\$('usda5-diff-count'),dlEl=\$('usda5-diff-list');
    if(!dsEl||!dcEl||!dlEl)return;
    dsEl.style.display='block';dcEl.textContent=syncAllDiffs.length;var h='';
    syncAllDiffs.forEach(function(item,idx){h+='<div style="border:1px solid #e5e7eb;border-radius:6px;margin-bottom:8px;padding:12px;"><label style="display:flex;align-items:start;gap:10px;cursor:pointer;"><input type="checkbox" class="usda5-diff-cb" data-index="'+idx+'" checked style="margin-top:4px;"><div style="flex:1;"><strong>'+esc(item.name)+'</strong> <span style="color:#999;font-size:0.82rem;">(#'+item.post_id+' | FDC: '+item.fdc_id+')</span><div style="margin-top:6px;font-size:0.82rem;">';
    item.changes.forEach(function(ch){h+='<div style="display:grid;grid-template-columns:160px 100px 20px 100px;gap:4px;padding:2px 0;"><span style="color:#666;">'+esc(ch.label)+':</span><span style="color:#dc2626;text-decoration:line-through;">'+ch.old_value+' '+esc(ch.egyseg)+'</span><span>→</span><span style="color:#16a34a;font-weight:600;">'+ch.new_value+' '+esc(ch.egyseg)+'</span></div>';});
    h+='</div></div></label></div>';});dlEl.innerHTML=h;
}

if(\$('usda5-diff-select-all'))\$('usda5-diff-select-all').addEventListener('change',function(){var c=this.checked;document.querySelectorAll('.usda5-diff-cb').forEach(function(cb){cb.checked=c;});});
if(\$('usda5-diff-apply'))\$('usda5-diff-apply').addEventListener('click',function(){var btn=this,st=\$('usda5-diff-apply-status'),sel=[];
    document.querySelectorAll('.usda5-diff-cb:checked').forEach(function(cb){sel.push(syncAllDiffs[parseInt(cb.dataset.index)]);});
    if(!sel.length){if(st){st.textContent='⚠️ Jelölj ki!';st.style.color='#d97706';}return;}
    btn.disabled=true;if(st){st.textContent='⏳...';st.style.color='#666';}
    ajax('usda5_apply_diffs',{diffs:JSON.stringify(sel)},function(r){btn.disabled=false;if(st){st.textContent=r.success?'✅ '+r.data:'❌ '+r.data;st.style.color=r.success?'#16a34a':'#dc2626';}});
});

function doSearch(){
    var qEl=\$('usda5-search-query');if(!qEl)return;
    var q=qEl.value.trim();if(!q)return;
    var btn=\$('usda5-search-btn'),stEl=\$('usda5-search-type'),resEl=\$('usda5-search-results');
    if(!btn||!stEl||!resEl)return;
    var st=stEl.value;
    btn.disabled=true;btn.textContent='⏳...';
    resEl.innerHTML='<div style="color:#666;">Keresés: "'+esc(q)+'"...</div>';
    ajax('usda5_search',{query:q,usda_type:st},function(r){
        btn.disabled=false;btn.textContent='🔍 Keresés';
        if(!r.success){resEl.innerHTML='<div style="color:#dc2626;">❌ '+esc(r.data)+'</div>';return;}
        var foods=r.data.foods;if(!foods||!foods.length){resEl.innerHTML='<div style="color:#d97706;">Nincs találat.</div>';return;}
        var h='<div style="font-size:0.85rem;color:#666;margin-bottom:8px;">'+foods.length+' találat:</div>';
        foods.forEach(function(f){h+='<div style="border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;margin-bottom:6px;display:flex;justify-content:space-between;align-items:center;"><div style="flex:1;min-width:0;"><strong>'+esc(f.name)+'</strong> <span style="font-size:0.75rem;color:#94a3b8;">FDC:'+f.fdc_id+' | '+esc(f.data_type)+'</span>';
        if(f.kcal!==null)h+='<div style="font-size:0.78rem;color:#666;">🔥'+f.kcal+' | F:'+(f.protein||'?')+' | Sz:'+(f.carb||'?')+' | Zs:'+(f.fat||'?')+'</div>';
        if(f.already_imported)h+='<div style="font-size:0.78rem;color:#16a34a;">✅ Már (#'+f.existing_id+')</div>';
        h+='</div>';if(!f.already_imported)h+='<button class="button usda5-import-single" data-fdc-id="'+f.fdc_id+'" style="font-size:0.82rem;margin-left:8px;">📥</button>';h+='</div>';});
        resEl.innerHTML=h;
        document.querySelectorAll('.usda5-import-single').forEach(function(ib){ib.addEventListener('click',function(){var fid=this.dataset.fdcId,tb=this;tb.disabled=true;tb.textContent='⏳';ajax('usda5_import_single',{fdc_id:fid},function(r){tb.textContent=r.success?'✅':'❌';tb.style.color=r.success?'#16a34a':'#dc2626';});});});
    });
}
if(\$('usda5-search-btn'))\$('usda5-search-btn').addEventListener('click',doSearch);
if(\$('usda5-search-query'))\$('usda5-search-query').addEventListener('keypress',function(e){if(e.key==='Enter')doSearch();});

if(\$('usda5-log-export'))\$('usda5-log-export').addEventListener('click',function(){var logEl=\$('usda5-log');if(!logEl)return;var log=logEl.innerText;var blob=new Blob([log],{type:'text/plain'});var a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='usda-log-'+new Date().toISOString().slice(0,10)+'.txt';a.click();});

function esc(s){var d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
})();
JSEOF;

    wp_register_script( 'usda5-import-js', false, [], USDA5_PLUGIN_VERSION, true );
    wp_enqueue_script( 'usda5-import-js' );
    wp_add_inline_script( 'usda5-import-js', $js );
    wp_localize_script( 'usda5-import-js', 'usda5Data', [
        'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
        'nonce'         => wp_create_nonce( 'usda5_nonce' ),
        'isRunning'     => ( ( $state['status'] ?? 'idle' ) === 'running' ),
        'savedPage'     => intval( $state['page'] ?? 1 ),
        'lastKnownPage' => intval( $state['last_known_page'] ?? 0 ),
    ] );
}
add_action( 'admin_enqueue_scripts', 'usda5_admin_scripts' );

/* ══════════════════════════════════════════════���════════════
   AJAX: usda5_start (START GUARD)
   ═══════════════════════════════════════════════════════════ */

function usda5_start_handler() {
    check_ajax_referer( 'usda5_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $current = usda5_get_state();
    if ( in_array( $current['status'] ?? 'idle', [ 'running', 'stopping' ], true ) ) {
        wp_send_json_error( 'Az import már fut vagy éppen leáll.' );
    }

    $mode        = sanitize_key( $_POST['mode'] ?? 'import' );
    $page        = max( 1, intval( $_POST['page'] ?? 1 ) );
    $dataset     = sanitize_text_field( $_POST['dataset'] ?? 'Foundation' );
    $batch_size  = min( USDA5_MAX_BATCH, max( 10, intval( $_POST['batch_size'] ?? 50 ) ) );
    $batch_delay = max( 4, floatval( $_POST['batch_delay'] ?? 5 ) );
    $max_items   = max( 0, intval( $_POST['max_items'] ?? 0 ) );

    $rpm = round( 60 / $batch_delay );
    if ( $rpm > USDA5_RATE_PER_MIN ) wp_send_json_error( 'Túl gyors! ' . $rpm . '/perc > ' . USDA5_RATE_PER_MIN );

    $datasets      = usda5_get_dataset_descriptions();
    $dataset_label = isset( $datasets[ $dataset ] ) ? $datasets[ $dataset ]['hu'] : $dataset;

    $old_state   = usda5_get_state();
    $is_continue = ( $mode === 'continue' );

    if ( $is_continue ) {
        $mode          = 'import';
        $dataset       = $old_state['dataset'] ?? $dataset;
        $dataset_label = $old_state['dataset_label'] ?? $dataset_label;
    }

    $state = usda5_default_state();
    $state['status']          = 'running';
    $state['mode']            = ( $mode === 'sync' ) ? 'sync' : 'import';
    $state['page']            = $page;
    $state['current_processing_page'] = $page;
    $state['dataset']         = $dataset;
    $state['dataset_label']   = $dataset_label;
    $state['batch_size']      = $batch_size;
    $state['batch_delay']     = $batch_delay;
    $state['max_items']       = $max_items;
    $state['started_at']      = current_time( 'Y-m-d H:i:s' );
    $state['date']            = current_time( 'Y-m-d H:i:s' );
    $state['last_known_page'] = max( $old_state['last_known_page'] ?? 0, $page );

    if ( $is_continue ) {
        $state['stats']    = $old_state['stats'] ?? $state['stats'];
        $state['adaptive'] = $old_state['adaptive'] ?? $state['adaptive'];
        $state['adaptive']['current_delay']      = $batch_delay;
        $state['adaptive']['current_batch_size'] = $batch_size;
        $state['total']                 = $old_state['total'] ?? 0;
        $state['consecutive_empty']     = $old_state['consecutive_empty'] ?? 0;
        $state['consecutive_full_skip'] = $old_state['consecutive_full_skip'] ?? 0;
    } else {
        $state['adaptive']['current_delay']      = $batch_delay;
        $state['adaptive']['current_batch_size'] = $batch_size;
    }

    if ( $mode === 'sync' ) {
        global $wpdb;
        $state['sync_total'] = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type='alapanyag' AND p.post_status='publish'
               AND pm.meta_key='usda_fdc_id' AND pm.meta_value != ''"
        );
    }

    // Direkt option write – start handler MINDIG írhat
    $state['_updated'] = microtime( true );
    update_option( USDA5_STATE_KEY, $state, false );
    wp_cache_delete( USDA5_STATE_KEY, 'options' );

    update_option( USDA5_LOG_KEY, [], false );
    update_option( USDA5_LASTRUN_KEY, 0, false );
    usda5_force_unlock();

    wp_clear_scheduled_hook( USDA5_CRON_HOOK );
    wp_schedule_event( time(), 'usda5_30s', USDA5_CRON_HOOK );
    spawn_cron();

    usda5_log( 'info', '🚀 ' . ( $state['mode'] === 'sync' ? 'Szinkron' : 'Import' ) . ' indítása (v' . USDA5_PLUGIN_VERSION . ')...' );
    usda5_log( 'info', '📚 ' . $dataset_label . ' | Batch: ' . $batch_size . ' | Delay: ' . $batch_delay . 's | Max: ' . ( $max_items > 0 ? $max_items : '∞' ) );
    usda5_log( 'info', '🤖 Adaptív + 🛡️ Dup védelem + ⏹ Stop-safe + 📊 totalHits-done aktív' );
    if ( $page > 1 ) usda5_log( 'info', '📌 Folytatás: oldal ' . $page );

    wp_send_json_success( 'Elindítva.' );
}
add_action( 'wp_ajax_usda5_start', 'usda5_start_handler' );

/* ═══════════════════════════════════════════════════════════
   AJAX: usda5_stop (NON-BLOCKING, max 2s)
   ═══════════════════════════════════════════════════════════ */

function usda5_stop_handler() {
    check_ajax_referer( 'usda5_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    usda5_force_status( 'stopping' );
    usda5_log( 'info', '⏳ Leállítás kérve...' );

    $waited = 0;
    while ( $waited < 2 ) {
        usleep( 250000 ); $waited += 0.25;
        $state = usda5_get_state();
        if ( in_array( $state['status'], [ 'stopped', 'done' ], true ) ) {
            usda5_force_unlock();
            usda5_log( 'info', '⏹ Leállítva.' );
            wp_send_json_success( 'Leállítva.' );
            return;
        }
    }

    usda5_force_status( 'stopped' );
    usda5_force_unlock();
    usda5_log( 'info', '⏹ Force stop.' );
    wp_send_json_success( 'Leállítva (force).' );
}
add_action( 'wp_ajax_usda5_stop', 'usda5_stop_handler' );

/* ═══════════════════════════════════════════════════════════
   AJAX: usda5_reset
   ═══════════════════════════════════════════════════════════ */

function usda5_reset_handler() {
    check_ajax_referer( 'usda5_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );
    delete_option( USDA5_STATE_KEY );
    delete_option( USDA5_LOG_KEY );
    delete_option( USDA5_LASTRUN_KEY );
    usda5_force_unlock();
    wp_clear_scheduled_hook( USDA5_CRON_HOOK );
    wp_send_json_success( 'Törölve.' );
}
add_action( 'wp_ajax_usda5_reset', 'usda5_reset_handler' );

/* ═══════════════════════════════════════════════════════════
   AJAX: usda5_status (STOP-SAFE)
   ═══════════════════════════════════════════════════════════ */

function usda5_status_handler() {
    check_ajax_referer( 'usda5_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $state = usda5_get_state();
    $log   = get_option( USDA5_LOG_KEY, [] );
    if ( ! is_array( $log ) ) $log = [];

    if ( $state['status'] === 'running' ) {
        $last_run = floatval( get_option( USDA5_LASTRUN_KEY, 0 ) );
        $now      = microtime( true );
        $delay    = floatval( $state['batch_delay'] ?? 5 );
        if ( $state['adaptive']['enabled'] ?? false ) $delay = floatval( $state['adaptive']['current_delay'] ?? $delay );

        if ( ( $now - $last_run ) >= $delay ) {
            if ( usda5_is_strictly_running() ) {
                $lock_uuid = usda5_acquire_lock();
                if ( $lock_uuid !== false ) {
                    if ( usda5_is_strictly_running() ) {
                        update_option( USDA5_LASTRUN_KEY, $now, false );
                        usda5_run_batch();
                    }
                    usda5_release_lock( $lock_uuid );
                    $state = usda5_get_state();
                    $log   = get_option( USDA5_LOG_KEY, [] );
                    if ( ! is_array( $log ) ) $log = [];
                }
            }
        }

        if ( usda5_is_strictly_running() ) {
            spawn_cron();
        }
    }

    wp_send_json_success( usda5_build_status_response( $state, $log ) );
}
add_action( 'wp_ajax_usda5_status', 'usda5_status_handler' );

/**
 * Readonly status – stop utáni poll. SOHA nem futtat batch-et.
 */
function usda5_status_readonly_handler() {
    check_ajax_referer( 'usda5_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $state = usda5_get_state();
    $log   = get_option( USDA5_LOG_KEY, [] );
    if ( ! is_array( $log ) ) $log = [];

    wp_send_json_success( usda5_build_status_response( $state, $log ) );
}
add_action( 'wp_ajax_usda5_status_readonly', 'usda5_status_readonly_handler' );

function usda5_build_status_response( $state, $log ) {
    return [
        'status'                  => $state['status'],
        'mode'                    => $state['mode'],
        'page'                    => $state['page'],
        'current_processing_page' => $state['current_processing_page'] ?? $state['page'],
        'dataset'                 => $state['dataset'],
        'dataset_label'           => $state['dataset_label'],
        'batch_size'              => $state['adaptive']['current_batch_size'] ?? $state['batch_size'],
        'batch_delay'             => round( $state['adaptive']['current_delay'] ?? $state['batch_delay'], 1 ),
        'max_items'               => $state['max_items'],
        'total'                   => $state['total'],
        'sync_total'              => $state['sync_total'],
        'last_known_page'         => $state['last_known_page'] ?? 0,
        'processed'               => $state['stats']['processed'] ?? 0,
        'created'                 => $state['stats']['created'] ?? 0,
        'updated'                 => $state['stats']['updated'] ?? 0,
        'skipped'                 => $state['stats']['skipped'] ?? 0,
        'errors'                  => $state['stats']['errors'] ?? 0,
        'empty_batches'           => $state['stats']['empty_batches'] ?? 0,
        'api_calls'               => $state['stats']['api_calls'] ?? 0,
        'smart_skipped'           => $state['stats']['smart_skipped'] ?? 0,
        'curl28_count'            => $state['stats']['curl28_count'] ?? 0,
        'dup_prevented'           => $state['stats']['dup_prevented'] ?? 0,
        'diff'                    => $state['stats']['diff'] ?? 0,
        'unchanged'               => $state['stats']['unchanged'] ?? 0,
        'diffs'                   => $state['diffs'] ?? [],
        'recent_log'              => array_slice( $log, -80 ),
        'adaptive'                => [
            'enabled'      => $state['adaptive']['enabled'] ?? false,
            'phase'        => $state['adaptive']['phase'] ?? 'import',
            'avg_response' => $state['adaptive']['avg_response_time'] ?? 0,
            'adjustment'   => $state['adaptive']['last_adjustment'] ?? '',
            'time_saved'   => round( $state['adaptive']['total_time_saved'] ?? 0 ),
            'timeouts'     => $state['adaptive']['consecutive_timeouts'] ?? 0,
        ],
    ];
}

/* ═══════════════════════════════════════════════════════════
   AJAX: usda5_search (üres tömb védelem)
   ═══════════════════════════════════════════════════════════ */

function usda5_search_handler() {
    check_ajax_referer( 'usda5_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $query     = sanitize_text_field( $_POST['query'] ?? '' );
    $usda_type = sanitize_text_field( $_POST['usda_type'] ?? 'Foundation,SR Legacy' );
    if ( empty( $query ) ) wp_send_json_error( 'Üres keresés.' );

    $data_types = array_map( 'trim', explode( ',', $usda_type ) );
    $response   = wp_remote_post( 'https://api.nal.usda.gov/fdc/v1/foods/search?api_key=' . USDA_API_KEY, [
        'timeout' => 20,
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body'    => wp_json_encode( [ 'query' => $query, 'dataType' => $data_types, 'pageSize' => 15 ] ),
    ] );
    if ( is_wp_error( $response ) ) wp_send_json_error( 'API hiba: ' . $response->get_error_message() );
    if ( wp_remote_retrieve_response_code( $response ) !== 200 ) wp_send_json_error( 'HTTP ' . wp_remote_retrieve_response_code( $response ) );

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    $fdc_ids = [];
    foreach ( ( $body['foods'] ?? [] ) as $f ) {
        $fid = intval( $f['fdcId'] ?? 0 );
        if ( $fid > 0 ) $fdc_ids[] = strval( $fid );
    }

    $existing_map = usda5_fdc_to_post_id_map( $fdc_ids );

    $foods = [];
    foreach ( ( $body['foods'] ?? [] ) as $f ) {
        $fid = intval( $f['fdcId'] ?? 0 );
        if ( ! $fid ) continue;
        $kcal=$prot=$carb=$fat=null;
        foreach ( ( $f['foodNutrients'] ?? [] ) as $fn ) {
            $nid = intval( $fn['nutrientId'] ?? 0 );
            $val = isset( $fn['value'] ) ? round( floatval( $fn['value'] ), 1 ) : null;
            if($nid===1008)$kcal=$val;if($nid===1003)$prot=$val;if($nid===1005)$carb=$val;if($nid===1004)$fat=$val;
        }
        $fid_str = strval( $fid );
        $foods[] = [
            'fdc_id'=>$fid,'name'=>$f['description']??'','data_type'=>$f['dataType']??'',
            'kcal'=>$kcal,'protein'=>$prot,'carb'=>$carb,'fat'=>$fat,
            'already_imported'=>isset($existing_map[$fid_str]),'existing_id'=>intval($existing_map[$fid_str]??0),
        ];
    }
    wp_send_json_success( [ 'foods' => $foods ] );
}
add_action( 'wp_ajax_usda5_search', 'usda5_search_handler' );

/* ═══════════════════════════════════════════════════════════
   AJAX: usda5_import_single
   ═══════════════════════════════════════════════════════════ */

function usda5_import_single_handler() {
    check_ajax_referer( 'usda5_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );
    if ( ! function_exists( 'update_field' ) ) wp_send_json_error( 'ACF nincs betöltve.' );

    $fdc_id = intval( $_POST['fdc_id'] ?? 0 );
    if ( ! $fdc_id ) wp_send_json_error( 'Nincs FDC ID.' );

    $fdc_str = strval( $fdc_id );
    $exists = usda5_fdc_exists( $fdc_str );
    if ( $exists !== false ) wp_send_json_error( 'Már importálva (#' . $exists . ')' );

    $data = usda5_api_get_product( $fdc_id );
    if ( $data === false ) wp_send_json_error( 'API hiba.' );

    $name    = usda5_proper_case( usda5_clean_description( $data['description'] ?? 'Ismeretlen' ) );
    $post_id = wp_insert_post( [
        'post_type'   => 'alapanyag',
        'post_title'  => $name,
        'post_status' => 'publish',
        'meta_input'  => [ 'usda_import_version' => USDA5_PLUGIN_VERSION ],
    ], true );
    if ( is_wp_error( $post_id ) ) wp_send_json_error( 'WP: ' . $post_id->get_error_message() );

    $nmap = usda5_get_nutrient_mapping();
    foreach ( ( $data['foodNutrients'] ?? [] ) as $fn ) {
        $nid = intval( $fn['nutrient']['id'] ?? $fn['nutrientId'] ?? 0 );
        if ( isset( $nmap[$nid] ) ) {
            $v = round( floatval( $fn['amount'] ?? $fn['value'] ?? 0 ), 4 );
            usda5_save_acf_field( $nmap[$nid]['acf_key'], $v, $post_id );
        }
    }
    usda5_save_acf_field( 'usda_fdc_id', $fdc_str, $post_id, true );
    usda5_save_acf_field( 'usda_last_sync', current_time( 'Y-m-d H:i:s' ), $post_id );
    usda5_save_acf_field( 'elsodleges_forras', 'usda', $post_id );
    usda5_save_acf_field( 'eredeti_nev', $data['description'] ?? '', $post_id );

    wp_send_json_success( 'Importálva: ' . $name . ' (#' . $post_id . ')' );
}
add_action( 'wp_ajax_usda5_import_single', 'usda5_import_single_handler' );

/* ═══════════════════════════════════════════════════════════
   AJAX: usda5_apply_diffs
   ═══════════════════════════════════════════════════════════ */

function usda5_apply_diffs_handler() {
    check_ajax_referer( 'usda5_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $diffs = json_decode( stripslashes( $_POST['diffs'] ?? '[]' ), true );
    if ( empty( $diffs ) || ! is_array( $diffs ) ) wp_send_json_error( 'Nincs eltérés.' );

    $ok=0;$err=0;
    foreach ( $diffs as $diff ) {
        $pid = intval( $diff['post_id'] ?? 0 );
        if ( $pid < 1 || empty( $diff['changes'] ) ) { $err++; continue; }
        foreach ( $diff['changes'] as $ch ) {
            $ak=$ch['acf_key']??'';$nv=$ch['new_value']??null;
            if(!empty($ak)&&$nv!==null) usda5_save_acf_field($ak,round(floatval($nv),4),$pid);
        }
        usda5_save_acf_field( 'usda_last_sync', current_time( 'Y-m-d H:i:s' ), $pid );
        $ok++;
    }

    $state=usda5_get_state();$state['diffs']=[];usda5_save_state($state);
    wp_send_json_success($ok.' frissítve.'.($err>0?' ('.$err.' hiba)':''));
}
add_action( 'wp_ajax_usda5_apply_diffs', 'usda5_apply_diffs_handler' );

/* ═══════════════════════════════════════════════════════════
   WP-CRON (STRICT RUNNING CHECK + STALE CHECK)
   v5.4: stale check cron-ban is fut
   ═══════════════════════════════════════════════════════════ */

function usda5_cron_schedules( $schedules ) {
    $schedules['usda5_30s'] = [ 'interval' => 30, 'display' => 'USDA5: 30s' ];
    return $schedules;
}
add_filter( 'cron_schedules', 'usda5_cron_schedules' );

function usda5_cron_handler() {
    if ( ! usda5_is_strictly_running() ) {
        wp_clear_scheduled_hook( USDA5_CRON_HOOK );
        return;
    }

    // v5.4: stale check a cron-ban is
    usda5_stale_check_internal();

    if ( ! usda5_is_strictly_running() ) {
        wp_clear_scheduled_hook( USDA5_CRON_HOOK );
        return;
    }

    $state    = usda5_get_state();
    $last_run = floatval( get_option( USDA5_LASTRUN_KEY, 0 ) );
    $delay    = floatval( $state['batch_delay'] ?? 5 );
    if ( $state['adaptive']['enabled'] ?? false ) $delay = floatval( $state['adaptive']['current_delay'] ?? $delay );
    if ( ( microtime( true ) - $last_run ) < $delay ) return;

    if ( ! usda5_is_strictly_running() ) return;

    $lock = usda5_acquire_lock();
    if ( $lock === false ) return;

    if ( ! usda5_is_strictly_running() ) {
        usda5_release_lock( $lock );
        wp_clear_scheduled_hook( USDA5_CRON_HOOK );
        return;
    }

    update_option( USDA5_LASTRUN_KEY, microtime( true ), false );
    usda5_run_batch();
    usda5_release_lock( $lock );
}
add_action( USDA5_CRON_HOOK, 'usda5_cron_handler' );

function usda5_ensure_cron() {
    if ( usda5_is_strictly_running() && ! wp_next_scheduled( USDA5_CRON_HOOK ) ) {
        wp_schedule_event( time(), 'usda5_30s', USDA5_CRON_HOOK );
    }
}
add_action( 'admin_init', 'usda5_ensure_cron' );
add_action( 'wp_loaded', 'usda5_ensure_cron' );

/* ═══════════════════════════════════════════════════════════
   DISABLE_WP_CRON BACKUP
   ═══════════════════════════════════════════════════════════ */

function usda5_admin_init_batch_trigger() {
    if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) return;
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( ! usda5_is_strictly_running() ) return;

    $state    = usda5_get_state();
    $last_run = floatval( get_option( USDA5_LASTRUN_KEY, 0 ) );
    $delay    = floatval( $state['adaptive']['current_delay'] ?? $state['batch_delay'] ?? 5 );

    if ( ( microtime( true ) - $last_run ) >= $delay ) {
        if ( ! usda5_is_strictly_running() ) return;
        $lock = usda5_acquire_lock();
        if ( $lock !== false ) {
            if ( usda5_is_strictly_running() ) {
                update_option( USDA5_LASTRUN_KEY, microtime( true ), false );
                usda5_run_batch();
            }
            usda5_release_lock( $lock );
        }
    }
}
add_action( 'admin_init', 'usda5_admin_init_batch_trigger' );

/* ═══════════════════════════════════════════════════════════
   STALE CHECK (adaptív timeout)
   v5.4: külön belső függvény – cron-ból és admin_init-ből is hívható
   ═══════════════════════════════════════════════════════════ */

function usda5_stale_check_internal() {
    wp_cache_delete( USDA5_STATE_KEY, 'options' );
    $state = get_option( USDA5_STATE_KEY, [] );
    if ( empty( $state ) || ( $state['status'] ?? 'idle' ) !== 'running' ) return;

    $delay          = floatval( $state['adaptive']['current_delay'] ?? $state['batch_delay'] ?? 5 );
    $stale_timeout  = max( 600, $delay * 20 );

    $upd = floatval( $state['_updated'] ?? 0 );
    if ( $upd > 0 && ( microtime( true ) - $upd ) > $stale_timeout ) {
        usda5_force_status( 'stopped' );
        usda5_force_unlock();
        usda5_log( 'warn', '⏹ Auto-stop: >' . round( $stale_timeout / 60 ) . ' perc inaktív.' );
    }
}

function usda5_stale_check() {
    usda5_stale_check_internal();
}
add_action( 'admin_init', 'usda5_stale_check' );

/* ═══════════════════════════════════════════════════════════
   ADMIN BAR
   ═══════════════════════════════════════════════════════════ */

function usda5_admin_bar_indicator( $wp_admin_bar ) {
    if ( ! current_user_can( 'manage_options' ) ) return;
    wp_cache_delete( USDA5_STATE_KEY, 'options' );
    $state = get_option( USDA5_STATE_KEY, [] );
    if ( empty( $state ) || ( $state['status'] ?? 'idle' ) !== 'running' ) return;
    $wp_admin_bar->add_node( [
        'id'    => 'usda5-running',
        'title' => '🇺🇸 USDA (oldal ' . intval( $state['current_processing_page'] ?? $state['page'] ?? 0 ) . ' | +' . intval( $state['stats']['created'] ?? 0 ) . ')',
        'href'  => admin_url( 'edit.php?post_type=alapanyag&page=usda-import' ),
        'meta'  => [ 'class' => 'usda5-bar-run' ],
    ] );
}
add_action( 'admin_bar_menu', 'usda5_admin_bar_indicator', 999 );

function usda5_admin_bar_style() {
    wp_cache_delete( USDA5_STATE_KEY, 'options' );
    $state = get_option( USDA5_STATE_KEY, [] );
    if ( empty( $state ) || ( $state['status'] ?? 'idle' ) !== 'running' ) return;
    echo '<style>#wpadminbar .usda5-bar-run>.ab-item{background:#2563eb!important;color:#fff!important;}#wpadminbar .usda5-bar-run:hover>.ab-item{background:#1d4ed8!important;}</style>';
}
add_action( 'admin_head', 'usda5_admin_bar_style' );

/* ═══════════════════════════════════════════════════════════
   V4 MIGRÁCIÓ + RÉGI CRON TAKARÍTÁS
   ═══════════════════════════════════════════════════════════ */

function usda5_migrate_v4() {
    if ( get_option( 'usda5_migrated_v4', false ) ) return;

    $v4 = get_option( 'usda_import_state_v4', [] );
    if ( ! empty( $v4 ) && empty( get_option( USDA5_STATE_KEY ) ) ) {
        $ns = usda5_default_state();
        if ( isset( $v4['page'] ) ) $ns['page'] = intval( $v4['page'] );
        if ( isset( $v4['dataset'] ) ) { $ns['dataset'] = $v4['dataset']; $ns['dataset_label'] = $v4['dataset_label'] ?? $v4['dataset']; }
        foreach ( [ 'created', 'skipped', 'errors', 'processed' ] as $k ) {
            if ( isset( $v4['stats'][$k] ) ) $ns['stats'][$k] = intval( $v4['stats'][$k] );
        }
        $ns['status']   = 'stopped';
        $ns['_updated'] = microtime( true );
        update_option( USDA5_STATE_KEY, $ns, false );
        usda5_log( 'info', '🔀 V4→V5 migráció (oldal ' . $ns['page'] . ')' );
    }

    foreach ( [ 'usda_import_state_v4', 'usda_import_log_v4', 'usda_import_last_run_time_v4', 'usda_import_lock_v4' ] as $k ) delete_option( $k );
    wp_clear_scheduled_hook( 'usda_import_cron_batch_v4' );
    update_option( 'usda5_migrated_v4', true );
}
add_action( 'admin_init', 'usda5_migrate_v4' );

function usda5_cleanup_old_cron() {
    foreach ( [ 'usda_import_cron_batch', 'usda_import_cron_batch_v4' ] as $h ) {
        if ( wp_next_scheduled( $h ) ) wp_clear_scheduled_hook( $h );
    }
}
add_action( 'init', 'usda5_cleanup_old_cron' );

/* ═══════════════════════════════════════════════════════════
   REST API
   ═══════════════════════════════════════════════════════════ */

function usda5_register_rest() {
    register_rest_route( 'usda-import/v1', '/status', [
        'methods'             => 'GET',
        'callback'            => function() {
            $s = usda5_get_state();
            return new WP_REST_Response( [
                'status'  => $s['status'],
                'mode'    => $s['mode'],
                'page'    => $s['page'],
                'current_processing_page' => $s['current_processing_page'] ?? $s['page'],
                'dataset' => $s['dataset'],
                'stats'   => $s['stats'],
                'total'   => $s['total'],
                'adaptive'=> $s['adaptive'],
                'last_known_page' => $s['last_known_page'] ?? 0,
                'plugin_version'  => USDA5_PLUGIN_VERSION,
            ], 200 );
        },
        'permission_callback' => function() { return current_user_can( 'manage_options' ); },
    ] );
}
add_action( 'rest_api_init', 'usda5_register_rest' );
