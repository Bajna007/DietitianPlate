<?php

/**
 * 18. – OFF / OPENFOODFACTS IMPORT (v7) – WP-CRON HÁTTÉRFOLYAMAT
 */
/**
 * 18. – OFF / OPENFOODFACTS IMPORT (v10.1) – WP-CRON HÁTTÉRFOLYAMAT
 */
/**
 * OFF Import v10.1 FINAL – OpenFoodFacts Import
 *
 * JAVÍTÁSOK v10.0 → v10.1:
 *  1. ACF FIELD KEY CACHE: acf_get_field() egyszer fut mezőnként, reference auto-create
 *  2. NO AUTO-STOP: smart skip / üres batch NEM állítja le – végtelenül megy max_items-ig vagy manuális stop-ig
 *  3. SYNC SIMPLIFY: mindig csak meglévő OFF alapanyagokat szinkronizál, checkbox eltávolítva
 *
 * Korábbi javítások (v9 → v10):
 *  1. STOP-SAFE save_state: DB status védelem
 *  2. FORCE_STATUS: közvetlen status írás + cron cleanup
 *  3. IS_STRICTLY_RUNNING: helper minden belépési ponthoz
 *  4. ATOMI MUTEX LOCK: DB INSERT IGNORE
 *  5. START GUARD: already running/stopping ellenőrzés
 *  6. PROCESSED++: minden skip ágban növekszik
 *  7. STOP HANDLER: non-blocking max 2s + force_status
 *  8. LOG ESCAPE: server-side sanitize_text_field()
 *  9. JS XSS FIX: esc() minden dinamikus tartalomra
 *  10. JS GUARD: off10Data undefined védelem
 *  11. KÉP FAIL COUNTER: state-ben tárolva
 *  12. DEACTIVATION HOOK: cron + lock cleanup
 *  13. VERZIÓ CHECK: plugin_version a state-ben
 *  14. READONLY STATUS: stop utáni poll nem triggerel batch-et
 *  15. BATCH_SIZE SYNC: adaptív méret visszaírása
 *  16. SQL ÜRES TÖMB: védelem közös helper-rel
 *  17. STALE CHECK: adaptív timeout
 *  18. CRON DOUBLE CHECK: lock után is ellenőriz
 *  19. NULL REFERENCE FIX: $state paraméter
 *  20. RUNNING/STOPPING: UI differenciálás
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'OFF10_PLUGIN_VERSION', '10.1' );

/* ═══════════════════════════════════════════════════════════
   KONSTANSOK
   ═══════════════════════════════════════════════════════════ */
define( 'OFF10_STATE_KEY',       'off10_import_state' );
define( 'OFF10_LOG_KEY',         'off10_import_log' );
define( 'OFF10_LASTRUN_KEY',     'off10_last_run_time' );
define( 'OFF10_LOCK_KEY',        'off10_batch_lock' );
define( 'OFF10_CRON_HOOK',       'off10_cron_batch' );
define( 'OFF10_RATE_BUCKET_KEY', 'off10_rate_bucket' );

define( 'OFF10_RATE_LIMIT',  100 );
define( 'OFF10_MAX_BATCH',   100 );
define( 'OFF10_MAX_RETRY',   5 );
define( 'OFF10_TIMEOUT',     45 );
define( 'OFF10_LOG_MAX',     500 );
define( 'OFF10_LOCK_TTL',    120 );
define( 'OFF10_DIFFS_MAX',   200 );

define( 'OFF10_ADAPTIVE_MIN_DELAY',     0.8 );
define( 'OFF10_ADAPTIVE_MAX_DELAY',     30 );
define( 'OFF10_ADAPTIVE_MIN_BATCH',     10 );
define( 'OFF10_ADAPTIVE_MAX_BATCH',     100 );
define( 'OFF10_ADAPTIVE_SWEET_SPOT',    3.0 );
define( 'OFF10_ADAPTIVE_TIMEOUT_CEIL',  8.0 );
define( 'OFF10_CURL28_CONSECUTIVE_MAX', 3 );

/* ═══════════════════════════════════════════════════════════
   DEACTIVATION HOOK
   ═══════════════════════════════════════════════════════════ */
register_deactivation_hook( __FILE__, 'off10_deactivation_cleanup' );

function off10_deactivation_cleanup() {
    wp_clear_scheduled_hook( OFF10_CRON_HOOK );
    off10_force_unlock();
    $state = get_option( OFF10_STATE_KEY, [] );
    if ( ! empty( $state ) && isset( $state['status'] ) && $state['status'] === 'running' ) {
        $state['status']   = 'stopped';
        $state['_updated'] = microtime( true );
        update_option( OFF10_STATE_KEY, $state, false );
    }
}

/* ═══════════════════════════════════════════════════════════
   ORSZÁG LISTA
   ═══════════════════════════════════════════════════════════ */
function off10_country_map() {
    return [
        'hungary'        => '🇭🇺 Magyarország',
        'germany'        => '��🇪 Németország',
        'austria'        => '🇦🇹 Ausztria',
        'united-kingdom' => '🇬🇧 Egyesült Királyság',
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
}

/* ═══════════════════════════════════════════════════════════
   MUTEX LOCK (ATOMI – DB INSERT IGNORE)
   ═══════════════════════════════════════════════════════════ */
function off10_acquire_lock() {
    global $wpdb;
    $uuid        = wp_generate_uuid4();
    $lock_key    = '_off10_lock';
    $lock_expiry = time() + OFF10_LOCK_TTL;

    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value < %s",
        $lock_key . '_expiry',
        time()
    ) );

    $inserted = $wpdb->query( $wpdb->prepare(
        "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
        $lock_key,
        $uuid
    ) );

    if ( $inserted ) {
        delete_option( $lock_key . '_expiry' );
        add_option( $lock_key . '_expiry', $lock_expiry, '', 'no' );
        return $uuid;
    }

    $existing_expiry = get_option( $lock_key . '_expiry', 0 );
    if ( $existing_expiry > 0 && time() > $existing_expiry ) {
        $wpdb->delete( $wpdb->options, [ 'option_name' => $lock_key ] );
        $wpdb->delete( $wpdb->options, [ 'option_name' => $lock_key . '_expiry' ] );
        $inserted = $wpdb->query( $wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
            $lock_key,
            $uuid
        ) );
        if ( $inserted ) {
            delete_option( $lock_key . '_expiry' );
            add_option( $lock_key . '_expiry', $lock_expiry, '', 'no' );
            return $uuid;
        }
    }

    return false;
}

function off10_release_lock( $uuid ) {
    global $wpdb;
    $lock_key = '_off10_lock';
    $current  = $wpdb->get_var( $wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
        $lock_key
    ) );
    if ( $current === $uuid ) {
        $wpdb->delete( $wpdb->options, [ 'option_name' => $lock_key ] );
        $wpdb->delete( $wpdb->options, [ 'option_name' => $lock_key . '_expiry' ] );
        return true;
    }
    return false;
}

function off10_force_unlock() {
    global $wpdb;
    $lock_key = '_off10_lock';
    $wpdb->delete( $wpdb->options, [ 'option_name' => $lock_key ] );
    $wpdb->delete( $wpdb->options, [ 'option_name' => $lock_key . '_expiry' ] );
    delete_transient( 'off9_batch_lock' );
    delete_transient( OFF10_LOCK_KEY );
}

/* ═══════════════════════════════════════════════════════════
   RATE LIMITER
   ═══════════════════════════════════════════════════════════ */
function off10_rate_consume( $tokens = 1 ) {
    wp_cache_delete( OFF10_RATE_BUCKET_KEY, 'options' );
    $bucket = get_option( OFF10_RATE_BUCKET_KEY, [] );
    $now    = microtime( true );
    if ( empty( $bucket ) || ! isset( $bucket['tokens'] ) ) {
        $bucket = [ 'tokens' => OFF10_RATE_LIMIT - $tokens, 'last_time' => $now ];
        update_option( OFF10_RATE_BUCKET_KEY, $bucket, false );
        return true;
    }
    $elapsed    = $now - $bucket['last_time'];
    $new_tokens = min( OFF10_RATE_LIMIT, $bucket['tokens'] + $elapsed * ( OFF10_RATE_LIMIT / 60.0 ) );
    if ( $new_tokens < $tokens ) return false;
    $bucket['tokens']    = $new_tokens - $tokens;
    $bucket['last_time'] = $now;
    update_option( OFF10_RATE_BUCKET_KEY, $bucket, false );
    return true;
}

/* ═══════════════════════════════════════════════════════════
   STATE MANAGEMENT (STOP-SAFE + VERZIÓ CHECK)
   ═══════════════════════════════════════════════════════════ */
function off10_default_state() {
    return [
        'plugin_version'          => OFF10_PLUGIN_VERSION,
        'status'                  => 'idle',
        'mode'                    => 'import',
        'page'                    => 1,
        'current_processing_page' => 0,
        'country'                 => 'hungary',
        'country_label'           => '🇭🇺 Magyarország',
        'batch_size'              => 50,
        'batch_delay'             => 3,
        'max_items'               => 0,
        'total'                   => 0,
        'sync_total'              => 0,
        'started_at'              => '',
        'stats'                   => [
            'processed'       => 0,
            'created'         => 0,
            'updated'         => 0,
            'skipped'         => 0,
            'errors'          => 0,
            'empty_batches'   => 0,
            'api_calls'       => 0,
            'diff'            => 0,
            'unchanged'       => 0,
            'smart_skipped'   => 0,
            'curl28_count'    => 0,
            'images_failed'   => 0,
            'img_consecutive' => 0,
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
            'current_delay'        => 3.0,
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

function off10_maybe_migrate_state( &$state ) {
    $state_version = $state['plugin_version'] ?? '9.0';
    if ( version_compare( $state_version, OFF10_PLUGIN_VERSION, '>=' ) ) return;
    if ( ! isset( $state['stats']['img_consecutive'] ) )  $state['stats']['img_consecutive'] = 0;
    if ( ! isset( $state['stats']['empty_batches'] ) )    $state['stats']['empty_batches'] = 0;
    if ( ! isset( $state['stats']['smart_skipped'] ) )    $state['stats']['smart_skipped'] = 0;
    if ( ! isset( $state['stats']['curl28_count'] ) )     $state['stats']['curl28_count'] = 0;
    off10_log( 'info', '🔀 State migráció: v' . $state_version . ' → v' . OFF10_PLUGIN_VERSION );
    $state['plugin_version'] = OFF10_PLUGIN_VERSION;
}

function off10_get_state() {
    wp_cache_delete( OFF10_STATE_KEY, 'options' );
    $state = get_option( OFF10_STATE_KEY, [] );
    if ( empty( $state ) ) return off10_default_state();
    $default = off10_default_state();
    $state = wp_parse_args( $state, $default );
    $state['stats']    = wp_parse_args( $state['stats'] ?? [], $default['stats'] );
    $state['adaptive'] = wp_parse_args( $state['adaptive'] ?? [], $default['adaptive'] );
    off10_maybe_migrate_state( $state );
    return $state;
}

function off10_save_state( $state ) {
    wp_cache_delete( OFF10_STATE_KEY, 'options' );
    $db_state  = get_option( OFF10_STATE_KEY, [] );
    $db_status = $db_state['status'] ?? 'idle';
    if ( in_array( $db_status, [ 'stopping', 'stopped' ], true ) && $state['status'] === 'running' ) {
        $state['status'] = $db_status;
        off10_log( 'debug', '🛡️ State védelem: "' . $db_status . '" megőrizve' );
    }
    $state['_updated'] = microtime( true );
    update_option( OFF10_STATE_KEY, $state, false );
}

function off10_force_status( $new_status ) {
    wp_cache_delete( OFF10_STATE_KEY, 'options' );
    $state = get_option( OFF10_STATE_KEY, [] );
    if ( empty( $state ) ) $state = off10_default_state();
    $state['status']   = $new_status;
    $state['_updated'] = microtime( true );
    update_option( OFF10_STATE_KEY, $state, false );
    if ( in_array( $new_status, [ 'stopping', 'stopped', 'done', 'done_sync', 'error', 'idle' ], true ) ) {
        wp_clear_scheduled_hook( OFF10_CRON_HOOK );
    }
}

function off10_advance_page( &$state, $new_page ) {
    $state['page'] = $new_page;
    $state['current_processing_page'] = $new_page;
    if ( $new_page > ( $state['last_known_page'] ?? 0 ) ) {
        $state['last_known_page'] = $new_page;
    }
    $state['date'] = current_time( 'Y-m-d H:i:s' );
}

function off10_should_stop() {
    wp_cache_delete( OFF10_STATE_KEY, 'options' );
    $state  = get_option( OFF10_STATE_KEY, [] );
    $status = $state['status'] ?? 'idle';
    return in_array( $status, [ 'stopping', 'stopped', 'idle' ], true );
}

function off10_is_strictly_running() {
    wp_cache_delete( OFF10_STATE_KEY, 'options' );
    $state = get_option( OFF10_STATE_KEY, [] );
    return ( $state['status'] ?? 'idle' ) === 'running';
}

/* ═══════════════════════════════════════════════════════════
   LOG
   ═══════════════════════════════════════════════════════════ */
function off10_log( $type, $msg ) {
    $msg  = sanitize_text_field( $msg );
    $type = sanitize_key( $type );
    $log  = get_option( OFF10_LOG_KEY, [] );
    if ( ! is_array( $log ) ) $log = [];
    $log[] = [ 'type' => $type, 'msg' => $msg, 'time' => current_time( 'H:i:s' ) ];
    if ( count( $log ) > OFF10_LOG_MAX ) $log = array_slice( $log, -OFF10_LOG_MAX );
    update_option( OFF10_LOG_KEY, $log, false );
}

/* ═══════════════════════════════════════════════════════════
   NUTRIENT FIELDS
   ═══════════════════════════════════════════════════════════ */
function off10_get_nutrient_fields() {
    if ( function_exists( 'tapanyag_get_alapanyag_nutrient_fields' ) ) {
        $fields = tapanyag_get_alapanyag_nutrient_fields();
        if ( ! empty( $fields ) ) return $fields;
    }
    return [
        [ 'off_key' => 'energy-kcal_100g',  'acf_key' => 'kaloria',       'label' => 'Kalória',       'egyseg' => 'kcal' ],
        [ 'off_key' => 'proteins_100g',      'acf_key' => 'feherje',       'label' => 'Fehérje',       'egyseg' => 'g' ],
        [ 'off_key' => 'carbohydrates_100g', 'acf_key' => 'szenhidrat',    'label' => 'Szénhidrát',    'egyseg' => 'g' ],
        [ 'off_key' => 'fat_100g',           'acf_key' => 'zsir',          'label' => 'Zsír',          'egyseg' => 'g' ],
        [ 'off_key' => 'fiber_100g',         'acf_key' => 'rost',          'label' => 'Rost',          'egyseg' => 'g' ],
        [ 'off_key' => 'sugars_100g',        'acf_key' => 'cukor',         'label' => 'Cukor',         'egyseg' => 'g' ],
        [ 'off_key' => 'saturated-fat_100g', 'acf_key' => 'telitett_zsir', 'label' => 'Telített zsír', 'egyseg' => 'g' ],
        [ 'off_key' => 'sodium_100g',        'acf_key' => 'natrium',       'label' => 'Nátrium',       'egyseg' => 'mg' ],
        [ 'off_key' => 'salt_100g',          'acf_key' => 'so',            'label' => 'Só',            'egyseg' => 'g' ],
    ];
}

/* ═══════════════════════════════════════════════════════════
   ACF FIELD KEY CACHE + SAVE HELPER
   ═══════════════════════════════════════════════════════════ */
function off10_get_acf_field_key( $field_name ) {
    static $cache = [];
    if ( isset( $cache[ $field_name ] ) ) return $cache[ $field_name ];
    if ( function_exists( 'acf_get_field' ) ) {
        $field_obj = acf_get_field( $field_name );
        if ( $field_obj && ! empty( $field_obj['key'] ) ) {
            $cache[ $field_name ] = $field_obj['key'];
            return $cache[ $field_name ];
        }
    }
    $cache[ $field_name ] = false;
    return false;
}

function off10_save_acf_field( $key, $value, $post_id, $critical = false ) {
    if ( function_exists( 'update_field' ) ) {
        $result = update_field( $key, $value, $post_id );
        if ( $result ) return true;

        $field_key = off10_get_acf_field_key( $key );
        if ( $field_key !== false ) {
            update_post_meta( $post_id, '_' . $key, $field_key );
            $result = update_field( $field_key, $value, $post_id );
            if ( $result ) return true;
        }
    }

    if ( $critical ) {
        update_post_meta( $post_id, $key, $value );
        off10_log( 'warn', '⚠️ ACF fallback: ' . $key . ' → meta (post #' . $post_id . ')' );
        return true;
    }
    return false;
}

/* ═══════════════════════════════════════════════════════════
   ADAPTÍV SZABÁLYOZÓ
   ═══════════════════════════════════════════════════════════ */
function off10_adaptive_adjust( &$state, $response_time, $was_error = false, $error_type = '' ) {
    $a = &$state['adaptive'];
    if ( ! $a['enabled'] ) return;

    $a['response_times'][] = $response_time;
    if ( count( $a['response_times'] ) > 10 ) {
        $a['response_times'] = array_slice( $a['response_times'], -10 );
    }
    $a['avg_response_time'] = round( array_sum( $a['response_times'] ) / count( $a['response_times'] ), 2 );

    if ( $was_error || $error_type === 'curl28' ) {
        $a['consecutive_timeouts']++;
        $a['consecutive_fast'] = 0;
        $state['stats']['curl28_count']++;

        if ( $a['consecutive_timeouts'] >= OFF10_CURL28_CONSECUTIVE_MAX ) {
            $a['cooldown_until']     = microtime( true ) + 60;
            $a['current_delay']      = min( OFF10_ADAPTIVE_MAX_DELAY, $a['current_delay'] * 3 );
            $a['current_batch_size'] = OFF10_ADAPTIVE_MIN_BATCH;
            $a['last_adjustment']    = '🛑 Cooldown 60s (3× timeout)';
            off10_log( 'warn', '🛑 3× cURL 28! Cooldown 60s. Delay: ' . $a['current_delay'] . 's, Batch: ' . $a['current_batch_size'] );
            return;
        }

        $a['current_delay']      = min( OFF10_ADAPTIVE_MAX_DELAY, $a['current_delay'] * 2 );
        $a['current_batch_size'] = max( OFF10_ADAPTIVE_MIN_BATCH, intval( round( $a['current_batch_size'] * 0.6 / 10 ) * 10 ) );
        $a['last_adjustment']    = '🔻 Lassítás (timeout): delay=' . $a['current_delay'] . 's, batch=' . $a['current_batch_size'];
        off10_log( 'warn', $a['last_adjustment'] );
        return;
    }

    $a['consecutive_timeouts'] = 0;
    $avg = $a['avg_response_time'];

    if ( $avg < OFF10_ADAPTIVE_SWEET_SPOT ) {
        $a['consecutive_fast']++;
        if ( $a['consecutive_fast'] >= 3 ) {
            $new_delay = max( OFF10_ADAPTIVE_MIN_DELAY, round( $a['current_delay'] * 0.8, 1 ) );
            $new_batch = min( OFF10_ADAPTIVE_MAX_BATCH, $a['current_batch_size'] + 10 );
            if ( $new_delay !== $a['current_delay'] || $new_batch !== $a['current_batch_size'] ) {
                $a['current_delay']      = $new_delay;
                $a['current_batch_size'] = $new_batch;
                $a['last_adjustment']    = '🔺 Gyorsítás: delay=' . $a['current_delay'] . 's, batch=' . $a['current_batch_size'] . ' (avg: ' . $avg . 's)';
                off10_log( 'info', $a['last_adjustment'] );
            }
            $a['consecutive_fast'] = 0;
        }
        return;
    }

    if ( $avg > OFF10_ADAPTIVE_TIMEOUT_CEIL ) {
        $a['consecutive_fast']   = 0;
        $a['current_delay']      = min( OFF10_ADAPTIVE_MAX_DELAY, round( $a['current_delay'] * 1.5, 1 ) );
        $a['current_batch_size'] = max( OFF10_ADAPTIVE_MIN_BATCH, $a['current_batch_size'] - 10 );
        $a['last_adjustment']    = '🔻 Lassítás (avg ' . $avg . 's): delay=' . $a['current_delay'] . 's, batch=' . $a['current_batch_size'];
        off10_log( 'info', $a['last_adjustment'] );
        return;
    }

    $a['consecutive_fast'] = 0;
}

function off10_adaptive_in_cooldown( $state ) {
    $cd = $state['adaptive']['cooldown_until'] ?? 0;
    if ( $cd <= 0 ) return false;
    if ( microtime( true ) < $cd ) {
        off10_log( 'debug', '⏳ Cooldown: még ' . round( $cd - microtime( true ) ) . ' mp' );
        return true;
    }
    return false;
}

function off10_adaptive_detect_phase( &$state, $new_count, $total_count ) {
    $a = &$state['adaptive'];
    if ( $total_count === 0 ) { $a['phase'] = 'skip'; return; }
    $ratio = $new_count / $total_count;
    if ( $ratio === 0.0 ) {
        if ( $a['phase'] !== 'skip' ) { $a['phase'] = 'skip'; off10_log( 'info', '📊 Fázis: SKIP' ); }
    } elseif ( $ratio < 0.2 ) {
        if ( $a['phase'] !== 'tail' ) { $a['phase'] = 'tail'; off10_log( 'info', '📊 Fázis: TAIL (' . round( $ratio * 100 ) . '% új)' ); }
    } else {
        if ( $a['phase'] !== 'import' ) { $a['phase'] = 'import'; off10_log( 'info', '📊 Fázis: IMPORT (' . round( $ratio * 100 ) . '% új)' ); }
    }
}

/* ═══════════════════════════════════════════════════════════
   BARCODE HELPERS (üres tömb biztos)
   ═══════════════════════════════════════════════════════════ */
function off10_barcode_exists_batch( $barcodes ) {
    if ( empty( $barcodes ) ) return [];
    global $wpdb;
    $ph   = implode( ',', array_fill( 0, count( $barcodes ), '%s' ) );
    $rows = $wpdb->get_col( $wpdb->prepare(
        "SELECT pm.meta_value FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE pm.meta_key = 'off_barcode' AND pm.meta_value IN ($ph)
           AND p.post_type = 'alapanyag' AND p.post_status IN ('publish','draft')
         GROUP BY pm.meta_value",
        ...$barcodes
    ) );
    return array_flip( $rows );
}

function off10_barcode_to_post_id_map( $barcodes ) {
    if ( empty( $barcodes ) ) return [];
    global $wpdb;
    $ph   = implode( ',', array_fill( 0, count( $barcodes ), '%s' ) );
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT pm.meta_value AS barcode, p.ID FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE pm.meta_key = 'off_barcode' AND pm.meta_value IN ($ph)
           AND p.post_type = 'alapanyag' AND p.post_status IN ('publish','draft')",
        ...$barcodes
    ) );
    $map = [];
    foreach ( $rows as $row ) $map[ $row->barcode ] = intval( $row->ID );
    return $map;
}

function off10_barcode_exists_single( $barcode ) {
    global $wpdb;
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT p.ID FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE pm.meta_key = 'off_barcode' AND pm.meta_value = %s
           AND p.post_type = 'alapanyag' AND p.post_status IN ('publish','draft')
         LIMIT 1",
        $barcode
    ) );
    return $exists ? intval( $exists ) : false;
}

/* ═══════════════════════════════════════════════════════════
   OFF API: SEARCH
   ═══════════════════════════════════════════════════════════ */
function off10_api_search( $country, $page, $page_size, &$state = null ) {
    $url    = 'https://world.openfoodfacts.org/cgi/search.pl';
    $params = [
        'action'    => 'process',
        'json'      => 'true',
        'page'      => $page,
        'page_size' => $page_size,
        'fields'    => 'code,product_name,product_name_hu,nutriments,image_front_url,url,brands',
        'sort_by'   => 'unique_scans_n',
    ];
    if ( ! empty( $country ) ) {
        $params['tagtype_0']      = 'countries';
        $params['tag_contains_0'] = 'contains';
        $params['tag_0']          = $country;
    }

    $full_url    = $url . '?' . http_build_query( $params );
    $retry_delay = 1;
    $has_state   = ( $state !== null && is_array( $state ) );

    for ( $attempt = 1; $attempt <= OFF10_MAX_RETRY; $attempt++ ) {
        if ( $attempt > 1 && off10_should_stop() ) return false;

        $t_start  = microtime( true );
        $response = wp_remote_get( $full_url, [
            'timeout'    => OFF10_TIMEOUT,
            'user-agent' => 'TapanyagLexikon/2.0 (WordPress OFF Import; contact@tapanyaglexikon.hu)',
        ] );
        $t_elapsed = round( microtime( true ) - $t_start, 2 );

        if ( is_wp_error( $response ) ) {
            $err    = $response->get_error_message();
            $is_c28 = ( strpos( $err, 'cURL error 28' ) !== false || strpos( $err, 'timed out' ) !== false );
            if ( $has_state ) {
                off10_adaptive_adjust( $state, $t_elapsed, true, $is_c28 ? 'curl28' : 'error' );
                off10_save_state( $state );
            }
            off10_log( 'warn', '⚠️ ' . ( $is_c28 ? 'TIMEOUT' : 'HTTP hiba' ) . ' #' . $attempt . '/' . OFF10_MAX_RETRY . ' (' . $t_elapsed . 's): ' . $err );
            if ( $attempt < OFF10_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( $code === 429 ) {
            if ( $has_state ) {
                off10_adaptive_adjust( $state, $t_elapsed, true, 'rate_limit' );
                off10_save_state( $state );
            }
            off10_log( 'warn', '🚦 429 Rate limited! retry ' . $attempt );
            if ( $attempt < OFF10_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        if ( $code !== 200 ) {
            off10_log( 'warn', '⚠️ HTTP ' . $code . ' #' . $attempt . ' (' . $t_elapsed . 's)' );
            if ( $attempt < OFF10_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) || strlen( $body ) < 10 ) {
            off10_log( 'warn', '⚠️ Üres válasz #' . $attempt );
            if ( $attempt < OFF10_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        $data = json_decode( $body, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            off10_log( 'warn', '⚠️ JSON hiba #' . $attempt . ': ' . json_last_error_msg() );
            if ( $attempt < OFF10_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        if ( $has_state ) off10_adaptive_adjust( $state, $t_elapsed, false );
        off10_log( 'debug', '📡 API: ' . $t_elapsed . 's, ' . count( $data['products'] ?? [] ) . ' termék' );
        return $data;
    }

    off10_log( 'error', '❌ API: ' . OFF10_MAX_RETRY . ' sikertelen. Oldal: ' . $page );
    return false;
}

/* ═══════════════════════════════════════════════════════════
   OFF API: PRODUCT
   ═══════════════════════════════════════════════════════════ */
function off10_api_get_product( $barcode ) {
    $url         = 'https://world.openfoodfacts.org/api/v2/product/' . urlencode( $barcode ) . '.json';
    $retry_delay = 1;

    for ( $attempt = 1; $attempt <= OFF10_MAX_RETRY; $attempt++ ) {
        if ( $attempt > 1 && off10_should_stop() ) return false;

        $response = wp_remote_get( $url, [
            'timeout'    => OFF10_TIMEOUT,
            'user-agent' => 'TapanyagLexikon/2.0 (WordPress OFF Import; contact@tapanyaglexikon.hu)',
        ] );

        if ( is_wp_error( $response ) ) {
            if ( $attempt < OFF10_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code === 429 ) {
            off10_log( 'warn', '🚦 429 (product ' . $barcode . ') retry ' . $attempt );
            if ( $attempt < OFF10_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }
        if ( $code !== 200 ) {
            if ( $attempt < OFF10_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) || strlen( $body ) < 10 ) {
            if ( $attempt < OFF10_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        $data = json_decode( $body, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            if ( $attempt < OFF10_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 16, $retry_delay * 2 ); }
            continue;
        }

        return $data;
    }
    return false;
}

/* ════════════════════════════════════════════════���══════════
   KÉP SIDELOAD (state-based fail counter)
   ═══════════════════════════════════════════════════════════ */
function off10_safe_sideload_image( $url, $post_id, $alt, &$state ) {
    $img_consecutive = intval( $state['stats']['img_consecutive'] ?? 0 );

    if ( $img_consecutive >= 3 ) {
        update_post_meta( $post_id, '_off10_pending_image', $url );
        return false;
    }

    if ( ! function_exists( 'media_sideload_image' ) ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $head = wp_remote_head( $url, [ 'timeout' => 5, 'user-agent' => 'TapanyagLexikon/2.0' ] );
    if ( is_wp_error( $head ) || wp_remote_retrieve_response_code( $head ) !== 200 ) {
        $state['stats']['img_consecutive']++;
        $state['stats']['images_failed']++;
        update_post_meta( $post_id, '_off10_pending_image', $url );
        off10_log( 'debug', '📷 Kép nem elérhető' );
        return false;
    }

    $cl = intval( wp_remote_retrieve_header( $head, 'content-length' ) );
    if ( $cl > 5 * 1024 * 1024 ) {
        update_post_meta( $post_id, '_off10_pending_image', $url );
        off10_log( 'debug', '📷 Kép túl nagy (' . round( $cl / 1048576, 1 ) . 'MB)' );
        return false;
    }

    $result = media_sideload_image( $url, $post_id, $alt, 'id' );
    if ( is_wp_error( $result ) ) {
        $state['stats']['img_consecutive']++;
        $state['stats']['images_failed']++;
        update_post_meta( $post_id, '_off10_pending_image', $url );
        off10_log( 'debug', '📷 Sideload hiba: ' . $result->get_error_message() );
        return false;
    }

    $state['stats']['img_consecutive'] = 0;
    set_post_thumbnail( $post_id, $result );
    return $result;
}

/* ═══════════════════════════════════════════════════════════
   BATCH ROUTER (STOP-SAFE, NO AUTO-STOP)
   ═══════════════════════════════════════════════════════════ */
function off10_run_batch() {
    if ( off10_should_stop() ) return;
    $state = off10_get_state();
    if ( $state['status'] !== 'running' ) return;

    // Max items check – ez az EGYETLEN auto-stop
    if ( $state['mode'] === 'import' && $state['max_items'] > 0 ) {
        if ( ( $state['stats']['created'] ?? 0 ) >= $state['max_items'] ) {
            $state['status'] = 'done';
            off10_save_state( $state );
            off10_log( 'success', '🎉 Maximum elérve: ' . $state['max_items'] );
            wp_clear_scheduled_hook( OFF10_CRON_HOOK );
            return;
        }
    }

    if ( ! off10_rate_consume() ) {
        off10_log( 'warn', '⏳ Rate limit – várakozás...' );
        return;
    }

    if ( $state['mode'] === 'sync' ) {
        off10_batch_sync( $state );
    } else {
        off10_batch_import( $state );
    }
}

/* ═══════════════════════════════════════════════════════════
   BATCH IMPORT (NO AUTO-STOP on empty)
   ═══════════════════════════════════════════════════════════ */
function off10_batch_import( $state ) {
    if ( ! function_exists( 'update_field' ) ) {
        $state['status'] = 'error';
        off10_save_state( $state );
        off10_log( 'error', '❌ ACF (update_field) nincs betöltve!' );
        wp_clear_scheduled_hook( OFF10_CRON_HOOK );
        return;
    }

    if ( off10_should_stop() ) {
        off10_log( 'info', '⏹ Batch elején leállítva.' );
        return;
    }

    $page    = intval( $state['page'] ?? 1 );
    $country = $state['country'] ?? 'hungary';

    $batch_size = intval( $state['batch_size'] ?? 50 );
    if ( $state['adaptive']['enabled'] ?? false ) {
        $batch_size = intval( $state['adaptive']['current_batch_size'] ?? $batch_size );
    }
    $state['batch_size'] = $batch_size;

    if ( off10_adaptive_in_cooldown( $state ) ) {
        off10_save_state( $state );
        return;
    }

    $state['current_processing_page'] = $page;
    off10_save_state( $state );

    off10_log( 'info', '📦 Batch oldal ' . $page . ' (méret: ' . $batch_size . ', delay: ' . round( $state['adaptive']['current_delay'] ?? $state['batch_delay'], 1 ) . 's)' );

    $result = off10_api_search( $country, $page, $batch_size, $state );

    if ( $result === false ) {
        $state['stats']['errors']++;
        // v10.1: API hiba → továbblépés, NEM leállás
        off10_advance_page( $state, $page + 1 );
        off10_save_state( $state );
        off10_log( 'warn', '⚠️ API hiba – továbblépés oldal ' . ( $page + 1 ) . '-re' );
        return;
    }

    if ( off10_should_stop() ) {
        off10_log( 'info', '⏹ API hívás után leállítva.' );
        return;
    }

    if ( isset( $result['count'] ) && $result['count'] > 0 ) {
        $state['total'] = intval( $result['count'] );
    }

    $state['stats']['api_calls']++;
    $products = $result['products'] ?? [];

    // v10.1: Üres oldal → továbblépés, NEM leállás
    if ( empty( $products ) ) {
        $state['consecutive_empty']++;
        $state['stats']['empty_batches']++;
        off10_advance_page( $state, $page + 1 );
        off10_save_state( $state );
        off10_log( 'info', '📭 Üres oldal ' . $page . ' – továbblépés (sorozat: ' . $state['consecutive_empty'] . ')' );
        return;
    }

    $state['consecutive_empty'] = 0;

    // Batch deduplikáció
    $barcodes = [];
    foreach ( $products as $p ) {
        $bc = trim( $p['code'] ?? '' );
        if ( $bc !== '' ) $barcodes[] = $bc;
    }
    $existing_barcodes = off10_barcode_exists_batch( $barcodes );

    $new_in_batch = 0;
    foreach ( $products as $p ) {
        $bc = trim( $p['code'] ?? '' );
        if ( $bc !== '' && ! isset( $existing_barcodes[ $bc ] ) ) {
            $name = trim( $p['product_name_hu'] ?? $p['product_name'] ?? '' );
            if ( ! empty( $name ) ) $new_in_batch++;
        }
    }

    off10_adaptive_detect_phase( $state, $new_in_batch, count( $products ) );

    // v10.1: Smart skip – továbblépés, SOHA nem leáll
    if ( $new_in_batch === 0 ) {
        $state['consecutive_full_skip']++;
        $state['stats']['smart_skipped']++;
        $state['stats']['skipped']   += count( $products );
        $state['stats']['processed'] += count( $products );

        $cfs  = $state['consecutive_full_skip'];
        $skip = 1;
        if ( $cfs >= 10 )     $skip = 50;
        elseif ( $cfs >= 5 )  $skip = 20;
        elseif ( $cfs >= 3 )  $skip = 10;

        $new_page = $page + $skip;
        off10_advance_page( $state, $new_page );

        $saved = $skip * floatval( $state['adaptive']['current_delay'] ?? $state['batch_delay'] );
        $state['adaptive']['total_time_saved'] += $saved;

        if ( $skip > 1 ) {
            off10_log( 'info', '⚡ Turbo skip: +' . $skip . ' oldal → ' . $new_page . ' (~' . round( $saved ) . 's megtakarítva)' );
        } else {
            off10_log( 'info', '⏭ Skip: oldal ' . $page . ' – mind létezik → ' . $new_page );
        }

        off10_save_state( $state );
        return;
    }

    $state['consecutive_full_skip'] = 0;
    $state['stats']['img_consecutive'] = 0;

    $nutrient_fields = off10_get_nutrient_fields();

    $batch_created = 0;
    $batch_skipped = 0;
    $batch_errors  = 0;

    foreach ( $products as $product ) {
        if ( off10_should_stop() ) {
            off10_log( 'info', '⏹ Leállítva batch közben. Oldal: ' . $page . ', +' . $batch_created . ' új' );
            off10_save_state( $state );
            wp_clear_scheduled_hook( OFF10_CRON_HOOK );
            return;
        }

        $barcode = trim( $product['code'] ?? '' );

        if ( empty( $barcode ) || isset( $existing_barcodes[ $barcode ] ) ) {
            $batch_skipped++;
            $state['stats']['skipped']++;
            $state['stats']['processed']++;
            continue;
        }

        if ( $state['max_items'] > 0 && $state['stats']['created'] >= $state['max_items'] ) {
            $state['status'] = 'done';
            off10_save_state( $state );
            off10_log( 'success', '🎉 Maximum elérve: ' . $state['max_items'] );
            wp_clear_scheduled_hook( OFF10_CRON_HOOK );
            return;
        }

        $name    = trim( $product['product_name_hu'] ?? '' );
        $name_en = trim( $product['product_name'] ?? '' );
        if ( empty( $name ) ) $name = $name_en;
        if ( empty( $name ) ) {
            $batch_skipped++;
            $state['stats']['skipped']++;
            $state['stats']['processed']++;
            off10_log( 'skip', '⏭ Nincs név: ' . $barcode );
            continue;
        }

        // Atomi dup check
        $existing_post_id = off10_barcode_exists_single( $barcode );
        if ( $existing_post_id !== false ) {
            $batch_skipped++;
            $state['stats']['skipped']++;
            $state['stats']['processed']++;
            $existing_barcodes[ $barcode ] = true;
            off10_log( 'debug', '🛡️ Dup megakadályozva: ' . $barcode . ' (#' . $existing_post_id . ')' );
            continue;
        }

        $post_id = wp_insert_post( [
            'post_type'   => 'alapanyag',
            'post_title'  => $name,
            'post_status' => 'draft',
            'meta_input'  => [
                'off_url'            => $product['url'] ?? '',
                'off_imported_at'    => current_time( 'Y-m-d H:i:s' ),
                'off_import_version' => OFF10_PLUGIN_VERSION,
            ],
        ], true );

        if ( is_wp_error( $post_id ) ) {
            $batch_errors++;
            $state['stats']['errors']++;
            $state['stats']['processed']++;
            off10_log( 'error', '❌ WP: ' . $post_id->get_error_message() );
            continue;
        }

        // Tápértékek – ACF field-del
        $nutriments = $product['nutriments'] ?? [];
        foreach ( $nutrient_fields as $field ) {
            $off_key = $field['off_key'] ?? '';
            $acf_key = $field['acf_key'] ?? '';
            if ( empty( $off_key ) || empty( $acf_key ) ) continue;
            $value = $nutriments[ $off_key ] ?? null;
            if ( $value !== null ) {
                off10_save_acf_field( $acf_key, round( floatval( $value ), 4 ), $post_id );
            }
        }

        // Azonosító mezők – ACF field key cache
        off10_save_acf_field( 'off_barcode', $barcode, $post_id, true );
        off10_save_acf_field( 'elsodleges_forras', 'off', $post_id, true );
        off10_save_acf_field( 'eredeti_nev', $name_en ?: $name, $post_id, true );
        if ( ! empty( $name_en ) && $name_en !== $name ) {
            off10_save_acf_field( 'off_name_en', $name_en, $post_id );
        }
        if ( ! empty( $product['brands'] ) ) {
            off10_save_acf_field( 'off_brands', sanitize_text_field( $product['brands'] ), $post_id );
        }

        // Kép
        $img_url = $product['image_front_url'] ?? '';
        if ( ! empty( $img_url ) && ! has_post_thumbnail( $post_id ) ) {
            off10_safe_sideload_image( $img_url, $post_id, $name, $state );
        }

        $batch_created++;
        $state['stats']['created']++;
        $state['stats']['processed']++;
        $existing_barcodes[ $barcode ] = true;
    }

    off10_advance_page( $state, $page + 1 );

    // v10.1: Utolsó oldal check – CSAK ha az API kevesebbet adott mint kértünk ÉS total ismert
    if ( count( $products ) < $batch_size && $state['total'] > 0 ) {
        $estimated_last_page = ceil( $state['total'] / $batch_size );
        if ( $page >= $estimated_last_page ) {
            $state['status'] = 'done';
            off10_save_state( $state );
            off10_log( 'success', '🏁 Import kész! Össz: ' . $state['stats']['created'] . ' új. (Utolsó oldal: ' . $page . ')' );
            wp_clear_scheduled_hook( OFF10_CRON_HOOK );
            return;
        }
    }

    // Batch végén stop check
    if ( off10_should_stop() ) {
        off10_log( 'info', '⏹ Batch végén leállítva. Oldal: ' . $page );
        off10_save_state( $state );
        wp_clear_scheduled_hook( OFF10_CRON_HOOK );
        return;
    }

    off10_save_state( $state );
    off10_log( 'success', '✅ Oldal ' . $page . ': +' . $batch_created . ' új, ' . $batch_skipped . ' skip, ' . $batch_errors . ' hiba | Össz: ' . $state['stats']['created'] . ' | Köv: ' . $state['page'] . ' | avg: ' . ( $state['adaptive']['avg_response_time'] ?? '?' ) . 's' );
}

/* ═══════════════════════════════════════════════════════════
   BATCH SYNC (mindig csak meglévőket)
   ═══════════════════════════════════════════════════════════ */
function off10_batch_sync( $state ) {
    if ( ! function_exists( 'update_field' ) || ! function_exists( 'get_field' ) ) {
        $state['status'] = 'error';
        off10_save_state( $state );
        off10_log( 'error', '❌ ACF nincs betöltve.' );
        wp_clear_scheduled_hook( OFF10_CRON_HOOK );
        return;
    }

    if ( off10_should_stop() ) {
        off10_log( 'info', '⏹ Sync batch elején leállítva.' );
        return;
    }

    $page     = intval( $state['page'] ?? 1 );
    $per_page = 20;

    $state['current_processing_page'] = $page;
    off10_save_state( $state );

    off10_log( 'info', '🔄 Szinkron batch #' . $page );

    // Mindig csak meglévő OFF alapanyagok
    $post_ids = get_posts( [
        'post_type'      => 'alapanyag',
        'post_status'    => [ 'publish', 'draft' ],
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'meta_query'     => [ [ 'key' => 'off_barcode', 'value' => '', 'compare' => '!=' ] ],
        'fields'         => 'ids',
    ] );

    if ( empty( $post_ids ) ) {
        $state['status'] = 'done_sync';
        off10_save_state( $state );
        $dc = count( $state['diffs'] ?? [] );
        off10_log( 'success', '🎉 Szinkron kész! ' . ( $state['stats']['processed'] ?? 0 ) . ' ellenőrizve, ' . $dc . ' eltérés.' );
        wp_clear_scheduled_hook( OFF10_CRON_HOOK );
        return;
    }

    $nutrient_fields = off10_get_nutrient_fields();

    foreach ( $post_ids as $pid ) {
        if ( off10_should_stop() ) {
            off10_log( 'info', '⏹ Szinkron leállítva. Oldal: ' . $page );
            off10_save_state( $state );
            wp_clear_scheduled_hook( OFF10_CRON_HOOK );
            return;
        }

        $barcode = get_field( 'off_barcode', $pid );
        if ( empty( $barcode ) ) $barcode = get_post_meta( $pid, 'off_barcode', true );
        if ( empty( $barcode ) ) {
            $state['stats']['skipped']++;
            $state['stats']['processed']++;
            continue;
        }

        if ( ! off10_rate_consume() ) {
            off10_log( 'warn', '⏳ Rate limit – szinkron szünetel' );
            off10_save_state( $state );
            return;
        }

        $product_data = off10_api_get_product( $barcode );
        $state['stats']['api_calls']++;

        if ( $product_data === false ) {
            $state['stats']['errors']++;
            $state['stats']['processed']++;
            off10_log( 'error', '❌ API: ' . $barcode );
            continue;
        }

        if ( empty( $product_data['product'] ) ) {
            $state['stats']['skipped']++;
            $state['stats']['processed']++;
            continue;
        }

        $product    = $product_data['product'];
        $nutriments = $product['nutriments'] ?? [];
        $changes    = [];

        foreach ( $nutrient_fields as $field ) {
            $off_key = $field['off_key'] ?? '';
            $acf_key = $field['acf_key'] ?? '';
            $label   = $field['label'] ?? $off_key;
            $egyseg  = $field['egyseg'] ?? '';
            if ( empty( $off_key ) || empty( $acf_key ) ) continue;

            $old = floatval( get_field( $acf_key, $pid ) );
            $new = isset( $nutriments[ $off_key ] ) ? floatval( $nutriments[ $off_key ] ) : null;
            if ( $new !== null && abs( $new - $old ) > 0.01 ) {
                $changes[] = [
                    'acf_key'   => $acf_key,
                    'label'     => $label,
                    'egyseg'    => $egyseg,
                    'old_value' => round( $old, 2 ),
                    'new_value' => round( $new, 2 ),
                ];
            }
        }

        if ( ! empty( $changes ) ) {
            $state['stats']['diff']++;
            if ( count( $state['diffs'] ) < OFF10_DIFFS_MAX ) {
                $state['diffs'][] = [
                    'post_id' => $pid,
                    'name'    => get_the_title( $pid ),
                    'barcode' => $barcode,
                    'changes' => $changes,
                ];
            }
            off10_log( 'warn', '⚠️ Eltérés: ' . get_the_title( $pid ) . ' (' . count( $changes ) . ' mező)' );
        } else {
            $state['stats']['unchanged']++;
        }

        $state['stats']['processed']++;
        usleep( 600000 );
    }

    off10_advance_page( $state, $page + 1 );

    if ( off10_should_stop() ) {
        off10_log( 'info', '⏹ Sync végén leállítva.' );
        off10_save_state( $state );
        wp_clear_scheduled_hook( OFF10_CRON_HOOK );
        return;
    }

    off10_save_state( $state );
    off10_log( 'info', '🔄 Szinkron oldal ' . $page . ' kész. Eltérések: ' . ( $state['stats']['diff'] ?? 0 ) );
}

/* ═══════════════════════════════════════════════════════════
   ADMIN MENÜ
   ═══════════════════════════════════════════════════════════ */
function off10_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=alapanyag',
        'OpenFoodFacts Import v10.1',
        '🔄 OFF Import',
        'manage_options',
        'off-import',
        'off10_admin_page'
    );
}
add_action( 'admin_menu', 'off10_admin_menu' );

/* ══���════════════════════════════════════════════════════════
   ADMIN OLDAL HTML
   ═══════════════════════════════════════════════════════════ */
function off10_admin_page() {
    global $wpdb;

    $existing_count = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'alapanyag' AND p.post_status IN ('publish','draft')
           AND pm.meta_key = 'off_barcode' AND pm.meta_value != ''"
    );

    $state       = off10_get_state();
    $status      = $state['status'] ?? 'idle';
    $is_running  = ( $status === 'running' );
    $is_stopping = ( $status === 'stopping' );
    $has_saved   = ! $is_running && ! $is_stopping && ! empty( $state['page'] ) && intval( $state['page'] ) > 1 && in_array( $status, [ 'stopped', 'error' ], true );
    $countries   = off10_country_map();
    $last_known  = intval( $state['last_known_page'] ?? 0 );

    $saved_country    = $state['country'] ?? 'hungary';
    $saved_batch_size = intval( $state['adaptive']['current_batch_size'] ?? $state['batch_size'] ?? 50 );
    $saved_delay      = floatval( $state['adaptive']['current_delay'] ?? $state['batch_delay'] ?? 3 );
    $saved_max        = intval( $state['max_items'] ?? 0 );

    ?>
    <div class="wrap">
        <h1>🍊 OpenFoodFacts – Termék Import <small style="font-size:0.6em;color:#999;">v10.1 Final</small></h1>

        <?php if ( $is_stopping ) : ?>
        <div style="background:#fef3c7;border:2px solid #f59e0b;border-radius:8px;padding:16px 20px;margin:20px 0;max-width:900px;">
            <h3 style="margin:0;color:#92400e;">⏳ Leállítás folyamatban...</h3>
            <p style="margin:8px 0 0;color:#92400e;">Az aktuális batch befejezése után megáll.</p>
            <script>setTimeout(function(){location.reload();},3000);</script>
        </div>
        <?php endif; ?>

        <div class="off10-card">
            <h3 style="margin-top:0;">ℹ️ Állapot</h3>
            <table class="widefat" style="max-width:600px;">
                <tr><td><strong>OFF-ból importált</strong></td><td><strong style="font-size:1.2rem;color:#f59e0b;"><?php echo $existing_count; ?></strong> db</td></tr>
                <tr><td><strong>API limit</strong></td><td><span style="color:#dc2626;font-weight:600;">100 kérés/perc</span></td></tr>
                <tr><td><strong>Motor</strong></td><td>🔄 Hibrid Adaptív v10.1 Final (stop-safe + ACF key cache + no auto-stop)</td></tr>
                <?php if ( $is_running ) : ?>
                <tr><td><strong>Folyamat</strong></td><td><span class="off10-pulse" style="color:#16a34a;font-weight:700;">🟢 FUT</span> – Oldal <?php echo intval( $state['current_processing_page'] ?: $state['page'] ); ?> | +<?php echo intval( $state['stats']['created'] ); ?> | <?php echo esc_html( $state['country_label'] ); ?></td></tr>
                <?php elseif ( $is_stopping ) : ?>
                <tr><td><strong>Folyamat</strong></td><td><span style="color:#f59e0b;font-weight:700;">⏳ LEÁLL...</span></td></tr>
                <?php elseif ( $has_saved ) : ?>
                <tr><td><strong>Mentett pozíció</strong></td><td><span style="color:#2563eb;">📌 Oldal <?php echo intval( $state['page'] ); ?> | <?php echo esc_html( $state['country_label'] ); ?> | +<?php echo intval( $state['stats']['created'] ); ?> | <?php echo esc_html( $state['date'] ); ?></span></td></tr>
                <?php endif; ?>
            </table>
        </div>

        <div id="off10-settings-box" class="off10-card" style="background:#f8fafc;border-color:#e2e8f0;<?php echo ( $is_running || $is_stopping ) ? 'opacity:0.5;pointer-events:none;' : ''; ?>">
            <h3 style="margin-top:0;">⚙️ Beállítások</h3>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
                <div>
                    <label class="off10-label" for="off10-country">Ország:</label>
                    <select id="off10-country" style="width:100%;">
                        <?php foreach ( $countries as $val => $lbl ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>"<?php selected( $val, $saved_country ); ?>><?php echo esc_html( $lbl ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="off10-label" for="off10-batch-size">Induló batch:</label>
                    <input type="number" id="off10-batch-size" value="<?php echo $saved_batch_size; ?>" min="10" max="100" step="10" style="width:100%;">
                    <span class="off10-hint">Adaptív: 10–100 auto</span>
                </div>
                <div>
                    <label class="off10-label" for="off10-batch-delay">Induló delay:</label>
                    <input type="number" id="off10-batch-delay" value="<?php echo $saved_delay; ?>" min="0.7" max="60" step="0.1" style="width:100%;">
                    <span class="off10-hint">Adaptív: 0.8–30s auto</span>
                </div>
                <div>
                    <label class="off10-label" for="off10-max">Maximum:</label>
                    <input type="number" id="off10-max" value="<?php echo $saved_max; ?>" min="0" max="999999" step="10" style="width:100%;">
                    <span class="off10-hint">0 = korlátlan (manuális stop)</span>
                </div>
            </div>
            <div id="off10-limit-warning" class="off10-warning" style="display:none;"></div>
            <div id="off10-time-estimate-box" class="off10-estimate">💡 <strong>Becsült idő</strong>: <span id="off10-time-estimate">–</span> (<span id="off10-batch-count-est">–</span> batch × <span id="off10-delay-est">–</span>s) | API kérés/perc: <strong><span id="off10-req-per-min">–</span></strong>/100</div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;max-width:900px;margin:20px 0;">
            <div class="off10-mode-card" style="background:#f0fdf4;border-color:#86efac;">
                <h3 style="margin-top:0;color:#16a34a;font-size:1rem;">🚀 Új import</h3>
                <?php if ( $last_known > 1 && $existing_count > 0 && ! $is_running && ! $is_stopping ) : ?>
                    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;padding:8px 12px;margin-bottom:10px;font-size:0.82rem;color:#92400e;">⚠️ Már van <strong><?php echo $existing_count; ?></strong> OFF (oldal <?php echo $last_known; ?>-ig).</div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <button id="off10-import-start" class="button button-primary" style="font-size:13px;padding:5px 16px;">🚀 Elejétől</button>
                        <button id="off10-import-smart" class="button" style="font-size:13px;padding:5px 16px;background:#f59e0b;color:#fff;border-color:#d97706;">⚡ Smart (<?php echo $last_known; ?>. oldaltól)</button>
                    </div>
                    <span class="off10-hint">⚡ = átugorja az ismert <?php echo $last_known - 1; ?> oldalt</span>
                <?php else : ?>
                    <p style="font-size:0.82rem;color:#555;">Az elejétől indul. Manuális stop-ig fut.</p>
                    <button id="off10-import-start" class="button button-primary" style="font-size:13px;padding:5px 16px;" <?php echo ( $is_running || $is_stopping ) ? 'disabled' : ''; ?>>🚀 Indítás</button>
                <?php endif; ?>
            </div>

            <div class="off10-mode-card" style="background:<?php echo $has_saved ? '#eff6ff' : ( $is_running ? '#f0fdf4' : '#f8fafc' ); ?>;border-color:<?php echo $has_saved ? '#93c5fd' : ( $is_running ? '#86efac' : '#e2e8f0' ); ?>;">
                <h3 style="margin-top:0;color:#2563eb;font-size:1rem;">▶️ Folytatás</h3>
                <?php if ( $is_running || $is_stopping ) : ?>
                    <p style="font-size:0.82rem;color:#16a34a;font-weight:600;">🟢 <?php echo $is_stopping ? 'Leáll...' : 'Fut...'; ?></p>
                    <button class="button" disabled>▶️ <?php echo $is_stopping ? 'Leáll...' : 'Fut...'; ?></button>
                <?php elseif ( $has_saved ) : ?>
                    <p style="font-size:0.82rem;color:#555;">Utolsó: <strong>oldal <?php echo intval( $state['page'] ); ?></strong> (+<?php echo intval( $state['stats']['created'] ); ?>)<br><?php echo esc_html( $state['date'] ); ?></p>
                    <button id="off10-import-continue" class="button" style="font-size:13px;padding:5px 16px;background:#3b82f6;color:#fff;border-color:#2563eb;">▶️ Folytatás</button>
                    <button id="off10-import-reset" class="button" style="font-size:12px;padding:4px 10px;color:#dc2626;margin-left:4px;">🗑️</button>
                <?php else : ?>
                    <p style="font-size:0.82rem;color:#999;">Nincs mentett pozíció.</p>
                    <button class="button" disabled>▶️ Nincs adat</button>
                <?php endif; ?>
            </div>

            <div class="off10-mode-card" style="background:#fffbeb;border-color:#fcd34d;">
                <h3 style="margin-top:0;color:#d97706;font-size:1rem;">🔄 Szinkron</h3>
                <p style="font-size:0.82rem;color:#555;">Meglévő <?php echo $existing_count; ?> OFF alapanyag frissítése.</p>
                <button id="off10-sync-start" class="button" style="font-size:13px;padding:5px 16px;background:#f59e0b;color:#fff;border-color:#d97706;" <?php echo ( $existing_count < 1 || $is_running || $is_stopping ) ? 'disabled' : ''; ?>>🔄 Szinkron indítása</button>
            </div>
        </div>

        <div style="margin:0 0 12px;">
            <button id="off10-stop" class="button button-secondary" style="font-size:14px;padding:6px 20px;<?php echo $is_running ? '' : 'display:none;'; ?>">⏹ Leállítás</button>
        </div>

        <div class="off10-card">
            <h3 style="margin-top:0;">🔍 Egyedi OFF keresés</h3>
            <p style="font-size:0.88rem;color:#555;">Keress rá egy termékre (szöveg vagy vonalkód).</p>
            <div style="display:flex;gap:8px;">
                <input type="text" id="off10-search-query" placeholder="pl. túró rudi, müzli, vagy vonalkód..." style="flex:1;font-size:14px;padding:6px 12px;">
                <select id="off10-search-country" style="font-size:14px;padding:6px 8px;">
                    <option value="">🌍 Összes</option>
                    <?php foreach ( $countries as $val => $lbl ) : if ( $val === '' ) continue; ?>
                        <option value="<?php echo esc_attr( $val ); ?>"<?php selected( $val, 'hungary' ); ?>><?php echo esc_html( $lbl ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button id="off10-search-btn" class="button" style="font-size:14px;padding:6px 16px;">🔍 Keresés</button>
            </div>
            <div id="off10-search-results" style="margin-top:12px;"></div>
        </div>

        <div id="off10-live-status" class="off10-card" style="<?php echo $is_running ? '' : 'display:none;'; ?>">
            <h3 style="margin-top:0;">📊 Élő státusz <span id="off10-live-indicator" class="off10-pulse" style="color:#16a34a;">●</span></h3>
            <div class="off10-progress-wrap"><div id="off10-progress-bar" class="off10-progress-fill">0%</div></div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:8px;">
                <div style="text-align:center;padding:8px;background:#f0f6fc;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;" id="off10-s-processed">0</div><div style="font-size:0.72rem;color:#666;">Feldolgozva</div></div>
                <div style="text-align:center;padding:8px;background:#f0fdf4;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#16a34a;" id="off10-s-created">0</div><div style="font-size:0.72rem;color:#666;">Új</div></div>
                <div style="text-align:center;padding:8px;background:#fffbeb;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#d97706;" id="off10-s-skipped">0</div><div style="font-size:0.72rem;color:#666;">Kihagyva</div></div>
                <div style="text-align:center;padding:8px;background:#fef2f2;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#dc2626;" id="off10-s-errors">0</div><div style="font-size:0.72rem;color:#666;">Hiba</div></div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:12px;">
                <div style="text-align:center;padding:8px;background:#f5f3ff;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#7c3aed;" id="off10-s-empty">0</div><div style="font-size:0.72rem;color:#666;">Üres batch</div></div>
                <div style="text-align:center;padding:8px;background:#f0f6fc;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#2563eb;" id="off10-s-page">0</div><div style="font-size:0.72rem;color:#666;">Oldal</div></div>
                <div style="text-align:center;padding:8px;background:#fef3c7;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#d97706;" id="off10-s-smartskip">0</div><div style="font-size:0.72rem;color:#666;">Smart skip</div></div>
                <div style="text-align:center;padding:8px;background:#f0f6fc;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#0891b2;" id="off10-s-api">0</div><div style="font-size:0.72rem;color:#666;">API hívás</div></div>
            </div>
            <div id="off10-adaptive-info" style="font-size:0.82rem;color:#555;margin-bottom:8px;padding:8px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;"></div>
            <div id="off10-eta" style="font-size:0.85rem;color:#666;margin-bottom:8px;padding:6px 10px;background:#f8fafc;border-radius:4px;"></div>
            <div id="off10-live-info" style="font-size:0.85rem;color:#666;margin-bottom:8px;padding:6px 10px;background:#f8fafc;border-radius:4px;"></div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                <span style="font-size:0.85rem;font-weight:600;">📋 Log</span>
                <button id="off10-log-export" class="button" style="font-size:11px;padding:2px 8px;">📥 Export</button>
            </div>
            <div id="off10-log" style="background:#1e1e1e;color:#d4d4d4;font-family:monospace;font-size:0.82rem;padding:12px 16px;border-radius:6px;max-height:300px;overflow-y:auto;line-height:1.6;"></div>
        </div>

        <div id="off10-diff-section" style="display:none;background:#fff;border:2px solid #f59e0b;border-radius:8px;padding:20px 24px;margin:20px 0;max-width:900px;">
            <h3 style="margin-top:0;color:#d97706;">⚠️ Eltérések – <span id="off10-diff-count">0</span> alapanyagnál</h3>
            <p style="font-size:0.88rem;color:#555;">Jelöld ki melyeket szeretnéd frissíteni.</p>
            <div style="margin-bottom:12px;"><label style="font-size:0.88rem;cursor:pointer;"><input type="checkbox" id="off10-diff-select-all" checked> Összes</label></div>
            <div id="off10-diff-list" style="max-height:500px;overflow-y:auto;"></div>
            <div style="margin-top:16px;">
                <button id="off10-diff-apply" class="button button-primary" style="font-size:14px;padding:6px 20px;">✅ Frissítés</button>
                <span id="off10-diff-apply-status" style="margin-left:12px;font-size:0.88rem;"></span>
            </div>
        </div>
    </div>

    <style>
        .off10-card{background:#fff;border:1px solid #ccd0d4;border-radius:8px;padding:20px 24px;margin:20px 0;max-width:900px;}
        .off10-mode-card{border:2px solid;border-radius:8px;padding:18px 20px;}
        .off10-label{font-weight:600;font-size:0.88rem;display:block;margin-bottom:4px;}
        .off10-hint{color:#888;font-size:0.75rem;}
        .off10-warning{margin-top:12px;padding:10px 14px;border-radius:6px;font-size:0.85rem;}
        .off10-estimate{margin-top:12px;padding:10px 14px;background:#fffbeb;border-radius:6px;font-size:0.85rem;color:#92400e;}
        .off10-progress-wrap{background:#f0f0f1;border-radius:6px;height:28px;overflow:hidden;margin-bottom:12px;}
        .off10-progress-fill{background:linear-gradient(90deg,#F59E0B,#f97316);height:100%;width:0%;transition:width 0.4s ease;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;font-size:0.85rem;}
        @keyframes off10pulse{0%,100%{opacity:1;}50%{opacity:0.4;}}
        .off10-pulse{animation:off10pulse 1.5s infinite;}
    </style>
    <?php
}

/* ═══════════════════════════════════════════════════════════
   ADMIN SCRIPTS (JS)
   ═══════════════════════════════════════════════════════════ */
function off10_admin_scripts( $hook ) {
    if ( $hook !== 'alapanyag_page_off-import' ) return;
    $state          = off10_get_state();
    $countries_json = wp_json_encode( off10_country_map() );

    $js = <<<JSEOF
(function(){
'use strict';
if(typeof off10Data==='undefined'){console.error('OFF10: off10Data not loaded');return;}
var \$=function(id){return document.getElementById(id);};
var pollTimer=null,syncAllDiffs=[],startTime=0,startProcessed=0;
var stopRequested=false;
var CL={$countries_json};

function validate(){
    var bsEl=\$('off10-batch-size'),dlEl=\$('off10-batch-delay'),mxEl=\$('off10-max');
    if(!bsEl||!dlEl||!mxEl)return true;
    var bs=parseInt(bsEl.value)||50;
    var dl=parseFloat(dlEl.value)||3;
    var mx=parseInt(mxEl.value);if(isNaN(mx)||mx<0)mx=0;
    var rpm=Math.round(60/dl);
    var batches=mx>0?Math.ceil(mx/bs):0;
    var tsec=batches*dl,mn=Math.floor(tsec/60),sc=Math.round(tsec%60);
    var setT=function(id,v){var e=\$(id);if(e)e.textContent=v;};
    setT('off10-req-per-min',rpm);
    setT('off10-batch-count-est',mx>0?batches:'∞');
    setT('off10-delay-est',dl);
    setT('off10-time-estimate',mx>0?((mn>0?'~'+mn+'p ':'')+sc+'s'):'∞ (manuális stop)');
    var w=\$('off10-limit-warning'),sb=\$('off10-import-start'),cb=\$('off10-import-continue');
    if(rpm>100){if(w){w.style.display='block';w.style.background='#fef2f2';w.style.color='#dc2626';w.innerHTML='🚫 Túl gyors! '+rpm+'/perc > 100.';}if(sb)sb.disabled=true;if(cb)cb.disabled=true;return false;}
    if(rpm>80){if(w){w.style.display='block';w.style.background='#fffbeb';w.style.color='#92400e';w.innerHTML='⚠️ Közel a limithez! '+rpm+'/perc.';}if(sb)sb.disabled=false;if(cb)cb.disabled=false;return true;}
    if(w)w.style.display='none';if(sb)sb.disabled=false;if(cb)cb.disabled=false;return true;
}
if(\$('off10-batch-size'))\$('off10-batch-size').addEventListener('input',validate);
if(\$('off10-batch-delay'))\$('off10-batch-delay').addEventListener('input',validate);
if(\$('off10-max'))\$('off10-max').addEventListener('input',validate);
validate();

function ajax(action,data,cb){
    var fd=new FormData();fd.append('action',action);fd.append('nonce',off10Data.nonce);
    if(data){for(var k in data)fd.append(k,data[k]);}
    fetch(off10Data.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){if(cb)cb(r);}).catch(function(e){if(cb)cb({success:false,data:'Hálózat: '+e.message});});
}

function startProc(mode,page){
    if(!validate())return;
    var mxEl=\$('off10-max');
    var mx=mxEl?parseInt(mxEl.value):0;if(isNaN(mx)||mx<0)mx=0;
    var d={mode:mode,page:page,country:(\$('off10-country')||{}).value||'hungary',batch_size:(\$('off10-batch-size')||{}).value||50,batch_delay:(\$('off10-batch-delay')||{}).value||3,max_items:mx};
    ajax('off10_start',d,function(r){if(r.success)location.reload();else alert('Hiba: '+(r.data||'Ismeretlen'));});
}

if(\$('off10-import-start'))\$('off10-import-start').addEventListener('click',function(){startProc('import',1);});
if(\$('off10-import-smart'))\$('off10-import-smart').addEventListener('click',function(){startProc('import',off10Data.lastKnownPage||1);});
if(\$('off10-import-continue'))\$('off10-import-continue').addEventListener('click',function(){startProc('continue',off10Data.savedPage);});
if(\$('off10-import-reset'))\$('off10-import-reset').addEventListener('click',function(){if(!confirm('Biztosan törlöd?'))return;ajax('off10_reset',{},function(){location.reload();});});

if(\$('off10-stop'))\$('off10-stop').addEventListener('click',function(){
    this.disabled=true;this.textContent='⏳ Leállítás...';stopRequested=true;
    ajax('off10_stop',{},function(){});
});

if(\$('off10-sync-start'))\$('off10-sync-start').addEventListener('click',function(){startProc('sync',1);});

function poll(){
    if(stopRequested){
        ajax('off10_status_readonly',{},function(r){
            if(!r.success)return;var s=r.data;
            updateUI(s);
            if(s.status!=='running'&&s.status!=='stopping'){
                clearInterval(pollTimer);setTimeout(function(){location.reload();},500);
            }
        });
        return;
    }
    ajax('off10_status',{},function(r){
        if(!r.success)return;var s=r.data;
        updateUI(s);
        if(s.status!=='running'){
            clearInterval(pollTimer);
            var li=\$('off10-live-indicator');if(li){li.textContent='⏹';li.style.animation='none';}
            var stBtn=\$('off10-stop');if(stBtn)stBtn.style.display='none';
            if(s.status==='done_sync'&&s.diffs&&s.diffs.length>0){syncAllDiffs=s.diffs;showDiffs();}
            setTimeout(function(){location.reload();},2500);
        }
    });
}

function updateUI(s){
    var setT=function(id,v){var e=\$(id);if(e)e.textContent=v;};
    setT('off10-s-processed',s.processed||0);
    setT('off10-s-created',s.created||0);
    setT('off10-s-skipped',s.skipped||0);
    setT('off10-s-errors',s.errors||0);
    setT('off10-s-empty',s.empty_batches||0);
    setT('off10-s-page',s.current_processing_page||s.page||0);
    setT('off10-s-smartskip',s.smart_skipped||0);
    setT('off10-s-api',s.api_calls||0);

    var pct=0;
    if(s.mode==='sync'&&s.sync_total>0)pct=Math.min(100,Math.round((s.processed/s.sync_total)*100));
    else if(s.total>0)pct=Math.min(100,Math.round(((s.page*s.batch_size)/s.total)*100));
    else if(s.max_items>0&&s.created>0)pct=Math.min(100,Math.round((s.created/s.max_items)*100));
    var pb=\$('off10-progress-bar');if(pb){pb.style.width=pct+'%';pb.textContent=pct+'%';}

    if(startTime===0){startTime=Date.now();startProcessed=s.processed||0;}
    var elapsed=(Date.now()-startTime)/1000,done=(s.processed||0)-startProcessed,etaEl=\$('off10-eta');
    if(etaEl&&done>0&&elapsed>5){var rate=done/elapsed,rem=0;
        if(s.mode==='sync'&&s.sync_total>0)rem=Math.max(0,s.sync_total-s.processed);
        else if(s.max_items>0)rem=Math.max(0,s.max_items-s.created);
        else if(s.total>0){var estTotal=s.total;rem=Math.max(0,estTotal-s.processed);}
        if(rem>0){var es=Math.round(rem/rate),em=Math.floor(es/60),eh=Math.floor(em/60);etaEl.innerHTML='⏱ ETA: <strong>'+(eh>0?eh+'ó ':'')+(em%60>0?(em%60)+'p ':'')+es%60+'s</strong> | '+rate.toFixed(1)+'/s';}else if(etaEl){etaEl.textContent='';}
    }

    var liEl=\$('off10-live-info');
    if(liEl)liEl.innerHTML='Ország: <strong>'+esc(CL[s.country]||s.country||'Összes')+'</strong> | Batch: <strong>'+s.batch_size+'</strong> | Delay: <strong>'+s.batch_delay+'s</strong> | Total: <strong>'+(s.total||'?')+'</strong> | Max: <strong>'+(s.max_items>0?s.max_items:'∞')+'</strong>';

    var ai=\$('off10-adaptive-info');
    if(ai&&s.adaptive&&s.adaptive.enabled){ai.style.display='block';ai.innerHTML='🤖 <strong>Adaptív</strong>: Fázis=<strong>'+esc(s.adaptive.phase)+'</strong> | API avg: <strong>'+s.adaptive.avg_response+'s</strong> | Timeouts: <strong>'+s.adaptive.timeouts+'</strong> | Megtakarítva: <strong>~'+s.adaptive.time_saved+'s</strong>'+(s.adaptive.adjustment?' | '+esc(s.adaptive.adjustment):'');}else if(ai){ai.style.display='none';}

    if(s.recent_log&&s.recent_log.length>0){var le=\$('off10-log');if(le){var lh='';var colors={info:'#60a5fa',success:'#4ade80',warn:'#fbbf24',error:'#f87171',skip:'#c084fc',debug:'#94a3b8'};s.recent_log.forEach(function(e){lh+='<div style="color:'+(colors[e.type]||'#d4d4d4')+'">['+esc(e.time)+'] '+esc(e.msg)+'</div>';});le.innerHTML=lh;le.scrollTop=le.scrollHeight;}}
}

if(off10Data.isRunning){var lsEl=\$('off10-live-status');if(lsEl)lsEl.style.display='block';var stBtn=\$('off10-stop');if(stBtn)stBtn.style.display='inline-block';pollTimer=setInterval(poll,3000);poll();}

function showDiffs(){
    var dsEl=\$('off10-diff-section'),dcEl=\$('off10-diff-count'),dlEl=\$('off10-diff-list');
    if(!dsEl||!dcEl||!dlEl)return;
    dsEl.style.display='block';dcEl.textContent=syncAllDiffs.length;var h='';
    syncAllDiffs.forEach(function(item,idx){h+='<div style="border:1px solid #e5e7eb;border-radius:6px;margin-bottom:8px;padding:12px;"><label style="display:flex;align-items:start;gap:10px;cursor:pointer;"><input type="checkbox" class="off10-diff-cb" data-index="'+idx+'" checked style="margin-top:4px;"><div style="flex:1;"><strong>'+esc(item.name)+'</strong> <span style="color:#999;font-size:0.82rem;">(#'+item.post_id+' | '+esc(item.barcode)+')</span><div style="margin-top:6px;font-size:0.82rem;">';
    item.changes.forEach(function(ch){h+='<div style="display:grid;grid-template-columns:160px 100px 20px 100px;gap:4px;padding:2px 0;"><span style="color:#666;">'+esc(ch.label)+':</span><span style="color:#dc2626;text-decoration:line-through;">'+ch.old_value+' '+esc(ch.egyseg)+'</span><span>→</span><span style="color:#16a34a;font-weight:600;">'+ch.new_value+' '+esc(ch.egyseg)+'</span></div>';});
    h+='</div></div></label></div>';});dlEl.innerHTML=h;
}

if(\$('off10-diff-select-all'))\$('off10-diff-select-all').addEventListener('change',function(){var c=this.checked;document.querySelectorAll('.off10-diff-cb').forEach(function(cb){cb.checked=c;});});
if(\$('off10-diff-apply'))\$('off10-diff-apply').addEventListener('click',function(){var btn=this,st=\$('off10-diff-apply-status'),sel=[];
    document.querySelectorAll('.off10-diff-cb:checked').forEach(function(cb){sel.push(syncAllDiffs[parseInt(cb.dataset.index)]);});
    if(!sel.length){if(st){st.textContent='⚠️ Jelölj ki!';st.style.color='#d97706';}return;}
    btn.disabled=true;if(st){st.textContent='⏳...';st.style.color='#666';}
    ajax('off10_apply_diffs',{diffs:JSON.stringify(sel)},function(r){btn.disabled=false;if(st){st.textContent=r.success?'✅ '+r.data:'❌ '+r.data;st.style.color=r.success?'#16a34a':'#dc2626';}});
});

function doSearch(){
    var qEl=\$('off10-search-query');if(!qEl)return;
    var q=qEl.value.trim();if(!q)return;
    var btn=\$('off10-search-btn'),coEl=\$('off10-search-country'),resEl=\$('off10-search-results');
    if(!btn||!resEl)return;
    var co=coEl?coEl.value:'';
    btn.disabled=true;btn.textContent='⏳...';
    resEl.innerHTML='<div style="color:#666;">Keresés: "'+esc(q)+'"...</div>';
    ajax('off10_search',{query:q,country:co},function(r){
        btn.disabled=false;btn.textContent='🔍 Keresés';
        if(!r.success){resEl.innerHTML='<div style="color:#dc2626;">❌ '+esc(r.data)+'</div>';return;}
        var foods=r.data.foods;if(!foods||!foods.length){resEl.innerHTML='<div style="color:#d97706;">Nincs találat.</div>';return;}
        var h='<div style="font-size:0.85rem;color:#666;margin-bottom:8px;">'+foods.length+' találat:</div>';
        foods.forEach(function(f){h+='<div style="border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;margin-bottom:6px;display:flex;justify-content:space-between;align-items:center;"><div style="flex:1;min-width:0;"><strong>'+esc(f.name)+'</strong>';
        if(f.name_hu&&f.name_hu!==f.name)h+=' <span style="color:#16a34a;font-size:0.82rem;">🇭🇺 '+esc(f.name_hu)+'</span>';
        h+=' <span style="font-size:0.75rem;color:#94a3b8;">'+esc(f.barcode)+'</span>';
        if(f.brands)h+='<div style="font-size:0.78rem;color:#64748b;">🏷️ '+esc(f.brands)+'</div>';
        h+='<div style="font-size:0.78rem;color:#666;">🔥 '+(f.kcal||'?')+' kcal | F: '+(f.protein||'?')+'g | Sz: '+(f.carb||'?')+'g | Zs: '+(f.fat||'?')+'g</div>';
        if(f.image_url)h+='<div style="margin-top:4px;"><img src="'+esc(f.image_url)+'" style="max-height:40px;border-radius:4px;" loading="lazy"></div>';
        if(f.already_imported)h+='<div style="font-size:0.78rem;color:#16a34a;">✅ Már (#'+f.existing_id+')</div>';
        h+='</div>';if(!f.already_imported)h+='<button class="button off10-import-single" data-barcode="'+esc(f.barcode)+'" style="font-size:0.82rem;margin-left:8px;">📥</button>';h+='</div>';});
        resEl.innerHTML=h;
        document.querySelectorAll('.off10-import-single').forEach(function(ib){ib.addEventListener('click',function(){var bc=this.dataset.barcode,tb=this;tb.disabled=true;tb.textContent='⏳';ajax('off10_import_single',{barcode:bc},function(r){tb.textContent=r.success?'✅':'❌';tb.style.color=r.success?'#16a34a':'#dc2626';});});});
    });
}
if(\$('off10-search-btn'))\$('off10-search-btn').addEventListener('click',doSearch);
if(\$('off10-search-query'))\$('off10-search-query').addEventListener('keypress',function(e){if(e.key==='Enter')doSearch();});
if(\$('off10-log-export'))\$('off10-log-export').addEventListener('click',function(){var logEl=\$('off10-log');if(!logEl)return;var log=logEl.innerText;var blob=new Blob([log],{type:'text/plain'});var a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='off-log-'+new Date().toISOString().slice(0,10)+'.txt';a.click();});

function esc(s){var d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
})();
JSEOF;

    wp_register_script( 'off10-import-js', false, [], OFF10_PLUGIN_VERSION, true );
    wp_enqueue_script( 'off10-import-js' );
    wp_add_inline_script( 'off10-import-js', $js );
    wp_localize_script( 'off10-import-js', 'off10Data', [
        'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
        'nonce'         => wp_create_nonce( 'off10_nonce' ),
        'isRunning'     => ( ( $state['status'] ?? 'idle' ) === 'running' ),
        'savedPage'     => intval( $state['page'] ?? 1 ),
        'lastKnownPage' => intval( $state['last_known_page'] ?? 0 ),
    ] );
}
add_action( 'admin_enqueue_scripts', 'off10_admin_scripts' );

/* ═══════════════════════════════════════════════════════════
   AJAX: off10_start (START GUARD)
   ═══════════════════════════════════════════════════════════ */
function off10_start_handler() {
    check_ajax_referer( 'off10_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $current = off10_get_state();
    if ( in_array( $current['status'] ?? 'idle', [ 'running', 'stopping' ], true ) ) {
        wp_send_json_error( 'Az import már fut vagy éppen leáll.' );
    }

    $mode        = sanitize_key( $_POST['mode'] ?? 'import' );
    $page        = max( 1, intval( $_POST['page'] ?? 1 ) );
    $country     = sanitize_text_field( $_POST['country'] ?? 'hungary' );
    $batch_size  = min( OFF10_MAX_BATCH, max( 10, intval( $_POST['batch_size'] ?? 50 ) ) );
    $batch_delay = max( 0.7, floatval( $_POST['batch_delay'] ?? 3 ) );
    $max_items   = max( 0, intval( $_POST['max_items'] ?? 0 ) );

    $rpm = round( 60 / $batch_delay );
    if ( $rpm > OFF10_RATE_LIMIT ) wp_send_json_error( 'Túl gyors! ' . $rpm . '/perc > ' . OFF10_RATE_LIMIT );

    $countries     = off10_country_map();
    $country_label = $countries[ $country ] ?? $country;
    $old_state     = off10_get_state();
    $is_continue   = ( $mode === 'continue' );

    if ( $is_continue ) {
        $mode          = 'import';
        $country       = $old_state['country'] ?? $country;
        $country_label = $old_state['country_label'] ?? $country_label;
    }

    $state = off10_default_state();
    $state['status']                  = 'running';
    $state['mode']                    = ( $mode === 'sync' ) ? 'sync' : 'import';
    $state['page']                    = $page;
    $state['current_processing_page'] = $page;
    $state['country']                 = $country;
    $state['country_label']           = $country_label;
    $state['batch_size']              = $batch_size;
    $state['batch_delay']             = $batch_delay;
    $state['max_items']               = $max_items;
    $state['started_at']              = current_time( 'Y-m-d H:i:s' );
    $state['date']                    = current_time( 'Y-m-d H:i:s' );
    $state['last_known_page']         = max( $old_state['last_known_page'] ?? 0, $page );

    if ( $is_continue ) {
        $state['stats']             = $old_state['stats'] ?? $state['stats'];
        $state['adaptive']          = $old_state['adaptive'] ?? $state['adaptive'];
        $state['consecutive_empty']     = $old_state['consecutive_empty'] ?? 0;
        $state['consecutive_full_skip'] = $old_state['consecutive_full_skip'] ?? 0;
    }

    $state['adaptive']['current_delay']      = $batch_delay;
    $state['adaptive']['current_batch_size'] = $batch_size;

    if ( $mode === 'sync' ) {
        global $wpdb;
        $state['sync_total'] = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type='alapanyag' AND p.post_status IN ('publish','draft')
               AND pm.meta_key='off_barcode' AND pm.meta_value != ''"
        );
    }

    $state['_updated'] = microtime( true );
    update_option( OFF10_STATE_KEY, $state, false );
    wp_cache_delete( OFF10_STATE_KEY, 'options' );

    update_option( OFF10_LOG_KEY, [], false );
    update_option( OFF10_LASTRUN_KEY, 0, false );
    off10_force_unlock();

    wp_clear_scheduled_hook( OFF10_CRON_HOOK );
    wp_schedule_event( time(), 'off10_30s', OFF10_CRON_HOOK );
    spawn_cron();

    off10_log( 'info', '🚀 ' . ( $state['mode'] === 'sync' ? 'Szinkron' : 'Import' ) . ' indítása (v' . OFF10_PLUGIN_VERSION . ')...' );
    off10_log( 'info', '🌍 ' . $country_label . ' | Batch: ' . $batch_size . ' | Delay: ' . $batch_delay . 's | Max: ' . ( $max_items > 0 ? $max_items : '∞' ) );
    off10_log( 'info', '🤖 Adaptív + 🛡️ Stop-safe + ACF key cache aktív' );
    if ( $max_items === 0 ) off10_log( 'info', '♾️ Korlátlan mód – manuális stop-ig fut' );
    if ( $page > 1 ) off10_log( 'info', '📌 Folytatás: oldal ' . $page );

    wp_send_json_success( 'Elindítva.' );
}
add_action( 'wp_ajax_off10_start', 'off10_start_handler' );

/* ═══════════════════════════════════════════════════════════
   AJAX: off10_stop
   ═══════════════════════════════════════════════════════════ */
function off10_stop_handler() {
    check_ajax_referer( 'off10_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    off10_force_status( 'stopping' );
    off10_log( 'info', '⏳ Leállítás kérve...' );

    $waited = 0;
    while ( $waited < 2 ) {
        usleep( 250000 ); $waited += 0.25;
        $state = off10_get_state();
        if ( in_array( $state['status'], [ 'stopped', 'done' ], true ) ) {
            off10_force_unlock();
            off10_log( 'info', '⏹ Leállítva.' );
            wp_send_json_success( 'Leállítva.' );
            return;
        }
    }

    off10_force_status( 'stopped' );
    off10_force_unlock();
    off10_log( 'info', '⏹ Force stop.' );
    wp_send_json_success( 'Leállítva (force).' );
}
add_action( 'wp_ajax_off10_stop', 'off10_stop_handler' );

/* ═══════════════════════════════════════════════════════════
   AJAX: off10_reset
   ═══════════════════════════════════════════════════════════ */
function off10_reset_handler() {
    check_ajax_referer( 'off10_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );
    delete_option( OFF10_STATE_KEY );
    delete_option( OFF10_LOG_KEY );
    delete_option( OFF10_LASTRUN_KEY );
    off10_force_unlock();
    wp_clear_scheduled_hook( OFF10_CRON_HOOK );
    wp_send_json_success( 'Törölve.' );
}
add_action( 'wp_ajax_off10_reset', 'off10_reset_handler' );

/* ═══════════════════════════════════════════════════════════
   AJAX: off10_status (STOP-SAFE)
   ═══════════════════════════════════════════════════════════ */
function off10_status_handler() {
    check_ajax_referer( 'off10_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $state = off10_get_state();
    $log   = get_option( OFF10_LOG_KEY, [] );
    if ( ! is_array( $log ) ) $log = [];

    if ( $state['status'] === 'running' ) {
        wp_cache_delete( OFF10_LASTRUN_KEY, 'options' );
        $last_run = floatval( get_option( OFF10_LASTRUN_KEY, 0 ) );
        $now      = microtime( true );
        $delay    = floatval( $state['batch_delay'] ?? 3 );
        if ( $state['adaptive']['enabled'] ?? false ) {
            $delay = floatval( $state['adaptive']['current_delay'] ?? $delay );
        }

        if ( ( $now - $last_run ) >= $delay ) {
            if ( off10_is_strictly_running() ) {
                $lock_uuid = off10_acquire_lock();
                if ( $lock_uuid !== false ) {
                    if ( off10_is_strictly_running() ) {
                        update_option( OFF10_LASTRUN_KEY, $now, false );
                        off10_run_batch();
                    }
                    off10_release_lock( $lock_uuid );
                    $state = off10_get_state();
                    $log   = get_option( OFF10_LOG_KEY, [] );
                    if ( ! is_array( $log ) ) $log = [];
                }
            }
        }

        if ( off10_is_strictly_running() ) spawn_cron();
    }

    wp_send_json_success( off10_build_status_response( $state, $log ) );
}
add_action( 'wp_ajax_off10_status', 'off10_status_handler' );

function off10_status_readonly_handler() {
    check_ajax_referer( 'off10_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );
    $state = off10_get_state();
    $log   = get_option( OFF10_LOG_KEY, [] );
    if ( ! is_array( $log ) ) $log = [];
    wp_send_json_success( off10_build_status_response( $state, $log ) );
}
add_action( 'wp_ajax_off10_status_readonly', 'off10_status_readonly_handler' );

function off10_build_status_response( $state, $log ) {
    return [
        'status'                  => $state['status'],
        'mode'                    => $state['mode'],
        'page'                    => $state['page'],
        'current_processing_page' => $state['current_processing_page'] ?? $state['page'],
        'country'                 => $state['country'],
        'country_label'           => $state['country_label'] ?? '',
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
        'images_failed'           => $state['stats']['images_failed'] ?? 0,
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
   AJAX: off10_search
   ═══════════════════════════════════════════════════════════ */
function off10_search_handler() {
    check_ajax_referer( 'off10_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $query   = sanitize_text_field( $_POST['query'] ?? '' );
    $country = sanitize_key( $_POST['country'] ?? '' );
    if ( empty( $query ) ) wp_send_json_error( 'Üres keresés.' );

    $is_barcode = preg_match( '/^\d{8,14}$/', $query );

    if ( $is_barcode ) {
        $data = off10_api_get_product( $query );
        if ( $data === false || empty( $data['product'] ) ) {
            wp_send_json_success( [ 'foods' => [] ] );
            return;
        }
        $p = $data['product'];
        $n = $p['nutriments'] ?? [];
        $eid = off10_barcode_exists_single( $query );
        wp_send_json_success( [ 'foods' => [ [
            'barcode'          => $query,
            'name'             => $p['product_name'] ?? '',
            'name_hu'          => $p['product_name_hu'] ?? '',
            'brands'           => $p['brands'] ?? '',
            'kcal'             => round( $n['energy-kcal_100g'] ?? $n['energy-kcal'] ?? 0, 1 ),
            'protein'          => round( $n['proteins_100g'] ?? 0, 1 ),
            'carb'             => round( $n['carbohydrates_100g'] ?? 0, 1 ),
            'fat'              => round( $n['fat_100g'] ?? 0, 1 ),
            'image_url'        => $p['image_front_url'] ?? '',
            'already_imported' => ( $eid !== false ),
            'existing_id'      => $eid ?: 0,
        ] ] ] );
        return;
    }

    $params = [
        'action'       => 'process',
        'json'         => 'true',
        'search_terms' => $query,
        'page_size'    => 15,
        'page'         => 1,
        'fields'       => 'code,product_name,product_name_hu,nutriments,image_front_url,brands',
        'sort_by'      => 'unique_scans_n',
    ];
    if ( ! empty( $country ) ) {
        $params['tagtype_0']      = 'countries';
        $params['tag_contains_0'] = 'contains';
        $params['tag_0']          = $country;
    }

    $response = wp_remote_get( 'https://world.openfoodfacts.org/cgi/search.pl?' . http_build_query( $params ), [
        'timeout'    => 20,
        'user-agent' => 'TapanyagLexikon/2.0 (WordPress OFF Import; contact@tapanyaglexikon.hu)',
    ] );

    if ( is_wp_error( $response ) ) wp_send_json_error( 'API hiba: ' . $response->get_error_message() );
    if ( wp_remote_retrieve_response_code( $response ) !== 200 ) wp_send_json_error( 'HTTP ' . wp_remote_retrieve_response_code( $response ) );

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $data['products'] ) ) {
        wp_send_json_success( [ 'foods' => [] ] );
        return;
    }

    $barcodes = [];
    foreach ( $data['products'] as $p ) {
        $bc = trim( $p['code'] ?? '' );
        if ( ! empty( $bc ) ) $barcodes[] = $bc;
    }
    $existing_map = off10_barcode_to_post_id_map( $barcodes );

    $foods = [];
    foreach ( $data['products'] as $p ) {
        $bc = trim( $p['code'] ?? '' );
        $n  = $p['nutriments'] ?? [];
        $foods[] = [
            'barcode'          => $bc,
            'name'             => $p['product_name'] ?? '',
            'name_hu'          => $p['product_name_hu'] ?? '',
            'brands'           => $p['brands'] ?? '',
            'kcal'             => round( $n['energy-kcal_100g'] ?? $n['energy-kcal'] ?? 0, 1 ),
            'protein'          => round( $n['proteins_100g'] ?? 0, 1 ),
            'carb'             => round( $n['carbohydrates_100g'] ?? 0, 1 ),
            'fat'              => round( $n['fat_100g'] ?? 0, 1 ),
            'image_url'        => $p['image_front_url'] ?? '',
            'already_imported' => isset( $existing_map[ $bc ] ),
            'existing_id'      => intval( $existing_map[ $bc ] ?? 0 ),
        ];
    }
    wp_send_json_success( [ 'foods' => $foods ] );
}
add_action( 'wp_ajax_off10_search', 'off10_search_handler' );

/* ═══════════════════════════════════════════════════════════
   AJAX: off10_import_single
   ═══════════════════════════════════════════════════════════ */
function off10_import_single_handler() {
    check_ajax_referer( 'off10_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );
    if ( ! function_exists( 'update_field' ) ) wp_send_json_error( 'ACF nincs betöltve.' );

    $barcode = sanitize_text_field( $_POST['barcode'] ?? '' );
    if ( empty( $barcode ) ) wp_send_json_error( 'Nincs vonalkód.' );

    $exists = off10_barcode_exists_single( $barcode );
    if ( $exists !== false ) wp_send_json_error( 'Már importálva (#' . $exists . ')' );

    $data = off10_api_get_product( $barcode );
    if ( $data === false || empty( $data['product'] ) ) wp_send_json_error( 'Nem található az OFF-ban.' );

    $product    = $data['product'];
    $nutriments = $product['nutriments'] ?? [];
    $name       = trim( $product['product_name_hu'] ?? '' );
    $name_en    = trim( $product['product_name'] ?? '' );
    if ( empty( $name ) ) $name = $name_en;
    if ( empty( $name ) ) $name = 'OFF #' . $barcode;

    $post_id = wp_insert_post( [
        'post_type'   => 'alapanyag',
        'post_title'  => $name,
        'post_status' => 'draft',
        'meta_input'  => [
            'off_url'            => $product['url'] ?? '',
            'off_imported_at'    => current_time( 'Y-m-d H:i:s' ),
            'off_import_version' => OFF10_PLUGIN_VERSION,
        ],
    ], true );
    if ( is_wp_error( $post_id ) ) wp_send_json_error( 'WP: ' . $post_id->get_error_message() );

    $nf = off10_get_nutrient_fields();
    foreach ( $nf as $f ) {
        $ok = $f['off_key'] ?? '';
        $ak = $f['acf_key'] ?? '';
        if ( empty( $ok ) || empty( $ak ) ) continue;
        $v = $nutriments[ $ok ] ?? null;
        if ( $v !== null ) off10_save_acf_field( $ak, round( floatval( $v ), 4 ), $post_id );
    }

    off10_save_acf_field( 'off_barcode', $barcode, $post_id, true );
    off10_save_acf_field( 'elsodleges_forras', 'off', $post_id, true );
    off10_save_acf_field( 'eredeti_nev', $name_en ?: $name, $post_id, true );
    if ( ! empty( $name_en ) && $name_en !== $name ) off10_save_acf_field( 'off_name_en', $name_en, $post_id );
    if ( ! empty( $product['brands'] ) ) off10_save_acf_field( 'off_brands', sanitize_text_field( $product['brands'] ), $post_id );

    $img = $product['image_front_url'] ?? '';
    if ( ! empty( $img ) ) {
        if ( ! function_exists( 'media_sideload_image' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        $r = media_sideload_image( $img, $post_id, $name, 'id' );
        if ( ! is_wp_error( $r ) ) set_post_thumbnail( $post_id, $r );
    }

    wp_send_json_success( 'Importálva: ' . $name . ' (#' . $post_id . ')' );
}
add_action( 'wp_ajax_off10_import_single', 'off10_import_single_handler' );

/* ═══════════════════════════════════════════════════════════
   AJAX: off10_apply_diffs
   ═══════════════════════════════════════════════════════════ */
function off10_apply_diffs_handler() {
    check_ajax_referer( 'off10_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $diffs = json_decode( stripslashes( $_POST['diffs'] ?? '[]' ), true );
    if ( empty( $diffs ) || ! is_array( $diffs ) ) wp_send_json_error( 'Nincs eltérés.' );

    $ok = 0; $err = 0;
    foreach ( $diffs as $diff ) {
        $pid = intval( $diff['post_id'] ?? 0 );
        if ( $pid < 1 || empty( $diff['changes'] ) ) { $err++; continue; }
        foreach ( $diff['changes'] as $ch ) {
            $ak = $ch['acf_key'] ?? '';
            $nv = $ch['new_value'] ?? null;
            if ( ! empty( $ak ) && $nv !== null ) off10_save_acf_field( $ak, round( floatval( $nv ), 4 ), $pid );
        }
        update_post_meta( $pid, 'off_synced_at', current_time( 'Y-m-d H:i:s' ) );
        $ok++;
    }

    $state = off10_get_state();
    $state['diffs'] = [];
    off10_save_state( $state );
    wp_send_json_success( $ok . ' frissítve.' . ( $err > 0 ? ' (' . $err . ' hiba)' : '' ) );
}
add_action( 'wp_ajax_off10_apply_diffs', 'off10_apply_diffs_handler' );

/* ═══════════════════════════════════════════════════════════
   AJAX: off10_sync_one (publish box)
   ═══════════════════════════════════════════════════════════ */
function off10_sync_one_handler() {
    check_ajax_referer( 'off10_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $pid = intval( $_POST['post_id'] ?? 0 );
    if ( $pid < 1 ) wp_send_json_error( 'Érvénytelen ID.' );

    $barcode = function_exists( 'get_field' ) ? get_field( 'off_barcode', $pid ) : '';
    if ( empty( $barcode ) ) $barcode = get_post_meta( $pid, 'off_barcode', true );
    if ( empty( $barcode ) ) wp_send_json_error( 'Nincs vonalkód.' );

    $data = off10_api_get_product( $barcode );
    if ( $data === false || empty( $data['product'] ) ) wp_send_json_error( 'Nem található: ' . $barcode );

    $nutriments = $data['product']['nutriments'] ?? [];
    $changes    = [];
    $nf         = off10_get_nutrient_fields();

    if ( function_exists( 'get_field' ) ) {
        foreach ( $nf as $f ) {
            $ok = $f['off_key'] ?? '';
            $ak = $f['acf_key'] ?? '';
            $lb = $f['label'] ?? $ok;
            if ( empty( $ok ) || empty( $ak ) ) continue;
            $old = floatval( get_field( $ak, $pid ) );
            $new = isset( $nutriments[ $ok ] ) ? floatval( $nutriments[ $ok ] ) : null;
            if ( $new !== null && abs( $new - $old ) > 0.01 ) {
                off10_save_acf_field( $ak, $new, $pid );
                $changes[] = $lb . ': ' . round( $old, 2 ) . ' → ' . round( $new, 2 );
            }
        }
    }

    update_post_meta( $pid, 'off_synced_at', current_time( 'Y-m-d H:i:s' ) );
    wp_send_json_success( empty( $changes ) ? 'Nincs eltérés – naprakész.' : count( $changes ) . ' frissítve: ' . implode( ', ', $changes ) );
}
add_action( 'wp_ajax_off10_sync_one', 'off10_sync_one_handler' );

/* ═══════════════════════════════════════════════════════════
   WP-CRON (STRICT RUNNING + DOUBLE CHECK)
   ═══════════════════════════════════════════════════════════ */
function off10_cron_schedules( $schedules ) {
    $schedules['off10_30s'] = [ 'interval' => 30, 'display' => 'OFF10: 30s' ];
    return $schedules;
}
add_filter( 'cron_schedules', 'off10_cron_schedules' );

function off10_cron_handler() {
    if ( ! off10_is_strictly_running() ) {
        wp_clear_scheduled_hook( OFF10_CRON_HOOK );
        return;
    }

    $state = off10_get_state();
    wp_cache_delete( OFF10_LASTRUN_KEY, 'options' );
    $last_run = floatval( get_option( OFF10_LASTRUN_KEY, 0 ) );
    $delay    = floatval( $state['batch_delay'] ?? 3 );
    if ( $state['adaptive']['enabled'] ?? false ) {
        $delay = floatval( $state['adaptive']['current_delay'] ?? $delay );
    }
    if ( ( microtime( true ) - $last_run ) < $delay ) return;

    if ( ! off10_is_strictly_running() ) return;

    $lock = off10_acquire_lock();
    if ( $lock === false ) return;

    if ( ! off10_is_strictly_running() ) {
        off10_release_lock( $lock );
        wp_clear_scheduled_hook( OFF10_CRON_HOOK );
        return;
    }

    update_option( OFF10_LASTRUN_KEY, microtime( true ), false );
    off10_run_batch();
    off10_release_lock( $lock );
}
add_action( OFF10_CRON_HOOK, 'off10_cron_handler' );

function off10_ensure_cron() {
    if ( off10_is_strictly_running() && ! wp_next_scheduled( OFF10_CRON_HOOK ) ) {
        wp_schedule_event( time(), 'off10_30s', OFF10_CRON_HOOK );
    }
}
add_action( 'admin_init', 'off10_ensure_cron' );
add_action( 'wp_loaded', 'off10_ensure_cron' );

/* ═══════════════════════════════════════════════════════════
   DISABLE_WP_CRON BACKUP
   ═══════════════════════════════════════════════════════════ */
function off10_admin_init_batch_trigger() {
    if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) return;
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( ! off10_is_strictly_running() ) return;

    $state    = off10_get_state();
    $last_run = floatval( get_option( OFF10_LASTRUN_KEY, 0 ) );
    $delay    = floatval( $state['adaptive']['current_delay'] ?? $state['batch_delay'] ?? 3 );

    if ( ( microtime( true ) - $last_run ) >= $delay ) {
        if ( ! off10_is_strictly_running() ) return;
        $lock = off10_acquire_lock();
        if ( $lock !== false ) {
            if ( off10_is_strictly_running() ) {
                update_option( OFF10_LASTRUN_KEY, microtime( true ), false );
                off10_run_batch();
            }
            off10_release_lock( $lock );
        }
    }
}
add_action( 'admin_init', 'off10_admin_init_batch_trigger' );

/* ═══════════════════════════════════════════════════════════
   PUBLISH BOX SZINKRON GOMB
   ═══════════════════════════════════════════════════════════ */
function off10_publish_box_sync( $post ) {
    if ( $post->post_type !== 'alapanyag' ) return;
    $barcode = get_post_meta( $post->ID, 'off_barcode', true );
    if ( empty( $barcode ) ) return;
    $synced = get_post_meta( $post->ID, 'off_synced_at', true );
    $nonce  = wp_create_nonce( 'off10_nonce' );
    ?>
    <div class="misc-pub-section" style="border-top:1px solid #f0f0f1;padding-top:8px;">
        <span style="font-size:0.85rem;">🔄 OFF Szinkron</span>
        <span style="display:block;font-size:0.78rem;color:#999;">Vonalkód: <?php echo esc_html( $barcode ); ?><?php if ( $synced ) : ?><br>Utolsó: <?php echo esc_html( $synced ); ?><?php endif; ?></span>
        <button type="button" class="button" id="off10-sync-one-btn" style="margin-top:4px;font-size:12px;" data-post="<?php echo $post->ID; ?>" data-nonce="<?php echo $nonce; ?>">🔄 Szinkronizálás</button>
        <span id="off10-sync-one-status" style="font-size:0.78rem;display:block;margin-top:4px;"></span>
        <script>
        document.getElementById('off10-sync-one-btn').addEventListener('click',function(){
            var b=this,s=document.getElementById('off10-sync-one-status');b.disabled=true;b.textContent='⏳...';
            var fd=new FormData();fd.append('action','off10_sync_one');fd.append('nonce',b.dataset.nonce);fd.append('post_id',b.dataset.post);
            fetch(ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){b.disabled=false;b.textContent='🔄 Szinkronizálás';s.textContent=r.success?'✅ '+r.data:'❌ '+r.data;s.style.color=r.success?'#16a34a':'#dc2626';});
        });
        </script>
    </div>
    <?php
}
add_action( 'post_submitbox_misc_actions', 'off10_publish_box_sync' );

/* ═══════════════════════════════════════════════════════════
   STALE CHECK (adaptív timeout)
   ═══════════════════════════════════════════════════════════ */
function off10_stale_check() {
    $state = off10_get_state();
    if ( $state['status'] !== 'running' ) return;
    $delay         = floatval( $state['adaptive']['current_delay'] ?? $state['batch_delay'] ?? 3 );
    $stale_timeout = max( 600, $delay * 20 );
    $upd = floatval( $state['_updated'] ?? 0 );
    if ( $upd > 0 && ( microtime( true ) - $upd ) > $stale_timeout ) {
        off10_force_status( 'stopped' );
        off10_force_unlock();
        off10_log( 'warn', '⏹ Auto-stop: >' . round( $stale_timeout / 60 ) . ' perc inaktív.' );
    }
}
add_action( 'admin_init', 'off10_stale_check' );

/* ═══════════════════════════════════════════════════════════
   ADMIN BAR
   ═══════════════════════════════════════════════════════════ */
function off10_admin_bar_indicator( $wp_admin_bar ) {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $state = off10_get_state();
    if ( $state['status'] !== 'running' ) return;
    $wp_admin_bar->add_node( [
        'id'    => 'off10-running',
        'title' => '🍊 OFF (oldal ' . intval( $state['current_processing_page'] ?: $state['page'] ) . ' | +' . intval( $state['stats']['created'] ) . ')',
        'href'  => admin_url( 'edit.php?post_type=alapanyag&page=off-import' ),
        'meta'  => [ 'class' => 'off10-bar-run' ],
    ] );
}
add_action( 'admin_bar_menu', 'off10_admin_bar_indicator', 999 );

function off10_admin_bar_style() {
    $state = off10_get_state();
    if ( $state['status'] !== 'running' ) return;
    echo '<style>#wpadminbar .off10-bar-run>.ab-item{background:#f59e0b!important;color:#fff!important;}#wpadminbar .off10-bar-run:hover>.ab-item{background:#d97706!important;}</style>';
}
add_action( 'admin_head', 'off10_admin_bar_style' );

/* ═══════════════════════════════════════════════════════════
   V7/V8/V9 → V10 MIGRÁCIÓ
   ═══════════════════════════════════════════════════════════ */
function off10_migrate_old() {
    if ( get_option( 'off10_migrated', false ) ) return;

    $v9 = get_option( 'off9_import_state', [] );
    if ( empty( $v9 ) ) $v9 = get_option( OFF10_STATE_KEY, [] );
    $v8 = get_option( 'off8_import_state', [] );
    $v7 = get_option( 'off_import_state', [] );

    $source = ! empty( $v9 ) ? $v9 : ( ! empty( $v8 ) ? $v8 : $v7 );

    if ( ! empty( $source ) && empty( get_option( OFF10_STATE_KEY ) ) ) {
        $ns = off10_default_state();
        if ( isset( $source['page'] ) ) $ns['page'] = intval( $source['page'] );
        if ( isset( $source['country'] ) ) {
            $ns['country']       = $source['country'];
            $ns['country_label'] = $source['country_label'] ?? $source['country'];
        }
        if ( isset( $source['last_known_page'] ) ) $ns['last_known_page'] = intval( $source['last_known_page'] );
        foreach ( [ 'created', 'updated', 'skipped', 'errors', 'processed', 'smart_skipped', 'empty_batches', 'api_calls' ] as $k ) {
            if ( isset( $source['stats'][ $k ] ) ) $ns['stats'][ $k ] = intval( $source['stats'][ $k ] );
            elseif ( isset( $source[ $k ] ) ) $ns['stats'][ $k ] = intval( $source[ $k ] );
        }
        if ( isset( $source['adaptive'] ) ) $ns['adaptive'] = wp_parse_args( $source['adaptive'], $ns['adaptive'] );
        $ns['status'] = 'stopped';
        off10_save_state( $ns );
        off10_log( 'info', '🔀 Migráció → V10 (oldal ' . $ns['page'] . ')' );
    }

    foreach ( [
        'off9_import_state', 'off9_import_log', 'off9_last_run_time', 'off9_batch_lock', 'off9_rate_bucket',
        'off8_import_state', 'off8_import_log', 'off8_last_run_time', 'off8_batch_lock',
        'off_import_state', 'off_import_log', 'off_import_last_run',
        'off9_migrated_v8',
    ] as $k ) {
        delete_option( $k );
    }
    delete_transient( 'off9_batch_lock' );
    delete_transient( 'off8_batch_lock' );

    update_option( 'off10_migrated', true );
}
add_action( 'admin_init', 'off10_migrate_old' );

/* ═══════════════════════════════════════════════════════════
   RÉGI CRON TAKARÍTÁS
   ═══════════════════════════════════════════════════════════ */
function off10_cleanup_old_cron() {
    foreach ( [ 'off_import_cron', 'off_import_batch', 'off_cron_batch', 'off_import_cron_hook', 'off8_cron_batch', 'off9_cron_batch' ] as $h ) {
        if ( wp_next_scheduled( $h ) ) wp_clear_scheduled_hook( $h );
    }
}
add_action( 'init', 'off10_cleanup_old_cron' );

/* ═══════════════════════════════════════════════════════════
   REST API
   ═══════════════════════════════════════════════════════════ */
function off10_register_rest() {
    register_rest_route( 'off-import/v1', '/status', [
        'methods'             => 'GET',
        'callback'            => function () {
            $s = off10_get_state();
            return new WP_REST_Response( [
                'status'                  => $s['status'],
                'mode'                    => $s['mode'],
                'page'                    => $s['page'],
                'current_processing_page' => $s['current_processing_page'] ?? $s['page'],
                'country'                 => $s['country'],
                'stats'                   => $s['stats'],
                'total'                   => $s['total'],
                'adaptive'                => $s['adaptive'],
                'last_known_page'         => $s['last_known_page'] ?? 0,
                'plugin_version'          => OFF10_PLUGIN_VERSION,
            ], 200 );
        },
        'permission_callback' => function () { return current_user_can( 'manage_options' ); },
    ] );
}
add_action( 'rest_api_init', 'off10_register_rest' );
