<?php

/**
 * 22. – FORDÍTÓ MODUL – DeepL API (v1 FINAL)
 */
/**
 * 22. – FORDÍTÓ MODUL – DeepL API (v2 FINAL)
 *
 * Motor: HIBRID ADAPTÍV (böngésző polling + WP-Cron fallback)
 * Admin: Alapanyagok → 🌐 Fordító v2
 *
 * v2 FINAL FUNKCIÓK:
 *  1.  Multi-key pool + smart rotáció + admin UI kezelés
 *  2.  🔒 AES-256-CBC titkosítás (AUTH_KEY + AUTH_SALT)
 *  3.  Valós idejű API key validálás (DeepL /v2/usage)
 *  4.  Kulcsonkénti usage dashboard + progress bar
 *  5.  3 rotációs stratégia: balanced / fill-first / round-robin
 *  6.  Auto-váltás 456 (quota exceeded) esetén
 *  7.  Hibrid adaptív motor (polling + WP-Cron)
 *  8.  Stop-safe 5 rétegű state management
 *  9.  Mutex lock (transient UUID)
 *  10. Stale check (>10 perc → auto stop)
 *  11. Adaptív delay (2s–30s, DeepL válaszidő alapján)
 *  12. Éjféli WP-Cron – naponta 1× auto fordítás
 *  13. Admin bar indikátor futás közben
 *  14. Progress bar + ETA
 *  15. CSV export (log + eredmények)
 *  16. REST API (/fordito/v2/status + /keys)
 *  17. Email értesítés (wp_mail)
 *  18. Undo utolsó batch
 *  19. Fordítási minőség check
 *  20. Magyar pre-detect (lokális regex → API spórolás)
 *  21. Race condition védelem (post_modified check)
 *  22. Readonly status endpoint (stop után)
 *  23. UTF-8 safe nagybetűsítés
 *  24. V1 migráció + régi cron cleanup
 *
 * TITKOSÍTÁS:
 *  - AES-256-CBC + HMAC-SHA256 (encrypt-then-MAC)
 *  - Kulcs: hash('sha256', AUTH_KEY . AUTH_SALT)
 *  - IV: random 16 byte / titkosításonként
 *  - Tárolt formátum: base64( IV . ciphertext . HMAC )
 *  - DB-ben olvashatatlan – csak futásidőben dekódolható
 *  - Zero config – WordPress saját konstansait használja
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ══════════════════════════════════════════════════════════════
// KONSTANSOK
// ══════════════════════════════════════════════════════════════

define( 'FV2_STATE_KEY',         'fv2_state' );
define( 'FV2_LOG_KEY',           'fv2_log' );
define( 'FV2_LASTRUN_KEY',       'fv2_last_run' );
define( 'FV2_LOCK_KEY',          'fv2_lock' );
define( 'FV2_CRON_HOOK',         'fv2_cron_batch' );
define( 'FV2_NIGHTLY_HOOK',      'fv2_nightly' );
define( 'FV2_RATE_BUCKET_KEY',   'fv2_rate_bucket' );
define( 'FV2_KEYS_OPTION',       'fv2_api_keys_enc' );   // Titkosított!
define( 'FV2_KEY_USAGE_PREFIX',  'fv2_key_usage_' );
define( 'FV2_ACTIVE_KEY_OPTION', 'fv2_active_key_idx' );
define( 'FV2_ROTATION_OPTION',   'fv2_rotation_strategy' );
define( 'FV2_LAST_BATCH_KEY',    'fv2_last_batch' );
define( 'FV2_AUTO_LOG_KEY',      'fv2_auto_log' );
define( 'FV2_AUTO_COUNT_KEY',    'fv2_auto_count' );
define( 'FV2_NIGHTLY_ENABLED_KEY', 'fv2_nightly_enabled' );

define( 'FV2_LOG_MAX',           500 );
define( 'FV2_LOCK_TTL',          120 );
define( 'FV2_MAX_TEXTS',         50 );
define( 'FV2_MAX_RETRY',         4 );
define( 'FV2_API_TIMEOUT',       30 );
define( 'FV2_RETRY_BASE_DELAY',  3 );
define( 'FV2_STALE_SECONDS',     600 );

define( 'FV2_ADAPTIVE_MIN_DELAY',     2.0 );
define( 'FV2_ADAPTIVE_MAX_DELAY',     30.0 );
define( 'FV2_ADAPTIVE_SWEET_SPOT',    2.0 );
define( 'FV2_ADAPTIVE_TIMEOUT_CEIL',  8.0 );
define( 'FV2_ADAPTIVE_CONSEC_MAX',    3 );

define( 'FV2_CIPHER_METHOD', 'aes-256-cbc' );


// ══════════════════════════════════════════════════════════════
// 🔒 AES-256-CBC TITKOSÍTÁSI RÉTEG
// ══════════════════════════════════════════════════════════════

/**
 * Titkosítási kulcs deriválás a WordPress saját konstansaiból.
 * AUTH_KEY + AUTH_SALT → SHA-256 hash = 32 byte = AES-256 kulcs
 *
 * Előnyök:
 *  - Nincs extra config – AUTH_KEY/AUTH_SALT már wp-config.php-ban van
 *  - Oldalanként egyedi (minden WP installnak más AUTH_KEY-je van)
 *  - Ha a DB leakel, a kulcsok olvashatatlanok
 *
 * HMAC kulcs: külön deriváció (AUTH_KEY . 'hmac' . AUTH_SALT)
 */
function fv2_get_encryption_key() {
    if ( ! defined( 'AUTH_KEY' ) || ! defined( 'AUTH_SALT' ) ) {
        // Fallback ha nincs beállítva (nem kellene előfordulnia)
        return hash( 'sha256', 'fv2-fallback-' . DB_NAME . '-' . ABSPATH, true );
    }
    return hash( 'sha256', AUTH_KEY . AUTH_SALT, true ); // 32 byte raw
}

function fv2_get_hmac_key() {
    if ( ! defined( 'AUTH_KEY' ) || ! defined( 'AUTH_SALT' ) ) {
        return hash( 'sha256', 'fv2-hmac-fallback-' . DB_NAME . '-' . ABSPATH, true );
    }
    return hash( 'sha256', AUTH_KEY . 'hmac' . AUTH_SALT, true );
}

/**
 * Titkosítás: AES-256-CBC + HMAC-SHA256 (Encrypt-then-MAC)
 *
 * Tárolt formátum: base64( IV[16] . ciphertext . HMAC[32] )
 *
 * @param string $plaintext A titkosítandó szöveg
 * @return string|false Base64 kódolt titkosított string
 */
function fv2_encrypt( $plaintext ) {
    $key    = fv2_get_encryption_key();
    $iv_len = openssl_cipher_iv_length( FV2_CIPHER_METHOD );
    $iv     = openssl_random_pseudo_bytes( $iv_len );

    $ciphertext = openssl_encrypt( $plaintext, FV2_CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv );
    if ( $ciphertext === false ) return false;

    // HMAC a teljes IV+ciphertext-re (Encrypt-then-MAC)
    $hmac = hash_hmac( 'sha256', $iv . $ciphertext, fv2_get_hmac_key(), true );

    return base64_encode( $iv . $ciphertext . $hmac );
}

/**
 * Visszafejtés: HMAC ellenőrzés → AES-256-CBC dekódolás
 *
 * @param string $encoded Base64 kódolt titkosított string
 * @return string|false Visszafejtett szöveg, vagy false ha hibás
 */
function fv2_decrypt( $encoded ) {
    $raw = base64_decode( $encoded, true );
    if ( $raw === false ) return false;

    $iv_len  = openssl_cipher_iv_length( FV2_CIPHER_METHOD );
    $hmac_len = 32; // SHA-256 = 32 byte

    // Minimum hossz: IV + legalább 1 byte cipher + HMAC
    if ( strlen( $raw ) < $iv_len + 1 + $hmac_len ) return false;

    $iv         = substr( $raw, 0, $iv_len );
    $hmac       = substr( $raw, -$hmac_len );
    $ciphertext = substr( $raw, $iv_len, -$hmac_len );

    // HMAC ellenőrzés (timing-safe)
    $expected_hmac = hash_hmac( 'sha256', $iv . $ciphertext, fv2_get_hmac_key(), true );
    if ( ! hash_equals( $expected_hmac, $hmac ) ) return false; // Tampered!

    $plaintext = openssl_decrypt( $ciphertext, FV2_CIPHER_METHOD, fv2_get_encryption_key(), OPENSSL_RAW_DATA, $iv );
    return ( $plaintext !== false ) ? $plaintext : false;
}

/**
 * Egy API kulcs titkosítása (tároláshoz)
 */
function fv2_encrypt_api_key( $api_key ) {
    return fv2_encrypt( $api_key );
}

/**
 * Egy API kulcs visszafejtése (használathoz)
 */
function fv2_decrypt_api_key( $encrypted ) {
    return fv2_decrypt( $encrypted );
}

/**
 * API kulcs maszkolása megjelenítéshez
 * Eredmény: "...xxxxxxxx" (utolsó 8 karakter)
 */
function fv2_mask_key( $api_key ) {
    if ( mb_strlen( $api_key ) <= 8 ) return '********';
    return '...' . substr( $api_key, -8 );
}


// ══════════════════════════════════════════════════════════════
// MULTI-KEY POOL MANAGER (🔒 TITKOSÍTOTT)
// ══════════════════════��═══════════════════════════════════════

/**
 * Kulcs struktúra (tárolt – DB-ben):
 * [
 *   'key_enc'  => 'base64(AES256(api_key))',   // titkosítva!
 *   'key_mask' => '...abcd1234',                // megjelenítéshez
 *   'label'    => 'Free #1',
 *   'type'     => 'free',
 *   'added_at' => '2026-02-27 10:00:00',
 *   'enabled'  => true,
 * ]
 *
 * FONTOS: A 'key' mező SOHA nem tárolódik plain text-ben!
 */

function fv2_get_keys() {
    return get_option( FV2_KEYS_OPTION, [] );
}

function fv2_save_keys( $keys ) {
    update_option( FV2_KEYS_OPTION, $keys, false );
}

/**
 * Visszaadja a tárolt kulcs DECRYPTED API key-jét
 */
function fv2_get_decrypted_key( $idx ) {
    $keys = fv2_get_keys();
    if ( ! isset( $keys[ $idx ] ) ) return false;
    $k = $keys[ $idx ];

    // V2 formátum: titkosított
    if ( ! empty( $k['key_enc'] ) ) {
        $decrypted = fv2_decrypt_api_key( $k['key_enc'] );
        if ( $decrypted !== false ) return $decrypted;
        // Ha dekódolás sikertelen (AUTH_KEY változott?) → jelezzük
        return false;
    }

    // V1 fallback: plain text (migrálás előtt)
    if ( ! empty( $k['key'] ) ) return $k['key'];

    return false;
}

function fv2_get_key_usage( $idx ) {
    wp_cache_delete( FV2_KEY_USAGE_PREFIX . $idx, 'options' );
    return get_option( FV2_KEY_USAGE_PREFIX . $idx, [
        'used'       => 0,
        'limit'      => 500000,
        'updated'    => '',
        'status'     => 'unknown',
        'last_error' => '',
        'calls'      => 0,
    ] );
}

function fv2_save_key_usage( $idx, $usage ) {
    update_option( FV2_KEY_USAGE_PREFIX . $idx, $usage, false );
}

function fv2_get_rotation() {
    return get_option( FV2_ROTATION_OPTION, 'balanced' );
}

function fv2_get_active_idx() {
    return intval( get_option( FV2_ACTIVE_KEY_OPTION, 0 ) );
}

function fv2_set_active_idx( $idx ) {
    update_option( FV2_ACTIVE_KEY_OPTION, $idx, false );
}

/**
 * DeepL /v2/usage hívás EGY kulcsra → valós usage + validálás
 * Elfogad plain text API kulcsot (memóriában)
 */
function fv2_validate_key( $api_key ) {
    $is_free = str_ends_with( $api_key, ':fx' );
    $base    = $is_free ? 'https://api-free.deepl.com/v2' : 'https://api.deepl.com/v2';

    $response = wp_remote_get( $base . '/usage', [
        'timeout' => 10,
        'headers' => [ 'Authorization' => 'DeepL-Auth-Key ' . $api_key ],
    ] );

    if ( is_wp_error( $response ) ) {
        return [ 'valid' => false, 'error' => $response->get_error_message() ];
    }

    $code = wp_remote_retrieve_response_code( $response );

    if ( $code === 403 ) {
        return [ 'valid' => false, 'error' => 'Érvénytelen API kulcs (403 Forbidden)' ];
    }

    if ( $code !== 200 ) {
        return [ 'valid' => false, 'error' => 'HTTP ' . $code ];
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( ! $body || ! isset( $body['character_count'] ) ) {
        return [ 'valid' => false, 'error' => 'Hibás válasz' ];
    }

    return [
        'valid' => true,
        'type'  => $is_free ? 'free' : 'pro',
        'used'  => intval( $body['character_count'] ),
        'limit' => intval( $body['character_limit'] ),
    ];
}

/**
 * Összes kulcs usage frissítése (DeepL API-ból)
 */
function fv2_refresh_all_key_usage() {
    $keys = fv2_get_keys();
    foreach ( $keys as $idx => $k ) {
        if ( empty( $k['enabled'] ) ) continue;
        $api_key = fv2_get_decrypted_key( $idx );
        if ( $api_key === false ) {
            $u = fv2_get_key_usage( $idx );
            $u['status']     = 'error';
            $u['last_error'] = 'Dekódolási hiba – AUTH_KEY változott?';
            $u['updated']    = current_time( 'Y-m-d H:i:s' );
            fv2_save_key_usage( $idx, $u );
            continue;
        }
        $result = fv2_validate_key( $api_key );
        $usage  = fv2_get_key_usage( $idx );
        if ( $result['valid'] ) {
            $usage['used']       = $result['used'];
            $usage['limit']      = $result['limit'];
            $usage['status']     = ( $result['used'] >= $result['limit'] ) ? 'exhausted' : 'ok';
            $usage['updated']    = current_time( 'Y-m-d H:i:s' );
            $usage['last_error'] = '';
        } else {
            $usage['status']     = 'error';
            $usage['last_error'] = $result['error'];
            $usage['updated']    = current_time( 'Y-m-d H:i:s' );
        }
        fv2_save_key_usage( $idx, $usage );
    }
}

/**
 * Smart rotáció – következő kulcs kiválasztása
 */
function fv2_select_best_key( $force_rotate = false ) {
    $keys     = fv2_get_keys();
    $strategy = fv2_get_rotation();
    $current  = fv2_get_active_idx();

    if ( empty( $keys ) ) return false;

    if ( ! $force_rotate && isset( $keys[ $current ] ) && ! empty( $keys[ $current ]['enabled'] ) ) {
        $usage = fv2_get_key_usage( $current );
        if ( $usage['status'] === 'ok' || $usage['status'] === 'unknown' ) {
            if ( $usage['limit'] > 0 && $usage['used'] < $usage['limit'] * 0.95 ) {
                // Ellenőrizzük hogy a kulcs dekódolható
                if ( fv2_get_decrypted_key( $current ) !== false ) {
                    return $current;
                }
            }
        }
    }

    $available = [];
    foreach ( $keys as $idx => $k ) {
        if ( empty( $k['enabled'] ) ) continue;
        if ( fv2_get_decrypted_key( $idx ) === false ) continue; // Nem dekódolható
        $u = fv2_get_key_usage( $idx );
        if ( $u['status'] === 'exhausted' || $u['status'] === 'error' ) continue;
        $remain = max( 0, ( $u['limit'] ?: 500000 ) - ( $u['used'] ?: 0 ) );
        $available[ $idx ] = [
            'remain' => $remain,
            'pct'    => $u['limit'] > 0 ? ( $u['used'] / $u['limit'] ) : 0,
            'calls'  => $u['calls'] ?? 0,
        ];
    }

    if ( empty( $available ) ) return false;

    switch ( $strategy ) {
        case 'fill':
            foreach ( $available as $idx => $info ) {
                fv2_set_active_idx( $idx );
                return $idx;
            }
            break;

        case 'robin':
            $indices = array_keys( $available );
            $pos     = array_search( $current, $indices );
            $next    = ( $pos !== false && $pos + 1 < count( $indices ) ) ? $indices[ $pos + 1 ] : $indices[0];
            fv2_set_active_idx( $next );
            return $next;

        case 'balanced':
        default:
            uasort( $available, function( $a, $b ) { return $b['remain'] - $a['remain']; } );
            $best = array_key_first( $available );
            fv2_set_active_idx( $best );
            return $best;
    }

    return false;
}

/**
 * Aktív kulcs adatai lekérése (decrypted API key + metadata)
 */
function fv2_get_active_key() {
    $idx = fv2_select_best_key();
    if ( $idx === false ) return false;

    $keys = fv2_get_keys();
    if ( ! isset( $keys[ $idx ] ) ) return false;

    $k       = $keys[ $idx ];
    $api_key = fv2_get_decrypted_key( $idx );
    if ( $api_key === false ) return false;

    $is_free = str_ends_with( $api_key, ':fx' );

    return [
        'idx'     => $idx,
        'key'     => $api_key,  // Plain text – CSAK memóriában!
        'label'   => $k['label'],
        'type'    => $is_free ? 'free' : 'pro',
        'api_url' => $is_free ? 'https://api-free.deepl.com/v2' : 'https://api.deepl.com/v2',
    ];
}

/**
 * Kulcs usage növelése batch után
 */
function fv2_increment_key_usage( $idx, $chars ) {
    $usage = fv2_get_key_usage( $idx );
    $usage['used']  = ( $usage['used'] ?? 0 ) + $chars;
    $usage['calls'] = ( $usage['calls'] ?? 0 ) + 1;
    if ( $usage['limit'] > 0 && $usage['used'] >= $usage['limit'] ) {
        $usage['status'] = 'exhausted';
    }
    $usage['updated'] = current_time( 'Y-m-d H:i:s' );
    fv2_save_key_usage( $idx, $usage );
}

/**
 * Kulcs kimerülés jelzése (456 válasz után)
 */
function fv2_mark_key_exhausted( $idx ) {
    $usage = fv2_get_key_usage( $idx );
    $usage['status']     = 'exhausted';
    $usage['last_error'] = '456 Quota exceeded';
    $usage['updated']    = current_time( 'Y-m-d H:i:s' );
    fv2_save_key_usage( $idx, $usage );
    fv2_log( 'warn', '🔑 Kulcs #' . $idx . ' kimerült (456). Rotálás...' );
}

/**
 * Összesített statisztikák
 */
function fv2_get_pool_summary() {
    $keys        = fv2_get_keys();
    $total_used  = 0;
    $total_limit = 0;
    $active      = 0;
    $exhausted   = 0;
    $error       = 0;
    $decrypt_err = 0;

    foreach ( $keys as $idx => $k ) {
        if ( empty( $k['enabled'] ) ) continue;
        if ( fv2_get_decrypted_key( $idx ) === false ) { $decrypt_err++; continue; }
        $u = fv2_get_key_usage( $idx );
        $total_used  += $u['used'] ?? 0;
        $total_limit += $u['limit'] ?? 500000;
        if ( in_array( $u['status'] ?? 'unknown', [ 'ok', 'unknown' ], true ) ) $active++;
        elseif ( ( $u['status'] ?? '' ) === 'exhausted' ) $exhausted++;
        else $error++;
    }

    $total_remain = max( 0, $total_limit - $total_used );

    return [
        'key_count'    => count( $keys ),
        'active'       => $active,
        'exhausted'    => $exhausted,
        'error'        => $error,
        'decrypt_err'  => $decrypt_err,
        'total_used'   => $total_used,
        'total_limit'  => $total_limit,
        'total_remain' => $total_remain,
        'est_names'    => $total_remain > 0 ? round( $total_remain / 28 ) : 0,
    ];
}


// ══════════════════════════════════════════════════════════════
// MUTEX LOCK
// ══════════════════════════════════════════════════════════════

function fv2_acquire_lock() {
    $existing = get_transient( FV2_LOCK_KEY );
    if ( $existing !== false ) return false;
    $uuid = wp_generate_uuid4();
    set_transient( FV2_LOCK_KEY, $uuid, FV2_LOCK_TTL );
    usleep( 10000 );
    $check = get_transient( FV2_LOCK_KEY );
    return ( $check === $uuid ) ? $uuid : false;
}

function fv2_release_lock( $uuid ) {
    if ( get_transient( FV2_LOCK_KEY ) === $uuid ) { delete_transient( FV2_LOCK_KEY ); return true; }
    return false;
}

function fv2_force_unlock() { delete_transient( FV2_LOCK_KEY ); }


// ══════════════════════════════════════════════════════════════
// RATE LIMITER
// ══════════════════════════════════════════════════════════════

function fv2_rate_consume( $chars = 1 ) {
    wp_cache_delete( FV2_RATE_BUCKET_KEY, 'options' );
    $bucket = get_option( FV2_RATE_BUCKET_KEY, [] );
    $now    = microtime( true );
    $limit  = 80000;
    if ( empty( $bucket ) || ! isset( $bucket['tokens'] ) ) {
        $bucket = [ 'tokens' => $limit - $chars, 'last_time' => $now ];
        update_option( FV2_RATE_BUCKET_KEY, $bucket, false );
        return true;
    }
    $elapsed    = $now - $bucket['last_time'];
    $new_tokens = min( $limit, $bucket['tokens'] + $elapsed * ( $limit / 60.0 ) );
    if ( $new_tokens < $chars ) return false;
    $bucket['tokens']    = $new_tokens - $chars;
    $bucket['last_time'] = $now;
    update_option( FV2_RATE_BUCKET_KEY, $bucket, false );
    return true;
}


// ══════════════════════════════════════════════════════════════
// STATE MANAGEMENT (STOP-SAFE)
// ══════════════════════════════════════════════════════════════

function fv2_default_state() {
    return [
        'status'       => 'idle',
        'mode'         => 'translate',
        'offset'       => 0,
        'filter'       => 'all',
        'filter_label' => '📋 Összes',
        'batch_size'   => 50,
        'batch_delay'  => 2,
        'max_items'    => 500,
        'batch_num'    => 0,
        'started_at'   => '',
        'is_nightly'   => false,
        'active_key_idx' => 0,
        'stats' => [
            'translated'  => 0,
            'skipped'     => 0,
            'errors'      => 0,
            'flagged'     => 0,
            'chars_used'  => 0,
            'api_calls'   => 0,
            'keys_rotated'=> 0,
            'magyar_pre'  => 0,
        ],
        'date'     => '',
        '_updated' => microtime( true ),
        'adaptive' => [
            'enabled'              => true,
            'current_delay'        => 2.0,
            'avg_response_time'    => 0,
            'response_times'       => [],
            'consecutive_timeouts' => 0,
            'consecutive_fast'     => 0,
            'last_adjustment'      => '',
            'cooldown_until'       => 0,
        ],
    ];
}

function fv2_get_state() {
    wp_cache_delete( FV2_STATE_KEY, 'options' );
    $state = get_option( FV2_STATE_KEY, [] );
    if ( empty( $state ) ) return fv2_default_state();
    $def   = fv2_default_state();
    $state = wp_parse_args( $state, $def );
    $state['stats']    = wp_parse_args( $state['stats'] ?? [], $def['stats'] );
    $state['adaptive'] = wp_parse_args( $state['adaptive'] ?? [], $def['adaptive'] );
    return $state;
}

function fv2_save_state( $state ) {
    wp_cache_delete( FV2_STATE_KEY, 'options' );
    $db = get_option( FV2_STATE_KEY, [] );
    $ds = $db['status'] ?? 'idle';
    if ( in_array( $ds, [ 'stopping', 'stopped' ], true ) && $state['status'] === 'running' ) {
        $state['status'] = $ds;
    }
    $state['_updated'] = microtime( true );
    update_option( FV2_STATE_KEY, $state, false );
    wp_cache_delete( FV2_STATE_KEY, 'options' );
}

function fv2_force_status( $new_status ) {
    wp_cache_delete( FV2_STATE_KEY, 'options' );
    $state = get_option( FV2_STATE_KEY, fv2_default_state() );
    $state['status']   = $new_status;
    $state['_updated'] = microtime( true );
    update_option( FV2_STATE_KEY, $state, false );
    wp_cache_delete( FV2_STATE_KEY, 'options' );
    if ( in_array( $new_status, [ 'stopping', 'stopped', 'done', 'error', 'idle' ], true ) ) {
        wp_clear_scheduled_hook( FV2_CRON_HOOK );
    }
}

function fv2_should_stop() {
    wp_cache_delete( FV2_STATE_KEY, 'options' );
    $s = get_option( FV2_STATE_KEY, [] );
    return in_array( $s['status'] ?? 'idle', [ 'stopping', 'stopped', 'idle' ], true );
}

function fv2_is_strictly_running() {
    wp_cache_delete( FV2_STATE_KEY, 'options' );
    return ( get_option( FV2_STATE_KEY, [] )['status'] ?? 'idle' ) === 'running';
}


// ══════════════════════════════════════════════════════════════
// LOG
// ══════════════════════════════════════════════════════════════

function fv2_log( $type, $msg ) {
    $log   = get_option( FV2_LOG_KEY, [] );
    $log[] = [ 'type' => $type, 'msg' => $msg, 'time' => current_time( 'H:i:s' ) ];
    if ( count( $log ) > FV2_LOG_MAX ) $log = array_slice( $log, -FV2_LOG_MAX );
    update_option( FV2_LOG_KEY, $log, false );
}

function fv2_auto_log( $msg ) {
    $log   = get_option( FV2_AUTO_LOG_KEY, [] );
    $log[] = [ 'time' => current_time( 'Y-m-d H:i:s' ), 'msg' => $msg ];
    if ( count( $log ) > FV2_LOG_MAX ) $log = array_slice( $log, -FV2_LOG_MAX );
    update_option( FV2_AUTO_LOG_KEY, $log, false );
}


// ══════════════════════════════════════════════════════════════
// ADAPTÍV SZABÁLYOZÓ
// ══════════════════════════════════════════════════════════════

function fv2_adaptive_adjust( &$state, $response_time, $was_error = false ) {
    $a = &$state['adaptive'];
    if ( ! $a['enabled'] ) return;

    $a['response_times'][] = $response_time;
    if ( count( $a['response_times'] ) > 10 ) $a['response_times'] = array_slice( $a['response_times'], -10 );
    $a['avg_response_time'] = round( array_sum( $a['response_times'] ) / count( $a['response_times'] ), 2 );

    if ( $was_error ) {
        $a['consecutive_timeouts']++;
        $a['consecutive_fast'] = 0;
        if ( $a['consecutive_timeouts'] >= FV2_ADAPTIVE_CONSEC_MAX ) {
            $a['cooldown_until']  = microtime( true ) + 60;
            $a['current_delay']   = min( FV2_ADAPTIVE_MAX_DELAY, $a['current_delay'] * 3 );
            $a['last_adjustment'] = '🛑 Cooldown 60s';
            fv2_log( 'warn', $a['last_adjustment'] );
            return;
        }
        $a['current_delay']   = min( FV2_ADAPTIVE_MAX_DELAY, $a['current_delay'] * 2 );
        $a['last_adjustment'] = '🔻 delay=' . $a['current_delay'] . 's';
        return;
    }

    $a['consecutive_timeouts'] = 0;
    $avg = $a['avg_response_time'];

    if ( $avg < FV2_ADAPTIVE_SWEET_SPOT ) {
        $a['consecutive_fast']++;
        if ( $a['consecutive_fast'] >= 3 ) {
            $nd = max( FV2_ADAPTIVE_MIN_DELAY, round( $a['current_delay'] * 0.8, 1 ) );
            if ( $nd !== $a['current_delay'] ) {
                $a['current_delay']   = $nd;
                $a['last_adjustment'] = '🔺 delay=' . $nd . 's (avg:' . $avg . 's)';
            }
            $a['consecutive_fast'] = 0;
        }
    } elseif ( $avg > FV2_ADAPTIVE_TIMEOUT_CEIL ) {
        $a['consecutive_fast'] = 0;
        $a['current_delay']    = min( FV2_ADAPTIVE_MAX_DELAY, round( $a['current_delay'] * 1.5, 1 ) );
        $a['last_adjustment']  = '🔻 delay=' . $a['current_delay'] . 's (avg:' . $avg . 's)';
    } else {
        $a['consecutive_fast'] = 0;
    }
}

function fv2_adaptive_in_cooldown( $state ) {
    $cd = $state['adaptive']['cooldown_until'] ?? 0;
    return ( $cd > 0 && microtime( true ) < $cd );
}


// ══════════════════════════════════════════════════════════════
// MAGYAR PRE-DETECT
// ══════════════════════════════════════════════════════════════

function fv2_is_likely_hungarian( $text ) {
    $hungarian_chars = '/[áéíóöőúüű]/iu';
    $match_count     = preg_match_all( $hungarian_chars, $text );

    if ( $match_count > 0 ) {
        $ratio = $match_count / mb_strlen( $text, 'UTF-8' );
        if ( $ratio > 0.08 ) return true;
    }

    $hungarian_words = [
        'csirkemell', 'marhahús', 'sertés', 'bárány', 'hal', 'tojás',
        'tej', 'sajt', 'túró', 'vaj', 'kenyér', 'liszt', 'cukor',
        'alma', 'körte', 'szilva', 'barack', 'szőlő', 'dinnye',
        'burgonya', 'paradicsom', 'paprika', 'hagyma', 'fokhagyma',
        'főtt', 'nyers', 'sült', 'párolt', 'fagyasztott',
        'zsíros', 'sovány', 'félzsíros', 'teljes',
    ];

    $lower = mb_strtolower( $text, 'UTF-8' );
    foreach ( $hungarian_words as $hw ) {
        if ( mb_strpos( $lower, $hw ) !== false ) return true;
    }

    return false;
}


// ═══════════════════════════════════════���══════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════

function fv2_mb_ucfirst( $s ) {
    $s = trim( $s );
    if ( mb_strlen( $s, 'UTF-8' ) === 0 ) return $s;
    return mb_strtoupper( mb_substr( $s, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $s, 1, null, 'UTF-8' );
}

function fv2_check_quality( $original, $translated ) {
    $flags = [];
    if ( empty( trim( $translated ) ) ) $flags[] = 'üres';
    $tl = mb_strlen( trim( $translated ), 'UTF-8' );
    $ol = mb_strlen( trim( $original ), 'UTF-8' );
    if ( $tl > 0 && $tl < 2 ) $flags[] = 'túl rövid';
    if ( $ol > 0 && $tl > 0 && $tl / $ol > 3.0 ) $flags[] = 'túl hosszú';
    if ( mb_strtolower( trim( $original ), 'UTF-8' ) === mb_strtolower( trim( $translated ), 'UTF-8' ) ) $flags[] = 'változatlan';
    return $flags;
}


// ══════════════════════════════════════════════════════════════
// DeepL API: BATCH FORDÍTÁS (multi-key + auto-rotate)
// ══════════════════════════════════════════════════════════════

function fv2_deepl_translate_batch( $texts, &$state = null ) {
    if ( empty( $texts ) ) return false;

    $active = fv2_get_active_key();
    if ( ! $active ) {
        fv2_log( 'error', '❌ Nincs elérhető API kulcs!' );
        return false;
    }

    $chars_used = 0;
    foreach ( $texts as $t ) $chars_used += mb_strlen( $t, 'UTF-8' );

    $body_parts = [];
    foreach ( $texts as $text ) $body_parts[] = 'text=' . urlencode( $text );
    $body_parts[]  = 'target_lang=HU';
    $body_string   = implode( '&', $body_parts );
    $retry_delay   = FV2_RETRY_BASE_DELAY;

    for ( $attempt = 1; $attempt <= FV2_MAX_RETRY; $attempt++ ) {
        if ( $attempt > 1 && fv2_should_stop() ) return false;

        $active = fv2_get_active_key();
        if ( ! $active ) {
            fv2_log( 'error', '❌ Nincs elérhető kulcs (retry #' . $attempt . ')' );
            return false;
        }

        $t_start  = microtime( true );
        $response = wp_remote_post( $active['api_url'] . '/translate', [
            'timeout' => FV2_API_TIMEOUT,
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key ' . $active['key'],
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body' => $body_string,
        ] );
        $t_elapsed = round( microtime( true ) - $t_start, 2 );

        if ( is_wp_error( $response ) ) {
            if ( $state ) { fv2_adaptive_adjust( $state, $t_elapsed, true ); fv2_save_state( $state ); }
            fv2_log( 'warn', '⚠️ DeepL hiba #' . $attempt . ' [' . $active['label'] . '] (' . $t_elapsed . 's): ' . $response->get_error_message() );
            if ( $attempt < FV2_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 30, $retry_delay * 2 ); }
            continue;
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( $code === 456 ) {
            fv2_mark_key_exhausted( $active['idx'] );
            $new_idx = fv2_select_best_key( true );
            if ( $new_idx === false ) {
                fv2_log( 'error', '🚫 MINDEN kulcs kimerült!' );
                return false;
            }
            if ( $state ) $state['stats']['keys_rotated']++;
            $keys = fv2_get_keys();
            fv2_log( 'info', '🔄 Rotáció → ' . ( $keys[ $new_idx ]['label'] ?? '#' . $new_idx ) );
            if ( $attempt < FV2_MAX_RETRY ) continue;
            return false;
        }

        if ( $code === 429 ) {
            if ( $state ) { fv2_adaptive_adjust( $state, $t_elapsed, true ); fv2_save_state( $state ); }
            fv2_log( 'warn', '🚦 429 [' . $active['label'] . '] retry ' . $attempt );
            if ( $attempt < FV2_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 30, $retry_delay * 2 ); }
            continue;
        }

        if ( $code === 403 ) {
            fv2_log( 'error', '🔒 403 – Érvénytelen kulcs: ' . $active['label'] );
            $u = fv2_get_key_usage( $active['idx'] );
            $u['status'] = 'error'; $u['last_error'] = '403 Forbidden';
            fv2_save_key_usage( $active['idx'], $u );
            fv2_select_best_key( true );
            if ( $attempt < FV2_MAX_RETRY ) continue;
            return false;
        }

        if ( $code !== 200 ) {
            fv2_log( 'warn', '⚠️ HTTP ' . $code . ' [' . $active['label'] . '] #' . $attempt );
            if ( $attempt < FV2_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 30, $retry_delay * 2 ); }
            continue;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $body && isset( $body['translations'] ) ) {
            if ( $state ) fv2_adaptive_adjust( $state, $t_elapsed, false );
            fv2_increment_key_usage( $active['idx'], $chars_used );
            return [
                'translations' => $body['translations'],
                'chars_used'   => $chars_used,
                'key_idx'      => $active['idx'],
                'key_label'    => $active['label'],
            ];
        }

        fv2_log( 'warn', '⚠️ Hibás JSON #' . $attempt );
        if ( $attempt < FV2_MAX_RETRY ) { sleep( $retry_delay ); $retry_delay = min( 30, $retry_delay * 2 ); }
    }

    return false;
}


// ═════════���════════════════════════════════════════════════════
// BATCH ROUTER + FORDÍTÁS
// ══════════════════════════════════════════════════════════════

function fv2_run_batch() {
    if ( fv2_should_stop() ) return;
    $state = fv2_get_state();
    if ( $state['status'] !== 'running' ) return;

    if ( $state['max_items'] > 0 && $state['stats']['translated'] >= $state['max_items'] ) {
        $state['status'] = 'done'; fv2_save_state( $state );
        fv2_log( 'success', '🎉 Maximum: ' . $state['stats']['translated'] );
        wp_clear_scheduled_hook( FV2_CRON_HOOK );
        fv2_finish_notify( $state );
        return;
    }

    if ( fv2_adaptive_in_cooldown( $state ) ) { fv2_save_state( $state ); return; }

    fv2_run_translate_batch( $state );
}

function fv2_run_translate_batch( &$state ) {
    if ( ! function_exists( 'update_field' ) ) {
        $state['status'] = 'error'; fv2_save_state( $state );
        wp_clear_scheduled_hook( FV2_CRON_HOOK );
        fv2_log( 'error', '❌ ACF nincs betöltve.' );
        return;
    }

    if ( fv2_should_stop() ) return;

    $batch_size = intval( $state['batch_size'] ?? 50 );
    $offset     = intval( $state['offset'] ?? 0 );
    $filter     = $state['filter'] ?? 'all';
    $mode       = $state['mode'] ?? 'translate';

    $meta_query = [];
    if ( $mode === 'retranslate' ) {
        $meta_query[] = [ 'key' => 'eredeti_nev', 'compare' => 'EXISTS' ];
        $meta_query[] = [ 'key' => 'eredeti_nev', 'value' => '', 'compare' => '!=' ];
    } else {
        $meta_query['relation'] = 'OR';
        $meta_query[] = [ 'key' => 'forditas_forras', 'compare' => 'NOT EXISTS' ];
        $meta_query[] = [ 'key' => 'forditas_forras', 'value' => '' ];
    }

    if ( $filter === 'usda' ) $meta_query = [ 'relation' => 'AND', $meta_query, [ 'key' => 'usda_fdc_id', 'compare' => 'EXISTS' ] ];
    elseif ( $filter === 'off' ) $meta_query = [ 'relation' => 'AND', $meta_query, [ 'key' => 'off_barcode', 'compare' => 'EXISTS' ] ];
    elseif ( $filter === 'no_source' ) $meta_query = [ 'relation' => 'AND', $meta_query, [ 'key' => 'usda_fdc_id', 'compare' => 'NOT EXISTS' ], [ 'key' => 'off_barcode', 'compare' => 'NOT EXISTS' ] ];

    $posts = get_posts( [
        'post_type' => 'alapanyag', 'posts_per_page' => $batch_size, 'offset' => $offset,
        'post_status' => 'publish', 'meta_query' => $meta_query, 'orderby' => 'ID', 'order' => 'ASC',
    ] );

    if ( empty( $posts ) ) {
        $state['status'] = 'done'; fv2_save_state( $state );
        wp_clear_scheduled_hook( FV2_CRON_HOOK );
        fv2_log( 'success', '🏁 Kész! ' . $state['stats']['translated'] . ' lefordítva.' );
        fv2_finish_notify( $state );
        return;
    }

    $state['batch_num'] = ( $state['batch_num'] ?? 0 ) + 1;
    $active_key = fv2_get_active_key();
    $key_label  = $active_key ? $active_key['label'] : '?';

    fv2_log( 'info', '📦 Batch #' . $state['batch_num'] . ': ' . count( $posts ) . ' [🔑 ' . $key_label . ']' );

    $texts       = [];
    $post_map    = [];
    $magyar_skip = 0;

    foreach ( $posts as $post ) {
        $name = $mode === 'retranslate'
                ? ( get_post_meta( $post->ID, 'eredeti_nev', true ) ?: $post->post_title )
                : $post->post_title;
        if ( empty( $name ) ) $name = $post->post_title;

        if ( fv2_is_likely_hungarian( $name ) ) {
            $magyar_skip++;
            $state['stats']['magyar_pre']++;
            $state['stats']['skipped']++;
            $existing = get_post_meta( $post->ID, 'eredeti_nev', true );
            if ( empty( $existing ) ) update_field( 'eredeti_nev', $name, $post->ID );
            update_field( 'forditas_forras', 'magyar_pre_detect', $post->ID );
            update_field( 'forditas_datum', current_time( 'Y-m-d H:i:s' ), $post->ID );
            continue;
        }

        $texts[]    = $name;
        $post_map[] = [
            'post_id'       => $post->ID,
            'original_name' => $name,
            'current_title' => $post->post_title,
            'post_modified' => $post->post_modified,
        ];
    }

    if ( $magyar_skip > 0 ) {
        fv2_log( 'skip', '🇭🇺 ' . $magyar_skip . ' magyar pre-detect (~' . ( $magyar_skip * 28 ) . ' kar.)' );
    }

    if ( empty( $texts ) ) {
        $state['offset'] = $offset + $batch_size;
        $state['date']   = current_time( 'Y-m-d H:i:s' );
        fv2_save_state( $state );
        return;
    }

    $est_chars = 0;
    foreach ( $texts as $t ) $est_chars += mb_strlen( $t, 'UTF-8' );
    if ( ! fv2_rate_consume( $est_chars ) ) {
        fv2_log( 'warn', '⏳ Rate limit' ); fv2_save_state( $state ); return;
    }

    $result = fv2_deepl_translate_batch( $texts, $state );
    $state['stats']['api_calls']++;

    if ( $result === false ) {
        $state['stats']['errors']++;
        $state['offset'] = $offset + $batch_size;
        $state['date']   = current_time( 'Y-m-d H:i:s' );
        fv2_save_state( $state );
        return;
    }

    if ( fv2_should_stop() ) return;

    $translations = $result['translations'];
    $chars_batch  = $result['chars_used'];
    $used_key     = $result['key_label'] ?? '?';

    $translated  = 0;
    $skipped     = 0;
    $flagged     = 0;
    $undo_items  = [];

    foreach ( $translations as $i => $tr ) {
        if ( ! isset( $post_map[ $i ] ) ) continue;
        if ( fv2_should_stop() ) break;

        $pid       = $post_map[ $i ]['post_id'];
        $orig      = $post_map[ $i ]['original_name'];
        $cur_title = $post_map[ $i ]['current_title'];
        $saved_mod = $post_map[ $i ]['post_modified'];
        $trans     = trim( $tr['text'] ?? '' );
        $src_lang  = strtoupper( $tr['detected_source_language'] ?? 'EN' );

        $cur_post = get_post( $pid );
        if ( ! $cur_post || $cur_post->post_modified !== $saved_mod ) { $skipped++; continue; }

        if ( $src_lang === 'HU' ) {
            $skipped++;
            if ( empty( get_post_meta( $pid, 'eredeti_nev', true ) ) ) update_field( 'eredeti_nev', $orig, $pid );
            update_field( 'forditas_forras', 'magyar_eredeti', $pid );
            update_field( 'forditas_datum', current_time( 'Y-m-d H:i:s' ), $pid );
            continue;
        }

        $qf = fv2_check_quality( $orig, $trans );
        if ( ! empty( $qf ) ) {
            $flagged++;
            update_post_meta( $pid, '_fv2_quality_flag', implode( ', ', $qf ) );
            if ( in_array( 'üres', $qf, true ) ) { $skipped++; continue; }
        } else {
            delete_post_meta( $pid, '_fv2_quality_flag' );
        }

        $trans = fv2_mb_ucfirst( $trans );
        $undo_items[] = [ 'post_id' => $pid, 'old_title' => $cur_title, 'new_title' => $trans, 'old_forras' => get_post_meta( $pid, 'forditas_forras', true ) ];

        update_field( 'eredeti_nev', $orig, $pid );
        wp_update_post( [ 'ID' => $pid, 'post_title' => $trans ] );
        update_field( 'forditas_forras', 'deepl', $pid );
        update_field( 'forditas_datum', current_time( 'Y-m-d H:i:s' ), $pid );
        update_field( 'forditas_nyelv', $src_lang, $pid );

        $translated++;
    }

    $state['stats']['translated'] += $translated;
    $state['stats']['skipped']    += $skipped;
    $state['stats']['flagged']    += $flagged;
    $state['stats']['chars_used'] += $chars_batch;
    $state['offset']               = $offset + $batch_size;
    $state['date']                 = current_time( 'Y-m-d H:i:s' );
    $state['active_key_idx']       = fv2_get_active_idx();

    if ( ! empty( $undo_items ) ) {
        update_option( FV2_LAST_BATCH_KEY, [ 'time' => current_time( 'Y-m-d H:i:s' ), 'items' => $undo_items ], false );
    }

    if ( fv2_should_stop() ) { fv2_save_state( $state ); wp_clear_scheduled_hook( FV2_CRON_HOOK ); return; }

    fv2_save_state( $state );

    fv2_log( 'success',
        '✅ #' . $state['batch_num'] . ': ' . $translated . ' ford., ' . $skipped . ' skip' .
        ( $flagged > 0 ? ', ' . $flagged . '⚠️' : '' ) .
        ' (' . $chars_batch . ' kar.) [🔑 ' . $used_key . '] | Össz: ' . $state['stats']['translated']
    );
}


// ══════════════════════════════════════════════════════════════
// BEFEJEZÉS ÉRTESÍTÉS
// ═════════════════════════════════════════════���════════════════

function fv2_finish_notify( $state ) {
    $s = $state['stats'];
    $summary = ( $state['is_nightly'] ? '🌙 Éjféli' : '✋ Manuális' ) . ': ' . $s['translated'] . ' ford., ' . $s['skipped'] . ' skip, ' . $s['errors'] . ' hiba, ' . $s['chars_used'] . ' kar., ' . $s['keys_rotated'] . ' rotáció';
    fv2_auto_log( $summary );
    update_option( FV2_AUTO_COUNT_KEY, $s['translated'], false );
    if ( $s['translated'] > 0 ) {
        $email = get_option( 'admin_email' );
        if ( $email ) wp_mail( $email, '🌐 Fordító v2 – ' . $s['translated'] . ' lefordítva', $summary . "\n\n" . admin_url( 'edit.php?post_type=alapanyag&page=fordito-v2' ) );
    }
}


// ══════════════════════════════════════════════════════════════
// ADMIN MENÜ
// ══════════════════════════════════════════════════════════════

function fv2_admin_menu() {
    add_submenu_page( 'edit.php?post_type=alapanyag', 'Fordító v2', '🌐 Fordító v2', 'manage_options', 'fordito-v2', 'fv2_admin_page' );
}
add_action( 'admin_menu', 'fv2_admin_menu' );


// ══════════════════════════════════════════════════════════════
// ADMIN OLDAL HTML
// ══════════════════════════════════════════════════════════════

function fv2_admin_page() {

    $state       = fv2_get_state();
    $status      = $state['status'] ?? 'idle';
    $is_running  = ( $status === 'running' );
    $is_stopping = ( $status === 'stopping' );
    $has_saved   = ! $is_running && ! $is_stopping && intval( $state['offset'] ?? 0 ) > 0 && in_array( $status, [ 'stopped', 'error' ], true );

    $keys     = fv2_get_keys();
    $pool     = fv2_get_pool_summary();
    $rotation = fv2_get_rotation();
    $active_i = fv2_get_active_idx();

    $total_q = new WP_Query( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'publish' ] );
    $total_count = $total_q->found_posts; wp_reset_postdata();

    $trans_q = new WP_Query( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'publish', 'meta_query' => [ [ 'key' => 'forditas_forras', 'value' => 'deepl' ] ] ] );
    $trans_count = $trans_q->found_posts; wp_reset_postdata();

    $magyar_q = new WP_Query( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'publish', 'meta_query' => [ [ 'key' => 'forditas_forras', 'value' => [ 'magyar_eredeti', 'magyar_pre_detect' ], 'compare' => 'IN' ] ] ] );
    $magyar_count = $magyar_q->found_posts; wp_reset_postdata();

    $untranslated = max( 0, $total_count - $trans_count - $magyar_count );
    $last_batch   = get_option( FV2_LAST_BATCH_KEY, [] );
    $can_undo     = ! empty( $last_batch['items'] );
    $auto_log     = get_option( FV2_AUTO_LOG_KEY, [] );
    $nightly_next = wp_next_scheduled( FV2_NIGHTLY_HOOK );

    ?>
    <div class="wrap">
        <h1>🌐 Fordító v2 FINAL – DeepL <small style="font-size:0.6em;color:#999;">Multi-Key Adaptív 🔒 AES-256</small></h1>

        <?php if ( $is_stopping ) : ?>
        <div style="background:#fef3c7;border:2px solid #f59e0b;border-radius:8px;padding:16px 20px;margin:20px 0;max-width:900px;">
            <h3 style="margin:0;color:#92400e;">⏳ Leállítás...</h3>
            <script>setTimeout(function(){location.reload();},3000);</script>
        </div>
        <?php endif; ?>

        <!-- ═══ API KEY POOL DASHBOARD ═══ -->
        <div class="fv2-card" style="border-color:#8b5cf6;">
            <h3 style="margin-top:0;color:#7c3aed;">🔑 API Kulcs Pool
                <span style="font-size:0.72rem;background:#16a34a;color:#fff;padding:2px 8px;border-radius:4px;vertical-align:middle;">🔒 AES-256</span>
                <span style="font-size:0.75rem;color:#666;font-weight:400;">
                    (<?php echo $pool['key_count']; ?> kulcs | <?php echo $pool['active']; ?> aktív |
                    ~<?php echo number_format( $pool['est_names'] ); ?> név)
                </span>
            </h3>

            <?php if ( $pool['decrypt_err'] > 0 ) : ?>
            <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:0.85rem;color:#dc2626;">
                ⚠️ <strong><?php echo $pool['decrypt_err']; ?> kulcs nem dekódolható!</strong>
                Ez akkor fordul elő, ha az AUTH_KEY/AUTH_SALT megváltozott a wp-config.php-ban.
                Töröld és add újra az érintett kulcsokat.
            </div>
            <?php endif; ?>

            <!-- Összesített bar -->
            <div style="margin-bottom:16px;">
                <?php
                $total_pct   = $pool['total_limit'] > 0 ? round( ( $pool['total_used'] / $pool['total_limit'] ) * 100, 1 ) : 0;
                $total_color = $total_pct > 90 ? '#dc2626' : ( $total_pct > 70 ? '#d97706' : '#16a34a' );
                ?>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
                    <div style="flex:1;max-width:400px;background:#f0f0f1;border-radius:6px;height:20px;overflow:hidden;">
                        <div style="width:<?php echo min(100,$total_pct);?>%;height:100%;background:<?php echo $total_color;?>;border-radius:6px;transition:width 0.3s;"></div>
                    </div>
                    <span style="font-size:0.88rem;font-weight:600;">
                        <?php echo number_format($pool['total_used']);?> / <?php echo number_format($pool['total_limit']);?>
                        (<?php echo $total_pct;?>%)
                    </span>
                </div>
                <div style="font-size:0.82rem;color:#666;">
                    Maradt: <strong><?php echo number_format($pool['total_remain']);?></strong> kar.
                    (~<strong><?php echo number_format($pool['est_names']);?></strong> terméknév)
                    <?php if($pool['exhausted']>0):?> | <span style="color:#dc2626;"><strong><?php echo $pool['exhausted'];?></strong> kimerült</span><?php endif;?>
                </div>
            </div>

            <!-- Kulcsonkénti lista -->
            <div id="fv2-key-list" style="margin-bottom:16px;">
                <?php if ( empty( $keys ) ) : ?>
                    <div style="padding:20px;text-align:center;color:#94a3b8;background:#f8fafc;border-radius:8px;border:2px dashed #e2e8f0;">
                        <div style="font-size:1.5rem;margin-bottom:8px;">🔑</div>
                        <div>Még nincs API kulcs. Adj hozzá egyet lent!</div>
                    </div>
                <?php else : ?>
                    <?php foreach ( $keys as $idx => $k ) :
                        $u = fv2_get_key_usage( $idx );
                        $kpct = ( $u['limit'] ?? 500000 ) > 0 ? round( ( ( $u['used'] ?? 0 ) / ( $u['limit'] ?? 500000 ) ) * 100, 1 ) : 0;
                        $kcolor = '#16a34a';
                        if ( $kpct > 90 || ( $u['status'] ?? '' ) === 'exhausted' ) $kcolor = '#dc2626';
                        elseif ( $kpct > 70 ) $kcolor = '#d97706';
                        if ( ( $u['status'] ?? '' ) === 'error' ) $kcolor = '#dc2626';

                        $is_active = ( $idx === $active_i );
                        $key_type  = $k['type'] ?? 'free';
                        $type_badge = $key_type === 'pro'
                            ? '<span style="background:#2563eb;color:#fff;padding:1px 6px;border-radius:3px;font-size:0.7rem;font-weight:600;">PRO</span>'
                            : '<span style="background:#f59e0b;color:#fff;padding:1px 6px;border-radius:3px;font-size:0.7rem;font-weight:600;">FREE</span>';

                        $status_emoji = '🟢';
                        if ( ( $u['status'] ?? '' ) === 'exhausted' ) $status_emoji = '🔴';
                        elseif ( ( $u['status'] ?? '' ) === 'error' ) $status_emoji = '⚠️';
                        elseif ( ( $u['status'] ?? '' ) === 'unknown' ) $status_emoji = '❔';
                        elseif ( $kpct > 90 ) $status_emoji = '🟡';

                        $can_decrypt = ( fv2_get_decrypted_key( $idx ) !== false );
                        $mask = $k['key_mask'] ?? ( ! empty( $k['key'] ) ? fv2_mask_key( $k['key'] ) : '???' );
                    ?>
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;margin-bottom:6px;background:<?php echo $is_active?'#f0fdf4':($can_decrypt?'#fafafa':'#fef2f2');?>;border:<?php echo $is_active?'2px solid #86efac':($can_decrypt?'1px solid #e5e7eb':'2px solid #fca5a5');?>;border-radius:8px;">
                        <div style="font-size:1.2rem;"><?php echo $status_emoji; ?></div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                                <strong style="color:#1e293b;"><?php echo esc_html($k['label']);?></strong>
                                <?php echo $type_badge; ?>
                                <?php if($is_active):?><span style="background:#16a34a;color:#fff;padding:1px 6px;border-radius:3px;font-size:0.7rem;">← AKTÍV</span><?php endif;?>
                                <?php if(empty($k['enabled'])):?><span style="background:#94a3b8;color:#fff;padding:1px 6px;border-radius:3px;font-size:0.7rem;">KIKAPCSOLVA</span><?php endif;?>
                                <?php if(!$can_decrypt):?><span style="background:#dc2626;color:#fff;padding:1px 6px;border-radius:3px;font-size:0.7rem;">🔓 DEKÓD HIBA</span><?php endif;?>
                                <span style="background:#7c3aed;color:#fff;padding:1px 6px;border-radius:3px;font-size:0.65rem;">🔒</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="flex:1;max-width:200px;background:#e5e7eb;border-radius:4px;height:10px;overflow:hidden;">
                                    <div style="width:<?php echo min(100,$kpct);?>%;height:100%;background:<?php echo $kcolor;?>;border-radius:4px;"></div>
                                </div>
                                <span style="font-size:0.82rem;color:#555;">
                                    <?php echo number_format($u['used']??0);?> / <?php echo number_format($u['limit']??500000);?>
                                    (<?php echo $kpct;?>%)
                                </span>
                            </div>
                            <?php if(!empty($u['last_error'])):?>
                                <div style="font-size:0.78rem;color:#dc2626;margin-top:2px;">❌ <?php echo esc_html($u['last_error']);?></div>
                            <?php endif;?>
                            <div style="font-size:0.75rem;color:#94a3b8;margin-top:2px;">
                                🔒 <?php echo esc_html($mask);?>
                                <?php if(!empty($u['updated'])):?> | <?php echo esc_html($u['updated']);?><?php endif;?>
                                | Hívások: <?php echo intval($u['calls']??0);?>
                            </div>
                        </div>
                        <div style="display:flex;gap:4px;">
                            <button class="button fv2-key-toggle" data-idx="<?php echo $idx;?>" data-enabled="<?php echo $k['enabled']?'1':'0';?>" style="font-size:11px;padding:2px 8px;" title="<?php echo $k['enabled']?'Kikapcsolás':'Bekapcsolás';?>"><?php echo $k['enabled']?'⏸':'▶️';?></button>
                            <button class="button fv2-key-refresh" data-idx="<?php echo $idx;?>" style="font-size:11px;padding:2px 8px;" title="Frissítés">🔄</button>
                            <button class="button fv2-key-delete" data-idx="<?php echo $idx;?>" style="font-size:11px;padding:2px 8px;color:#dc2626;" title="Törlés">🗑️</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Kulcs hozzáadás -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;">
                <h4 style="margin:0 0 8px;font-size:0.92rem;">➕ Új API kulcs hozzáadása <span style="font-size:0.75rem;color:#7c3aed;font-weight:400;">🔒 AES-256-CBC titkosítva tárolódik</span></h4>
                <div style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;">
                    <div style="flex:1;min-width:200px;">
                        <label style="font-size:0.82rem;font-weight:600;display:block;margin-bottom:2px;">API kulcs:</label>
                        <input type="password" id="fv2-new-key" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx:fx" style="width:100%;font-size:14px;padding:6px 12px;font-family:monospace;" autocomplete="off">
                    </div>
                    <div style="min-width:120px;">
                        <label style="font-size:0.82rem;font-weight:600;display:block;margin-bottom:2px;">Név:</label>
                        <input type="text" id="fv2-new-label" placeholder="Free #1" value="Free #<?php echo count($keys)+1;?>" style="width:100%;font-size:14px;padding:6px 12px;">
                    </div>
                    <button id="fv2-toggle-key-vis" class="button" style="font-size:11px;padding:6px 10px;height:38px;" title="Mutat/Elrejt">👁️</button>
                    <button id="fv2-add-key-btn" class="button button-primary" style="font-size:14px;padding:6px 20px;height:38px;">
                        ✅ Validálás & Hozzáadás
                    </button>
                </div>
                <div id="fv2-add-key-status" style="margin-top:8px;font-size:0.88rem;"></div>
                <p style="font-size:0.75rem;color:#94a3b8;margin:6px 0 0;">
                    Free kulcs = <code>:fx</code> végű | Pro = végződés nélkül |
                    A kulcs <strong>titkosítva</strong> tárolódik (AES-256-CBC + HMAC-SHA256) |
                    <a href="https://www.deepl.com/pro-api" target="_blank">DeepL API ↗</a>
                </p>
            </div>

            <!-- Rotáció -->
            <div style="margin-top:12px;display:flex;gap:12px;align-items:center;">
                <label style="font-size:0.85rem;font-weight:600;">Rotáció:</label>
                <select id="fv2-rotation" style="font-size:14px;padding:4px 8px;">
                    <option value="balanced" <?php selected($rotation,'balanced');?>>⚖️ Balanced</option>
                    <option value="fill" <?php selected($rotation,'fill');?>>📥 Fill-first</option>
                    <option value="robin" <?php selected($rotation,'robin');?>>🔄 Round-robin</option>
                </select>
                <button id="fv2-save-rotation" class="button" style="font-size:12px;">💾</button>
                <button id="fv2-refresh-all" class="button" style="font-size:12px;">🔄 Összes frissítése</button>
            </div>
        </div>

        <!-- ═══ ÁLLAPOT ═══ -->
        <div class="fv2-card">
            <h3 style="margin-top:0;">ℹ️ Állapot</h3>
            <table class="widefat" style="max-width:650px;">
                <tr><td><strong>Összes</strong></td><td><strong style="font-size:1.2rem;"><?php echo $total_count;?></strong></td></tr>
                <tr><td><strong>DeepL</strong></td><td><strong style="color:#16a34a;"><?php echo $trans_count;?></strong></td></tr>
                <tr><td><strong>Magyar</strong></td><td><strong style="color:#2563eb;"><?php echo $magyar_count;?></strong></td></tr>
                <tr><td><strong>Lefordítatlan</strong></td><td><strong style="font-size:1.2rem;color:#d97706;"><?php echo $untranslated;?></strong></td></tr>
                <tr><td><strong>Motor</strong></td><td>Hibrid Adaptív v2 + Multi-Key 🔒</td></tr>
                <tr><td><strong>Titkosítás</strong></td><td><span style="color:#7c3aed;">🔒 AES-256-CBC + HMAC-SHA256</span> <span style="color:#94a3b8;font-size:0.82rem;">(AUTH_KEY + AUTH_SALT)</span></td></tr>
                <tr><td><strong>Éjféli auto</strong></td><td><?php if($nightly_next):?>✅ <?php echo esc_html(wp_date('Y-m-d H:i',$nightly_next));?><?php else:?>⏸<?php endif;?></td></tr>
                <?php if($is_running):?>
                <tr><td><strong>Fut</strong></td><td><span class="fv2-pulse" style="color:#16a34a;font-weight:700;">🟢 FUT</span> <?php echo intval($state['stats']['translated']);?> | <?php echo esc_html($state['filter_label']);?></td></tr>
                <?php elseif($has_saved):?>
                <tr><td><strong>Mentett</strong></td><td>📌 <?php echo intval($state['stats']['translated']);?> | <?php echo esc_html($state['date']);?></td></tr>
                <?php endif;?>
            </table>
        </div>

        <!-- ═══ BEÁLLÍTÁSOK ═══ -->
        <div id="fv2-settings-box" class="fv2-card" style="background:#f8fafc;<?php echo($is_running||$is_stopping)?'opacity:0.5;pointer-events:none;':'';?>">
            <h3 style="margin-top:0;">⚙️ Beállítások</h3>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
                <div><label class="fv2-label">Szűrés:</label><select id="fv2-filter" style="width:100%;"><option value="all">📋 Összes</option><option value="usda">🇺🇸 USDA</option><option value="off">🍊 OFF</option><option value="no_source">❓ Forrás nélküli</option></select></div>
                <div><label class="fv2-label">Batch:</label><input type="number" id="fv2-batch-size" value="50" min="5" max="50" step="5" style="width:100%;"><span class="fv2-hint">Max: 50</span></div>
                <div><label class="fv2-label">Delay:</label><input type="number" id="fv2-batch-delay" value="2" min="2" max="30" step="0.5" style="width:100%;"><span class="fv2-hint">Adaptív: 2–30s</span></div>
                <div><label class="fv2-label">Maximum:</label><input type="number" id="fv2-max" value="500" min="0" max="50000" step="10" style="width:100%;"><span class="fv2-hint">0 = ∞</span></div>
            </div>
            <div id="fv2-limit-warning" class="fv2-warning" style="display:none;"></div>
            <div class="fv2-estimate">💡 <span id="fv2-time-estimate">–</span> | ~<span id="fv2-char-estimate">–</span> kar. (marad: <span id="fv2-char-remain">–</span>)</div>
        </div>

        <!-- ═══ MÓDOK ═══ -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;max-width:900px;margin:20px 0;">
            <div class="fv2-mode-card" style="background:#f0fdf4;border-color:#86efac;">
                <h3 style="margin-top:0;color:#16a34a;">🚀 Batch</h3>
                <button id="fv2-start" class="button button-primary" <?php echo($is_running||$is_stopping||empty($keys))?'disabled':'';?>>🚀 Indítás</button>
            </div>
            <div class="fv2-mode-card" style="background:<?php echo $has_saved?'#eff6ff':'#f8fafc';?>;border-color:<?php echo $has_saved?'#93c5fd':'#e2e8f0';?>;">
                <h3 style="margin-top:0;color:#2563eb;">▶️ Folytatás</h3>
                <?php if($has_saved):?>
                    <button id="fv2-continue" class="button" style="background:#3b82f6;color:#fff;">▶️</button>
                    <button id="fv2-reset" class="button" style="color:#dc2626;font-size:12px;">🗑️</button>
                <?php else:?><button class="button" disabled>–</button><?php endif;?>
            </div>
            <div class="fv2-mode-card" style="background:#fffbeb;border-color:#fcd34d;">
                <h3 style="margin-top:0;color:#d97706;">🔄 Újra</h3>
                <button id="fv2-retranslate" class="button" style="background:#f59e0b;color:#fff;" <?php echo($trans_count<1||$is_running||$is_stopping||empty($keys))?'disabled':'';?>>🔄</button>
            </div>
        </div>

        <div style="margin:0 0 12px;display:flex;gap:8px;align-items:center;">
            <button id="fv2-stop" class="button" style="font-size:14px;padding:6px 20px;<?php echo $is_running?'':'display:none;';?>">⏹ Leállítás</button>
            <?php if($can_undo):?><button id="fv2-undo-btn" class="button" style="background:#2563eb;color:#fff;">↩️ Undo (<?php echo count($last_batch['items']);?>)</button><?php endif;?>
            <button id="fv2-export-csv" class="button">📥 CSV</button>
            <?php $fv2_night_on = get_option( FV2_NIGHTLY_ENABLED_KEY, 'yes' ) === 'yes'; ?>
            <label style="display:inline-flex;align-items:center;gap:8px;margin-right:16px;font-size:14px;font-weight:600;cursor:pointer;user-select:none;">
                <span>🌙 Éjféli fordítás</span>
                <input type="checkbox" id="fv2-nightly-toggle" <?php echo $fv2_night_on ? 'checked' : ''; ?> style="display:none;">
                <span id="fv2-nightly-toggle-visual" style="display:inline-block;width:40px;height:22px;border-radius:11px;background:<?php echo $fv2_night_on ? '#22c55e' : '#d1d5db'; ?>;position:relative;transition:background 0.25s;cursor:pointer;">
                    <span style="position:absolute;top:2px;left:<?php echo $fv2_night_on ? '20px' : '2px'; ?>;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.15);transition:left 0.25s;"></span>
                </span>
                <span id="fv2-nightly-toggle-label" style="font-size:12px;color:<?php echo $fv2_night_on ? '#16a34a' : '#9ca3af'; ?>;"><?php echo $fv2_night_on ? 'BE' : 'KI'; ?></span>
            </label>
        </div>

        <!-- ═══ EGYEDI FORDÍTÁS ═══ -->
        <div class="fv2-card">
            <h3 style="margin-top:0;">🔍 Egyedi fordítás</h3>
            <div style="display:flex;gap:8px;"><input type="text" id="fv2-search-query" placeholder="pl. Chicken breast, raw..." style="flex:1;font-size:14px;padding:6px 12px;"><button id="fv2-search-btn" class="button" <?php echo empty($keys)?'disabled':'';?>>🌐 Fordítás</button></div>
            <div id="fv2-search-result" style="margin-top:12px;"></div>
            <div style="margin-top:16px;border-top:1px solid #e5e7eb;padding-top:16px;">
                <h4 style="margin-top:0;">📋 Lefordítatlan</h4>
                <div style="display:flex;gap:8px;margin-bottom:8px;">
                    <select id="fv2-list-filter" style="font-size:14px;padding:6px 8px;"><option value="all">📋 Összes</option><option value="usda">🇺🇸 USDA</option><option value="off">🍊 OFF</option></select>
                    <button id="fv2-list-btn" class="button">📋 Mutasd (max 20)</button>
                </div>
                <div id="fv2-list-results"></div>
            </div>
        </div>

        <!-- ═══ ÉLŐ STÁTUSZ ═══ -->
        <div id="fv2-live-status" class="fv2-card" style="<?php echo $is_running?'':'display:none;';?>">
            <h3 style="margin-top:0;">📊 Élő státusz <span id="fv2-live-indicator" class="fv2-pulse" style="color:#16a34a;">●</span></h3>
            <div class="fv2-progress-wrap"><div id="fv2-progress-bar" class="fv2-progress-fill">0%</div></div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:8px;">
                <div style="text-align:center;padding:8px;background:#f0fdf4;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#16a34a;" id="fv2-s-translated">0</div><div style="font-size:0.72rem;">Lefordítva</div></div>
                <div style="text-align:center;padding:8px;background:#fffbeb;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#d97706;" id="fv2-s-skipped">0</div><div style="font-size:0.72rem;">Kihagyva</div></div>
                <div style="text-align:center;padding:8px;background:#fef2f2;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#dc2626;" id="fv2-s-errors">0</div><div style="font-size:0.72rem;">Hiba</div></div>
                <div style="text-align:center;padding:8px;background:#f5f3ff;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#7c3aed;" id="fv2-s-chars">0</div><div style="font-size:0.72rem;">Karakter</div></div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:12px;">
                <div style="text-align:center;padding:8px;background:#fef3c7;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#b45309;" id="fv2-s-flagged">0</div><div style="font-size:0.72rem;">⚠️ Flag</div></div>
                <div style="text-align:center;padding:8px;background:#ecfdf5;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#059669;" id="fv2-s-magyar">0</div><div style="font-size:0.72rem;">🇭🇺 Pre</div></div>
                <div style="text-align:center;padding:8px;background:#f0f6fc;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#2563eb;" id="fv2-s-batch">0</div><div style="font-size:0.72rem;">Batch #</div></div>
                <div style="text-align:center;padding:8px;background:#fce7f3;border-radius:6px;"><div style="font-size:1.3rem;font-weight:700;color:#be185d;" id="fv2-s-rotated">0</div><div style="font-size:0.72rem;">🔑 Rot.</div></div>
            </div>
            <div id="fv2-adaptive-info" style="font-size:0.82rem;color:#555;margin-bottom:8px;padding:8px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;"></div>
            <div id="fv2-eta" style="font-size:0.85rem;color:#666;margin-bottom:8px;padding:6px 10px;background:#f8fafc;border-radius:4px;"></div>
            <div id="fv2-live-info" style="font-size:0.85rem;color:#666;margin-bottom:8px;padding:6px 10px;background:#f8fafc;border-radius:4px;"></div>
            <div id="fv2-log" style="background:#1e1e2e;color:#cdd6f4;font-family:monospace;font-size:0.82rem;padding:12px 16px;border-radius:6px;max-height:300px;overflow-y:auto;line-height:1.6;"></div>
        </div>

        <?php if(!empty($auto_log)):?>
        <div class="fv2-card"><h3 style="margin-top:0;">🌙 Auto futások</h3><div style="max-height:200px;overflow-y:auto;font-size:0.85rem;"><?php foreach(array_reverse(array_slice($auto_log,-20)) as $al):?><div style="padding:3px 0;border-bottom:1px solid #f1f5f9;"><span style="color:#94a3b8;"><?php echo esc_html($al['time']);?></span> – <?php echo esc_html($al['msg']);?></div><?php endforeach;?></div></div>
        <?php endif;?>
    </div>

    <style>
        .fv2-card{background:#fff;border:1px solid #ccd0d4;border-radius:8px;padding:20px 24px;margin:20px 0;max-width:900px;}
        .fv2-mode-card{border:2px solid;border-radius:8px;padding:18px 20px;}
        .fv2-label{font-weight:600;font-size:0.88rem;display:block;margin-bottom:4px;}
        .fv2-hint{color:#888;font-size:0.75rem;}
        .fv2-warning{margin-top:12px;padding:10px 14px;border-radius:6px;font-size:0.85rem;}
        .fv2-estimate{margin-top:12px;padding:10px 14px;background:#fffbeb;border-radius:6px;font-size:0.85rem;color:#92400e;}
        .fv2-progress-wrap{background:#f0f0f1;border-radius:6px;height:28px;overflow:hidden;margin-bottom:12px;}
        .fv2-progress-fill{background:linear-gradient(90deg,#8b5cf6,#6366f1);height:100%;width:0%;transition:width 0.4s;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;font-size:0.85rem;}
        @keyframes fv2pulse{0%,100%{opacity:1;}50%{opacity:0.4;}}
        .fv2-pulse{animation:fv2pulse 1.5s infinite;}
    </style>
    <?php
}


// ══════════════════════════════════════════════════════════════
// ADMIN SCRIPTS (JS)
// ══════════════════════════════════════════════════════════════

function fv2_admin_scripts( $hook ) {
    if ( $hook !== 'alapanyag_page_fordito-v2' ) return;
    $state = fv2_get_state();
    $pool  = fv2_get_pool_summary();

    if ( $state['status'] !== 'running' ) fv2_refresh_all_key_usage();

    $js = <<<'JSEOF'
(function(){
'use strict';
var $=function(id){return document.getElementById(id);};
var pollTimer=null,startTime=0,startT=0,stopRequested=false;

function ajax(action,data,cb){
    var fd=new FormData();fd.append('action',action);fd.append('nonce',fv2Data.nonce);
    if(data){for(var k in data)fd.append(k,data[k]);}
    fetch(fv2Data.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){if(cb)cb(r);}).catch(function(e){if(cb)cb({success:false,data:'Net: '+e.message});});
}
function esc(s){var d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}

// ── KEY VISIBILITY TOGGLE ──
$('fv2-toggle-key-vis').addEventListener('click',function(){
    var inp=$('fv2-new-key');
    inp.type=(inp.type==='password')?'text':'password';
    this.textContent=(inp.type==='password')?'👁️':'🙈';
});

// ── KEY MANAGEMENT ──
$('fv2-add-key-btn').addEventListener('click',function(){
    var key=$('fv2-new-key').value.trim(),label=$('fv2-new-label').value.trim();
    if(!key){$('fv2-add-key-status').innerHTML='<span style="color:#dc2626;">Írd be az API kulcsot!</span>';return;}
    if(!label)label='Kulcs #'+(document.querySelectorAll('[data-idx]').length+1);
    var btn=this;btn.disabled=true;btn.textContent='⏳ Validálás...';
    $('fv2-add-key-status').innerHTML='<span style="color:#666;">🔍 DeepL API ellenőrzés + 🔒 titkosítás...</span>';
    ajax('fv2_add_key',{key:key,label:label},function(r){
        btn.disabled=false;btn.textContent='✅ Validálás & Hozzáadás';
        if(r.success){
            $('fv2-add-key-status').innerHTML='<span style="color:#16a34a;">✅ '+esc(r.data.msg)+' 🔒</span>';
            $('fv2-new-key').value='';
            setTimeout(function(){location.reload();},1500);
        }else{
            $('fv2-add-key-status').innerHTML='<span style="color:#dc2626;">❌ '+esc(r.data)+'</span>';
        }
    });
});

document.querySelectorAll('.fv2-key-toggle').forEach(function(b){b.addEventListener('click',function(){ajax('fv2_toggle_key',{idx:this.dataset.idx,enabled:this.dataset.enabled==='1'?'0':'1'},function(){location.reload();});});});
document.querySelectorAll('.fv2-key-refresh').forEach(function(b){b.addEventListener('click',function(){var btn=this;btn.disabled=true;btn.textContent='⏳';ajax('fv2_refresh_key',{idx:this.dataset.idx},function(){location.reload();});});});
document.querySelectorAll('.fv2-key-delete').forEach(function(b){b.addEventListener('click',function(){if(!confirm('Biztosan törlöd?'))return;ajax('fv2_delete_key',{idx:this.dataset.idx},function(){location.reload();});});});
$('fv2-save-rotation').addEventListener('click',function(){ajax('fv2_save_rotation',{strategy:$('fv2-rotation').value},function(r){alert(r.success?'✅ Mentve':'❌ Hiba');});});
$('fv2-refresh-all').addEventListener('click',function(){this.disabled=true;this.textContent='⏳...';ajax('fv2_refresh_all_keys',{},function(){location.reload();});});

// ── BECSLÉS ──
function validate(){
    var bs=parseInt($('fv2-batch-size').value)||50,dl=parseFloat($('fv2-batch-delay').value)||2;
    var mx=parseInt($('fv2-max').value);if(isNaN(mx)||mx<0)mx=0;
    var batches=mx>0?Math.ceil(mx/bs):0,tsec=batches*dl,mn=Math.floor(tsec/60),sc=Math.round(tsec%60);
    var ec=mx>0?mx*28:0,tr=Math.max(0,parseInt(fv2Data.poolRemain)-ec);
    $('fv2-time-estimate').textContent=mx>0?((mn>0?'~'+mn+'p ':'')+sc+'s'):'∞';
    $('fv2-char-estimate').textContent=ec>0?ec.toLocaleString():'∞';
    $('fv2-char-remain').textContent=tr.toLocaleString();
    var w=$('fv2-limit-warning'),sb=$('fv2-start');
    if(bs>50){w.style.display='block';w.style.background='#fef2f2';w.style.color='#dc2626';w.innerHTML='🚫 Max 50!';if(sb)sb.disabled=true;return false;}
    if(ec>0&&ec>parseInt(fv2Data.poolRemain)){w.style.display='block';w.style.background='#fef2f2';w.style.color='#dc2626';w.innerHTML='🚫 Nincs elég karakter!';if(sb)sb.disabled=true;return false;}
    w.style.display='none';if(sb)sb.disabled=false;return true;
}
$('fv2-batch-size').addEventListener('input',validate);$('fv2-batch-delay').addEventListener('input',validate);$('fv2-max').addEventListener('input',validate);validate();

// ── INDÍTÁS ──
function startProc(mode,offset){if(!validate())return;var mx=parseInt($('fv2-max').value);if(isNaN(mx)||mx<0)mx=0;
ajax('fv2_start',{mode:mode,offset:offset,filter:$('fv2-filter').value,batch_size:$('fv2-batch-size').value,batch_delay:$('fv2-batch-delay').value,max_items:mx},function(r){if(r.success)location.reload();else alert(r.data);});}

$('fv2-start').addEventListener('click',function(){startProc('translate',0);});
$('fv2-retranslate').addEventListener('click',function(){startProc('retranslate',0);});
if($('fv2-continue'))$('fv2-continue').addEventListener('click',function(){startProc('continue',fv2Data.savedOffset);});
if($('fv2-reset'))$('fv2-reset').addEventListener('click',function(){if(!confirm('Törlés?'))return;ajax('fv2_reset',{},function(){location.reload();});});
$('fv2-stop').addEventListener('click',function(){this.disabled=true;this.textContent='⏳...';stopRequested=true;ajax('fv2_stop',{},function(){});});
if($('fv2-undo-btn'))$('fv2-undo-btn').addEventListener('click',function(){if(!confirm('↩️ Visszaállítás?'))return;this.disabled=true;ajax('fv2_undo',{},function(r){alert(r.success?'✅ '+r.data:'❌ '+r.data);location.reload();});});
$('fv2-export-csv').addEventListener('click',function(){ajax('fv2_export_csv',{},function(r){if(!r.success)return;var b=new Blob(['\uFEFF'+r.data],{type:'text/csv'});var a=document.createElement('a');a.href=URL.createObjectURL(b);a.download='fordito_'+new Date().toISOString().slice(0,10)+'.csv';a.click();});});

if(document.getElementById('fv2-nightly-toggle'))document.getElementById('fv2-nightly-toggle').addEventListener('change',function(){
    var en=this.checked?'yes':'no';
    var vis=document.getElementById('fv2-nightly-toggle-visual');
    var lbl=document.getElementById('fv2-nightly-toggle-label');
    if(vis){vis.style.background=this.checked?'#22c55e':'#d1d5db';vis.children[0].style.left=this.checked?'20px':'2px';}
    if(lbl){lbl.textContent=this.checked?'BE':'KI';lbl.style.color=this.checked?'#16a34a':'#9ca3af';}
    var fd=new FormData();fd.append('action','fv2_toggle_nightly');fd.append('nonce',fv2Data.nonce);fd.append('enabled',en);
    fetch(fv2Data.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
        if(r.success)console.log(r.data);
    });
});

// ── POLLING ──
function poll(){
    ajax(stopRequested?'fv2_status_readonly':'fv2_status',{},function(r){
        if(!r.success)return;var s=r.data;
        $('fv2-s-translated').textContent=s.translated||0;$('fv2-s-skipped').textContent=s.skipped||0;
        $('fv2-s-errors').textContent=s.errors||0;$('fv2-s-chars').textContent=(s.chars_used||0).toLocaleString();
        $('fv2-s-flagged').textContent=s.flagged||0;$('fv2-s-magyar').textContent=s.magyar_pre||0;
        $('fv2-s-batch').textContent=s.batch_num||0;$('fv2-s-rotated').textContent=s.keys_rotated||0;
        var pct=s.max_items>0?Math.min(100,Math.round((s.translated/s.max_items)*100)):0;
        $('fv2-progress-bar').style.width=pct+'%';$('fv2-progress-bar').textContent=pct+'%';
        if(startTime===0){startTime=Date.now();startT=s.translated||0;}
        var el=(Date.now()-startTime)/1000,dn=(s.translated||0)-startT;
        if(dn>0&&el>10){var rate=dn/el,rem=s.max_items>0?Math.max(0,s.max_items-s.translated):0;
        if(rem>0){var es=Math.round(rem/rate),em=Math.floor(es/60);$('fv2-eta').innerHTML='⏱ ETA: <strong>'+(em>0?em+'p ':'')+es%60+'s</strong> | '+rate.toFixed(2)+'/s';}else $('fv2-eta').textContent='';}
        var fl={all:'📋 Összes',usda:'🇺🇸 USDA',off:'🍊 OFF',no_source:'❓'};
        $('fv2-live-info').innerHTML='Szűrő: <strong>'+(fl[s.filter]||s.filter)+'</strong> | Batch: <strong>'+s.batch_size+'</strong> | Delay: <strong>'+s.batch_delay+'s</strong> | 🔑 <strong>'+esc(s.active_key_label||'?')+'</strong>';
        var ai=$('fv2-adaptive-info');
        if(s.adaptive&&s.adaptive.enabled){ai.style.display='block';ai.innerHTML='🤖 avg=<strong>'+s.adaptive.avg_response+'s</strong> | TO: <strong>'+s.adaptive.timeouts+'</strong>'+(s.adaptive.adjustment?' | '+esc(s.adaptive.adjustment):'');}else ai.style.display='none';
        if(s.recent_log&&s.recent_log.length){var le=$('fv2-log'),lh='';var cl={info:'#89dceb',success:'#a6e3a1',warn:'#f9e2af',error:'#f38ba8',skip:'#cba6f7',debug:'#585b70'};s.recent_log.forEach(function(e){lh+='<div style="color:'+(cl[e.type]||'#cdd6f4')+'">['+esc(e.time)+'] '+esc(e.msg)+'</div>';});le.innerHTML=lh;le.scrollTop=le.scrollHeight;}
        if(s.status!=='running'&&s.status!=='stopping'){clearInterval(pollTimer);$('fv2-live-indicator').textContent='⏹';$('fv2-live-indicator').style.animation='none';$('fv2-stop').style.display='none';setTimeout(function(){location.reload();},2000);}
    });
}
if(fv2Data.isRunning){$('fv2-live-status').style.display='block';$('fv2-stop').style.display='inline-block';pollTimer=setInterval(poll,3000);poll();}

// ── Egyedi ──
$('fv2-search-btn').addEventListener('click',function(){var q=$('fv2-search-query').value.trim();if(!q)return;var b=this;b.disabled=true;b.textContent='⏳...';$('fv2-search-result').innerHTML='';
ajax('fv2_translate_single',{text:q},function(r){b.disabled=false;b.textContent='🌐 Fordítás';if(r.success){var d=r.data;$('fv2-search-result').innerHTML='<div style="padding:10px;background:#f0fdf4;border:1px solid #86efac;border-radius:6px;"><div style="font-size:0.82rem;color:#666;">Eredeti ('+esc(d.source_lang)+'):</div><div style="font-weight:600;">'+esc(d.original)+'</div><div style="margin-top:6px;font-weight:700;color:#16a34a;font-size:1.1rem;">🇭🇺 '+esc(d.translated)+'</div><div style="font-size:0.78rem;color:#94a3b8;">'+d.chars+' kar. [🔑 '+esc(d.key_label||'?')+'] 🔒</div></div>';}else{$('fv2-search-result').innerHTML='<span style="color:#dc2626;">❌ '+esc(r.data)+'</span>';}});});
$('fv2-search-query').addEventListener('keypress',function(e){if(e.key==='Enter')$('fv2-search-btn').click();});

// ── Lista ──
$('fv2-list-btn').addEventListener('click',function(){var b=this,f=$('fv2-list-filter').value;b.disabled=true;b.textContent='⏳...';
ajax('fv2_list_untranslated',{filter:f},function(r){b.disabled=false;b.textContent='📋 Mutasd (max 20)';
if(!r.success){$('fv2-list-results').innerHTML='<span style="color:#dc2626;">❌ '+esc(r.data)+'</span>';return;}
var items=r.data.items;if(!items.length){$('fv2-list-results').innerHTML='<span style="color:#16a34a;">✅ Kész!</span>';return;}
var h='<div style="font-size:0.85rem;color:#666;margin-bottom:6px;">'+r.data.total+' db:</div>';
items.forEach(function(i){h+='<div style="border:1px solid #e5e7eb;border-radius:6px;padding:8px 12px;margin-bottom:4px;display:flex;justify-content:space-between;"><div><strong>'+esc(i.name)+'</strong> <span style="font-size:0.75rem;color:#94a3b8;">#'+i.id+'</span>'+(i.source?' <span style="font-size:0.72rem;padding:1px 6px;background:#eff6ff;color:#2563eb;border-radius:3px;">'+esc(i.source)+'</span>':'')+'</div><button class="button fv2-single-btn" data-post-id="'+i.id+'" style="font-size:0.82rem;">🌐</button></div>';});
$('fv2-list-results').innerHTML=h;
document.querySelectorAll('.fv2-single-btn').forEach(function(b){b.addEventListener('click',function(){var pid=this.dataset.postId,tb=this;tb.disabled=true;tb.textContent='⏳';ajax('fv2_translate_post',{post_id:pid},function(rr){tb.textContent=rr.success?'✅':'❌';tb.style.color=rr.success?'#16a34a':'#dc2626';});});});});});
})();
JSEOF;

    wp_register_script( 'fv2-js', false, [], '2.0', true );
    wp_enqueue_script( 'fv2-js' );
    wp_add_inline_script( 'fv2-js', $js );

    wp_localize_script( 'fv2-js', 'fv2Data', [
        'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
        'nonce'       => wp_create_nonce( 'fv2_nonce' ),
        'isRunning'   => ( $state['status'] === 'running' ),
        'savedOffset' => intval( $state['offset'] ?? 0 ),
        'poolRemain'  => $pool['total_remain'],
    ] );
}
add_action( 'admin_enqueue_scripts', 'fv2_admin_scripts' );


// ══════════════════════════════════════════════════════════════
// AJAX: KEY MANAGEMENT (🔒 TITKOSÍTOTT TÁROLÁS)
// ════════════════════════════════════════════════════════════��═

function fv2_add_key_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $raw_key = sanitize_text_field( $_POST['key'] ?? '' );
    $label   = sanitize_text_field( $_POST['label'] ?? '' );
    if ( empty( $raw_key ) ) wp_send_json_error( 'Üres kulcs.' );
    if ( empty( $label ) ) $label = 'Kulcs #' . ( count( fv2_get_keys() ) + 1 );

    // Duplikátum check (dekódolás szükséges)
    $keys = fv2_get_keys();
    foreach ( $keys as $idx => $k ) {
        $existing_key = fv2_get_decrypted_key( $idx );
        if ( $existing_key !== false && $existing_key === $raw_key ) {
            wp_send_json_error( 'Ez a kulcs már hozzá van adva (' . esc_html( $k['label'] ) . ').' );
        }
    }

    // Validálás DeepL API-val
    $result = fv2_validate_key( $raw_key );
    if ( ! $result['valid'] ) {
        wp_send_json_error( $result['error'] );
    }

    // 🔒 Titkosítás
    $encrypted = fv2_encrypt_api_key( $raw_key );
    if ( $encrypted === false ) {
        wp_send_json_error( 'Titkosítási hiba! Ellenőrizd az openssl PHP extensiont.' );
    }

    $idx = count( $keys );
    $keys[] = [
        'key_enc'  => $encrypted,                // 🔒 titkosított
        'key_mask' => fv2_mask_key( $raw_key ),   // megjelenítéshez
        'label'    => $label,
        'type'     => $result['type'],
        'added_at' => current_time( 'Y-m-d H:i:s' ),
        'enabled'  => true,
        // 'key' mező SZÁNDÉKOSAN hiányzik – NEM tároljuk plain text-ben!
    ];
    fv2_save_keys( $keys );

    $usage = [
        'used'       => $result['used'],
        'limit'      => $result['limit'],
        'updated'    => current_time( 'Y-m-d H:i:s' ),
        'status'     => ( $result['used'] >= $result['limit'] ) ? 'exhausted' : 'ok',
        'last_error' => '',
        'calls'      => 0,
    ];
    fv2_save_key_usage( $idx, $usage );

    if ( count( $keys ) === 1 ) fv2_set_active_idx( 0 );

    $pct = $result['limit'] > 0 ? round( ( $result['used'] / $result['limit'] ) * 100, 1 ) : 0;
    wp_send_json_success( [
        'msg'  => $label . ' (' . strtoupper( $result['type'] ) . ') – ' . number_format( $result['used'] ) . '/' . number_format( $result['limit'] ) . ' (' . $pct . '%) – titkosítva tárolva',
        'idx'  => $idx,
        'type' => $result['type'],
    ] );
}
add_action( 'wp_ajax_fv2_add_key', 'fv2_add_key_handler' );

function fv2_delete_key_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    $idx  = intval( $_POST['idx'] ?? -1 );
    $keys = fv2_get_keys();
    if ( ! isset( $keys[ $idx ] ) ) wp_send_json_error( 'Nem létezik.' );

    // Usage optionok tisztítása
    $total = count( $keys );
    array_splice( $keys, $idx, 1 );
    fv2_save_keys( $keys );

    // Re-index usage options
    for ( $i = $idx; $i < $total; $i++ ) {
        $next_usage = get_option( FV2_KEY_USAGE_PREFIX . ( $i + 1 ), [] );
        if ( ! empty( $next_usage ) ) {
            update_option( FV2_KEY_USAGE_PREFIX . $i, $next_usage, false );
        }
    }
    delete_option( FV2_KEY_USAGE_PREFIX . ( $total - 1 ) );

    if ( fv2_get_active_idx() >= count( $keys ) ) fv2_set_active_idx( max( 0, count( $keys ) - 1 ) );
    wp_send_json_success( 'Törölve.' );
}
add_action( 'wp_ajax_fv2_delete_key', 'fv2_delete_key_handler' );

function fv2_toggle_key_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    $idx     = intval( $_POST['idx'] ?? -1 );
    $enabled = ( $_POST['enabled'] ?? '1' ) === '1';
    $keys    = fv2_get_keys();
    if ( ! isset( $keys[ $idx ] ) ) wp_send_json_error( 'Nem létezik.' );
    $keys[ $idx ]['enabled'] = $enabled;
    fv2_save_keys( $keys );
    wp_send_json_success( $enabled ? 'Bekapcsolva.' : 'Kikapcsolva.' );
}
add_action( 'wp_ajax_fv2_toggle_key', 'fv2_toggle_key_handler' );

function fv2_refresh_key_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    $idx  = intval( $_POST['idx'] ?? -1 );
    $keys = fv2_get_keys();
    if ( ! isset( $keys[ $idx ] ) ) wp_send_json_error( 'Nem létezik.' );
    $api_key = fv2_get_decrypted_key( $idx );
    if ( $api_key === false ) {
        $u = fv2_get_key_usage( $idx );
        $u['status'] = 'error'; $u['last_error'] = 'Dekódolási hiba'; $u['updated'] = current_time( 'Y-m-d H:i:s' );
        fv2_save_key_usage( $idx, $u );
        wp_send_json_error( 'Nem dekódolható – AUTH_KEY változott?' );
    }
    $result = fv2_validate_key( $api_key );
    $usage  = fv2_get_key_usage( $idx );
    if ( $result['valid'] ) {
        $usage['used'] = $result['used']; $usage['limit'] = $result['limit'];
        $usage['status'] = ( $result['used'] >= $result['limit'] ) ? 'exhausted' : 'ok';
        $usage['updated'] = current_time( 'Y-m-d H:i:s' ); $usage['last_error'] = '';
    } else {
        $usage['status'] = 'error'; $usage['last_error'] = $result['error']; $usage['updated'] = current_time( 'Y-m-d H:i:s' );
    }
    fv2_save_key_usage( $idx, $usage );
    wp_send_json_success( 'Frissítve.' );
}
add_action( 'wp_ajax_fv2_refresh_key', 'fv2_refresh_key_handler' );

function fv2_refresh_all_keys_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    fv2_refresh_all_key_usage();
    wp_send_json_success( 'Frissítve.' );
}
add_action( 'wp_ajax_fv2_refresh_all_keys', 'fv2_refresh_all_keys_handler' );

function fv2_save_rotation_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    $strategy = sanitize_key( $_POST['strategy'] ?? 'balanced' );
    if ( ! in_array( $strategy, [ 'balanced', 'fill', 'robin' ] ) ) $strategy = 'balanced';
    update_option( FV2_ROTATION_OPTION, $strategy, false );
    wp_send_json_success( 'Mentve: ' . $strategy );
}
add_action( 'wp_ajax_fv2_save_rotation', 'fv2_save_rotation_handler' );


// ══════════════════════════════════════════════════════════════
// AJAX: FORDÍTÓ MŰVELETEK
// ══════════════════════════════════════════════════════════════

function fv2_start_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    $cur = fv2_get_state();
    if ( in_array( $cur['status'], [ 'running', 'stopping' ], true ) ) wp_send_json_error( 'Már fut.' );
    if ( empty( fv2_get_keys() ) ) wp_send_json_error( 'Nincs API kulcs!' );
    $active = fv2_get_active_key();
    if ( ! $active ) wp_send_json_error( 'Nincs elérhető kulcs!' );

    $mode   = sanitize_key( $_POST['mode'] ?? 'translate' );
    $offset = max( 0, intval( $_POST['offset'] ?? 0 ) );
    $filter = sanitize_key( $_POST['filter'] ?? 'all' );
    $bs     = min( FV2_MAX_TEXTS, max( 5, intval( $_POST['batch_size'] ?? 50 ) ) );
    $bd     = max( FV2_ADAPTIVE_MIN_DELAY, floatval( $_POST['batch_delay'] ?? 2 ) );
    $mx     = max( 0, intval( $_POST['max_items'] ?? 500 ) );

    $fl = [ 'all'=>'📋 Összes', 'usda'=>'🇺🇸 USDA', 'off'=>'🍊 OFF', 'no_source'=>'❓' ];
    $old = fv2_get_state();
    $is_cont = ( $mode === 'continue' );

    $state = fv2_default_state();
    $state['status'] = 'running'; $state['mode'] = ($mode==='retranslate')?'retranslate':'translate';
    $state['offset'] = $offset; $state['filter'] = $filter; $state['filter_label'] = $fl[$filter]??$filter;
    $state['batch_size'] = $bs; $state['batch_delay'] = $bd; $state['max_items'] = $mx;
    $state['started_at'] = current_time('Y-m-d H:i:s'); $state['date'] = current_time('Y-m-d H:i:s');
    $state['active_key_idx'] = $active['idx']; $state['adaptive']['current_delay'] = $bd;

    if ($is_cont) { $state['mode']=$old['mode']??'translate'; $state['filter']=$old['filter']??$filter; $state['filter_label']=$old['filter_label']??$state['filter_label']; $state['stats']=$old['stats']??$state['stats']; $state['adaptive']=wp_parse_args($old['adaptive']??[],$state['adaptive']); $state['adaptive']['current_delay']=$bd; }

    $state['_updated'] = microtime(true);
    update_option( FV2_STATE_KEY, $state, false ); wp_cache_delete( FV2_STATE_KEY, 'options' );
    update_option( FV2_LOG_KEY, [], false ); update_option( FV2_LASTRUN_KEY, 0, false );
    fv2_force_unlock(); wp_clear_scheduled_hook( FV2_CRON_HOOK );
    wp_schedule_event( time(), 'fv2_30s', FV2_CRON_HOOK ); spawn_cron();

    fv2_log( 'info', '🚀 Indítás [🔑 ' . $active['label'] . ' 🔒] | ' . $state['filter_label'] . ' | Max: ' . ($mx>0?$mx:'∞') );
    wp_send_json_success( 'Elindítva.' );
}
add_action( 'wp_ajax_fv2_start', 'fv2_start_handler' );

function fv2_stop_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    fv2_force_status( 'stopping' );
    $w=0; while($w<5){usleep(500000);$w+=0.5;$s=fv2_get_state();if(in_array($s['status'],['stopped','done'])){fv2_force_unlock();wp_send_json_success('Leállítva.');return;}}
    fv2_force_status('stopped');fv2_force_unlock();wp_send_json_success('Force stop.');
}
add_action( 'wp_ajax_fv2_stop', 'fv2_stop_handler' );

function fv2_reset_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    delete_option(FV2_STATE_KEY);delete_option(FV2_LOG_KEY);delete_option(FV2_LASTRUN_KEY);
    fv2_force_unlock();wp_clear_scheduled_hook(FV2_CRON_HOOK);wp_send_json_success('Törölve.');
}
add_action( 'wp_ajax_fv2_reset', 'fv2_reset_handler' );

function fv2_status_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    $state=fv2_get_state();$log=get_option(FV2_LOG_KEY,[]);
    if($state['status']==='running'){
        wp_cache_delete(FV2_LASTRUN_KEY,'options');
        $lr=floatval(get_option(FV2_LASTRUN_KEY,0));$now=microtime(true);
        $dl=floatval($state['adaptive']['current_delay']??$state['batch_delay']??2);
        if(($now-$lr)>=$dl&&fv2_is_strictly_running()){$lk=fv2_acquire_lock();if($lk!==false){if(fv2_is_strictly_running()){update_option(FV2_LASTRUN_KEY,$now,false);fv2_run_batch();}fv2_release_lock($lk);$state=fv2_get_state();$log=get_option(FV2_LOG_KEY,[]);}}
        if(fv2_is_strictly_running())spawn_cron();
    }
    wp_send_json_success(fv2_build_response($state,$log));
}
add_action( 'wp_ajax_fv2_status', 'fv2_status_handler' );

function fv2_status_readonly_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    wp_send_json_success(fv2_build_response(fv2_get_state(),get_option(FV2_LOG_KEY,[])));
}
add_action( 'wp_ajax_fv2_status_readonly', 'fv2_status_readonly_handler' );

function fv2_build_response($state,$log){
    $ak=fv2_get_active_key();
    return['status'=>$state['status'],'mode'=>$state['mode'],'filter'=>$state['filter'],'filter_label'=>$state['filter_label']??'','batch_size'=>$state['batch_size'],'batch_delay'=>round($state['adaptive']['current_delay']??$state['batch_delay'],1),'max_items'=>$state['max_items'],'batch_num'=>$state['batch_num']??0,'translated'=>$state['stats']['translated']??0,'skipped'=>$state['stats']['skipped']??0,'errors'=>$state['stats']['errors']??0,'flagged'=>$state['stats']['flagged']??0,'chars_used'=>$state['stats']['chars_used']??0,'api_calls'=>$state['stats']['api_calls']??0,'keys_rotated'=>$state['stats']['keys_rotated']??0,'magyar_pre'=>$state['stats']['magyar_pre']??0,'active_key_label'=>$ak?$ak['label']:'–','recent_log'=>array_slice($log,-80),'adaptive'=>['enabled'=>$state['adaptive']['enabled']??false,'avg_response'=>$state['adaptive']['avg_response_time']??0,'adjustment'=>$state['adaptive']['last_adjustment']??'','timeouts'=>$state['adaptive']['consecutive_timeouts']??0]];
}

function fv2_undo_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    $batch=get_option(FV2_LAST_BATCH_KEY,[]);if(empty($batch['items']))wp_send_json_error('Nincs.');
    $ok=0;foreach($batch['items'] as $item){$pid=intval($item['post_id']??0);$ot=$item['old_title']??'';if(!$pid||empty($ot))continue;wp_update_post(['ID'=>$pid,'post_title'=>$ot]);$of=$item['old_forras']??'';if(function_exists('update_field')){if(empty($of)){delete_post_meta($pid,'forditas_forras');delete_post_meta($pid,'forditas_datum');}else update_field('forditas_forras',$of,$pid);}$ok++;}
    delete_option(FV2_LAST_BATCH_KEY);wp_send_json_success($ok.' visszaállítva.');
}
add_action( 'wp_ajax_fv2_undo', 'fv2_undo_handler' );

function fv2_translate_single_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    $text=sanitize_text_field($_POST['text']??'');if(empty($text))wp_send_json_error('Üres.');
    $result=fv2_deepl_translate_batch([$text]);if(!$result)wp_send_json_error('DeepL hiba.');
    $tr=$result['translations'][0]??null;if(!$tr)wp_send_json_error('Nincs eredmény.');
    wp_send_json_success(['original'=>$text,'translated'=>fv2_mb_ucfirst(trim($tr['text']??'')),'source_lang'=>$tr['detected_source_language']??'?','chars'=>$result['chars_used'],'key_label'=>$result['key_label']??'?']);
}
add_action( 'wp_ajax_fv2_translate_single', 'fv2_translate_single_handler' );

function fv2_translate_post_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    if(!function_exists('update_field'))wp_send_json_error('ACF.');
    $pid=intval($_POST['post_id']??0);if(!$pid||get_post_type($pid)!=='alapanyag')wp_send_json_error('Érvénytelen.');
    $post=get_post($pid);$name=$post->post_title;
    $result=fv2_deepl_translate_batch([$name]);if(!$result)wp_send_json_error('DeepL hiba.');
    $tr=$result['translations'][0];$sl=strtoupper($tr['detected_source_language']??'EN');$trans=fv2_mb_ucfirst(trim($tr['text']??''));
    if($sl==='HU'){update_field('eredeti_nev',$name,$pid);update_field('forditas_forras','magyar_eredeti',$pid);update_field('forditas_datum',current_time('Y-m-d H:i:s'),$pid);wp_send_json_success(['translated_name'=>$name.' (magyar)']);}
    update_field('eredeti_nev',$name,$pid);wp_update_post(['ID'=>$pid,'post_title'=>$trans]);update_field('forditas_forras','deepl',$pid);update_field('forditas_datum',current_time('Y-m-d H:i:s'),$pid);update_field('forditas_nyelv',$sl,$pid);
    wp_send_json_success(['translated_name'=>$trans]);
}
add_action( 'wp_ajax_fv2_translate_post', 'fv2_translate_post_handler' );

function fv2_list_untranslated_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    $filter=sanitize_key($_POST['filter']??'all');
    $mq=['relation'=>'OR',['key'=>'forditas_forras','compare'=>'NOT EXISTS'],['key'=>'forditas_forras','value'=>'']];
    if($filter==='usda')$mq=['relation'=>'AND',$mq,['key'=>'usda_fdc_id','compare'=>'EXISTS']];
    elseif($filter==='off')$mq=['relation'=>'AND',$mq,['key'=>'off_barcode','compare'=>'EXISTS']];
    $q=new WP_Query(['post_type'=>'alapanyag','posts_per_page'=>20,'post_status'=>'publish','meta_query'=>$mq,'orderby'=>'ID','order'=>'ASC']);
    $items=[];foreach($q->posts as $p){$src='';if(get_post_meta($p->ID,'usda_fdc_id',true))$src='USDA';elseif(get_post_meta($p->ID,'off_barcode',true))$src='OFF';$items[]=['id'=>$p->ID,'name'=>$p->post_title,'source'=>$src];}wp_reset_postdata();
    wp_send_json_success(['items'=>$items,'total'=>$q->found_posts]);
}
add_action( 'wp_ajax_fv2_list_untranslated', 'fv2_list_untranslated_handler' );

add_action( 'wp_ajax_fv2_toggle_nightly', function() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    $new = ( isset( $_POST['enabled'] ) && $_POST['enabled'] === 'yes' ) ? 'yes' : 'no';
    update_option( FV2_NIGHTLY_ENABLED_KEY, $new, false );
    if ( $new === 'yes' ) {
        fv2_schedule_nightly();
    } else {
        wp_clear_scheduled_hook( FV2_NIGHTLY_HOOK );
    }
    wp_send_json_success( $new === 'yes' ? 'Éjféli fordítás bekapcsolva.' : 'Éjféli fordítás kikapcsolva.' );
} );

function fv2_export_csv_handler() {
    check_ajax_referer( 'fv2_nonce', 'nonce' );
    if(!current_user_can('manage_options'))wp_send_json_error('Nincs jog.');
    $log=get_option(FV2_LOG_KEY,[]);$csv="Idő;Típus;Üzenet\n";
    foreach($log as $e)$csv.='"'.($e['time']??'').'";"'.($e['type']??'').'";"'.str_replace('"','""',$e['msg']??'').'"'."\n";
    wp_send_json_success($csv);
}
add_action( 'wp_ajax_fv2_export_csv', 'fv2_export_csv_handler' );


// ══════════════════════════════════════════════════════════════
// PUBLISH BOX
// ══════════════════════════════════════════════════════════════

function fv2_publish_box() {
    global $post;
    if(!$post||$post->post_type!=='alapanyag')return;
    $eredeti=get_post_meta($post->ID,'eredeti_nev',true);$forras=get_post_meta($post->ID,'forditas_forras',true);
    $datum=get_post_meta($post->ID,'forditas_datum',true);$nyelv=get_post_meta($post->ID,'forditas_nyelv',true);
    $flag=get_post_meta($post->ID,'_fv2_quality_flag',true);$nonce=wp_create_nonce('fv2_nonce');
    echo '<div class="misc-pub-section" style="padding:8px 10px;border-top:1px solid #f0f0f1;">';
    if(!empty($eredeti)&&$eredeti!==$post->post_title)echo '<div style="font-size:0.82rem;color:#666;margin-bottom:4px;">🌐 '.esc_html($eredeti).'</div>';
    if($forras){echo '<div style="font-size:0.78rem;color:#94a3b8;">';echo esc_html($forras);if($nyelv)echo ' ('.$nyelv.')';if($datum)echo ' | '.$datum;echo '</div>';}
    if($flag)echo '<div style="font-size:0.78rem;color:#dc2626;">⚠️ '.esc_html($flag).'</div>';
    echo '<button type="button" class="button" id="fv2-pub-btn" data-pid="'.$post->ID.'" data-n="'.$nonce.'" style="margin-top:4px;">🌐 Fordítás</button>';
    echo '<span id="fv2-pub-st" style="margin-left:6px;font-size:0.85rem;"></span></div>';
    ?><script>document.getElementById('fv2-pub-btn').addEventListener('click',function(){var b=this,s=document.getElementById('fv2-pub-st');b.disabled=true;s.textContent='⏳';var f=new FormData();f.append('action','fv2_translate_post');f.append('nonce',b.dataset.n);f.append('post_id',b.dataset.pid);fetch(ajaxurl,{method:'POST',body:f}).then(function(r){return r.json();}).then(function(r){b.disabled=false;s.textContent=r.success?'✅ '+r.data.translated_name:'❌ '+r.data;s.style.color=r.success?'#16a34a':'#dc2626';if(r.success)setTimeout(function(){location.reload();},1500);});});</script><?php
}
add_action( 'post_submitbox_misc_actions', 'fv2_publish_box' );


// ══════════════════════════════════════════════════════════════
// WP-CRON
// ══════════════════════════════════════════════════════════════

function fv2_cron_schedules($s){$s['fv2_30s']=['interval'=>30,'display'=>'FV2 30s'];return $s;}
add_filter( 'cron_schedules', 'fv2_cron_schedules' );

function fv2_cron_handler(){
    if(!fv2_is_strictly_running()){wp_clear_scheduled_hook(FV2_CRON_HOOK);return;}
    $state=fv2_get_state();$lr=floatval(get_option(FV2_LASTRUN_KEY,0));
    $dl=floatval($state['adaptive']['current_delay']??$state['batch_delay']??2);
    if((microtime(true)-$lr)<$dl)return;if(!fv2_is_strictly_running())return;
    $lk=fv2_acquire_lock();if(!$lk)return;
    if(!fv2_is_strictly_running()){fv2_release_lock($lk);wp_clear_scheduled_hook(FV2_CRON_HOOK);return;}
    update_option(FV2_LASTRUN_KEY,microtime(true),false);fv2_run_batch();fv2_release_lock($lk);
}
add_action( FV2_CRON_HOOK, 'fv2_cron_handler' );

function fv2_ensure_cron(){if(fv2_is_strictly_running()&&!wp_next_scheduled(FV2_CRON_HOOK))wp_schedule_event(time(),'fv2_30s',FV2_CRON_HOOK);}
add_action( 'admin_init', 'fv2_ensure_cron' );
add_action( 'wp_loaded', 'fv2_ensure_cron' );


// ══════════════════════════════════════════════════════════════
// ÉJFÉLI AUTO
// ══════════════════════════════════════════════════════════════

function fv2_schedule_nightly(){
    if(get_option(FV2_NIGHTLY_ENABLED_KEY,'yes')!=='yes'){wp_clear_scheduled_hook(FV2_NIGHTLY_HOOK);return;}
    if(!wp_next_scheduled(FV2_NIGHTLY_HOOK))wp_schedule_event(strtotime('tomorrow midnight')+120,'daily',FV2_NIGHTLY_HOOK);
}
add_action( 'admin_init', 'fv2_schedule_nightly' );

function fv2_nightly_run(){
    if(get_option(FV2_NIGHTLY_ENABLED_KEY,'yes')!=='yes'){fv2_auto_log('⏭ Éjféli kihagyva – kikapcsolva.');return;}
    $cur=fv2_get_state();
    if(in_array($cur['status'],['running','stopping'])){fv2_auto_log('⏭ Éjféli kihagyva – fut.');return;}
    if(empty(fv2_get_keys())){fv2_auto_log('❌ Nincs kulcs.');return;}
    $active=fv2_get_active_key();if(!$active){fv2_auto_log('❌ Nincs elérhető kulcs.');return;}
    $uq=new WP_Query(['post_type'=>'alapanyag','posts_per_page'=>1,'post_status'=>'publish','meta_query'=>['relation'=>'OR',['key'=>'forditas_forras','compare'=>'NOT EXISTS'],['key'=>'forditas_forras','value'=>'']]]);
    $cnt=$uq->found_posts;wp_reset_postdata();if($cnt<1){fv2_auto_log('✅ Nincs lefordítatlan.');return;}
    $state=fv2_default_state();$state['status']='running';$state['mode']='translate';$state['filter']='all';$state['filter_label']='📋 Éjféli';$state['batch_size']=50;$state['batch_delay']=3;$state['max_items']=min($cnt,2000);$state['started_at']=current_time('Y-m-d H:i:s');$state['date']=current_time('Y-m-d H:i:s');$state['is_nightly']=true;$state['active_key_idx']=$active['idx'];$state['adaptive']['current_delay']=3;$state['_updated']=microtime(true);
    update_option(FV2_STATE_KEY,$state,false);update_option(FV2_LOG_KEY,[],false);update_option(FV2_LASTRUN_KEY,0,false);fv2_force_unlock();wp_clear_scheduled_hook(FV2_CRON_HOOK);wp_schedule_event(time(),'fv2_30s',FV2_CRON_HOOK);
    fv2_auto_log('🌙 Éjféli: '.$cnt.' db, max '.$state['max_items']);fv2_log('info','🌙 Éjféli [🔑 '.$active['label'].' 🔒]');
}
add_action( FV2_NIGHTLY_HOOK, 'fv2_nightly_run' );


// ══════════════════════════════════════════════════════════════
// STALE + ADMIN BAR + REST + BACKUP
// ══════════════════════════════════════════════════════════════

function fv2_stale_check(){$s=fv2_get_state();if($s['status']!=='running')return;$u=floatval($s['_updated']??0);if($u>0&&(microtime(true)-$u)>FV2_STALE_SECONDS){fv2_force_status('stopped');fv2_force_unlock();fv2_log('warn','⏹ Auto-stop (stale).');}}
add_action( 'admin_init', 'fv2_stale_check' );

function fv2_admin_bar($bar){if(!current_user_can('manage_options'))return;$s=fv2_get_state();if($s['status']!=='running')return;$ak=fv2_get_active_key();$bar->add_node(['id'=>'fv2-run','title'=>'🌐 ('.$s['stats']['translated'].' | 🔑'.($ak?$ak['label']:'?').' 🔒)','href'=>admin_url('edit.php?post_type=alapanyag&page=fordito-v2'),'meta'=>['class'=>'fv2-bar']]);}
add_action( 'admin_bar_menu', 'fv2_admin_bar', 999 );

function fv2_admin_bar_style(){if(fv2_get_state()['status']!=='running')return;echo '<style>#wpadminbar .fv2-bar>.ab-item{background:#7c3aed!important;color:#fff!important;}#wpadminbar .fv2-bar:hover>.ab-item{background:#6d28d9!important;}</style>';}
add_action( 'admin_head', 'fv2_admin_bar_style' );

function fv2_register_rest(){
    register_rest_route('fordito/v2','/status',['methods'=>'GET','callback'=>function(){$s=fv2_get_state();return new WP_REST_Response(['status'=>$s['status'],'stats'=>$s['stats'],'pool'=>fv2_get_pool_summary()],200);},'permission_callback'=>function(){return current_user_can('manage_options');}]);
    register_rest_route('fordito/v2','/keys',['methods'=>'GET','callback'=>function(){$keys=fv2_get_keys();$out=[];foreach($keys as $i=>$k){$u=fv2_get_key_usage($i);$out[]=['label'=>$k['label'],'type'=>$k['type']??'free','enabled'=>$k['enabled'],'mask'=>$k['key_mask']??'***','encrypted'=>true,'usage'=>$u];}return new WP_REST_Response($out,200);},'permission_callback'=>function(){return current_user_can('manage_options');}]);
}
add_action( 'rest_api_init', 'fv2_register_rest' );

function fv2_admin_init_trigger(){
    if(!defined('DISABLE_WP_CRON')||!DISABLE_WP_CRON)return;if(!current_user_can('manage_options')||!fv2_is_strictly_running())return;
    $s=fv2_get_state();$lr=floatval(get_option(FV2_LASTRUN_KEY,0));$dl=floatval($s['adaptive']['current_delay']??$s['batch_delay']??2);
    if((microtime(true)-$lr)>=$dl){if(!fv2_is_strictly_running())return;$lk=fv2_acquire_lock();if($lk){if(fv2_is_strictly_running()){update_option(FV2_LASTRUN_KEY,microtime(true),false);fv2_run_batch();}fv2_release_lock($lk);}}
}
add_action( 'admin_init', 'fv2_admin_init_trigger' );


// ══════════════════════════════════════════════════════════════
// V1 MIGRÁCIÓ (plain text → titkosított)
// ══════════════════════════════════════════════════════════════

function fv2_migrate_v1() {
    if ( get_option( 'fv2_migrated_v1_enc', false ) ) return;

    // 1. V1 wp-config.php-ból DEEPL_API_KEY → pool (titkosítva)
    if ( defined( 'DEEPL_API_KEY' ) && ! empty( DEEPL_API_KEY ) && empty( fv2_get_keys() ) ) {
        $raw = DEEPL_API_KEY;
        $enc = fv2_encrypt_api_key( $raw );
        if ( $enc !== false ) {
            $keys = [ [
                'key_enc'  => $enc,
                'key_mask' => fv2_mask_key( $raw ),
                'label'    => 'V1 kulcs (migrált)',
                'type'     => str_ends_with( $raw, ':fx' ) ? 'free' : 'pro',
                'added_at' => current_time( 'Y-m-d H:i:s' ),
                'enabled'  => true,
            ] ];
            fv2_save_keys( $keys );
            $v = fv2_validate_key( $raw );
            if ( $v['valid'] ) fv2_save_key_usage( 0, [ 'used'=>$v['used'], 'limit'=>$v['limit'], 'updated'=>current_time('Y-m-d H:i:s'), 'status'=>'ok', 'last_error'=>'', 'calls'=>0 ] );
            fv2_set_active_idx( 0 );
        }
    }

    // 2. Régi plain text kulcsok áttitkosítása (ha van fv2_api_keys nem-enc option)
    $old_keys = get_option( 'fv2_api_keys', [] );
    if ( ! empty( $old_keys ) && empty( fv2_get_keys() ) ) {
        $new_keys = [];
        foreach ( $old_keys as $idx => $k ) {
            $raw = $k['key'] ?? '';
            if ( empty( $raw ) ) continue;
            $enc = fv2_encrypt_api_key( $raw );
            $new_keys[] = [
                'key_enc'  => $enc ?: '',
                'key_mask' => fv2_mask_key( $raw ),
                'label'    => $k['label'] ?? 'Kulcs #' . ( $idx + 1 ),
                'type'     => $k['type'] ?? ( str_ends_with( $raw, ':fx' ) ? 'free' : 'pro' ),
                'added_at' => $k['added_at'] ?? current_time( 'Y-m-d H:i:s' ),
                'enabled'  => $k['enabled'] ?? true,
            ];
        }
        if ( ! empty( $new_keys ) ) {
            fv2_save_keys( $new_keys );
            delete_option( 'fv2_api_keys' ); // Régi plain text törlése!
        }
    }

    // 3. V1 state migrálás
    $v1 = get_option( 'fordito_state_v1', [] );
    if ( ! empty( $v1 ) && empty( get_option( FV2_STATE_KEY ) ) ) {
        $ns = fv2_default_state();
        if ( isset($v1['offset']) ) $ns['offset'] = intval($v1['offset']);
        if ( isset($v1['filter']) ) $ns['filter'] = $v1['filter'];
        if ( isset($v1['stats']) ) foreach(['translated','skipped','errors','chars_used'] as $k){if(isset($v1['stats'][$k]))$ns['stats'][$k]=intval($v1['stats'][$k]);}
        $ns['status'] = 'stopped'; fv2_save_state($ns);
    }

    foreach(['fordito_state_v1','fordito_log_v1','fordito_last_run_time','fordito_usage_v1'] as $k)delete_option($k);
    wp_clear_scheduled_hook('fordito_cron_batch');
    update_option( 'fv2_migrated_v1_enc', true );
}
add_action( 'admin_init', 'fv2_migrate_v1' );

function fv2_cleanup_old(){foreach(['fordito_cron_batch'] as $h){if(wp_next_scheduled($h))wp_clear_scheduled_hook($h);}}
add_action( 'init', 'fv2_cleanup_old' );
