<?php

/**
 * 19 – CLEANUP ALAPANYAG  PLUGIN OFF + USDA V5
 */
/**
 * 19 – CLEANUP ALAPANYAG v6 FINAL 10/10 (Code Snippets)
 *
 * Motor: Scan → Előnézet → Kijelölés → Jóváhagyás → Végrehajtás
 * Admin: Alapanyagok → 🧹 Cleanup v6
 * Éjféli auto audit WP-Cron-nal (standard, NEM kell DISABLE_WP_CRON)
 * Atomi mutex, progress bar, admin bar, REST API, email, fuzzy, undo
 *
 * 10/10 JAVÍTÁSOK:
 *  - JS AbortController (párhuzamos fetch védelem)
 *  - Auto-reschedule (ha cron kihagyott napot)
 *  - PHP 8.2+ nullable/deprecation safe
 *  - DB index javasló admin notice
 *  - usleep chunk fuzzy loopban
 *  - Post cache + meta cache bulk
 *  - Egyszeri remover snippet beépítve
 *  - Nonce refresh, result limit, rate limit
 *  - Stale check (3 ponton), race condition védelem
 *  - Autoload=no minden option, ACF fallback
 *  - Lomtár: meta+attachment+thumbnail batch törlés
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ══════════════════════════════════════════════════════════════
// KONSTANSOK
// ══════════════════════════════════════════════════════════════

if ( ! defined( 'CLEANUP_VERSION' ) )            define( 'CLEANUP_VERSION',            '6.10.0' );
if ( ! defined( 'CLEANUP_STATE_KEY' ) )           define( 'CLEANUP_STATE_KEY',           'cleanup_state_v6' );
if ( ! defined( 'CLEANUP_LOG_KEY' ) )             define( 'CLEANUP_LOG_KEY',             'cleanup_log_v6' );
if ( ! defined( 'CLEANUP_AUTO_LOG_KEY' ) )        define( 'CLEANUP_AUTO_LOG_KEY',        'cleanup_auto_log_v6' );
if ( ! defined( 'CLEANUP_AUTO_LAST_COUNT' ) )     define( 'CLEANUP_AUTO_LAST_COUNT',     'cleanup_auto_last_count' );
if ( ! defined( 'CLEANUP_AUTO_LAST_TIME' ) )      define( 'CLEANUP_AUTO_LAST_TIME',      'cleanup_auto_last_time' );
if ( ! defined( 'CLEANUP_MIDNIGHT_HOOK' ) )       define( 'CLEANUP_MIDNIGHT_HOOK',       'cleanup_midnight_audit' );
if ( ! defined( 'CLEANUP_LOG_MAX' ) )             define( 'CLEANUP_LOG_MAX',             500 );
if ( ! defined( 'CLEANUP_AUTO_LOG_MAX' ) )        define( 'CLEANUP_AUTO_LOG_MAX',        500 );
if ( ! defined( 'CLEANUP_LAST_BATCH_KEY' ) )      define( 'CLEANUP_LAST_BATCH_KEY',      'cleanup_last_batch_v6' );
if ( ! defined( 'CLEANUP_SCAN_BATCH' ) )          define( 'CLEANUP_SCAN_BATCH',          2000 );
if ( ! defined( 'CLEANUP_MUTEX_KEY' ) )           define( 'CLEANUP_MUTEX_KEY',           'cleanup_mutex_v6_ts' );
if ( ! defined( 'CLEANUP_MUTEX_TIMEOUT' ) )       define( 'CLEANUP_MUTEX_TIMEOUT',       600 );
if ( ! defined( 'CLEANUP_PROGRESS_KEY' ) )        define( 'CLEANUP_PROGRESS_KEY',        'cleanup_progress_v6' );
if ( ! defined( 'CLEANUP_STALE_TIMEOUT' ) )       define( 'CLEANUP_STALE_TIMEOUT',       900 );
if ( ! defined( 'CLEANUP_REST_API_KEY_OPT' ) )    define( 'CLEANUP_REST_API_KEY_OPT',    'cleanup_rest_api_key' );
if ( ! defined( 'CLEANUP_FUZZY_LIMIT' ) )         define( 'CLEANUP_FUZZY_LIMIT',         15000 );
if ( ! defined( 'CLEANUP_FUZZY_SLEEP_EVERY' ) )   define( 'CLEANUP_FUZZY_SLEEP_EVERY',   500 );
if ( ! defined( 'CLEANUP_FUZZY_SLEEP_US' ) )      define( 'CLEANUP_FUZZY_SLEEP_US',      50000 );
if ( ! defined( 'CLEANUP_MAX_RESULTS' ) )         define( 'CLEANUP_MAX_RESULTS',         2000 );
if ( ! defined( 'CLEANUP_REST_RATE_LIMIT' ) )     define( 'CLEANUP_REST_RATE_LIMIT',     30 );
if ( ! defined( 'CLEANUP_MAX_KCAL' ) )            define( 'CLEANUP_MAX_KCAL',            930 );
if ( ! defined( 'CLEANUP_MIN_KCAL' ) )            define( 'CLEANUP_MIN_KCAL',            5 );
if ( ! defined( 'CLEANUP_ATWATER_PROTEIN' ) )     define( 'CLEANUP_ATWATER_PROTEIN',     4.1 );
if ( ! defined( 'CLEANUP_ATWATER_CARB' ) )        define( 'CLEANUP_ATWATER_CARB',        4.1 );
if ( ! defined( 'CLEANUP_ATWATER_FAT' ) )         define( 'CLEANUP_ATWATER_FAT',         9.3 );
if ( ! defined( 'CLEANUP_ATWATER_TOLERANCE' ) )   define( 'CLEANUP_ATWATER_TOLERANCE',   0.10 );
if ( ! defined( 'CLEANUP_NAME_MIN_LEN' ) )        define( 'CLEANUP_NAME_MIN_LEN',        2 );
if ( ! defined( 'CLEANUP_NAME_MAX_LEN' ) )        define( 'CLEANUP_NAME_MAX_LEN',        150 );
if ( ! defined( 'CLEANUP_MAX_SALT_PER_100G' ) )   define( 'CLEANUP_MAX_SALT_PER_100G',   100 );
if ( ! defined( 'CLEANUP_EMAIL_THRESHOLD' ) )     define( 'CLEANUP_EMAIL_THRESHOLD',     100 );
if ( ! defined( 'CLEANUP_INDEX_CHECK_KEY' ) )     define( 'CLEANUP_INDEX_CHECK_KEY',     'cleanup_index_check_done' );


// ══════════════════════════════════════════════════════════════
// INIT – opciók + cron + index check + stale fix
// ══════════════════════════════════════════════════════════════

add_action( 'admin_init', 'cleanup_init_all' );
add_action( 'wp_loaded', 'cleanup_init_cron_only' );

function cleanup_init_all(): void {
    cleanup_ensure_options();
    cleanup_ensure_cron();
    cleanup_get_state();
    cleanup_check_db_indexes();
}

function cleanup_init_cron_only(): void {
    cleanup_ensure_cron();
}

function cleanup_ensure_options(): void {
    if ( ! get_option( CLEANUP_REST_API_KEY_OPT ) ) {
        add_option( CLEANUP_REST_API_KEY_OPT, wp_generate_password( 32, false, false ), '', 'no' );
    }
    $defaults = [
        CLEANUP_STATE_KEY       => [],
        CLEANUP_LOG_KEY         => [],
        CLEANUP_AUTO_LOG_KEY    => [],
        CLEANUP_PROGRESS_KEY    => [],
        CLEANUP_LAST_BATCH_KEY  => [],
        CLEANUP_AUTO_LAST_TIME  => '',
        CLEANUP_AUTO_LAST_COUNT => 0,
    ];
    foreach ( $defaults as $k => $v ) {
        if ( get_option( $k ) === false ) {
            add_option( $k, $v, '', 'no' );
        }
    }
}

function cleanup_ensure_cron(): void {
    $next = wp_next_scheduled( CLEANUP_MIDNIGHT_HOOK );
    if ( ! $next ) {
        $tz = wp_timezone();
        $m  = new DateTime( 'tomorrow midnight', $tz );
        wp_schedule_event( $m->getTimestamp(), 'daily', CLEANUP_MIDNIGHT_HOOK );
    } elseif ( $next < ( time() - 86400 ) ) {
        wp_clear_scheduled_hook( CLEANUP_MIDNIGHT_HOOK );
        $tz = wp_timezone();
        $m  = new DateTime( 'tomorrow midnight', $tz );
        wp_schedule_event( $m->getTimestamp(), 'daily', CLEANUP_MIDNIGHT_HOOK );
        cleanup_auto_log( '⚠️ Cron reschedule (kihagyott nap)', 'warning' );
    }
}


// ══════════════════════════════════════════════════════════════
// DB INDEX CHECK + ADMIN NOTICE
// ══════════════════════════════════════════════════════════════

function cleanup_check_db_indexes(): void {
    if ( get_option( CLEANUP_INDEX_CHECK_KEY ) === 'done' ) return;
    if ( ! current_user_can( 'manage_options' ) ) return;
    global $wpdb;
    $indexes = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->postmeta} WHERE Column_name = 'meta_value'", ARRAY_A );
    $has_mv_index = false;
    if ( is_array( $indexes ) ) {
        foreach ( $indexes as $idx ) {
            if ( isset( $idx['Column_name'] ) && $idx['Column_name'] === 'meta_value' ) {
                $has_mv_index = true;
                break;
            }
        }
    }
    if ( ! $has_mv_index ) {
        add_action( 'admin_notices', 'cleanup_db_index_notice' );
    } else {
        update_option( CLEANUP_INDEX_CHECK_KEY, 'done', 'no' );
    }
}

function cleanup_db_index_notice(): void {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $dismiss_url = wp_nonce_url( admin_url( 'edit.php?post_type=alapanyag&page=cleanup&cleanup_dismiss_index=1' ), 'cleanup_dismiss_index' );
    echo '<div class="notice notice-warning is-dismissible"><p>';
    echo '<strong>🧹 Cleanup – DB optimalizálás ajánlott:</strong> A <code>wp_postmeta.meta_value</code> oszlopon nincs index. ';
    echo 'Ez lassítja a duplikátum- és meta-kereséseket nagy adatbázisnál.<br>';
    echo '<code>ALTER TABLE wp_postmeta ADD INDEX cleanup_meta_value (meta_value(191));</code> ';
    echo '<a href="' . esc_url( $dismiss_url ) . '">Elvetés</a>';
    echo '</p></div>';
}

add_action( 'admin_init', function(): void {
    if ( isset( $_GET['cleanup_dismiss_index'] ) && wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ?? '' ), 'cleanup_dismiss_index' ) && current_user_can( 'manage_options' ) ) {
        update_option( CLEANUP_INDEX_CHECK_KEY, 'done', 'no' );
        wp_safe_redirect( admin_url( 'edit.php?post_type=alapanyag&page=cleanup' ) );
        exit;
    }
} );


// ══════════════════════════════════════════════════════════════
// ATOMI MUTEX LOCK (INSERT IGNORE – DB szintű)
// ══════════════════════════════════════════════════════════════

function cleanup_acquire_lock(): bool {
    global $wpdb;
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name = %s AND CAST(option_value AS UNSIGNED) < %d",
        CLEANUP_MUTEX_KEY, time() - CLEANUP_MUTEX_TIMEOUT
    ) );
    wp_cache_delete( CLEANUP_MUTEX_KEY, 'options' );
    $result = $wpdb->query( $wpdb->prepare(
        "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %d, 'no')",
        CLEANUP_MUTEX_KEY, time()
    ) );
    wp_cache_delete( CLEANUP_MUTEX_KEY, 'options' );
    return ( $result === 1 );
}

function cleanup_release_lock(): void {
    global $wpdb;
    $wpdb->delete( $wpdb->options, [ 'option_name' => CLEANUP_MUTEX_KEY ] );
    wp_cache_delete( CLEANUP_MUTEX_KEY, 'options' );
}

function cleanup_is_locked(): bool {
    global $wpdb;
    wp_cache_delete( CLEANUP_MUTEX_KEY, 'options' );
    $ts = $wpdb->get_var( $wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
        CLEANUP_MUTEX_KEY
    ) );
    if ( $ts === null ) return false;
    if ( ( time() - intval( $ts ) ) > CLEANUP_STALE_TIMEOUT ) {
        cleanup_release_lock();
        cleanup_log_add( '⚠️ Stale lock feloldva', 'warning' );
        cleanup_update_state( 'idle', '' );
        return false;
    }
    return true;
}

function cleanup_touch_lock(): void {
    global $wpdb;
    $wpdb->update( $wpdb->options, [ 'option_value' => (string) time() ], [ 'option_name' => CLEANUP_MUTEX_KEY ] );
    wp_cache_delete( CLEANUP_MUTEX_KEY, 'options' );
}


// ══════════════════════════════════════════════════════════════
// PROGRESS (cache-safe)
// ══════════════════════════════════════════════════════════════

function cleanup_progress_update( array $data ): void {
    wp_cache_delete( CLEANUP_PROGRESS_KEY, 'options' );
    $cur = get_option( CLEANUP_PROGRESS_KEY, [] );
    if ( ! is_array( $cur ) ) $cur = [];
    update_option( CLEANUP_PROGRESS_KEY, array_merge( $cur, $data, [ 'updated_at' => time() ] ), 'no' );
    wp_cache_delete( CLEANUP_PROGRESS_KEY, 'options' );
}

function cleanup_progress_get(): array {
    wp_cache_delete( CLEANUP_PROGRESS_KEY, 'options' );
    $p = get_option( CLEANUP_PROGRESS_KEY, [] );
    return array_merge( [
        'status' => 'idle', 'task' => '', 'task_label' => '',
        'current' => 0, 'total' => 0, 'found' => 0, 'fixed' => 0,
        'phase' => '', 'updated_at' => 0, 'is_auto' => false,
    ], is_array( $p ) ? $p : [] );
}

function cleanup_progress_reset(): void {
    update_option( CLEANUP_PROGRESS_KEY, [
        'status' => 'idle', 'task' => '', 'task_label' => '',
        'current' => 0, 'total' => 0, 'found' => 0, 'fixed' => 0,
        'phase' => '', 'updated_at' => time(), 'is_auto' => false,
    ], 'no' );
    wp_cache_delete( CLEANUP_PROGRESS_KEY, 'options' );
}


// ══════════════════════════════════════════════════════════════
// STATE (cache-safe + stale auto-fix)
// ══════════════════════════════════════════════════════════════

function cleanup_update_state( string $status, string $label = '' ): void {
    update_option( CLEANUP_STATE_KEY, [
        'status' => $status, 'task_label' => $label, 'updated_at' => time(),
    ], 'no' );
    wp_cache_delete( CLEANUP_STATE_KEY, 'options' );
}

function cleanup_get_state(): array {
    wp_cache_delete( CLEANUP_STATE_KEY, 'options' );
    $s = get_option( CLEANUP_STATE_KEY, [] );
    if ( ! is_array( $s ) ) $s = [];
    if ( ( $s['status'] ?? '' ) === 'running' && ( time() - ( $s['updated_at'] ?? 0 ) ) > CLEANUP_STALE_TIMEOUT ) {
        $s['status'] = 'idle';
        update_option( CLEANUP_STATE_KEY, $s, 'no' );
        cleanup_release_lock();
        cleanup_progress_reset();
        cleanup_log_add( '⚠️ Stale state auto-fix', 'warning' );
    }
    return $s;
}


// ══════════════════════════════════════════════════════════════
// LOG (autoload=no)
// ══════════════════════════════════════════════════════════════

function cleanup_log_add( string $msg, string $type = 'info', ?string $key = null ): void {
    $key = $key ?? CLEANUP_LOG_KEY;
    $max = ( $key === CLEANUP_AUTO_LOG_KEY ) ? CLEANUP_AUTO_LOG_MAX : CLEANUP_LOG_MAX;
    $log = get_option( $key, [] );
    if ( ! is_array( $log ) ) $log = [];
    $log[] = [ 'time' => current_time( 'Y-m-d H:i:s' ), 'msg' => $msg, 'type' => $type ];
    if ( count( $log ) > $max ) $log = array_slice( $log, -$max );
    if ( get_option( $key ) === false ) add_option( $key, $log, '', 'no' );
    else update_option( $key, $log, 'no' );
}

function cleanup_auto_log( string $msg, string $type = 'info' ): void {
    cleanup_log_add( $msg, $type, CLEANUP_AUTO_LOG_KEY );
}

function cleanup_log_clear( ?string $key = null ): void {
    update_option( $key ?? CLEANUP_LOG_KEY, [], 'no' );
}


// ══════════════════════════════════════════════════════════════
// POST CACHE – full_audit: 1× DB lekérdezés + bulk meta cache
// ══════════════════════════════════════════════════════════════

global $cleanup_post_cache;
$cleanup_post_cache = [];

function cleanup_get_cached_posts( array $statuses = [ 'publish', 'draft', 'pending' ], string $fields = '' ): array {
    global $cleanup_post_cache;
    $key = md5( serialize( $statuses ) . '|' . $fields );
    if ( isset( $cleanup_post_cache[ $key ] ) ) return $cleanup_post_cache[ $key ];
    $result = cleanup_raw_get_posts( $statuses, $fields );
    $cleanup_post_cache[ $key ] = $result;
    return $result;
}

function cleanup_flush_post_cache(): void {
    global $cleanup_post_cache;
    $cleanup_post_cache = [];
}

function cleanup_raw_get_posts( array $statuses, string $fields = '' ): array {
    $all   = [];
    $page  = 1;
    $batch = CLEANUP_SCAN_BATCH;
    do {
        $args = [
            'post_type'              => 'alapanyag',
            'posts_per_page'         => $batch,
            'paged'                  => $page,
            'post_status'            => $statuses,
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];
        if ( $fields === 'ids' ) $args['fields'] = 'ids';
        $posts = get_posts( $args );
        if ( empty( $posts ) ) break;
        $all = array_merge( $all, $posts );
        $page++;
    } while ( count( $posts ) === $batch );
    if ( ! empty( $all ) ) {
        $ids = ( $fields === 'ids' ) ? $all : wp_list_pluck( $all, 'ID' );
        update_meta_cache( 'post', $ids );
    }
    return $all;
}


// ══════════════════════════════════════════════════════════════
// HELPEREK – Script / Nyelv / Karakter
// ══════════════════════════════════════════════════════════════

function cleanup_is_non_latin( string $t ): bool {
    $c = preg_replace( '/[\d\s\p{P}\p{S}]/u', '', $t );
    if ( $c === null ) return false;
    $l = mb_strlen( $c, 'UTF-8' );
    return ( $l > 0 && ( preg_match_all( '/\p{Latin}/u', $c ) / $l ) < 0.5 );
}

function cleanup_has_cyrillic( string $t ): bool {
    return (bool) preg_match( '/\p{Cyrillic}/u', $t );
}

function cleanup_detect_script( string $t ): string {
    $c = preg_replace( '/[\d\s\p{P}\p{S}]/u', '', $t );
    if ( $c === null || mb_strlen( $c, 'UTF-8' ) === 0 ) return 'ismeretlen';
    $scripts = [
        'Cirill' => '/\p{Cyrillic}/u', 'Kínai' => '/\p{Han}/u', 'Arab' => '/\p{Arabic}/u',
        'Thai' => '/\p{Thai}/u', 'Devanagari' => '/\p{Devanagari}/u', 'Koreai' => '/\p{Hangul}/u',
        'Katakana' => '/\p{Katakana}/u', 'Hiragana' => '/\p{Hiragana}/u',
        'Grúz' => '/\p{Georgian}/u', 'Örmény' => '/\p{Armenian}/u', 'Héber' => '/\p{Hebrew}/u',
        'Bengáli' => '/\p{Bengali}/u', 'Tamil' => '/\p{Tamil}/u', 'Telugu' => '/\p{Telugu}/u',
        'Gujarati' => '/\p{Gujarati}/u',
    ];
    $mn = 'egyéb'; $mc = 0;
    foreach ( $scripts as $n => $p ) {
        $cnt = preg_match_all( $p, $c );
        if ( $cnt > $mc ) { $mc = $cnt; $mn = $n; }
    }
    return $mn;
}


// ══════════════════════════════════════════════════════════════
// HELPEREK – Név / Szám / Kisbetű / Sanitize / Atwater
// ══════════════════════════════════════════════════════════════

function cleanup_is_lowercase_start( string $t ): bool {
    $t = trim( $t );
    if ( mb_strlen( $t, 'UTF-8' ) === 0 ) return false;
    $f = mb_substr( $t, 0, 1, 'UTF-8' );
    return (bool) preg_match( '/\p{L}/u', $f ) && $f !== mb_strtoupper( $f, 'UTF-8' );
}

function cleanup_mb_ucfirst( string $s ): string {
    return mb_strtoupper( mb_substr( $s, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $s, 1, null, 'UTF-8' );
}

function cleanup_is_numeric_only_name( string $t ): bool {
    $c = preg_replace( '/[\s\p{P}\p{S}]/u', '', trim( $t ) );
    if ( $c === null || mb_strlen( $c, 'UTF-8' ) === 0 ) return false;
    return (bool) preg_match( '/^\d+$/', $c );
}

function cleanup_sanitize_name( string $n ): string {
    $f = html_entity_decode( $n, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $f = wp_strip_all_tags( $f );
    $f = (string) preg_replace( '/[\x00-\x1F\x7F]/u', ' ', $f );
    $f = (string) preg_replace( '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{FE00}-\x{FE0F}\x{200B}-\x{200D}\x{FEFF}]/u', '', $f );
    $f = (string) preg_replace( '/[™®©℠℗]/u', '', $f );
    $f = (string) preg_replace( '/\s{2,}/u', ' ', $f );
    $f = (string) preg_replace( '/,{2,}/', ',', $f );
    return trim( rtrim( ltrim( trim( $f ), ' ,.' ), ' ,.' ) );
}

/**
 * @return array{calculated:float,actual:float,diff_pct:float,may_have_alcohol:bool}|false
 */
function cleanup_atwater_check( float $k, float $p, float $c, float $fat ) {
    if ( $k <= 0 ) return false;
    $calc = $p * CLEANUP_ATWATER_PROTEIN + $c * CLEANUP_ATWATER_CARB + $fat * CLEANUP_ATWATER_FAT;
    if ( $calc <= 0 ) return false;
    $dp = abs( $k - $calc ) / max( $k, $calc );
    if ( $dp > CLEANUP_ATWATER_TOLERANCE ) {
        return [
            'calculated' => round( $calc, 1 ), 'actual' => round( $k, 1 ),
            'diff_pct' => round( $dp * 100, 1 ), 'may_have_alcohol' => ( $k > $calc ),
        ];
    }
    return false;
}


// ══════════════════════════════════════════════════════════════
// HELPER – Alkohol detektálás (6 nyelv + whitelist)
// ══════════════════════════════════════════════════════════════

function cleanup_get_alcohol_whitelist(): array {
    return [
        'sörélesztő','sörliszt','sörárpa',
        'beer yeast','beer batter','beer bread','beer cheese','brewer\'s yeast','brewers yeast',
        'borecet','borkő','borkősav','borjú','borjúhús','borjúszelet','borjúcomb','borjúmáj',
        'borjúborda','borjúragu','borsó','borsóleves','borsópüré','borsófőzelék','bors',
        'borsikafű','borsmenta','borsmentatea','borsos','boróka','borókabogyó',
        'wine vinegar','wine sauce',
        'rumkrém','rumesszencia','rumaroma','rum aroma','rum ízű','rum extract','rum cake',
        'rum flavoring','rum flavour','rum balls','rumgolyó','rumpuncs torta',
        'ginger','gingersnap','gingerbread','ginger ale','ginger beer','ginseng','ginko','ginkgo','gyömbér',
        'pálinkás szósz','pálinkás','likőrkrém',
        'apple cider vinegar','almaecet','cider vinegar',
        'konyakos','cognac sauce','arak hal',
        'vodkás tészta','vodka sauce','penne alla vodka',
        'tiramisu','tiramisù','porter steak','stout cake','birsalma',
    ];
}

/**
 * @return string|false
 */
function cleanup_is_alcoholic( string $t ) {
    $lo = mb_strtolower( trim( $t ), 'UTF-8' );
    foreach ( cleanup_get_alcohol_whitelist() as $safe ) {
        if ( mb_strpos( $lo, mb_strtolower( $safe, 'UTF-8' ) ) !== false ) return false;
    }
    $all = array_unique( array_merge(
        ['sör','bor','pálinka','vodka','whisky','whiskey','rum','gin','tequila','brandy','konyak','cognac',
         'likőr','likör','pezsgő','champagne','prosecco','aperol','spritz','mojito','cocktail','koktél',
         'vermut','vermouth','abszint','absinth','absinthe','jägermeister','jagermeister','baileys',
         'amaretto','sambuca','grappa','calvados','schnapps','schnaps','puncs','grog','sangria','cider',
         'almabor','meggybor','fröccs','házi bor','vörösbor','fehérbor','rozé','rosé','tokaji',
         'egri bikavér','villányi','szekszárdi','bikavér','unicum','zwack','házi pálinka',
         'törkölypálinka','szilvapálinka','barackpálinka','körtepálinka','meggypálinka','málnapálinka'],
        ['beer','ale','lager','stout','porter','wine','red wine','white wine','rosé wine','sparkling wine',
         'vodka','whisky','whiskey','bourbon','scotch','rum','gin','tequila','mezcal','brandy','cognac',
         'liqueur','liquor','spirit','spirits','alcoholic','alcohol','champagne','prosecco','cava',
         'sake','saké','mead','mézbor','hard seltzer','hard cider','irish cream','kahlua','cointreau',
         'triple sec','grand marnier','chartreuse','campari','aperol','martini','margarita','daiquiri',
         'cosmopolitan','manhattan','old fashioned','negroni','mai tai','piña colada','pina colada',
         'long island','bloody mary','mimosa','bellini','kir royale','craft beer','ipa','pale ale',
         'pilsner','pilsener','wheat beer','weizen','hefeweizen','dunkel','bock','doppelbock',
         'barleywine','malt liquor','soju','shochu','baijiu','rakia','rakija','ouzo','pastis',
         'arak','arrack','cachaça','cachaca','caipirinha'],
        ['bier','wein','rotwein','weißwein','weisswein','sekt','schnaps','obstler','korn','weinbrand','glühwein','gluhwein'],
        ['vin','vin rouge','vin blanc','bière','biere','cidre','pastis','armagnac','calvados','eau de vie'],
        ['vino','vino rosso','vino bianco','birra','grappa','limoncello','amaretto','sambuca','prosecco','chianti','barolo','amaro'],
        ['cerveza','vino','vino tinto','vino blanco','sangria','tequila','mezcal']
    ) );
    usort( $all, function( string $a, string $b ): int { return mb_strlen( $b, 'UTF-8' ) - mb_strlen( $a, 'UTF-8' ); } );
    foreach ( $all as $kw ) {
        if ( preg_match( '/(?:^|\s|,|\(|\[|–|-)' . preg_quote( $kw, '/' ) . '(?:$|\s|,|\)|\]|–|-)/u', $lo ) ) return $kw;
    }
    foreach ( $all as $kw ) { if ( $lo === $kw ) return $kw; }
    if ( preg_match( '/\d+[.,]?\d*\s*%?\s*vol|vol\s*\.?\s*\d|abv|alkohol\s*tartalom|alcohol\s*content/ui', $lo ) ) return 'alkohol%';
    return false;
}


// ══════════════════════════════════════════════════════════════
// ADMIN MENÜ
// ══════════════════════════════════════════════════════════════

add_action( 'admin_menu', function(): void {
    add_submenu_page(
        'edit.php?post_type=alapanyag',
        'Cleanup v6 FINAL 10/10', '🧹 Cleanup v6',
        'manage_options', 'cleanup', 'cleanup_admin_page'
    );
} );


// ══════════════════════════════════════════════════════════════
// ADMIN BAR
// ══════════════════════════════════════════════════════════════

add_action( 'admin_bar_menu', function( WP_Admin_Bar $bar ): void {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $st  = cleanup_get_state();
    $run = ( ( $st['status'] ?? '' ) === 'running' );
    $url = admin_url( 'edit.php?post_type=alapanyag&page=cleanup' );
    if ( $run ) {
        $pr  = cleanup_progress_get();
        $pct = ( $pr['total'] > 0 ) ? round( $pr['current'] / $pr['total'] * 100 ) : 0;
        $bar->add_node( [ 'id' => 'cleanup-bar', 'title' => '<span style="color:#a6e3a1;animation:cleanupBarPulse 1.5s infinite;">🧹 Fut ' . intval( $pct ) . '%</span>', 'href' => $url ] );
        $bar->add_node( [ 'parent' => 'cleanup-bar', 'id' => 'cleanup-bar-d', 'title' => intval( $pr['current'] ) . '/' . intval( $pr['total'] ) . ' | ' . intval( $pr['found'] ) . ' prob.' . ( ! empty( $pr['is_auto'] ) ? ' (auto)' : '' ) ] );
    } else {
        $lt = get_option( CLEANUP_AUTO_LAST_TIME, '' );
        $lc = intval( get_option( CLEANUP_AUTO_LAST_COUNT, 0 ) );
        $t  = '🧹 Cleanup';
        if ( $lt ) $t .= ' – ' . $lt . ' (' . $lc . ')';
        $bar->add_node( [ 'id' => 'cleanup-bar', 'title' => esc_html( $t ), 'href' => $url ] );
    }
}, 999 );

add_action( 'admin_head', 'cleanup_bar_css' );
add_action( 'wp_head', 'cleanup_bar_css' );
function cleanup_bar_css(): void {
    if ( ! current_user_can( 'manage_options' ) ) return;
    echo '<style>@keyframes cleanupBarPulse{0%,100%{opacity:1;}50%{opacity:.4;}}#wpadminbar .ab-top-menu>#cleanup-bar>.ab-item{background:#1a1a2e!important;}</style>';
}


// ══════════════════════════════════════════════════════════════
// ADMIN PAGE – HTML
// ══════════════════════════════════════════════════════════════

function cleanup_admin_page(): void {
    $tq = new WP_Query( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'any' ] );
    $tc = $tq->found_posts; wp_reset_postdata();
    $pq = new WP_Query( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'publish' ] );
    $pc = $pq->found_posts; wp_reset_postdata();
    $dq = new WP_Query( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'draft' ] );
    $dc = $dq->found_posts; wp_reset_postdata();
    $rq = new WP_Query( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'trash' ] );
    $rc = $rq->found_posts; wp_reset_postdata();

    $state = cleanup_get_state();
    $run   = ( ( $state['status'] ?? '' ) === 'running' );
    $pr    = cleanup_progress_get();
    $lb    = get_option( CLEANUP_LAST_BATCH_KEY, [] );
    if ( ! is_array( $lb ) ) $lb = [];
    $undo  = ! empty( $lb['items'] );
    $alt   = get_option( CLEANUP_AUTO_LAST_TIME, '' );
    $alc   = intval( get_option( CLEANUP_AUTO_LAST_COUNT, 0 ) );
    $alog  = get_option( CLEANUP_AUTO_LOG_KEY, [] );
    if ( ! is_array( $alog ) ) $alog = [];
    $mlog  = get_option( CLEANUP_LOG_KEY, [] );
    if ( ! is_array( $mlog ) ) $mlog = [];
    $nm    = wp_next_scheduled( CLEANUP_MIDNIGHT_HOOK );
    $ns    = $nm ? date_i18n( 'Y-m-d H:i:s', $nm ) : 'Nincs ütemezve!';
    $ak    = get_option( CLEANUP_REST_API_KEY_OPT, '' );
    ?>
    <div class="wrap">
    <h1>🧹 Cleanup v6 FINAL 10/10</h1>

    <?php if ( $run ) : $pct = ( $pr['total'] > 0 ) ? round( $pr['current'] / $pr['total'] * 100 ) : 0; ?>
    <div id="cleanup-progress-wrap" style="background:#fff;border:2px solid #a6e3a1;border-radius:8px;padding:16px 24px;margin:16px 0;max-width:900px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
            <strong style="color:#16a34a;animation:cleanupPulse 1.5s infinite;">🟢 FUT</strong>
            <span id="cleanup-progress-label" style="font-size:.88rem;color:#555;"><?php echo esc_html( $pr['task_label'] ?? '' ); ?><?php echo ! empty( $pr['is_auto'] ) ? ' (AUTO)' : ''; ?></span>
        </div>
        <div style="background:#e5e7eb;border-radius:9999px;height:22px;overflow:hidden;position:relative;">
            <div id="cleanup-progress-bar" style="background:linear-gradient(90deg,#22c55e,#16a34a);height:100%;border-radius:9999px;transition:width .5s;width:<?php echo intval( $pct ); ?>%;"></div>
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;"><span id="cleanup-progress-pct"><?php echo intval( $pct ); ?>%</span></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:.82rem;color:#666;">
            <span id="cleanup-progress-phase"><?php echo esc_html( $pr['phase'] ?? '' ); ?></span>
            <span><span id="cleanup-progress-current"><?php echo intval( $pr['current'] ); ?></span>/<span id="cleanup-progress-total"><?php echo intval( $pr['total'] ); ?></span></span>
        </div>
    </div>
    <?php endif; ?>

    <div style="background:#fff;border:1px solid #ccd0d4;border-radius:8px;padding:20px 24px;margin:20px 0;max-width:900px;">
        <h3 style="margin-top:0;">ℹ️ Állapot</h3>
        <table class="widefat" style="max-width:700px;">
            <tr><td><strong>Alapanyagok</strong></td><td><strong style="font-size:1.2rem;"><?php echo intval( $tc ); ?></strong> (pub:<?php echo intval( $pc ); ?> draft:<?php echo intval( $dc ); ?> kuka:<?php echo intval( $rc ); ?>)</td></tr>
            <tr><td><strong>Auto audit</strong></td><td><?php if ( $alt ) : ?>📊 <strong><?php echo esc_html( $alt ); ?></strong> – <strong style="color:<?php echo $alc > 0 ? '#dc2626' : '#16a34a'; ?>;"><?php echo intval( $alc ); ?></strong> elem<?php else : ?><span style="color:#94a3b8;">Még nem futott.</span><?php endif; ?></td></tr>
            <tr><td><strong>Következő éjféli</strong></td><td>🕛 <?php echo esc_html( $ns ); ?></td></tr>
            <tr><td><strong>REST API</strong></td><td style="font-family:monospace;font-size:.82rem;">GET /cleanup/v1/status <button onclick="navigator.clipboard.writeText('<?php echo esc_attr( $ak ); ?>');this.textContent='✅';setTimeout(()=>this.textContent='📋',1500);" class="button" style="font-size:11px;padding:0 8px;margin-left:8px;">📋 key</button></td></tr>
            <tr><td><strong>Verzió</strong></td><td><?php echo esc_html( CLEANUP_VERSION ); ?></td></tr>
            <?php if ( $run ) : ?><tr><td><strong>Folyamat</strong></td><td><span style="color:#16a34a;font-weight:700;animation:cleanupPulse 1.5s infinite;">🟢 FUT</span> <?php echo esc_html( $state['task_label'] ?? '' ); ?></td></tr><?php endif; ?>
        </table>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:900px;margin:20px 0;">
    <?php
    $cards = [
        [ 'empty', '🗑️ Üres / hiányos', '#fef2f2', '#fca5a5', '#dc2626', 'Üres/szám/rövid/hosszú/0kcal/negatív' ],
        [ 'nutrition', '⚖️ Tápérték validáció', '#fef2f2', '#f97316', '#ea580c', 'Atwater/Makrók>100/Cukor>CH/Só' ],
        [ 'alcohol', '🍺 Alkoholos italok', '#f5f3ff', '#a855f7', '#9333ea', '6 nyelv, 150+ kulcsszó, whitelist' ],
        [ 'duplicates', '🔀 Duplikációk', '#fffbeb', '#fcd34d', '#d97706', 'Név+vonalkód+FDC+Fuzzy 85%' ],
        [ 'names', '🏷️ Név tisztítás', '#f0fdf4', '#86efac', '#16a34a', 'Emoji/HTML/™®©/zero-width/szóköz' ],
        [ 'orphan_meta', '🔗 Árva meta', '#f5f3ff', '#c4b5fd', '#7c3aed', 'Kukás meta, üres BC/FDC' ],
        [ 'foreign', '🌐 Idegen + Cirill', '#fef2f2', '#f9a8d4', '#db2777', '1 cirill = törlés, CJK/arab/thai...' ],
        [ 'capitalize', '🔠 Kis kezdőbetű', '#eff6ff', '#93c5fd', '#2563eb', 'UTF-8 safe nagybetűsítés + slug' ],
    ];
    foreach ( $cards as $c ) : ?>
        <div style="background:<?php echo $c[2]; ?>;border:2px solid <?php echo $c[3]; ?>;border-radius:8px;padding:18px 20px;">
            <h3 style="margin-top:0;color:<?php echo $c[4]; ?>;font-size:1rem;"><?php echo $c[1]; ?></h3>
            <p style="font-size:.82rem;color:#555;margin:4px 0 12px;"><?php echo $c[5]; ?></p>
            <button class="button cleanup-scan-btn" data-task="<?php echo esc_attr( $c[0] ); ?>" <?php echo $run ? 'disabled' : ''; ?>>🔍 Keresés</button>
        </div>
    <?php endforeach; ?>
    </div>

    <div style="margin:0 0 16px;display:flex;flex-wrap:wrap;gap:8px;max-width:900px;">
        <button id="cleanup-full-audit" class="button button-primary" style="font-size:14px;padding:8px 24px;" <?php echo $run ? 'disabled' : ''; ?>>📊 Teljes audit</button>
        <button id="cleanup-auto-now" class="button" style="font-size:14px;padding:8px 20px;background:#7c3aed;color:#fff;border-color:#6d28d9;" <?php echo $run ? 'disabled' : ''; ?>>🕛 Auto MOST</button>
        <button id="cleanup-empty-trash" class="button" style="font-size:14px;padding:8px 20px;background:#dc2626;color:#fff;border-color:#b91c1c;" <?php echo ( $run || $rc === 0 ) ? 'disabled' : ''; ?>>🗑️ Lomtár (<?php echo intval( $rc ); ?>)</button>
        <?php if ( $undo ) : ?><button id="cleanup-undo-btn" class="button" style="font-size:14px;padding:8px 20px;background:#2563eb;color:#fff;border-color:#1d4ed8;">↩️ Undo (<?php echo count( $lb['items'] ); ?>)</button><?php endif; ?>
        <button id="cleanup-export-csv" class="button" style="font-size:14px;padding:8px 20px;">📥 Log CSV</button>
        <button id="cleanup-export-auto-csv" class="button" style="font-size:14px;padding:8px 20px;">📥 Auto CSV</button>
        <?php if ( $run ) : ?><button id="cleanup-stop" class="button button-secondary" style="font-size:14px;padding:8px 20px;">⏹ Stop</button><?php endif; ?>
    </div>

    <div id="cleanup-results-section" style="display:none;background:#fff;border:2px solid #e5e7eb;border-radius:8px;padding:20px 24px;margin:20px 0;max-width:900px;">
        <h3 id="cleanup-results-title" style="margin-top:0;"></h3>
        <div id="cleanup-results-summary" style="padding:10px 14px;background:#f8fafc;border-radius:6px;font-size:.88rem;margin-bottom:12px;"></div>
        <div style="margin-bottom:12px;">
            <label style="font-size:.88rem;cursor:pointer;"><input type="checkbox" id="cleanup-select-all" checked> Összes</label>
            <button id="cleanup-export-results-csv" class="button" style="font-size:12px;padding:2px 10px;margin-left:16px;">📥 Eredmény CSV</button>
        </div>
        <div id="cleanup-results-list" style="max-height:500px;overflow-y:auto;"></div>
        <div style="margin-top:16px;display:flex;gap:8px;align-items:center;">
            <button id="cleanup-apply-btn" class="button" style="font-size:14px;padding:6px 20px;background:#dc2626;color:#fff;border-color:#b91c1c;">🗑️ Kijelöltek végrehajtása</button>
            <button id="cleanup-cancel-btn" class="button" style="font-size:14px;padding:6px 20px;">❌ Mégsem</button>
            <span id="cleanup-apply-status" style="margin-left:12px;font-size:.88rem;"></span>
        </div>
    </div>

    <div id="cleanup-trash-section" style="display:none;background:#fff;border:2px solid #fca5a5;border-radius:8px;padding:20px 24px;margin:20px 0;max-width:900px;">
        <h3 style="margin-top:0;color:#dc2626;">🗑️ Lomtár ürítés – VÉGLEGES!</h3>
        <div id="cleanup-trash-preview" style="padding:10px 14px;background:#fef2f2;border-radius:6px;font-size:.88rem;margin-bottom:12px;"></div>
        <div id="cleanup-trash-list" style="max-height:300px;overflow-y:auto;margin-bottom:12px;"></div>
        <div style="padding:12px 16px;background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;margin-bottom:16px;"><strong style="color:#dc2626;">⚠️ VÉGLEGES! NEM visszavonható!</strong></div>
        <div style="display:flex;gap:8px;align-items:center;">
            <button id="cleanup-trash-confirm" class="button" style="font-size:14px;padding:6px 20px;background:#dc2626;color:#fff;border-color:#b91c1c;">⚠️ Végleg törlés</button>
            <button id="cleanup-trash-cancel" class="button" style="font-size:14px;padding:6px 20px;">❌ Mégsem</button>
            <span id="cleanup-trash-status" style="margin-left:12px;font-size:.88rem;"></span>
        </div>
    </div>

    <div style="background:#fff;border:1px solid #ccd0d4;border-radius:8px;padding:20px 24px;margin:20px 0;max-width:900px;">
        <h3 style="margin-top:0;">📊 Scan eredmény</h3>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px;">
            <div style="text-align:center;padding:8px;background:#f0f6fc;border-radius:6px;">
                <div style="font-size:1.3rem;font-weight:700;" id="cleanup-s-scanned">–</div>
                <div style="font-size:.72rem;color:#666;">Vizsgált</div>
            </div>
            <div style="text-align:center;padding:8px;background:#fef2f2;border-radius:6px;">
                <div style="font-size:1.3rem;font-weight:700;color:#dc2626;" id="cleanup-s-found">–</div>
                <div style="font-size:.72rem;color:#666;">Probléma</div>
            </div>
            <div style="text-align:center;padding:8px;background:#f0fdf4;border-radius:6px;">
                <div style="font-size:1.3rem;font-weight:700;color:#16a34a;" id="cleanup-s-fixed">0</div>
                <div style="font-size:.72rem;color:#666;">Javítva</div>
            </div>
        </div>
        <div id="cleanup-live-info" style="font-size:.85rem;color:#666;padding:6px 10px;background:#f8fafc;border-radius:4px;"></div>
    </div>

    <?php
    $log_sections = [
        [ '🕛 Automatikus audit log', 'cleanup-auto-log-panel', $alog, '— Még nem futott auto audit —', '180px', false ],
        [ '📋 Manuális log', 'cleanup-log-panel', $mlog, '— Indíts egy scant! —', '280px', true ],
    ];
    foreach ( $log_sections as $ls ) : ?>
    <div style="background:#fff;border:1px solid #ccd0d4;border-radius:8px;padding:20px 24px;margin:20px 0;max-width:900px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:12px;">
            <h3 style="margin:0;"><?php echo $ls[0]; ?></h3>
            <?php if ( $ls[5] ) : ?><button id="cleanup-log-clear" class="button" style="font-size:12px;padding:4px 12px;">🗑️ Törlés</button><?php endif; ?>
        </div>
        <div id="<?php echo esc_attr( $ls[1] ); ?>" style="background:#1e1e2e;border-radius:6px;padding:12px 14px;height:<?php echo $ls[4]; ?>;overflow-y:auto;font-family:'Courier New',monospace;font-size:.78rem;line-height:1.6;color:#cdd6f4;">
        <?php if ( empty( $ls[2] ) ) : ?>
            <div style="color:#585b70;font-style:italic;"><?php echo esc_html( $ls[3] ); ?></div>
        <?php else :
            foreach ( (array) $ls[2] as $e ) :
                $co = '#cdd6f4';
                if ( ( $e['type'] ?? '' ) === 'success' ) $co = '#a6e3a1';
                elseif ( ( $e['type'] ?? '' ) === 'warning' ) $co = '#f9e2af';
                elseif ( ( $e['type'] ?? '' ) === 'error' ) $co = '#f38ba8';
                elseif ( ( $e['type'] ?? '' ) === 'info' ) $co = '#89dceb';
        ?>
            <div style="color:<?php echo esc_attr( $co ); ?>;"><span style="color:#585b70;">[<?php echo esc_html( substr( (string) ( $e['time'] ?? '' ), 11 ) ?: ( $e['time'] ?? '' ) ); ?>]</span> <?php echo esc_html( $e['msg'] ?? '' ); ?></div>
        <?php endforeach; endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- REMOVER SNIPPET INFO -->
    <div style="background:#fff;border:1px solid #ccd0d4;border-radius:8px;padding:20px 24px;margin:20px 0;max-width:900px;">
        <h3 style="margin-top:0;">🧹 Eltávolítás (ha végleg nem kell)</h3>
        <p style="font-size:.85rem;color:#555;">Ha véglegesen el akarod távolítani a Cleanup plugint, először futtasd az alábbi snippetet <strong>egyszer</strong>, majd töröld mindkét snippetet:</p>
        <pre style="background:#1e1e2e;color:#cdd6f4;padding:12px;border-radius:6px;font-size:.78rem;overflow-x:auto;">
// --- Cleanup v6 REMOVER (egyszer futtatni, utána törölni!) ---
foreach(['cleanup_state_v6','cleanup_log_v6','cleanup_auto_log_v6','cleanup_auto_last_count',
'cleanup_auto_last_time','cleanup_last_batch_v6','cleanup_mutex_v6_ts','cleanup_progress_v6',
'cleanup_rest_api_key','cleanup_email_failed','cleanup_index_check_done'] as $o) delete_option($o);
wp_clear_scheduled_hook('cleanup_midnight_audit');
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key IN('_cleanup_flag','_cleanup_flag_reason')");
        </pre>
    </div>

    </div>
    <style>@keyframes cleanupPulse{0%,100%{opacity:1;}50%{opacity:.4;}}</style>
    <?php
}


// ══════════════════════════════════════════════════════════════
// ADMIN JS – AbortController, closure-védett, nonce refresh
// ══════════════════════════════════════════════════════════════

add_action( 'admin_enqueue_scripts', function( string $hook ): void {
    if ( $hook !== 'alapanyag_page_cleanup' ) return;

    $js = <<<'JSEOF'
(function(){
'use strict';
var cfg=Object.assign({},window.cleanupData||{});
try{delete window.cleanupData;}catch(e){}

var $=function(id){return document.getElementById(id);};
var SR=[],CT='',pollT=null,activeCtrl=null;
var TL={'empty':'🗑️ Üres','nutrition':'⚖️ Tápérték','alcohol':'🍺 Alkohol','duplicates':'🔀 Duplikáció','names':'🏷️ Név','orphan_meta':'🔗 Meta','foreign':'🌐 Idegen','capitalize':'🔠 Nagybetű','full_audit':'📊 Teljes audit'};
var AL={'delete':'🗑️ Töröl','merge':'🔀 Merge','fix_name':'🏷️ Név javítás','capitalize':'🔠 Nagybetű','delete_meta':'🔗 Meta törlés','trash':'🗑️ Kukába','flag_review':'⚠️ Flag'};

function abortPrev(){if(activeCtrl){try{activeCtrl.abort();}catch(e){}activeCtrl=null;}}
function newCtrl(){abortPrev();activeCtrl=new AbortController();return activeCtrl;}

function safeFetch(url,opts){
    var ctrl=newCtrl();
    opts=opts||{};opts.signal=ctrl.signal;
    return fetch(url,opts).then(function(r){if(activeCtrl===ctrl)activeCtrl=null;return r;}).catch(function(e){if(e.name==='AbortError'){log('⏹ Megszakítva','warning');return null;}throw e;});
}

function log(m,t){
    var p=$('cleanup-log-panel');if(!p)return;
    var C={'info':'#89dceb','success':'#a6e3a1','warning':'#f9e2af','error':'#f38ba8'};
    var n=new Date(),ts=String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0');
    var ph=p.querySelector('div[style*="font-style"]');if(ph)ph.remove();
    var d=document.createElement('div');d.style.color=C[t]||'#cdd6f4';
    d.innerHTML='<span style="color:#585b70;">['+ts+']</span> '+eh(m);
    p.appendChild(d);p.scrollTop=p.scrollHeight;
}

setInterval(function(){
    var fd=new FormData();fd.append('action','cleanup_refresh_nonce');fd.append('nonce',cfg.nonce);
    fetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
        if(r&&r.success&&r.data&&r.data.nonce)cfg.nonce=r.data.nonce;
    }).catch(function(){});
},300000);

function startPoll(){
    if(pollT)return;
    pollT=setInterval(function(){
        var fd=new FormData();fd.append('action','cleanup_progress');fd.append('nonce',cfg.nonce);
        fetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
            if(!r||!r.success)return;var p=r.data;
            var bar=$('cleanup-progress-bar'),pe=$('cleanup-progress-pct'),ph=$('cleanup-progress-phase'),cu=$('cleanup-progress-current'),to=$('cleanup-progress-total');
            if(bar&&p.total>0){var pct=Math.round(p.current/p.total*100);bar.style.width=pct+'%';if(pe)pe.textContent=pct+'%';}
            if(ph)ph.textContent=p.phase||'';if(cu)cu.textContent=p.current||0;if(to)to.textContent=p.total||0;
            $('cleanup-s-scanned').textContent=p.current||'...';$('cleanup-s-found').textContent=p.found||0;
            if(p.status==='idle'||p.status==='done')stopPoll();
        }).catch(function(){});
    },2000);
}
function stopPoll(){if(pollT){clearInterval(pollT);pollT=null;}}

if($('cleanup-log-clear'))$('cleanup-log-clear').addEventListener('click',function(){
    $('cleanup-log-panel').innerHTML='<div style="color:#585b70;font-style:italic;">— Törölve —</div>';
    var fd=new FormData();fd.append('action','cleanup_log_clear');fd.append('nonce',cfg.nonce);
    fetch(cfg.ajaxUrl,{method:'POST',body:fd});
});

function csvX(type,fname){
    var fd=new FormData();fd.append('action','cleanup_export_csv');fd.append('nonce',cfg.nonce);fd.append('type',type);
    fetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.blob();}).then(function(b){
        var u=URL.createObjectURL(b),a=document.createElement('a');a.href=u;
        a.download=fname+'_'+new Date().toISOString().slice(0,10)+'.csv';a.click();URL.revokeObjectURL(u);
        log('📥 '+fname+' CSV','success');
    });
}
$('cleanup-export-csv').addEventListener('click',function(){csvX('log','cleanup_log');});
if($('cleanup-export-auto-csv'))$('cleanup-export-auto-csv').addEventListener('click',function(){csvX('auto_log','cleanup_auto_log');});

document.querySelectorAll('.cleanup-scan-btn').forEach(function(b){b.addEventListener('click',function(){runScan(this.dataset.task);});});
$('cleanup-full-audit').addEventListener('click',function(){runScan('full_audit');});

if($('cleanup-auto-now'))$('cleanup-auto-now').addEventListener('click',function(){
    if(!confirm('🕛 Automatikus audit MOST futtatása?\nTeljes szabálylista + automatikus kukázás.'))return;
    this.disabled=true;log('🕛 Auto audit...','info');startPoll();
    var fd=new FormData();fd.append('action','cleanup_auto_audit_now');fd.append('nonce',cfg.nonce);
    safeFetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){if(!r)return r;return r.json();}).then(function(r){
        stopPoll();if(!r)return;
        if(r.success){log('✔ Auto: '+r.data,'success');setTimeout(function(){location.reload();},2000);}
        else log('✖ '+r.data,'error');
    }).catch(function(e){stopPoll();log('✖ '+(e.message||e),'error');});
});

function dis(){document.querySelectorAll('.cleanup-scan-btn').forEach(function(b){b.disabled=true;});$('cleanup-full-audit').disabled=true;if($('cleanup-auto-now'))$('cleanup-auto-now').disabled=true;if($('cleanup-empty-trash'))$('cleanup-empty-trash').disabled=true;}
function ena(){document.querySelectorAll('.cleanup-scan-btn').forEach(function(b){b.disabled=false;});$('cleanup-full-audit').disabled=false;if($('cleanup-auto-now'))$('cleanup-auto-now').disabled=false;if($('cleanup-empty-trash'))$('cleanup-empty-trash').disabled=false;}

function runScan(task){
    CT=task;dis();
    $('cleanup-results-section').style.display='none';$('cleanup-trash-section').style.display='none';
    $('cleanup-live-info').innerHTML='⏳ <strong>'+TL[task]+'</strong> fut...';
    $('cleanup-s-scanned').textContent='...';$('cleanup-s-found').textContent='...';$('cleanup-s-fixed').textContent='0';
    log('▶ Scan: '+TL[task],'info');startPoll();
    var fd=new FormData();fd.append('action','cleanup_scan');fd.append('nonce',cfg.nonce);fd.append('task',task);
    safeFetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){if(!r)return r;return r.json();}).then(function(r){
        ena();stopPoll();if(!r)return;
        if(!r.success){log('✖ '+r.data,'error');$('cleanup-live-info').innerHTML='<span style="color:#dc2626;">❌ '+r.data+'</span>';return;}
        SR=r.data.items||[];var sc=r.data.scanned||0,tf=r.data.total_found||SR.length,tr=r.data.truncated||false;
        $('cleanup-s-scanned').textContent=sc;$('cleanup-s-found').textContent=tf;
        log('✔ '+sc+' vizsgált, '+tf+' probléma'+(tr?' (első '+SR.length+' mutatva)':''),'success');
        if(!tf){log('✅ Nincs probléma!','success');$('cleanup-live-info').innerHTML='<span style="color:#16a34a;font-weight:600;">✅ Nincs probléma!</span>';return;}
        $('cleanup-live-info').innerHTML='<span style="color:#dc2626;font-weight:600;">⚠️ '+tf+' probléma'+(tr?' (első '+SR.length+')':'')+' ↓</span>';
        showResults(task,SR,sc,tf,tr);
    }).catch(function(e){ena();stopPoll();if(e&&e.name!=='AbortError')log('✖ '+(e.message||e),'error');});
}

function showResults(task,items,sc,tf,tr){
    $('cleanup-results-section').style.display='block';
    $('cleanup-results-title').innerHTML=TL[task]+' – <span style="color:#dc2626;">'+tf+'</span>';
    var sh='Vizsgált: <strong>'+sc+'</strong> | Probléma: <strong style="color:#dc2626;">'+tf+'</strong>';
    if(tr)sh+=' <span style="color:#dc2626;">⚠️ Első '+items.length+' mutatva!</span>';
    $('cleanup-results-summary').innerHTML=sh;
    var h='';
    var TC={empty:'#fca5a5',zero_kcal:'#fca5a5',zero_macros:'#fca5a5',no_data:'#fca5a5',numeric_name:'#fca5a5',short_name:'#fca5a5',long_name:'#fca5a5',low_kcal:'#fca5a5',high_kcal:'#fca5a5',negative_value:'#fca5a5',macros_over_100:'#f97316',atwater_mismatch:'#f97316',sugar_exceeds_carb:'#f97316',sat_fat_exceeds_fat:'#f97316',fiber_exceeds_carb:'#f97316',salt_too_high:'#f97316',alcohol:'#a855f7',duplicate:'#fcd34d',name:'#86efac',orphan_meta:'#c4b5fd',foreign:'#f9a8d4',cyrillic:'#f9a8d4',lowercase:'#93c5fd'};
    items.forEach(function(it,ix){
        var bc=TC[it.type]||'#e5e7eb';
        h+='<div style="border:1px solid '+bc+';border-left:4px solid '+bc+';border-radius:6px;margin-bottom:6px;padding:10px 14px;">';
        h+='<label style="display:flex;align-items:start;gap:10px;cursor:pointer;">';
        h+='<input type="checkbox" class="cleanup-item-cb" data-index="'+ix+'" checked style="margin-top:4px;">';
        h+='<div style="flex:1;min-width:0;">';
        h+='<strong>'+eh(it.name||'?')+'</strong> <span style="font-size:.75rem;color:#94a3b8;">#'+(it.post_id||'?')+'</span>';
        h+=' <span style="font-size:.72rem;padding:1px 6px;background:#f1f5f9;border-radius:3px;">'+eh(it.type_label)+'</span>';
        if(it.kcal!==undefined){
            h+='<div style="font-size:.78rem;color:#94a3b8;margin-top:2px;">🔥'+it.kcal+' kcal | 💪'+(it.protein||0)+'g | 🌾'+(it.carb||0)+'g | 🫒'+(it.fat||0)+'g';
            if(it.atwater_calc)h+=' | ⚖️'+it.atwater_calc+' ('+it.atwater_diff_pct+'%)';
            if(it.may_have_alcohol)h+=' <span style="color:#9333ea;">🍷 alkohol?</span>';
            h+='</div>';
        }
        if(it.detected_script)h+='<div style="font-size:.78rem;color:#db2777;margin-top:2px;">🌐 '+eh(it.detected_script)+'</div>';
        if(it.alcohol_match)h+='<div style="font-size:.78rem;color:#9333ea;margin-top:2px;">🍺 "'+eh(it.alcohol_match)+'"</div>';
        h+='<div style="font-size:.82rem;color:#555;margin-top:4px;">'+eh(it.reason)+'</div>';
        h+='<div style="font-size:.78rem;color:#666;margin-top:2px;">→ <strong>'+(AL[it.action]||it.action)+'</strong></div>';
        if(it.fixed_name){h+='<div style="margin-top:4px;"><span style="color:#dc2626;text-decoration:line-through;">'+eh(it.name)+'</span> → <span style="color:#16a34a;font-weight:600;">'+eh(it.fixed_name)+'</span></div>';}
        if(it.duplicates&&it.duplicates.length){h+='<div style="font-size:.78rem;color:#d97706;margin-top:4px;">Marad: ';it.duplicates.forEach(function(d,i){if(i)h+=', ';h+='#'+d.id;});h+='</div>';}
        h+='</div></label></div>';
    });
    $('cleanup-results-list').innerHTML=h;$('cleanup-select-all').checked=true;
    $('cleanup-results-section').scrollIntoView({behavior:'smooth',block:'start'});
}

if($('cleanup-export-results-csv'))$('cleanup-export-results-csv').addEventListener('click',function(){
    if(!SR.length){log('⚠ Nincs eredmény','warning');return;}
    var csv='post_id;name;type;type_label;reason;action;kcal;protein;carb;fat\n';
    SR.forEach(function(i){csv+='"'+(i.post_id||'')+'";"'+ec(i.name||'')+'";"'+(i.type||'')+'";"'+ec(i.type_label||'')+'";"'+ec(i.reason||'')+'";"'+(i.action||'')+'";"'+(i.kcal||'')+'";"'+(i.protein||'')+'";"'+(i.carb||'')+'";"'+(i.fat||'')+'"\n';});
    var b=new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'}),u=URL.createObjectURL(b),a=document.createElement('a');a.href=u;a.download='cleanup_results_'+new Date().toISOString().slice(0,10)+'.csv';a.click();URL.revokeObjectURL(u);
    log('📥 Eredmény CSV ('+SR.length+')','success');
});

$('cleanup-select-all').addEventListener('change',function(){var c=this.checked;document.querySelectorAll('.cleanup-item-cb').forEach(function(cb){cb.checked=c;});});

$('cleanup-apply-btn').addEventListener('click',function(){
    var sel=[];document.querySelectorAll('.cleanup-item-cb:checked').forEach(function(cb){sel.push(SR[parseInt(cb.dataset.index)]);});
    if(!sel.length){$('cleanup-apply-status').textContent='⚠️ Jelölj ki!';return;}
    if(!confirm('⚠️ '+sel.length+' művelet végrehajtása?'))return;
    var btn=this;btn.disabled=true;$('cleanup-apply-status').textContent='⏳...';log('▶ '+sel.length+' elem','info');
    var fd=new FormData();fd.append('action','cleanup_apply');fd.append('nonce',cfg.nonce);fd.append('items',JSON.stringify(sel));
    safeFetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){if(!r)return r;return r.json();}).then(function(r){
        btn.disabled=false;if(!r)return;
        if(r.success){log('✔ '+r.data,'success');$('cleanup-apply-status').innerHTML='<span style="color:#16a34a;">✅ '+r.data+'</span>';$('cleanup-s-fixed').textContent=sel.length;setTimeout(function(){location.reload();},2000);}
        else{log('✖ '+r.data,'error');$('cleanup-apply-status').innerHTML='<span style="color:#dc2626;">❌</span>';}
    }).catch(function(e){btn.disabled=false;if(e&&e.name!=='AbortError')log('✖ '+(e.message||e),'error');});
});

$('cleanup-cancel-btn').addEventListener('click',function(){abortPrev();$('cleanup-results-section').style.display='none';SR=[];log('Visszavonva','warning');});

if($('cleanup-undo-btn'))$('cleanup-undo-btn').addEventListener('click',function(){
    if(!confirm('↩️ Visszaállítás?'))return;this.disabled=true;log('↩️ Undo...','info');
    var fd=new FormData();fd.append('action','cleanup_undo');fd.append('nonce',cfg.nonce);
    fetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
        if(r.success){log('✔ '+r.data,'success');setTimeout(function(){location.reload();},1500);}else log('✖ '+r.data,'error');
    });
});

if($('cleanup-empty-trash'))$('cleanup-empty-trash').addEventListener('click',function(){
    $('cleanup-results-section').style.display='none';$('cleanup-trash-section').style.display='block';$('cleanup-trash-status').textContent='';
    var fd=new FormData();fd.append('action','cleanup_trash_preview');fd.append('nonce',cfg.nonce);
    fetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
        if(!r.success){$('cleanup-trash-preview').innerHTML='❌';return;}
        var d=r.data;$('cleanup-trash-preview').innerHTML='<strong style="color:#dc2626;">🗑️ '+d.count+'</strong>'+(d.total_meta?' | '+d.total_meta+' meta':'')+(d.total_thumbs?' | '+d.total_thumbs+' kép':'');
        var h='';d.items.forEach(function(i){h+='<div style="font-size:.82rem;padding:4px 8px;border-bottom:1px solid #fee2e2;"><strong>#'+i.id+'</strong> – '+eh(i.title||'?')+'</div>';});
        if(d.count>d.items.length)h+='<div style="font-size:.78rem;color:#94a3b8;padding:6px 8px;">... +'+(d.count-d.items.length)+' további</div>';
        $('cleanup-trash-list').innerHTML=h;$('cleanup-trash-section').scrollIntoView({behavior:'smooth'});
    });
});
if($('cleanup-trash-cancel'))$('cleanup-trash-cancel').addEventListener('click',function(){$('cleanup-trash-section').style.display='none';});
if($('cleanup-trash-confirm'))$('cleanup-trash-confirm').addEventListener('click',function(){
    if(!confirm('⚠️ VÉGLEGES TÖRLÉS!'))return;this.disabled=true;$('cleanup-trash-status').textContent='⏳...';log('⚠️ Lomtár ürítés!','warning');tB(0);
});
function tB(total){
    var fd=new FormData();fd.append('action','cleanup_trash_empty');fd.append('nonce',cfg.nonce);
    fetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(r){
        if(!r.success){log('✖ '+r.data,'error');return;}total+=r.data.deleted;$('cleanup-trash-status').textContent='⏳ '+total;
        log('  🗑️ +'+r.data.deleted+' (össz:'+total+')','warning');
        if(r.data.has_more)tB(total);else{log('✔ '+total+' törölve','success');$('cleanup-trash-status').innerHTML='✅ '+total;setTimeout(function(){location.reload();},2000);}
    }).catch(function(e){log('✖ '+(e.message||e),'error');});
}

if($('cleanup-stop'))$('cleanup-stop').addEventListener('click',function(){
    this.disabled=true;abortPrev();stopPoll();
    var fd=new FormData();fd.append('action','cleanup_stop');fd.append('nonce',cfg.nonce);
    fetch(cfg.ajaxUrl,{method:'POST',body:fd}).then(function(){location.reload();});
});

if(cfg.isRunning)startPoll();
function eh(s){var d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
function ec(s){return String(s||'').replace(/"/g,'""');}
})();
JSEOF;

    wp_register_script( 'cleanup-js', false, [], CLEANUP_VERSION, true );
    wp_enqueue_script( 'cleanup-js' );
    wp_add_inline_script( 'cleanup-js', $js );
    wp_localize_script( 'cleanup-js', 'cleanupData', [
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'cleanup_nonce' ),
        'isRunning' => ( ( cleanup_get_state()['status'] ?? '' ) === 'running' ),
    ] );
} );


// ══════════════════════════════════════════════════════════════
// AJAX: NONCE REFRESH + PROGRESS POLL
// ═════════════════════════════════════���════════════════════════

add_action( 'wp_ajax_cleanup_refresh_nonce', function(): void {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    wp_send_json_success( [ 'nonce' => wp_create_nonce( 'cleanup_nonce' ) ] );
} );

add_action( 'wp_ajax_cleanup_progress', function(): void {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    wp_send_json_success( cleanup_progress_get() );
} );


// ══════════════════════════════════════════════════════════════
// AJAX: SCAN
// ══════════════════════════════════════════════════════════════

add_action( 'wp_ajax_cleanup_scan', function(): void {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    if ( cleanup_is_locked() ) wp_send_json_error( '⚠️ Másik folyamat fut.' );
    if ( ! cleanup_acquire_lock() ) wp_send_json_error( '⚠️ Lock hiba.' );
    @set_time_limit( 300 );

    $task = sanitize_key( $_POST['task'] ?? '' );
    if ( $task === '' ) { cleanup_release_lock(); wp_send_json_error( 'Nincs feladat.' ); }

    cleanup_update_state( 'running', $task );
    cleanup_log_add( '▶ Scan: ' . $task, 'info' );
    cleanup_progress_update( [ 'status' => 'running', 'task' => $task, 'task_label' => $task, 'current' => 0, 'total' => 0, 'found' => 0, 'fixed' => 0, 'phase' => 'Indítás...', 'is_auto' => false ] );

    $items   = [];
    $scanned = 0;
    $all     = ( $task === 'full_audit' );
    $run_list = [];
    if ( $task === 'empty' || $all ) $run_list[] = 'empty';
    if ( $task === 'nutrition' || $all ) $run_list[] = 'nutrition';
    if ( $task === 'alcohol' || $all ) $run_list[] = 'alcohol';
    if ( $task === 'duplicates' || $all ) $run_list[] = 'duplicates';
    if ( $task === 'names' || $all ) $run_list[] = 'names';
    if ( $task === 'orphan_meta' || $all ) $run_list[] = 'orphan_meta';
    if ( $task === 'foreign' || $all ) $run_list[] = 'foreign';
    if ( $task === 'capitalize' || $all ) $run_list[] = 'capitalize';

    $ti = 0;
    $tt = count( $run_list );
    foreach ( $run_list as $st ) {
        $ti++;
        cleanup_touch_lock();
        cleanup_progress_update( [ 'phase' => $st . ' (' . $ti . '/' . $tt . ')' ] );
        $fn = 'cleanup_scan_' . $st;
        if ( function_exists( $fn ) ) {
            $r       = $fn();
            $items   = array_merge( $items, $r['items'] );
            $scanned += $r['scanned'];
            cleanup_progress_update( [ 'current' => $scanned, 'found' => count( $items ) ] );
            cleanup_log_add( '  ✔ ' . $st . ': ' . count( $r['items'] ) . '/' . $r['scanned'], count( $r['items'] ) > 0 ? 'warning' : 'success' );
        }
    }

    cleanup_flush_post_cache();
    cleanup_progress_update( [ 'status' => 'done', 'current' => $scanned, 'total' => $scanned, 'found' => count( $items ), 'phase' => 'Kész' ] );
    cleanup_update_state( 'idle', '' );
    cleanup_release_lock();

    $total_found = count( $items );
    $truncated   = false;
    if ( $total_found > CLEANUP_MAX_RESULTS ) {
        $items     = array_slice( $items, 0, CLEANUP_MAX_RESULTS );
        $truncated = true;
        cleanup_log_add( '⚠️ ' . $total_found . ' → első ' . CLEANUP_MAX_RESULTS, 'warning' );
    }

    cleanup_log_add( '✔ Kész: ' . $scanned . ' vizsgált, ' . $total_found . ' probléma', $total_found > 0 ? 'warning' : 'success' );
    wp_send_json_success( [ 'items' => $items, 'scanned' => $scanned, 'task' => $task, 'total_found' => $total_found, 'truncated' => $truncated ] );
} );


// ══════════════════════════════════════════════════════════════
// SCAN FUNCTIONS
// ══════════════════════════════════════════════════════════════

function cleanup_scan_empty(): array {
    $items = [];
    $posts = cleanup_get_cached_posts( [ 'publish', 'draft', 'pending' ], 'ids' );
    $sc    = count( $posts );
    cleanup_progress_update( [ 'total' => $sc ] );

    foreach ( $posts as $i => $pid ) {
        if ( $i % 500 === 0 ) { cleanup_progress_update( [ 'current' => $i, 'found' => count( $items ) ] ); cleanup_touch_lock(); }
        $t  = get_the_title( $pid );
        $st = get_post_status( $pid );
        $k  = (float) get_post_meta( $pid, 'kaloria', true );
        $f  = (float) get_post_meta( $pid, 'feherje', true );
        $ch = (float) get_post_meta( $pid, 'szenhidrat', true );
        $zs = (float) get_post_meta( $pid, 'zsir', true );
        $ho = ( get_post_meta( $pid, 'off_barcode', true ) !== '' && get_post_meta( $pid, 'off_barcode', true ) !== false );
        $hu = ( get_post_meta( $pid, 'usda_fdc_id', true ) !== '' && get_post_meta( $pid, 'usda_fdc_id', true ) !== false );
        $tr = trim( $t );
        $b  = [ 'post_id' => $pid, 'kcal' => round( $k, 1 ), 'protein' => round( $f, 1 ), 'carb' => round( $ch, 1 ), 'fat' => round( $zs, 1 ), 'post_modified' => get_post_field( 'post_modified', $pid ) ];

        if ( $tr === '' )                                        { $items[] = $b + [ 'name' => '(üres)', 'type' => 'empty', 'type_label' => 'Üres név', 'reason' => $st, 'action' => 'trash' ]; continue; }
        if ( cleanup_is_numeric_only_name( $tr ) )               { $items[] = $b + [ 'name' => $t, 'type' => 'numeric_name', 'type_label' => 'Csak szám', 'reason' => '"' . $tr . '"', 'action' => 'trash' ]; continue; }
        if ( mb_strlen( $tr, 'UTF-8' ) < CLEANUP_NAME_MIN_LEN ) { $items[] = $b + [ 'name' => $t, 'type' => 'short_name', 'type_label' => 'Rövid(' . mb_strlen( $tr, 'UTF-8' ) . ')', 'reason' => 'Min ' . CLEANUP_NAME_MIN_LEN, 'action' => 'trash' ]; continue; }
        if ( mb_strlen( $tr, 'UTF-8' ) > CLEANUP_NAME_MAX_LEN ) { $items[] = $b + [ 'name' => $t, 'type' => 'long_name', 'type_label' => 'Hosszú(' . mb_strlen( $tr, 'UTF-8' ) . ')', 'reason' => 'Max ' . CLEANUP_NAME_MAX_LEN, 'action' => 'trash' ]; continue; }
        if ( $k < 0 || $f < 0 || $ch < 0 || $zs < 0 ) {
            $n = []; if ( $k < 0 ) $n[] = 'kcal=' . $k; if ( $f < 0 ) $n[] = 'F=' . $f; if ( $ch < 0 ) $n[] = 'CH=' . $ch; if ( $zs < 0 ) $n[] = 'Zs=' . $zs;
            $items[] = $b + [ 'name' => $t, 'type' => 'negative_value', 'type_label' => 'Negatív', 'reason' => implode( ', ', $n ), 'action' => 'trash' ]; continue;
        }
        if ( $k > CLEANUP_MAX_KCAL ) { $items[] = $b + [ 'name' => $t, 'type' => 'high_kcal', 'type_label' => '>' . CLEANUP_MAX_KCAL . 'kcal', 'reason' => round( $k, 1 ) . ' kcal', 'action' => 'trash' ]; continue; }
        if ( $st === 'draft' && $k <= 0 && ! $ho && ! $hu ) { $items[] = $b + [ 'name' => $t, 'type' => 'no_data', 'type_label' => 'Draft üres', 'reason' => 'Nincs adat', 'action' => 'trash' ]; }
    }
    return [ 'items' => $items, 'scanned' => $sc ];
}

function cleanup_scan_nutrition(): array {
    $items = [];
    $posts = cleanup_get_cached_posts( [ 'publish', 'draft', 'pending' ], 'ids' );
    $sc    = count( $posts );

    foreach ( $posts as $i => $pid ) {
        if ( $i % 500 === 0 ) { cleanup_progress_update( [ 'current' => $i ] ); cleanup_touch_lock(); }
        $t  = get_the_title( $pid );
        $k  = (float) get_post_meta( $pid, 'kaloria', true );
        $f  = (float) get_post_meta( $pid, 'feherje', true );
        $ch = (float) get_post_meta( $pid, 'szenhidrat', true );
        $zs = (float) get_post_meta( $pid, 'zsir', true );
        $cu = (float) get_post_meta( $pid, 'cukor', true );
        $ro = (float) get_post_meta( $pid, 'rost', true );
        $tz = (float) get_post_meta( $pid, 'telitett_zsir', true );
        $so = (float) get_post_meta( $pid, 'natrium', true );
        if ( $k <= 0 || ( $f <= 0 && $ch <= 0 && $zs <= 0 ) ) continue;
        $b = [ 'post_id' => $pid, 'name' => $t, 'kcal' => round( $k, 1 ), 'protein' => round( $f, 1 ), 'carb' => round( $ch, 1 ), 'fat' => round( $zs, 1 ), 'post_modified' => get_post_field( 'post_modified', $pid ) ];
        $mt = $f + $ch + $zs;
        if ( $mt > 100 ) { $items[] = $b + [ 'type' => 'macros_over_100', 'type_label' => 'Makró>100g', 'reason' => 'F+CH+Zs=' . round( $mt, 1 ) . 'g', 'action' => 'trash' ]; continue; }
        $aw = cleanup_atwater_check( $k, $f, $ch, $zs );
        if ( $aw ) { $items[] = $b + [ 'type' => 'atwater_mismatch', 'type_label' => 'Atwater', 'reason' => $aw['calculated'] . ' vs ' . $aw['actual'] . ' (' . $aw['diff_pct'] . '%)' . ( $aw['may_have_alcohol'] ? ' alk?' : '' ), 'action' => 'trash', 'atwater_calc' => $aw['calculated'], 'atwater_diff_pct' => $aw['diff_pct'], 'may_have_alcohol' => $aw['may_have_alcohol'] ]; }
        if ( $cu > 0 && $ch > 0 && $cu > $ch ) $items[] = $b + [ 'type' => 'sugar_exceeds_carb', 'type_label' => 'Cukor>CH', 'reason' => round( $cu, 1 ) . '>' . round( $ch, 1 ), 'action' => 'flag_review' ];
        if ( $tz > 0 && $zs > 0 && $tz > $zs ) $items[] = $b + [ 'type' => 'sat_fat_exceeds_fat', 'type_label' => 'Tzsír>Zsír', 'reason' => round( $tz, 1 ) . '>' . round( $zs, 1 ), 'action' => 'flag_review' ];
        if ( $ro > 0 && $ch > 0 && $ro > $ch ) $items[] = $b + [ 'type' => 'fiber_exceeds_carb', 'type_label' => 'Rost>CH', 'reason' => round( $ro, 1 ) . '>' . round( $ch, 1 ), 'action' => 'flag_review' ];
        if ( $so > CLEANUP_MAX_SALT_PER_100G ) $items[] = $b + [ 'type' => 'salt_too_high', 'type_label' => 'Só', 'reason' => round( $so, 1 ) . 'g (max ' . CLEANUP_MAX_SALT_PER_100G . ')', 'action' => 'trash' ];
    }
    return [ 'items' => $items, 'scanned' => $sc ];
}

function cleanup_scan_alcohol(): array {
    $items = [];
    $posts = cleanup_get_cached_posts();
    $sc    = count( $posts );
    foreach ( $posts as $i => $p ) {
        if ( $i % 500 === 0 ) { cleanup_progress_update( [ 'current' => $i ] ); cleanup_touch_lock(); }
        $t = $p->post_title;
        if ( trim( $t ) === '' ) continue;
        $m = cleanup_is_alcoholic( $t );
        if ( $m !== false ) {
            $items[] = [ 'post_id' => $p->ID, 'name' => $t, 'type' => 'alcohol', 'type_label' => 'Alkohol', 'reason' => '"' . $m . '" – ' . $p->post_status, 'action' => 'trash', 'alcohol_match' => $m, 'kcal' => (float) get_post_meta( $p->ID, 'kaloria', true ), 'protein' => (float) get_post_meta( $p->ID, 'feherje', true ), 'carb' => (float) get_post_meta( $p->ID, 'szenhidrat', true ), 'fat' => (float) get_post_meta( $p->ID, 'zsir', true ), 'post_modified' => $p->post_modified ];
        }
    }
    return [ 'items' => $items, 'scanned' => $sc ];
}

function cleanup_scan_duplicates(): array {
    $items = [];
    $posts = cleanup_get_cached_posts( [ 'publish' ] );
    $sc    = count( $posts );
    $al    = [];

    // Név
    $ng = [];
    foreach ( $posts as $p ) { $c = mb_strtolower( trim( $p->post_title ), 'UTF-8' ); if ( $c !== '' ) $ng[ $c ][] = [ 'id' => $p->ID, 'n' => $p->post_title, 'm' => $p->post_modified ]; }
    foreach ( $ng as $g ) {
        if ( count( $g ) < 2 ) continue;
        usort( $g, function( array $a, array $b ): int { return $a['id'] - $b['id']; } );
        $kp = array_shift( $g );
        foreach ( $g as $d ) { $al[ $d['id'] ] = 1; $items[] = [ 'post_id' => $d['id'], 'name' => $d['n'], 'type' => 'duplicate', 'type_label' => 'Név dup.', 'reason' => '"' . $kp['n'] . '" #' . $kp['id'], 'action' => 'merge', 'keep_id' => $kp['id'], 'duplicates' => [ [ 'id' => $kp['id'], 'name' => $kp['n'] ] ], 'post_modified' => $d['m'] ]; }
    }
    unset( $ng );

    // OFF
    $bg = [];
    foreach ( $posts as $p ) { $bc = (string) get_post_meta( $p->ID, 'off_barcode', true ); if ( $bc !== '' ) $bg[ $bc ][] = [ 'id' => $p->ID, 'n' => $p->post_title, 'm' => $p->post_modified ]; }
    foreach ( $bg as $bc => $g ) {
        if ( count( $g ) < 2 ) continue; $kp = $g[0];
        for ( $i = 1; $i < count( $g ); $i++ ) { if ( isset( $al[ $g[$i]['id'] ] ) ) continue; $al[ $g[$i]['id'] ] = 1; $items[] = [ 'post_id' => $g[$i]['id'], 'name' => $g[$i]['n'], 'type' => 'duplicate', 'type_label' => 'BC dup.', 'reason' => 'OFF:' . $bc . ' #' . $kp['id'], 'action' => 'merge', 'keep_id' => $kp['id'], 'duplicates' => [ [ 'id' => $kp['id'], 'name' => $kp['n'] ] ], 'post_modified' => $g[$i]['m'] ]; }
    }
    unset( $bg );

    // FDC
    $fg = [];
    foreach ( $posts as $p ) { $fd = (string) get_post_meta( $p->ID, 'usda_fdc_id', true ); if ( $fd !== '' ) $fg[ $fd ][] = [ 'id' => $p->ID, 'n' => $p->post_title, 'm' => $p->post_modified ]; }
    foreach ( $fg as $fd => $g ) {
        if ( count( $g ) < 2 ) continue; $kp = $g[0];
        for ( $i = 1; $i < count( $g ); $i++ ) { if ( isset( $al[ $g[$i]['id'] ] ) ) continue; $al[ $g[$i]['id'] ] = 1; $items[] = [ 'post_id' => $g[$i]['id'], 'name' => $g[$i]['n'], 'type' => 'duplicate', 'type_label' => 'FDC dup.', 'reason' => 'USDA:' . $fd . ' #' . $kp['id'], 'action' => 'merge', 'keep_id' => $kp['id'], 'duplicates' => [ [ 'id' => $kp['id'], 'name' => $kp['n'] ] ], 'post_modified' => $g[$i]['m'] ]; }
    }
    unset( $fg );

    // Fuzzy – optimalizált, usleep chunk
    cleanup_touch_lock();
    $pd = [];
    foreach ( $posts as $p ) {
        if ( isset( $al[ $p->ID ] ) ) continue;
        $cn = mb_strtolower( trim( $p->post_title ), 'UTF-8' );
        if ( $cn === '' ) continue;
        $pd[] = [ 'id' => $p->ID, 'n' => $p->post_title, 'c' => $cn, 'l' => mb_strlen( $cn, 'UTF-8' ), 'pf' => mb_substr( $cn, 0, 3, 'UTF-8' ), 'k' => (float) get_post_meta( $p->ID, 'kaloria', true ), 'm' => $p->post_modified ];
    }
    unset( $posts );

    usort( $pd, function( array $a, array $b ): int { return $a['l'] - $b['l']; } );
    $cnt = count( $pd );
    $fc  = 0;
    $fl  = CLEANUP_FUZZY_LIMIT;
    $se  = CLEANUP_FUZZY_SLEEP_EVERY;
    $su  = CLEANUP_FUZZY_SLEEP_US;

    for ( $i = 0; $i < $cnt && $fc < $fl; $i++ ) {
        if ( $i % 200 === 0 ) { cleanup_touch_lock(); cleanup_progress_update( [ 'phase' => 'Fuzzy ' . $i . '/' . $cnt ] ); }
        if ( isset( $al[ $pd[$i]['id'] ] ) ) continue;
        for ( $j = $i + 1; $j < $cnt && $fc < $fl; $j++ ) {
            if ( isset( $al[ $pd[$j]['id'] ] ) ) continue;
            if ( $pd[$j]['l'] > $pd[$i]['l'] * 1.35 ) break;
            if ( $pd[$i]['pf'] !== $pd[$j]['pf'] ) continue;
            $fc++;
            if ( $fc % $se === 0 ) { usleep( $su ); cleanup_touch_lock(); }
            similar_text( $pd[$i]['c'], $pd[$j]['c'], $pct );
            if ( $pct < 85 || $pd[$i]['k'] <= 0 || $pd[$j]['k'] <= 0 ) continue;
            $kd = abs( $pd[$i]['k'] - $pd[$j]['k'] ) / max( $pd[$i]['k'], $pd[$j]['k'] );
            if ( $kd > 0.15 ) continue;
            $al[ $pd[$j]['id'] ] = 1;
            $items[] = [ 'post_id' => $pd[$j]['id'], 'name' => $pd[$j]['n'], 'type' => 'duplicate', 'type_label' => 'Fuzzy(' . round( $pct ) . '%)', 'reason' => '"' . $pd[$i]['n'] . '" #' . $pd[$i]['id'] . ' Kcal:' . round( $kd * 100 ) . '%', 'action' => 'merge', 'keep_id' => $pd[$i]['id'], 'duplicates' => [ [ 'id' => $pd[$i]['id'], 'name' => $pd[$i]['n'] ] ], 'post_modified' => $pd[$j]['m'] ];
        }
    }
    if ( $fc >= $fl ) cleanup_log_add( '⚠️ Fuzzy limit (' . $fl . ')', 'warning' );
    return [ 'items' => $items, 'scanned' => $sc ];
}

function cleanup_scan_names(): array {
    $items = [];
    $posts = cleanup_get_cached_posts();
    $sc    = count( $posts );
    foreach ( $posts as $i => $p ) {
        if ( $i % 500 === 0 ) { cleanup_progress_update( [ 'current' => $i ] ); cleanup_touch_lock(); }
        $n = $p->post_title;
        if ( trim( $n ) === '' ) continue;
        $fx = cleanup_sanitize_name( $n );
        if ( $fx === $n ) continue;
        $rs = [];
        if ( preg_match( '/  +/', $n ) ) $rs[] = 'szóköz';
        if ( $n !== trim( $n ) ) $rs[] = 'trim';
        if ( $n !== html_entity_decode( $n, ENT_QUOTES, 'UTF-8' ) ) $rs[] = 'HTML';
        if ( preg_match( '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}]/u', $n ) ) $rs[] = 'emoji';
        if ( preg_match( '/[™®©℠℗]/u', $n ) ) $rs[] = '™®©';
        if ( preg_match( '/[\x{200B}-\x{200D}\x{FEFF}]/u', $n ) ) $rs[] = 'zero-width';
        if ( preg_match( '/[\x00-\x1F\x7F]/u', $n ) ) $rs[] = 'kontroll';
        if ( strpos( $n, ',,' ) !== false ) $rs[] = 'dupla vessző';
        if ( empty( $rs ) ) $rs[] = 'speciális';
        $items[] = [ 'post_id' => $p->ID, 'name' => $n, 'type' => 'name', 'type_label' => 'Név', 'reason' => implode( ', ', $rs ), 'action' => 'fix_name', 'fixed_name' => $fx, 'post_modified' => $p->post_modified ];
    }
    return [ 'items' => $items, 'scanned' => $sc ];
}

function cleanup_scan_orphan_meta(): array {
    global $wpdb;
    $items = [];
    $sc    = 0;
    $tp = get_posts( [ 'post_type' => 'alapanyag', 'posts_per_page' => -1, 'post_status' => 'trash', 'no_found_rows' => true, 'update_post_meta_cache' => true, 'update_post_term_cache' => false ] );
    $sc = count( $tp );
    foreach ( $tp as $p ) {
        $ho = ( (string) get_post_meta( $p->ID, 'off_barcode', true ) !== '' );
        $hu = ( (string) get_post_meta( $p->ID, 'usda_fdc_id', true ) !== '' );
        if ( $ho || $hu ) { $s = []; if ( $ho ) $s[] = 'OFF:' . get_post_meta( $p->ID, 'off_barcode', true ); if ( $hu ) $s[] = 'USDA:' . get_post_meta( $p->ID, 'usda_fdc_id', true ); $items[] = [ 'post_id' => $p->ID, 'name' => $p->post_title, 'type' => 'orphan_meta', 'type_label' => 'Kukás+meta', 'reason' => implode( ', ', $s ), 'action' => 'delete', 'post_modified' => $p->post_modified ]; }
    }
    foreach ( [ 'off_barcode', 'usda_fdc_id' ] as $mk ) {
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND (meta_value='' OR meta_value IS NULL)", $mk ) );
        foreach ( $rows as $r ) {
            $p = get_post( $r->post_id );
            if ( ! $p || $p->post_type !== 'alapanyag' ) continue;
            $items[] = [ 'post_id' => (int) $r->post_id, 'name' => $p->post_title, 'type' => 'orphan_meta', 'type_label' => 'Üres ' . $mk, 'reason' => $mk . ' üres', 'action' => 'delete_meta', 'meta_key' => $mk, 'post_modified' => $p->post_modified ];
            $sc++;
        }
    }
    return [ 'items' => $items, 'scanned' => $sc ];
}

function cleanup_scan_foreign(): array {
    $items = [];
    $posts = cleanup_get_cached_posts();
    $sc    = count( $posts );
    foreach ( $posts as $i => $p ) {
        if ( $i % 500 === 0 ) { cleanup_progress_update( [ 'current' => $i ] ); cleanup_touch_lock(); }
        $t = $p->post_title;
        if ( trim( $t ) === '' ) continue;
        $b = [ 'post_id' => $p->ID, 'name' => $t, 'kcal' => (float) get_post_meta( $p->ID, 'kaloria', true ), 'protein' => (float) get_post_meta( $p->ID, 'feherje', true ), 'carb' => (float) get_post_meta( $p->ID, 'szenhidrat', true ), 'fat' => (float) get_post_meta( $p->ID, 'zsir', true ), 'post_modified' => $p->post_modified ];
        if ( cleanup_has_cyrillic( $t ) ) { $items[] = $b + [ 'type' => 'cyrillic', 'type_label' => 'Cirill(SZIGORÚ)', 'reason' => $p->post_status, 'action' => 'trash', 'detected_script' => 'Cirill' ]; continue; }
        if ( cleanup_is_non_latin( $t ) ) { $sc_n = cleanup_detect_script( $t ); $items[] = $b + [ 'type' => 'foreign', 'type_label' => 'Idegen(' . $sc_n . ')', 'reason' => $p->post_status, 'action' => 'trash', 'detected_script' => $sc_n ]; }
    }
    return [ 'items' => $items, 'scanned' => $sc ];
}

function cleanup_scan_capitalize(): array {
    $items = [];
    $posts = cleanup_get_cached_posts( [ 'publish' ] );
    $sc    = count( $posts );
    foreach ( $posts as $i => $p ) {
        if ( $i % 500 === 0 ) { cleanup_progress_update( [ 'current' => $i ] ); cleanup_touch_lock(); }
        $t = $p->post_title;
        if ( trim( $t ) === '' || cleanup_has_cyrillic( $t ) || cleanup_is_non_latin( $t ) ) continue;
        if ( cleanup_is_lowercase_start( $t ) ) { $items[] = [ 'post_id' => $p->ID, 'name' => $t, 'type' => 'lowercase', 'type_label' => 'Kisbetű', 'reason' => 'UTF-8 safe', 'action' => 'capitalize', 'fixed_name' => cleanup_mb_ucfirst( trim( $t ) ), 'post_modified' => $p->post_modified ]; }
    }
    return [ 'items' => $items, 'scanned' => $sc ];
}


// ══════════════════════════════════════════════════════════════
// EXECUTE ITEM – közös motor
// ══════════════════════════════════════════════════════════════

function cleanup_execute_item( array $item, bool $auto = false ): array {
    $pid = (int) ( $item['post_id'] ?? 0 );
    $act = sanitize_key( $item['action'] ?? '' );
    $nm  = sanitize_text_field( $item['name'] ?? '?' );
    $lf  = $auto ? 'cleanup_auto_log' : 'cleanup_log_add';

    if ( ! $pid ) return [ 'stat' => 'errors', 'undo' => null ];
    $po = get_post( $pid );
    if ( ! $po || $po->post_type !== 'alapanyag' ) { $lf( '  ✖ #' . $pid . ' nem létezik', 'error' ); return [ 'stat' => 'errors', 'undo' => null ]; }
    if ( ! empty( $item['post_modified'] ) && $po->post_modified !== $item['post_modified'] ) { $lf( '  ⏭ #' . $pid . ' módosult', 'warning' ); return [ 'stat' => 'skipped', 'undo' => null ]; }

    switch ( $act ) {
        case 'delete':
            if ( wp_delete_post( $pid, true ) ) { $lf( '  🗑 #' . $pid . ' törölve', 'warning' ); return [ 'stat' => 'deleted', 'undo' => null ]; }
            return [ 'stat' => 'errors', 'undo' => null ];
        case 'trash':
            $os = $po->post_status;
            if ( wp_trash_post( $pid ) ) { $lf( '  🗑 #' . $pid . ' kuka', 'warning' ); return [ 'stat' => 'trashed', 'undo' => [ 'post_id' => $pid, 'old_status' => $os, 'name' => $nm ] ]; }
            return [ 'stat' => 'errors', 'undo' => null ];
        case 'fix_name':
            $fn = sanitize_text_field( $item['fixed_name'] ?? '' );
            if ( $fn === '' ) return [ 'stat' => 'errors', 'undo' => null ];
            wp_update_post( [ 'ID' => $pid, 'post_title' => $fn, 'post_name' => sanitize_title( $fn ) ] );
            $lf( '  🏷 #' . $pid . ' → "' . $fn . '"', 'success' );
            return [ 'stat' => 'fixed', 'undo' => null ];
        case 'capitalize':
            $fn = (string) ( $item['fixed_name'] ?? '' );
            if ( $fn === '' ) return [ 'stat' => 'errors', 'undo' => null ];
            wp_update_post( [ 'ID' => $pid, 'post_title' => $fn, 'post_name' => sanitize_title( $fn ) ] );
            $lf( '  🔠 #' . $pid . ' → "' . $fn . '"', 'success' );
            return [ 'stat' => 'capitalized', 'undo' => null ];
        case 'merge':
            $kid = (int) ( $item['keep_id'] ?? 0 );
            if ( ! $kid || ! get_post( $kid ) ) return [ 'stat' => 'errors', 'undo' => null ];
            cleanup_merge_data( $pid, $kid );
            $os = $po->post_status;
            wp_trash_post( $pid );
            $lf( '  🔀 #' . $pid . ' → #' . $kid, 'success' );
            return [ 'stat' => 'merged', 'undo' => [ 'post_id' => $pid, 'old_status' => $os, 'name' => $nm ] ];
        case 'delete_meta':
            $mk = sanitize_key( $item['meta_key'] ?? '' );
            if ( $mk === '' ) return [ 'stat' => 'errors', 'undo' => null ];
            delete_post_meta( $pid, $mk );
            delete_post_meta( $pid, '_' . $mk );
            $lf( '  🔗 #' . $pid . ' ' . $mk, 'info' );
            return [ 'stat' => 'meta_del', 'undo' => null ];
        case 'flag_review':
            wp_update_post( [ 'ID' => $pid, 'post_status' => 'draft' ] );
            update_post_meta( $pid, '_cleanup_flag', current_time( 'Y-m-d H:i:s' ) );
            update_post_meta( $pid, '_cleanup_flag_reason', sanitize_text_field( $item['reason'] ?? '' ) );
            $lf( '  ⚠️ #' . $pid . ' → draft', 'warning' );
            return [ 'stat' => 'flagged', 'undo' => [ 'post_id' => $pid, 'old_status' => 'publish', 'name' => $nm, 'was_flag' => true ] ];
        default:
            return [ 'stat' => 'errors', 'undo' => null ];
    }
}


// ══════════════════════════════════════════════════════════════
// AJAX: APPLY + UNDO + LOG + CSV + TRASH + STOP
// ══════════════════════════════════════════════════════════════

add_action( 'wp_ajax_cleanup_apply', function(): void {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    @set_time_limit( 300 );
    $items = json_decode( stripslashes( $_POST['items'] ?? '[]' ), true );
    if ( empty( $items ) || ! is_array( $items ) ) wp_send_json_error( 'Üres.' );
    $stats = [ 'deleted' => 0, 'trashed' => 0, 'fixed' => 0, 'capitalized' => 0, 'merged' => 0, 'meta_del' => 0, 'flagged' => 0, 'errors' => 0, 'skipped' => 0 ];
    $undo = [];
    cleanup_log_add( '▶ Végrehajtás: ' . count( $items ), 'info' );
    foreach ( $items as $it ) { $r = cleanup_execute_item( $it ); $stats[ $r['stat'] ]++; if ( $r['undo'] ) $undo[] = $r['undo']; }
    if ( $undo ) update_option( CLEANUP_LAST_BATCH_KEY, [ 'time' => current_time( 'Y-m-d H:i:s' ), 'items' => $undo ], 'no' );
    $s = []; foreach ( $stats as $k => $v ) if ( $v > 0 ) $s[] = $v . ' ' . $k;
    $str = implode( ', ', $s );
    cleanup_log_add( '✔ ' . $str, $stats['errors'] > 0 ? 'warning' : 'success' );
    wp_send_json_success( $str );
} );

add_action( 'wp_ajax_cleanup_undo', function(): void {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    $b = get_option( CLEANUP_LAST_BATCH_KEY, [] );
    if ( ! is_array( $b ) || empty( $b['items'] ) ) wp_send_json_error( 'Nincs.' );
    $ok = 0; $er = 0;
    cleanup_log_add( '↩️ Undo: ' . count( $b['items'] ), 'info' );
    foreach ( $b['items'] as $it ) {
        $pid = (int) ( $it['post_id'] ?? 0 );
        if ( ! $pid || ! get_post( $pid ) ) { $er++; continue; }
        wp_untrash_post( $pid );
        wp_update_post( [ 'ID' => $pid, 'post_status' => sanitize_key( $it['old_status'] ?? 'publish' ) ] );
        if ( ! empty( $it['was_flag'] ) ) { delete_post_meta( $pid, '_cleanup_flag' ); delete_post_meta( $pid, '_cleanup_flag_reason' ); }
        cleanup_log_add( '  ↩️ #' . $pid . ' → ' . ( $it['old_status'] ?? 'publish' ), 'success' );
        $ok++;
    }
    delete_option( CLEANUP_LAST_BATCH_KEY );
    wp_send_json_success( $ok . ' OK' . ( $er ? ', ' . $er . ' hiba' : '' ) );
} );

add_action( 'wp_ajax_cleanup_log_clear', function(): void {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( current_user_can( 'manage_options' ) ) cleanup_log_clear();
    wp_send_json_success( 'OK' );
} );

add_action( 'wp_ajax_cleanup_export_csv', function(): void {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Nincs jog.' );
    $type = sanitize_key( $_POST['type'] ?? 'log' );
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=cleanup_' . $type . '_' . gmdate( 'Y-m-d_His' ) . '.csv' );
    echo "\xEF\xBB\xBFIdőpont;Típus;Üzenet\n";
    $log = get_option( $type === 'auto_log' ? CLEANUP_AUTO_LOG_KEY : CLEANUP_LOG_KEY, [] );
    if ( is_array( $log ) ) {
        foreach ( $log as $e ) {
            echo '"' . esc_attr( $e['time'] ?? '' ) . '";"' . esc_attr( $e['type'] ?? '' ) . '";"' . str_replace( '"', '""', $e['msg'] ?? '' ) . '"' . "\n";
        }
    }
    wp_die();
} );

add_action( 'wp_ajax_cleanup_trash_preview', function(): void {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'X' );
    global $wpdb;
    $tp = get_posts( [ 'post_type' => 'alapanyag', 'posts_per_page' => -1, 'post_status' => 'trash', 'orderby' => 'ID', 'order' => 'DESC', 'no_found_rows' => true ] );
    $cnt = count( $tp ); $tm = 0; $tt = 0; $items = [];
    foreach ( $tp as $i => $p ) {
        $mc = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id=%d", $p->ID ) );
        $tm += $mc; if ( get_post_thumbnail_id( $p->ID ) ) $tt++;
        if ( $i < 100 ) $items[] = [ 'id' => $p->ID, 'title' => $p->post_title, 'source' => '-', 'meta' => $mc ];
    }
    wp_send_json_success( [ 'count' => $cnt, 'total_meta' => $tm, 'total_thumbs' => $tt, 'items' => $items ] );
} );

add_action( 'wp_ajax_cleanup_trash_empty', function(): void {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'X' );
    global $wpdb;
    $tp = get_posts( [ 'post_type' => 'alapanyag', 'posts_per_page' => 50, 'post_status' => 'trash', 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true ] );
    $d = 0;
    foreach ( $tp as $p ) {
        foreach ( get_posts( [ 'post_type' => 'attachment', 'posts_per_page' => -1, 'post_parent' => $p->ID, 'no_found_rows' => true ] ) as $a ) wp_delete_attachment( $a->ID, true );
        $tid = get_post_thumbnail_id( $p->ID );
        if ( $tid ) { $u = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='_thumbnail_id' AND meta_value=%s AND post_id!=%d", (string) $tid, $p->ID ) ); if ( ! $u ) wp_delete_attachment( $tid, true ); }
        $wpdb->delete( $wpdb->postmeta, [ 'post_id' => $p->ID ] );
        if ( wp_delete_post( $p->ID, true ) ) $d++;
    }
    $remaining = get_posts( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'trash', 'no_found_rows' => true ] );
    wp_send_json_success( [ 'deleted' => $d, 'has_more' => ! empty( $remaining ) ] );
} );


// ══════════════════════════════════════════════════════════════
// MERGE
// ══════════════════════════════════════════════════════════════

function cleanup_merge_data( int $from, int $to ): void {
    $uf = function_exists( 'update_field' );
    $to_off = (string) get_post_meta( $to, 'off_barcode', true );
    $fr_off = (string) get_post_meta( $from, 'off_barcode', true );
    if ( $to_off === '' && $fr_off !== '' ) {
        $uf ? update_field( 'off_barcode', $fr_off, $to ) : update_post_meta( $to, 'off_barcode', $fr_off );
        $ou = (string) get_post_meta( $from, 'off_url', true );
        if ( $ou !== '' ) { $uf ? update_field( 'off_url', $ou, $to ) : update_post_meta( $to, 'off_url', $ou ); }
    }
    $to_u = (string) get_post_meta( $to, 'usda_fdc_id', true );
    $fr_u = (string) get_post_meta( $from, 'usda_fdc_id', true );
    if ( $to_u === '' && $fr_u !== '' ) { $uf ? update_field( 'usda_fdc_id', $fr_u, $to ) : update_post_meta( $to, 'usda_fdc_id', $fr_u ); }
    if ( ! get_post_thumbnail_id( $to ) && get_post_thumbnail_id( $from ) ) set_post_thumbnail( $to, get_post_thumbnail_id( $from ) );
    $keys = ['kaloria','feherje','szenhidrat','zsir','rost','cukor','telitett_zsir','natrium','kalium','kalcium','vas','vitamin_c','vitamin_a','vitamin_d','vitamin_e','vitamin_k','vitamin_b1','vitamin_b2','vitamin_b3','vitamin_b5','vitamin_b6','vitamin_b9','vitamin_b12','magnezium','foszfor','cink','rez','mangan','szelen','koleszterin','egyszeresen_telitetlen_zsir','tobbszorosen_telitetlen_zsir'];
    foreach ( $keys as $k ) {
        $tv = (string) get_post_meta( $to, $k, true );
        $fv = (string) get_post_meta( $from, $k, true );
        if ( ( $tv === '' || (float) $tv == 0 ) && $fv !== '' && (float) $fv > 0 ) { $uf ? update_field( $k, $fv, $to ) : update_post_meta( $to, $k, $fv ); }
    }
}


// ══════════════════════════════════════════════════════════════
// STOP
// ══════════════════════════════════════════════════════════════

add_action( 'wp_ajax_cleanup_stop', function(): void {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'X' );
    cleanup_update_state( 'idle', '' );
    cleanup_release_lock();
    cleanup_progress_reset();
    cleanup_log_add( '⏹ Leállítva.', 'warning' );
    wp_send_json_success( 'OK' );
} );


// ══════════════════════════════════════════════════════════════
// ÉJFÉLI AUTO AUDIT
// ══════════════════════════════════════════════════════════════

add_action( CLEANUP_MIDNIGHT_HOOK, 'cleanup_run_auto_audit' );

add_action( 'wp_ajax_cleanup_auto_audit_now', function(): void {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jog.' );
    wp_send_json_success( cleanup_run_auto_audit() );
} );

function cleanup_run_auto_audit(): string {
    if ( cleanup_is_locked() ) { cleanup_auto_log( '⚠️ Lock aktív', 'warning' ); return 'Lock.'; }
    if ( ! cleanup_acquire_lock() ) { cleanup_auto_log( '⚠️ Lock fail', 'warning' ); return 'Lock fail.'; }
    @set_time_limit( 600 );

    cleanup_update_state( 'running', 'Auto audit' );
    cleanup_progress_update( [ 'status' => 'running', 'task' => 'auto', 'task_label' => '🕛 Auto', 'current' => 0, 'total' => 0, 'found' => 0, 'fixed' => 0, 'phase' => 'Scan...', 'is_auto' => true ] );
    cleanup_auto_log( '═══════════════════════════════', 'info' );
    cleanup_auto_log( '▶ Auto: ' . current_time( 'Y-m-d H:i:s' ), 'info' );

    $all_items = [];
    $total_sc  = 0;
    $tasks     = [ 'empty', 'nutrition', 'alcohol', 'duplicates', 'names', 'orphan_meta', 'foreign', 'capitalize' ];
    $ti = 0; $tt = count( $tasks );

    foreach ( $tasks as $st ) {
        $ti++; cleanup_touch_lock();
        cleanup_progress_update( [ 'phase' => $st . ' (' . $ti . '/' . $tt . ')' ] );
        $fn = 'cleanup_scan_' . $st;
        if ( function_exists( $fn ) ) {
            $r = $fn();
            $all_items = array_merge( $all_items, $r['items'] );
            $total_sc += $r['scanned'];
            cleanup_progress_update( [ 'current' => $total_sc, 'found' => count( $all_items ) ] );
            cleanup_auto_log( '  ✔ ' . $st . ': ' . count( $r['items'] ) . '/' . $r['scanned'], count( $r['items'] ) > 0 ? 'warning' : 'success' );
        }
    }
    cleanup_flush_post_cache();
    cleanup_auto_log( '📊 ' . $total_sc . ' / ' . count( $all_items ), count( $all_items ) > 0 ? 'warning' : 'success' );

    $stats = [ 'deleted' => 0, 'trashed' => 0, 'fixed' => 0, 'capitalized' => 0, 'merged' => 0, 'meta_del' => 0, 'flagged' => 0, 'errors' => 0, 'skipped' => 0 ];
    $undo_items = [];

    if ( ! empty( $all_items ) ) {
        cleanup_auto_log( '▶ Exec: ' . count( $all_items ), 'info' );
        foreach ( $all_items as $idx => $it ) {
            if ( $idx % 100 === 0 ) { cleanup_touch_lock(); cleanup_progress_update( [ 'phase' => 'Exec ' . $idx . '/' . count( $all_items ) ] ); }
            $r = cleanup_execute_item( $it, true );
            $stats[ $r['stat'] ]++;
            if ( $r['undo'] !== null ) $undo_items[] = $r['undo'];
        }
        if ( ! empty( $undo_items ) ) {
            update_option( CLEANUP_LAST_BATCH_KEY, [ 'time' => current_time( 'Y-m-d H:i:s' ) . ' (auto)', 'items' => $undo_items ], 'no' );
        }
    }

    $ta = array_sum( $stats ) - $stats['errors'] - $stats['skipped'];
    $sp = [];
    foreach ( $stats as $k => $v ) if ( $v > 0 ) $sp[] = $v . ' ' . $k;
    $ss = $sp ? implode( ', ', $sp ) : 'Nincs probléma';

    cleanup_auto_log( '✔ ' . $ss, $stats['errors'] > 0 ? 'warning' : 'success' );
    update_option( CLEANUP_AUTO_LAST_TIME, current_time( 'Y-m-d H:i:s' ), 'no' );
    update_option( CLEANUP_AUTO_LAST_COUNT, $ta, 'no' );

    if ( $ta >= CLEANUP_EMAIL_THRESHOLD ) cleanup_send_notification( $ta, $ss, $stats );

    cleanup_progress_update( [ 'status' => 'done', 'phase' => 'Kész', 'fixed' => $ta ] );
    cleanup_update_state( 'idle', '' );
    cleanup_release_lock();
    return $ss;
}


// ══════════════════════════════════════════════════════════════
// EMAIL (SMTP-aware)
// ══════════════════════════════════════════════════════════════

function cleanup_send_notification( int $count, string $summary, array $stats ): void {
    $email = get_option( 'admin_email' );
    if ( ! $email ) { cleanup_auto_log( '⚠️ Nincs admin email', 'error' ); return; }
    $site = get_bloginfo( 'name' );
    $body = "🧹 Cleanup Auto – " . current_time( 'Y-m-d H:i:s' ) . "\n\n" . $summary . "\n\n";
    foreach ( $stats as $k => $v ) if ( $v > 0 ) $body .= "  • " . $k . ": " . $v . "\n";
    $body .= "\n" . admin_url( 'edit.php?post_type=alapanyag&page=cleanup' ) . "\nUndo elérhető.\n";
    $sent = wp_mail( $email, '🧹 [' . $site . '] Cleanup: ' . $count . ' elem', $body );
    if ( $sent ) { cleanup_auto_log( '📧 OK: ' . $email, 'success' ); }
    else { cleanup_auto_log( '⚠️ Email BUKOTT – SMTP plugin kell!', 'error' ); update_option( 'cleanup_email_failed', current_time( 'Y-m-d H:i:s' ), 'no' ); }
}

add_action( 'admin_notices', function(): void {
    $f = get_option( 'cleanup_email_failed', '' );
    if ( ! $f || ! current_user_can( 'manage_options' ) ) return;
    $dismiss = wp_nonce_url( admin_url( 'edit.php?post_type=alapanyag&page=cleanup&cleanup_dismiss_email=1' ), 'cleanup_dismiss_email' );
    echo '<div class="notice notice-error is-dismissible"><p>🧹 <strong>Email hiba</strong> (' . esc_html( $f ) . '): SMTP plugin kell! <a href="' . esc_url( $dismiss ) . '">Elvetés</a></p></div>';
} );

add_action( 'admin_init', function(): void {
    if ( isset( $_GET['cleanup_dismiss_email'] ) && wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ?? '' ), 'cleanup_dismiss_email' ) && current_user_can( 'manage_options' ) ) {
        delete_option( 'cleanup_email_failed' );
        wp_safe_redirect( admin_url( 'edit.php?post_type=alapanyag&page=cleanup' ) );
        exit;
    }
} );


// ══════════════════════════════════════════════════════════════
// REST API (rate limited, API key + header)
// ══════════════════════════════════════════════════════════════

add_action( 'rest_api_init', function(): void {
    register_rest_route( 'cleanup/v1', '/status', [
        'methods'             => 'GET',
        'callback'            => 'cleanup_rest_status_handler',
        'permission_callback' => function(): bool {
            $ak = get_option( CLEANUP_REST_API_KEY_OPT, '' );
            if ( $ak !== '' ) {
                $p = sanitize_text_field( $_GET['api_key'] ?? '' );
                if ( $p === $ak ) return true;
                $h = sanitize_text_field( $_SERVER['HTTP_X_CLEANUP_API_KEY'] ?? '' );
                if ( $h === $ak ) return true;
            }
            return current_user_can( 'manage_options' );
        },
    ] );
} );

function cleanup_rest_status_handler(): WP_REST_Response {
    $ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? 'x' );
    $rk = 'cleanup_rl_' . md5( $ip );
    $rc = (int) get_transient( $rk );
    if ( $rc > CLEANUP_REST_RATE_LIMIT ) return new WP_REST_Response( [ 'error' => 'Rate limit' ], 429 );
    set_transient( $rk, $rc + 1, 60 );

    $st = cleanup_get_state();
    $pr = cleanup_progress_get();
    $pq = new WP_Query( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'publish', 'no_found_rows' => false ] );
    $pc = $pq->found_posts; wp_reset_postdata();
    $tq = new WP_Query( [ 'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'trash', 'no_found_rows' => false ] );
    $tc = $tq->found_posts; wp_reset_postdata();
    $nm = wp_next_scheduled( CLEANUP_MIDNIGHT_HOOK );
    $lb = get_option( CLEANUP_LAST_BATCH_KEY, [] );

    return new WP_REST_Response( [
        'version'    => CLEANUP_VERSION,
        'status'     => $st['status'] ?? 'idle',
        'is_running' => ( ( $st['status'] ?? '' ) === 'running' ),
        'progress'   => [ 'current' => (int) $pr['current'], 'total' => (int) $pr['total'], 'found' => (int) $pr['found'], 'phase' => $pr['phase'] ?? '', 'is_auto' => (bool) ( $pr['is_auto'] ?? false ) ],
        'auto_audit' => [ 'last_time' => get_option( CLEANUP_AUTO_LAST_TIME, '' ), 'last_count' => (int) get_option( CLEANUP_AUTO_LAST_COUNT, 0 ), 'next_run' => $nm ? gmdate( 'Y-m-d H:i:s', $nm ) : null ],
        'counts'     => [ 'publish' => $pc, 'trash' => $tc ],
        'can_undo'   => ( is_array( $lb ) && ! empty( $lb['items'] ) ),
        'timestamp'  => current_time( 'Y-m-d H:i:s' ),
    ], 200 );
}
