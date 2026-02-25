<?php

/**
 * 19 – ALAPANYAG TAKARÍTÁS - OFF import / szinkron után
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// CLEANUP PLUGIN v3 FINAL – TELJES ALAPANYAG KARBANTARTÁS
//
// Motor: Scan → Előnézet → Kijelölés → Jóváhagyás → Végrehajtás
// Admin: Alapanyagok → 🧹 Cleanup
//
// TÖRLÉSI SZABÁLYOK (SZIGORÚ):
//   1. Üres név
//   2. 0 kcal (bármilyen forrással)
//   3. Van kcal de MINDEN makró 0 (fehérje+szénhidrát+zsír = 0)
//   4. Semmi adata (nincs tápérték, nincs forrás)
//   5. Draft + nincs tápérték
//
// ÚJ FUNKCIÓK (v3):
//   6. Idegen nyelvű szűrés (cirill, kínai, arab, thai stb.)
//   7. Kis kezdőbetű → nagybetűsítés (UTF-8 safe, magyar ékezetek)
//   8. Lomtár ürítés (kukában lévő alapanyagok végleges törlése)
//
// BIZTONSÁG:
//   - Előnézet → Kijelölés → Jóváhagyás
//   - Kukába helyez (nem végleges törlés!) – kivéve lomtár ürítés
//   - Merge-nél adatok átmásolása
// ============================================================


// ══════════════════════════════════════════════════════════════
// KONSTANSOK
// ══════════════════════════════════════════════════════════════

if ( ! defined( 'CLEANUP_STATE_KEY' ) )   define( 'CLEANUP_STATE_KEY',   'cleanup_state_v2' );
if ( ! defined( 'CLEANUP_LOG_KEY' ) )     define( 'CLEANUP_LOG_KEY',     'cleanup_log_v2' );
if ( ! defined( 'CLEANUP_LASTRUN_KEY' ) ) define( 'CLEANUP_LASTRUN_KEY', 'cleanup_last_run_time' );
if ( ! defined( 'CLEANUP_CRON_HOOK' ) )   define( 'CLEANUP_CRON_HOOK',   'cleanup_cron_batch' );
if ( ! defined( 'CLEANUP_LOG_MAX' ) )     define( 'CLEANUP_LOG_MAX',     300 );


// ══════════════════════════════════════════════════════════════
// LOG SEGÉDFÜGGVÉNY
// ══════════════════════════════════════════════════════════════

function cleanup_log_add( $message, $type = 'info' ) {
    $log   = get_option( CLEANUP_LOG_KEY, [] );
    $log[] = [
        'time' => current_time( 'H:i:s' ),
        'msg'  => $message,
        'type' => $type, // info | success | warning | error
    ];
    if ( count( $log ) > CLEANUP_LOG_MAX ) {
        $log = array_slice( $log, -CLEANUP_LOG_MAX );
    }
    update_option( CLEANUP_LOG_KEY, $log );
}

function cleanup_log_clear() {
    update_option( CLEANUP_LOG_KEY, [] );
}


// ═════════════════════════════════════════════���════════════════
// HELPER: NEM-LATIN FELISMERÉS (v3 ÚJ)
// ══════════════════════════════════════════════════════════════

/**
 * Ellenőrzi, hogy egy string túlnyomórészt nem-latin karaktereket tartalmaz-e.
 * Felismeri: cirill (orosz, ukrán), kínai (CJK), japán, koreai, arab, thai, devanagari stb.
 *
 * @param string $title A vizsgálandó cím
 * @return bool true ha nem-latin (idegen nyelvű)
 */
function cleanup_is_non_latin( $title ) {
    // Eltávolítjuk: számok, szóközök, írásjelek, szimbólumok, ™®© stb.
    $clean = preg_replace( '/[\d\s\p{P}\p{S}]/u', '', $title );

    $len = mb_strlen( $clean, 'UTF-8' );
    if ( $len === 0 ) {
        return false; // Csak szám/írásjel/szóköz volt → nem idegen
    }

    // Latin betűk száma (beleértve magyar ékezetes: á, é, í, ó, ö, ő, ú, ü, ű)
    $latin_count = preg_match_all( '/\p{Latin}/u', $clean );

    // Ha a latin betűk aránya kevesebb mint 50% → idegen nyelvű
    return ( $latin_count / $len ) < 0.5;
}

/**
 * Meghatározza a domináns script nevét (logoláshoz).
 */
function cleanup_detect_script( $title ) {
    $clean = preg_replace( '/[\d\s\p{P}\p{S}]/u', '', $title );
    if ( mb_strlen( $clean, 'UTF-8' ) === 0 ) return 'ismeretlen';

    $scripts = [
        'Cirill'      => '/\p{Cyrillic}/u',
        'Kínai (CJK)' => '/\p{Han}/u',
        'Arab'        => '/\p{Arabic}/u',
        'Thai'        => '/\p{Thai}/u',
        'Devanagari'  => '/\p{Devanagari}/u',
        'Koreai'      => '/\p{Hangul}/u',
        'Japán (Katakana)' => '/\p{Katakana}/u',
        'Japán (Hiragana)' => '/\p{Hiragana}/u',
        'Grúz'        => '/\p{Georgian}/u',
        'Örmény'      => '/\p{Armenian}/u',
        'Héber'       => '/\p{Hebrew}/u',
        'Bengáli'     => '/\p{Bengali}/u',
        'Tamil'       => '/\p{Tamil}/u',
        'Telugu'      => '/\p{Telugu}/u',
        'Gujarati'    => '/\p{Gujarati}/u',
    ];

    $max_name  = 'egyéb nem-latin';
    $max_count = 0;

    foreach ( $scripts as $name => $pattern ) {
        $count = preg_match_all( $pattern, $clean );
        if ( $count > $max_count ) {
            $max_count = $count;
            $max_name  = $name;
        }
    }

    return $max_name;
}


// ══════════════════════════════════════════════════════════════
// HELPER: KISBETŰS KEZDET + UTF-8 UCFIRST (v3 ÚJ)
// ══════════════════════════════════════════════════════════════

/**
 * Ellenőrzi, hogy a title kisbetűvel kezdődik-e.
 * Figyelembe veszi a magyar ékezetes betűket is.
 */
function cleanup_is_lowercase_start( $title ) {
    $trimmed = trim( $title );
    if ( mb_strlen( $trimmed, 'UTF-8' ) === 0 ) return false;

    $first = mb_substr( $trimmed, 0, 1, 'UTF-8' );

    // Csak betűket vizsgálunk (nem szám, nem írásjel)
    if ( ! preg_match( '/\p{L}/u', $first ) ) {
        return false;
    }

    return $first !== mb_strtoupper( $first, 'UTF-8' );
}

/**
 * UTF-8 safe ucfirst – magyar ékezetes betűkkel is működik.
 * Pl. "almaliszt" → "Almaliszt", "édeskömény" → "Édeskömény", "őszibarack" → "Őszibarack"
 */
function cleanup_mb_ucfirst( $string ) {
    $first = mb_strtoupper( mb_substr( $string, 0, 1, 'UTF-8' ), 'UTF-8' );
    $rest  = mb_substr( $string, 1, null, 'UTF-8' );
    return $first . $rest;
}


// ══════════════════════════════════════════════════════════════
// 1. ADMIN MENÜ
// ══════════════════════════════════════════════════════════════

function cleanup_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=alapanyag',
        'Cleanup',
        '🧹 Cleanup',
        'manage_options',
        'cleanup',
        'cleanup_admin_page'
    );
}
add_action( 'admin_menu', 'cleanup_admin_menu' );


// ══════════════════════════════════════════════════════════════
// 2. ADMIN OLDAL HTML
// ══════════════════════════════════════════════════════════════

function cleanup_admin_page() {

    $total_q = new WP_Query( [
        'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'any',
    ] );
    $total_count = $total_q->found_posts;
    wp_reset_postdata();

    $published_q = new WP_Query( [
        'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'publish',
    ] );
    $published_count = $published_q->found_posts;
    wp_reset_postdata();

    $draft_q = new WP_Query( [
        'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'draft',
    ] );
    $draft_count = $draft_q->found_posts;
    wp_reset_postdata();

    $trash_q = new WP_Query( [
        'post_type' => 'alapanyag', 'posts_per_page' => 1, 'post_status' => 'trash',
    ] );
    $trash_count = $trash_q->found_posts;
    wp_reset_postdata();

    $state      = get_option( CLEANUP_STATE_KEY, [] );
    $is_running = ( ( $state['status'] ?? '' ) === 'running' );

    // Meglévő log betöltése megjelenítéshez
    $existing_log = get_option( CLEANUP_LOG_KEY, [] );

    ?>
    <div class="wrap">
        <h1>🧹 Cleanup v3 – Alapanyag karbantartás</h1>

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
                        (publish: <?php echo $published_count; ?>
                        | draft: <?php echo $draft_count; ?>
                        | kuka: <?php echo $trash_count; ?>)
                    </td>
                </tr>
                <tr>
                    <td><strong>Biztonság</strong></td>
                    <td>🛡️ Előnézet → Kijelölés → Jóváhagyás → Kukába (visszaállítható!)</td>
                </tr>

                <?php if ( $is_running ) : ?>
                    <tr>
                        <td><strong>Folyamat</strong></td>
                        <td>
                            <span style="color: #16a34a; font-weight: 700; animation: cleanupPulse 1.5s infinite;">
                                🟢 FUT
                            </span>
                            – <?php echo esc_html( $state['task_label'] ?? '' ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>

            <div style="margin-top: 16px; padding: 12px 16px; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; font-size: 0.88rem; max-width: 600px;">
                <strong>⚠️ Törlési / javítási szabályok:</strong>
                <ul style="margin: 6px 0 0 16px; line-height: 1.6;">
                    <li>🗑️ <strong>0 kalória</strong> – bármilyen forrással (OFF/USDA/kézi)</li>
                    <li>🗑️ <strong>Van kalória de minden makró 0</strong> – fehérje + szénhidrát + zsír = 0</li>
                    <li>🗑️ <strong>Üres név</strong> – nincs post_title</li>
                    <li>🗑️ <strong>Semmi adat</strong> – nincs tápérték, nincs forrás</li>
                    <li>🗑️ <strong>Draft + üres</strong> – piszkozat tápérték nélkül</li>
                    <li>🌐 <strong>Idegen nyelvű</strong> – orosz, kínai, arab, thai stb. (nem latin &lt;50%)</li>
                    <li>🔠 <strong>Kis kezdőbetű</strong> – nagybetűsítés (UTF-8 safe, magyar ékezetek)</li>
                    <li>🗑️ <strong>Lomtár ürítés</strong> – kukában lévő alapanyagok végleges törlése</li>
                </ul>
            </div>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── FELADATOK ───────────────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div style="
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            max-width: 900px;
            margin: 20px 0;
        ">
            <!-- ── ÜRES / HIÁNYOS ── -->
            <div style="background: #fef2f2; border: 2px solid #fca5a5; border-radius: 8px; padding: 18px 20px;">
                <h3 style="margin-top: 0; color: #dc2626; font-size: 1rem;">🗑️ Üres / hiányos alapanyagok</h3>
                <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                    Megkeresi azokat amelyeknek:
                </p>
                <ul style="font-size: 0.82rem; color: #555; margin: 4px 0 12px 16px; line-height: 1.5;">
                    <li>Üres név (nincs title)</li>
                    <li>0 kalória (bármilyen forrással!)</li>
                    <li>Van kalória de fehérje+szénhidrát+zsír = 0</li>
                    <li>Semmi adat (nincs tápérték + nincs forrás)</li>
                    <li>Draft + nincs hasznos adat</li>
                </ul>
                <button class="button cleanup-scan-btn" data-task="empty" style="font-size: 13px;"
                    <?php echo $is_running ? 'disabled' : ''; ?>>
                    🔍 Keresés
                </button>
            </div>

            <!-- ── DUPLIKÁCIÓK ── -->
            <div style="background: #fffbeb; border: 2px solid #fcd34d; border-radius: 8px; padding: 18px 20px;">
                <h3 style="margin-top: 0; color: #d97706; font-size: 1rem;">🔀 Duplikációk</h3>
                <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                    Megkeresi a duplikált alapanyagokat:
                </p>
                <ul style="font-size: 0.82rem; color: #555; margin: 4px 0 12px 16px; line-height: 1.5;">
                    <li>Pontos név egyezés</li>
                    <li>Azonos OFF vonalkód</li>
                    <li>Azonos USDA FDC ID</li>
                </ul>
                <button class="button cleanup-scan-btn" data-task="duplicates" style="font-size: 13px;"
                    <?php echo $is_running ? 'disabled' : ''; ?>>
                    🔍 Keresés
                </button>
            </div>

            <!-- ── NÉV TISZTÍTÁS ── -->
            <div style="background: #f0fdf4; border: 2px solid #86efac; border-radius: 8px; padding: 18px 20px;">
                <h3 style="margin-top: 0; color: #16a34a; font-size: 1rem;">🏷️ Név tisztítás</h3>
                <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                    Megkeresi a problémás neveket:
                </p>
                <ul style="font-size: 0.82rem; color: #555; margin: 4px 0 12px 16px; line-height: 1.5;">
                    <li>Dupla szóközök, vezető/záró szóközök</li>
                    <li>Felesleges írásjelek (vessző, pont a végén)</li>
                    <li>HTML entities, dupla vesszők</li>
                    <li>Nagyon hosszú nevek (>150 karakter)</li>
                </ul>
                <button class="button cleanup-scan-btn" data-task="names" style="font-size: 13px;"
                    <?php echo $is_running ? 'disabled' : ''; ?>>
                    🔍 Keresés
                </button>
            </div>

            <!-- ── ÁRVA META ── -->
            <div style="background: #f5f3ff; border: 2px solid #c4b5fd; border-radius: 8px; padding: 18px 20px;">
                <h3 style="margin-top: 0; color: #7c3aed; font-size: 1rem;">🔗 Árva meta adatok</h3>
                <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                    Megkeresi az elárvult meta bejegyzéseket:
                </p>
                <ul style="font-size: 0.82rem; color: #555; margin: 4px 0 12px 16px; line-height: 1.5;">
                    <li>Kukában lévő alapanyagok import adatai</li>
                    <li>Üres off_barcode / usda_fdc_id értékek</li>
                </ul>
                <button class="button cleanup-scan-btn" data-task="orphan_meta" style="font-size: 13px;"
                    <?php echo $is_running ? 'disabled' : ''; ?>>
                    🔍 Keresés
                </button>
            </div>

            <!-- ── v3 ÚJ: IDEGEN NYELVŰ ── -->
            <div style="background: #fef2f2; border: 2px solid #f9a8d4; border-radius: 8px; padding: 18px 20px;">
                <h3 style="margin-top: 0; color: #db2777; font-size: 1rem;">🌐 Idegen nyelvű alapanyagok</h3>
                <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                    Nem-latin karakteres nevek szűrése:
                </p>
                <ul style="font-size: 0.82rem; color: #555; margin: 4px 0 12px 16px; line-height: 1.5;">
                    <li>Cirill (orosz, ukrán, bolgár...)</li>
                    <li>Kínai (CJK), japán, koreai</li>
                    <li>Arab, héber, thai, hindi</li>
                    <li>Bármely nem-latin script (&lt;50% latin)</li>
                </ul>
                <button class="button cleanup-scan-btn" data-task="foreign" style="font-size: 13px;"
                    <?php echo $is_running ? 'disabled' : ''; ?>>
                    🔍 Keresés
                </button>
            </div>

            <!-- ── v3 ÚJ: NAGYBETŰSÍTÉS ── -->
            <div style="background: #eff6ff; border: 2px solid #93c5fd; border-radius: 8px; padding: 18px 20px;">
                <h3 style="margin-top: 0; color: #2563eb; font-size: 1rem;">🔠 Kis kezdőbetű javítás</h3>
                <p style="font-size: 0.82rem; color: #555; line-height: 1.4;">
                    Kisbetűs nevek nagybetűsítése:
                </p>
                <ul style="font-size: 0.82rem; color: #555; margin: 4px 0 12px 16px; line-height: 1.5;">
                    <li>UTF-8 safe (á→Á, é→É, ö→Ö, ü→Ü stb.)</li>
                    <li>Csak az első karakter változik</li>
                    <li>Slug is frissül automatikusan</li>
                </ul>
                <button class="button cleanup-scan-btn" data-task="capitalize" style="font-size: 13px;"
                    <?php echo $is_running ? 'disabled' : ''; ?>>
                    🔍 Keresés
                </button>
            </div>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── GOMBOK SOR ──────────────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div style="margin: 0 0 16px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; max-width: 900px;">
            <button id="cleanup-full-audit" class="button button-primary"
                    style="font-size: 14px; padding: 8px 24px;"
                    <?php echo $is_running ? 'disabled' : ''; ?>>
                📊 Teljes audit (minden ellenőrzés)
            </button>

            <!-- v3 ÚJ: LOMTÁR ÜRÍTÉS -->
            <button id="cleanup-empty-trash" class="button"
                    style="font-size: 14px; padding: 8px 20px; background: #dc2626; color: #fff; border-color: #b91c1c;"
                    <?php echo $is_running ? 'disabled' : ''; ?>
                    <?php echo $trash_count === 0 ? 'disabled title="Nincs elem a kukában"' : ''; ?>>
                🗑️ Lomtár ürítés (<?php echo $trash_count; ?> elem)
            </button>

            <?php if ( $is_running ) : ?>
                <button id="cleanup-stop" class="button button-secondary"
                        style="font-size: 14px; padding: 8px 20px;">
                    ⏹ Leállítás
                </button>
            <?php endif; ?>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── EREDMÉNYEK (ELŐNÉZET) ──────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div id="cleanup-results-section" style="
            display: none;
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 20px 0;
            max-width: 900px;
        ">
            <h3 id="cleanup-results-title" style="margin-top: 0;">🔍 Eredmények</h3>

            <div id="cleanup-results-summary" style="
                padding: 10px 14px;
                background: #f8fafc;
                border-radius: 6px;
                font-size: 0.88rem;
                margin-bottom: 12px;
            "></div>

            <div style="margin-bottom: 12px;">
                <label style="font-size: 0.88rem; cursor: pointer;">
                    <input type="checkbox" id="cleanup-select-all" checked>
                    Összes kijelölése
                </label>
            </div>

            <div id="cleanup-results-list" style="max-height: 500px; overflow-y: auto;"></div>

            <div style="margin-top: 16px; display: flex; gap: 8px; align-items: center;">
                <button id="cleanup-apply-btn" class="button"
                        style="font-size: 14px; padding: 6px 20px; background: #dc2626; color: #fff; border-color: #b91c1c;">
                    🗑️ Kijelöltek végrehajtása
                </button>
                <button id="cleanup-cancel-btn" class="button"
                        style="font-size: 14px; padding: 6px 20px;">
                    ❌ Mégsem
                </button>
                <span id="cleanup-apply-status" style="margin-left: 12px; font-size: 0.88rem;"></span>
            </div>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── LOMTÁR ÜRÍTÉS MEGERŐSÍTŐ PANEL (v3 ÚJ) ── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div id="cleanup-trash-section" style="
            display: none;
            background: #fff;
            border: 2px solid #fca5a5;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 20px 0;
            max-width: 900px;
        ">
            <h3 style="margin-top: 0; color: #dc2626;">🗑️ Lomtár ürítés – Megerősítés</h3>

            <div id="cleanup-trash-preview" style="
                padding: 10px 14px;
                background: #fef2f2;
                border-radius: 6px;
                font-size: 0.88rem;
                margin-bottom: 12px;
            "></div>

            <div id="cleanup-trash-list" style="max-height: 300px; overflow-y: auto; margin-bottom: 12px;"></div>

            <div style="padding: 12px 16px; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 6px; margin-bottom: 16px;">
                <strong style="color: #dc2626;">⚠️ FIGYELEM:</strong> Ez a művelet <strong>VÉGLEGES</strong>!
                A kukából törölt elemek <strong>NEM állíthatók vissza</strong>.
                Meta adatok, képek, minden törlődik!
            </div>

            <div style="display: flex; gap: 8px; align-items: center;">
                <button id="cleanup-trash-confirm" class="button"
                        style="font-size: 14px; padding: 6px 20px; background: #dc2626; color: #fff; border-color: #b91c1c;">
                    ⚠️ Végleges törlés – VISSZAVONHATATLAN
                </button>
                <button id="cleanup-trash-cancel" class="button"
                        style="font-size: 14px; padding: 6px 20px;">
                    ❌ Mégsem
                </button>
                <span id="cleanup-trash-status" style="margin-left: 12px; font-size: 0.88rem;"></span>
            </div>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── ÉLŐ STÁTUSZ ────────────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div id="cleanup-live-status" style="
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 20px 0;
            max-width: 900px;
        ">
            <h3 style="margin-top: 0;">
                📊 Scan eredmény
                <span id="cleanup-live-indicator"></span>
            </h3>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px;">
                <div style="text-align: center; padding: 8px; background: #f0f6fc; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700;" id="cleanup-s-scanned">–</div>
                    <div style="font-size: 0.72rem; color: #666;">Átvizsgálva</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #fef2f2; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #dc2626;" id="cleanup-s-found">–</div>
                    <div style="font-size: 0.72rem; color: #666;">Probléma</div>
                </div>
                <div style="text-align: center; padding: 8px; background: #f0fdf4; border-radius: 6px;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #16a34a;" id="cleanup-s-fixed">0</div>
                    <div style="font-size: 0.72rem; color: #666;">Javítva</div>
                </div>
            </div>

            <div id="cleanup-live-info" style="
                font-size: 0.85rem;
                color: #666;
                margin-bottom: 8px;
                padding: 6px 10px;
                background: #f8fafc;
                border-radius: 4px;
            "></div>
        </div>

        <?php // ═══════════════════════════════════════════════ ?>
        <?php // ── LOG PANEL ───────────────────────────────── ?>
        <?php // ═══════════════════════════════════════════════ ?>

        <div style="
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 20px 0;
            max-width: 900px;
        ">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <h3 style="margin: 0;">📋 Folyamat log</h3>
                <button id="cleanup-log-clear" class="button" style="font-size: 12px; padding: 4px 12px;">
                    🗑️ Log törlése
                </button>
            </div>

            <div id="cleanup-log-panel" style="
                background: #1e1e2e;
                border-radius: 6px;
                padding: 12px 14px;
                height: 280px;
                overflow-y: auto;
                font-family: 'Courier New', Courier, monospace;
                font-size: 0.78rem;
                line-height: 1.6;
                color: #cdd6f4;
            ">
                <?php if ( empty( $existing_log ) ) : ?>
                    <div style="color: #585b70; font-style: italic;">— Még nincs log bejegyzés. Indíts egy scant! —</div>
                <?php else : ?>
                    <?php foreach ( $existing_log as $entry ) :
                        $color = '#cdd6f4';
                        if ( $entry['type'] === 'success' ) $color = '#a6e3a1';
                        elseif ( $entry['type'] === 'warning' ) $color = '#f9e2af';
                        elseif ( $entry['type'] === 'error' )   $color = '#f38ba8';
                        elseif ( $entry['type'] === 'info' )    $color = '#89dceb';
                    ?>
                        <div style="color: <?php echo esc_attr( $color ); ?>;">
                            <span style="color: #585b70;">[<?php echo esc_html( $entry['time'] ); ?>]</span>
                            <?php echo esc_html( $entry['msg'] ); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <style>
        @keyframes cleanupPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
    </style>
    <?php
}


// ══════════════════════════════════════════════════════════════
// 3. ADMIN SCRIPTS (JS)
// ══════════════════════════════════════════════════════════════

function cleanup_admin_scripts( $hook ) {
    if ( $hook !== 'alapanyag_page_cleanup' ) return;

    $state = get_option( CLEANUP_STATE_KEY, [] );

    $js = <<<'JSEOF'
(function() {
    'use strict';

    var $ = document.getElementById.bind(document);
    var scanResult  = [];
    var currentTask = '';

    var taskLabels = {
        'empty':       '🗑️ Üres / hiányos alapanyagok',
        'duplicates':  '🔀 Duplikációk',
        'names':       '🏷️ Név problémák',
        'orphan_meta': '🔗 Árva meta',
        'foreign':     '🌐 Idegen nyelvű',
        'capitalize':  '🔠 Kis kezdőbetű',
        'full_audit':  '📊 Teljes audit'
    };

    var actionLabels = {
        'delete':       '🗑️ Véglegesen töröl',
        'merge':        '🔀 Összevonás (duplikátum kukába)',
        'fix_name':     '🏷️ Név javítás',
        'capitalize':   '🔠 Nagybetűsítés',
        'delete_meta':  '🔗 Meta törlés',
        'trash':        '🗑️ Kukába helyez'
    };

    // ══════════════════════════════════════════════════════
    // LOG SEGÉD
    // ══════════════════════════════════════════════════════

    function logAppend(msg, type) {
        var panel = $('cleanup-log-panel');
        if (!panel) return;

        var colors = {
            'info':    '#89dceb',
            'success': '#a6e3a1',
            'warning': '#f9e2af',
            'error':   '#f38ba8',
            'default': '#cdd6f4'
        };
        var color = colors[type] || colors['default'];

        var now = new Date();
        var hh  = String(now.getHours()).padStart(2, '0');
        var mm  = String(now.getMinutes()).padStart(2, '0');
        var ss  = String(now.getSeconds()).padStart(2, '0');
        var ts  = hh + ':' + mm + ':' + ss;

        // Ha az első bejegyzés a placeholder, töröljük
        var placeholder = panel.querySelector('div[style*="font-style: italic"]');
        if (placeholder) placeholder.remove();

        var line = document.createElement('div');
        line.style.color = color;
        line.innerHTML = '<span style="color:#585b70;">[' + ts + ']</span> ' + escHtml(msg);
        panel.appendChild(line);
        panel.scrollTop = panel.scrollHeight;
    }

    // Log törlés gomb
    $('cleanup-log-clear').addEventListener('click', function() {
        var panel = $('cleanup-log-panel');
        panel.innerHTML = '<div style="color:#585b70;font-style:italic;">— Log törölve. —</div>';

        var fd = new FormData();
        fd.append('action', 'cleanup_log_clear');
        fd.append('nonce', cleanupData.nonce);
        fetch(cleanupData.ajaxUrl, { method: 'POST', body: fd });
    });

    // ══════════════════════════════════════════════════════
    // SCAN
    // ══════════════════════════════════════════════════════

    document.querySelectorAll('.cleanup-scan-btn').forEach(function(btn) {
        btn.addEventListener('click', function() { runScan(this.dataset.task); });
    });

    $('cleanup-full-audit').addEventListener('click', function() { runScan('full_audit'); });

    function disableButtons() {
        document.querySelectorAll('.cleanup-scan-btn').forEach(function(b) { b.disabled = true; });
        $('cleanup-full-audit').disabled = true;
        if ($('cleanup-empty-trash')) $('cleanup-empty-trash').disabled = true;
    }

    function enableButtons() {
        document.querySelectorAll('.cleanup-scan-btn').forEach(function(b) { b.disabled = false; });
        $('cleanup-full-audit').disabled = false;
        if ($('cleanup-empty-trash')) $('cleanup-empty-trash').disabled = false;
    }

    function runScan(task) {
        currentTask = task;
        disableButtons();
        $('cleanup-results-section').style.display = 'none';
        $('cleanup-trash-section').style.display = 'none';

        $('cleanup-live-info').innerHTML = '⏳ Keresés: <strong>' + taskLabels[task] + '</strong>...';
        $('cleanup-s-scanned').textContent = '...';
        $('cleanup-s-found').textContent = '...';
        $('cleanup-s-fixed').textContent = '0';

        logAppend('▶ Scan indítva: ' + taskLabels[task], 'info');

        var fd = new FormData();
        fd.append('action', 'cleanup_scan');
        fd.append('nonce', cleanupData.nonce);
        fd.append('task', task);

        fetch(cleanupData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                enableButtons();

                if (!resp.success) {
                    logAppend('✖ Scan hiba: ' + resp.data, 'error');
                    $('cleanup-live-info').innerHTML = '<span style="color:#dc2626;">❌ ' + resp.data + '</span>';
                    return;
                }

                scanResult = resp.data.items || [];
                var scanned = resp.data.scanned || 0;

                $('cleanup-s-scanned').textContent = scanned;
                $('cleanup-s-found').textContent = scanResult.length;

                logAppend('✔ Scan kész: ' + scanned + ' db átvizsgálva', 'success');

                if (scanResult.length === 0) {
                    logAppend('✅ Nincs probléma – minden rendben!', 'success');
                    $('cleanup-live-info').innerHTML = '<span style="color:#16a34a;font-weight:600;">✅ Nincs probléma!</span>';
                    return;
                }

                logAppend('⚠ ' + scanResult.length + ' probléma találva:', 'warning');

                // Csoportonként megszámolja és loggolja
                var counts = {};
                scanResult.forEach(function(item) {
                    counts[item.type_label] = (counts[item.type_label] || 0) + 1;
                });
                Object.keys(counts).forEach(function(label) {
                    logAppend('   • ' + label + ': ' + counts[label] + ' db', 'warning');
                });

                // Részletes lista logba (max 30)
                var limit = Math.min(scanResult.length, 30);
                for (var i = 0; i < limit; i++) {
                    var it = scanResult[i];
                    logAppend(
                        '   [#' + (it.post_id || '?') + '] ' + (it.name || 'Névtelen') +
                        ' → ' + (it.type_label || it.type) + ' | ' + (it.reason || ''),
                        'default'
                    );
                }
                if (scanResult.length > 30) {
                    logAppend('   ... és még ' + (scanResult.length - 30) + ' további (lásd az eredmény listában)', 'default');
                }

                $('cleanup-live-info').innerHTML = '<span style="color:#dc2626;font-weight:600;">⚠️ ' + scanResult.length + ' probléma találva – ellenőrizd lent ↓</span>';
                showResults(task, scanResult, scanned);
            })
            .catch(function(err) {
                enableButtons();
                logAppend('✖ Hálózati hiba a scan során: ' + (err.message || err), 'error');
                $('cleanup-live-info').innerHTML = '<span style="color:#dc2626;">❌ Hálózati hiba</span>';
            });
    }

    // ══════════════════════════════════════════════════════
    // EREDMÉNYEK
    // ══════════════════════════════════════════════════════

    function showResults(task, items, scanned) {
        $('cleanup-results-section').style.display = 'block';
        $('cleanup-results-title').innerHTML = taskLabels[task] + ' – <span style="color:#dc2626;">' + items.length + ' probléma</span>';
        $('cleanup-results-summary').innerHTML =
            'Átvizsgálva: <strong>' + scanned + '</strong> | ' +
            'Probléma: <strong style="color:#dc2626;">' + items.length + '</strong> | ' +
            'Jelöld ki mit szeretnél végrehajtani ↓';

        var html = '';
        items.forEach(function(item, idx) {
            var borderColors = {
                empty:'#fca5a5', zero_kcal:'#fca5a5', zero_macros:'#fca5a5', no_data:'#fca5a5',
                duplicate:'#fcd34d', name:'#86efac', orphan_meta:'#c4b5fd',
                foreign:'#f9a8d4', lowercase:'#93c5fd'
            };
            var bc = borderColors[item.type] || '#e5e7eb';
            var typeColors = {
                empty:'#dc2626', zero_kcal:'#dc2626', zero_macros:'#dc2626', no_data:'#dc2626',
                duplicate:'#d97706', name:'#16a34a', orphan_meta:'#7c3aed',
                foreign:'#db2777', lowercase:'#2563eb'
            };
            var tc = typeColors[item.type] || '#666';

            html += '<div style="border:1px solid ' + bc + ';border-left:4px solid ' + bc + ';border-radius:6px;margin-bottom:6px;padding:10px 14px;">';
            html += '<label style="display:flex;align-items:start;gap:10px;cursor:pointer;">';
            html += '<input type="checkbox" class="cleanup-item-cb" data-index="' + idx + '" checked style="margin-top:4px;">';
            html += '<div style="flex:1;min-width:0;">';
            html += '<strong>' + escHtml(item.name || 'Névtelen') + '</strong>';
            html += ' <span style="font-size:0.75rem;color:#94a3b8;">#' + (item.post_id || '?') + '</span>';
            html += ' <span style="font-size:0.72rem;padding:1px 6px;background:' + tc + '18;color:' + tc + ';border-radius:3px;">' + escHtml(item.type_label) + '</span>';

            if (item.kcal !== undefined) {
                html += '<div style="font-size:0.78rem;color:#94a3b8;margin-top:2px;">';
                html += '🔥 ' + item.kcal + ' kcal | 💪 ' + (item.protein || 0) + 'g | 🌾 ' + (item.carb || 0) + 'g | 🫒 ' + (item.fat || 0) + 'g';
                html += '</div>';
            }

            // Detected script megjelenítése idegen nyelveknél
            if (item.detected_script) {
                html += '<div style="font-size:0.78rem;color:#db2777;margin-top:2px;">🌐 Script: <strong>' + escHtml(item.detected_script) + '</strong></div>';
            }

            html += '<div style="font-size:0.82rem;color:#555;margin-top:4px;">' + escHtml(item.reason) + '</div>';
            html += '<div style="font-size:0.78rem;color:#666;margin-top:2px;">Művelet: <strong>' + (actionLabels[item.action] || item.action) + '</strong></div>';

            if (item.duplicates && item.duplicates.length > 0) {
                html += '<div style="font-size:0.78rem;color:#d97706;margin-top:4px;">Duplikátumok: ';
                item.duplicates.forEach(function(dup, di) { if (di > 0) html += ', '; html += '#' + dup.id; });
                html += '</div>';
            }

            if (item.fixed_name) {
                html += '<div style="font-size:0.82rem;margin-top:4px;">';
                html += '<span style="color:#dc2626;text-decoration:line-through;">' + escHtml(item.name) + '</span>';
                html += ' → <span style="color:#16a34a;font-weight:600;">' + escHtml(item.fixed_name) + '</span>';
                html += '</div>';
            }

            html += '</div></label></div>';
        });

        $('cleanup-results-list').innerHTML = html;
        $('cleanup-select-all').checked = true;

        $('cleanup-results-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ══════════════════════════════════════════════════════
    // KIJELÖLÉS
    // ══════════════════════════════════════════════════════

    $('cleanup-select-all').addEventListener('change', function() {
        var c = this.checked;
        document.querySelectorAll('.cleanup-item-cb').forEach(function(cb) { cb.checked = c; });
    });

    // ══════════════════════════════════════════════════════
    // VÉGREHAJTÁS
    // ══════════════════════════════════════════════════════

    $('cleanup-apply-btn').addEventListener('click', function() {
        var selected = [];
        document.querySelectorAll('.cleanup-item-cb:checked').forEach(function(cb) {
            selected.push(scanResult[parseInt(cb.dataset.index)]);
        });

        if (selected.length === 0) {
            $('cleanup-apply-status').textContent = '⚠️ Jelölj ki legalább egyet!';
            $('cleanup-apply-status').style.color = '#d97706';
            return;
        }

        var msg = '⚠️ FIGYELEM!\n\n' + selected.length + ' művelet:\n\n';
        var dels   = selected.filter(function(s) { return s.action==='delete'||s.action==='trash'; }).length;
        var fixes  = selected.filter(function(s) { return s.action==='fix_name'; }).length;
        var caps   = selected.filter(function(s) { return s.action==='capitalize'; }).length;
        var merges = selected.filter(function(s) { return s.action==='merge'; }).length;
        var metas  = selected.filter(function(s) { return s.action==='delete_meta'; }).length;
        if (dels > 0)   msg += '🗑️ ' + dels + ' törlés/kukázás\n';
        if (fixes > 0)  msg += '🏷️ ' + fixes + ' név javítás\n';
        if (caps > 0)   msg += '🔠 ' + caps + ' nagybetűsítés\n';
        if (merges > 0) msg += '🔀 ' + merges + ' összevonás\n';
        if (metas > 0)  msg += '🔗 ' + metas + ' meta törlés\n';
        msg += '\nBiztosan folytatod?';

        if (!confirm(msg)) return;

        var btn = this, status = $('cleanup-apply-status');
        btn.disabled = true;
        status.textContent = '⏳ Végrehajtás... (' + selected.length + ' művelet)';
        status.style.color = '#666';

        logAppend('▶ Végrehajtás indítva: ' + selected.length + ' kijelölt elem', 'info');
        if (dels > 0)   logAppend('  → ' + dels + ' kukázás/törlés', 'warning');
        if (fixes > 0)  logAppend('  → ' + fixes + ' név javítás', 'info');
        if (caps > 0)   logAppend('  → ' + caps + ' nagybetűsítés', 'info');
        if (merges > 0) logAppend('  → ' + merges + ' összevonás (merge)', 'info');
        if (metas > 0)  logAppend('  → ' + metas + ' meta törlés', 'info');

        // Részletes lista a végrehajtandó elemekről
        var applyLimit = Math.min(selected.length, 20);
        for (var i = 0; i < applyLimit; i++) {
            var it = selected[i];
            logAppend(
                '  [#' + (it.post_id || '?') + '] ' + (it.name || 'Névtelen') +
                ' → ' + (actionLabels[it.action] || it.action),
                'default'
            );
        }
        if (selected.length > 20) {
            logAppend('  ... és még ' + (selected.length - 20) + ' további elem', 'default');
        }

        var fd = new FormData();
        fd.append('action', 'cleanup_apply');
        fd.append('nonce', cleanupData.nonce);
        fd.append('items', JSON.stringify(selected));

        fetch(cleanupData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                btn.disabled = false;
                if (resp.success) {
                    logAppend('✔ Végrehajtás sikeres: ' + resp.data, 'success');
                    status.innerHTML = '<span style="color:#16a34a;">✅ ' + resp.data + '</span>';
                    $('cleanup-s-fixed').textContent = resp.data;
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    logAppend('✖ Végrehajtás hiba: ' + resp.data, 'error');
                    status.innerHTML = '<span style="color:#dc2626;">❌ ' + resp.data + '</span>';
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                logAppend('✖ Hálózati hiba a végrehajtás során: ' + (err.message || err), 'error');
                status.textContent = '❌ Hálózati hiba';
                status.style.color = '#dc2626';
            });
    });

    $('cleanup-cancel-btn').addEventListener('click', function() {
        $('cleanup-results-section').style.display = 'none';
        logAppend('✖ Végrehajtás visszavonva – eredmények eldobva', 'warning');
        scanResult = [];
    });

    // ══════════════════════════════════════════════════════
    // LOMTÁR ÜRÍTÉS (v3 ÚJ)
    // ══════════════════════════════════════════════════════

    if ($('cleanup-empty-trash')) {
        $('cleanup-empty-trash').addEventListener('click', function() {
            $('cleanup-results-section').style.display = 'none';
            $('cleanup-trash-section').style.display = 'block';
            $('cleanup-trash-status').textContent = '';

            logAppend('🗑️ Lomtár előnézet betöltése...', 'info');

            var fd = new FormData();
            fd.append('action', 'cleanup_trash_preview');
            fd.append('nonce', cleanupData.nonce);

            fetch(cleanupData.ajaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(resp) {
                    if (!resp.success) {
                        $('cleanup-trash-preview').innerHTML = '<span style="color:#dc2626;">❌ ' + resp.data + '</span>';
                        logAppend('✖ Lomtár előnézet hiba: ' + resp.data, 'error');
                        return;
                    }

                    var data = resp.data;
                    $('cleanup-trash-preview').innerHTML =
                        '<strong style="color:#dc2626;">🗑️ ' + data.count + ' elem a kukában</strong>' +
                        (data.total_meta ? ' | <span style="color:#7c3aed;">' + data.total_meta + ' meta bejegyzés</span>' : '') +
                        (data.total_thumbs ? ' | <span style="color:#d97706;">' + data.total_thumbs + ' kiemelt kép</span>' : '');

                    var html = '';
                    data.items.forEach(function(item) {
                        html += '<div style="font-size:0.82rem;padding:4px 8px;border-bottom:1px solid #fee2e2;">';
                        html += '<strong>#' + item.id + '</strong> – ' + escHtml(item.title || 'Névtelen');
                        if (item.source) html += ' <span style="color:#94a3b8;font-size:0.72rem;">(' + escHtml(item.source) + ')</span>';
                        html += '</div>';
                    });
                    if (data.count > data.items.length) {
                        html += '<div style="font-size:0.78rem;color:#94a3b8;padding:6px 8px;">... és még ' + (data.count - data.items.length) + ' további elem</div>';
                    }
                    $('cleanup-trash-list').innerHTML = html;

                    logAppend('🗑️ Lomtár: ' + data.count + ' elem előnézetben', 'warning');

                    $('cleanup-trash-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
                })
                .catch(function(err) {
                    logAppend('✖ Lomtár előnézet hálózati hiba: ' + (err.message || err), 'error');
                });
        });
    }

    if ($('cleanup-trash-cancel')) {
        $('cleanup-trash-cancel').addEventListener('click', function() {
            $('cleanup-trash-section').style.display = 'none';
            logAppend('✖ Lomtár ürítés visszavonva', 'warning');
        });
    }

    if ($('cleanup-trash-confirm')) {
        $('cleanup-trash-confirm').addEventListener('click', function() {
            if (!confirm('⚠️ UTOLSÓ FIGYELMEZTETÉS!\n\nEz VÉGLEGESEN töröl minden alapanyagot a kukából.\nMeta adatok, képek, minden eltűnik.\n\nBIZTOS VAGY BENNE?')) return;

            var btn = this, status = $('cleanup-trash-status');
            btn.disabled = true;
            status.textContent = '⏳ Törlés folyamatban...';
            status.style.color = '#666';

            logAppend('⚠️ Lomtár végleges ürítés elindítva!', 'warning');

            runTrashBatch(0, 0);
        });
    }

    function runTrashBatch(offset, totalDeleted) {
        var fd = new FormData();
        fd.append('action', 'cleanup_trash_empty');
        fd.append('nonce', cleanupData.nonce);
        fd.append('offset', offset);

        fetch(cleanupData.ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (!resp.success) {
                    logAppend('✖ Lomtár törlés hiba: ' + resp.data, 'error');
                    $('cleanup-trash-status').innerHTML = '<span style="color:#dc2626;">❌ ' + resp.data + '</span>';
                    $('cleanup-trash-confirm').disabled = false;
                    return;
                }

                var data = resp.data;
                totalDeleted += data.deleted;

                logAppend('  🗑️ Batch: ' + data.deleted + ' törölve (összesen: ' + totalDeleted + ')', 'warning');
                $('cleanup-trash-status').textContent = '⏳ ' + totalDeleted + ' törölve...';

                     if (data.has_more) {

                        runTrashBatch(data.next_offset, totalDeleted);
                    } else {
                        logAppend('✔ Lomtár ürítés kész! Összesen ' + totalDeleted + ' elem véglegesen törölve.', 'success');
                        $('cleanup-trash-status').innerHTML = '<span style="color:#16a34a;">✅ ' + totalDeleted + ' elem véglegesen törölve!</span>';
                        $('cleanup-trash-confirm').disabled = false;
                        $('cleanup-s-fixed').textContent = totalDeleted;
                        setTimeout(function() { location.reload(); }, 2500);
                    }
                })
                .catch(function(err) {
                    logAppend('✖ Lomtár törlés hálózati hiba: ' + (err.message || err), 'error');
                    $('cleanup-trash-status').textContent = '❌ Hálózati hiba';
                    $('cleanup-trash-confirm').disabled = false;
                });
    }

    // ══════════════════════════════════════════════════════
    // LEÁLLÍTÁS
    // ══════════════════════════════════════════════════════

    if ($('cleanup-stop')) {
        $('cleanup-stop').addEventListener('click', function() {
            this.disabled = true; this.textContent = '⏳...';
            logAppend('⏹ Leállítás kérve...', 'warning');
            var fd = new FormData();
            fd.append('action', 'cleanup_stop'); fd.append('nonce', cleanupData.nonce);
            fetch(cleanupData.ajaxUrl, { method: 'POST', body: fd })
                .then(function() {
                    logAppend('⏹ Folyamat leállítva.', 'warning');
                    location.reload();
                });
        });
    }

    function escHtml(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
})();
JSEOF;

    wp_register_script( 'cleanup-js', false, [], '3.0', true );
    wp_enqueue_script( 'cleanup-js' );
    wp_add_inline_script( 'cleanup-js', $js );

    wp_localize_script( 'cleanup-js', 'cleanupData', [
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'cleanup_nonce' ),
        'isRunning' => ( ( $state['status'] ?? '' ) === 'running' ),
    ] );
}
add_action( 'admin_enqueue_scripts', 'cleanup_admin_scripts' );


// ══════════════════════════════════════════════════════════════
// 4. AJAX: SCAN
// ══════════════════════════════════════════════════════════════

function cleanup_scan_handler() {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $task = sanitize_key( $_POST['task'] ?? '' );
    if ( empty( $task ) ) wp_send_json_error( 'Nincs feladat.' );

    cleanup_log_add( '▶ Scan elindult: ' . $task, 'info' );

    $items   = [];
    $scanned = 0;

    if ( $task === 'empty' || $task === 'full_audit' ) {
        cleanup_log_add( '  → Üres / hiányos alapanyagok keresése...', 'info' );
        $r = cleanup_scan_empty();
        $items   = array_merge( $items, $r['items'] );
        $scanned += $r['scanned'];
        cleanup_log_add( '  ✔ Üres scan kész: ' . $r['scanned'] . ' vizsgált, ' . count( $r['items'] ) . ' probléma', count( $r['items'] ) > 0 ? 'warning' : 'success' );
    }
    if ( $task === 'duplicates' || $task === 'full_audit' ) {
        cleanup_log_add( '  → Duplikációk keresése...', 'info' );
        $r = cleanup_scan_duplicates();
        $items   = array_merge( $items, $r['items'] );
        $scanned += $r['scanned'];
        cleanup_log_add( '  ✔ Duplikáció scan kész: ' . $r['scanned'] . ' vizsgált, ' . count( $r['items'] ) . ' probléma', count( $r['items'] ) > 0 ? 'warning' : 'success' );
    }
    if ( $task === 'names' || $task === 'full_audit' ) {
        cleanup_log_add( '  → Név problémák keresése...', 'info' );
        $r = cleanup_scan_names();
        $items   = array_merge( $items, $r['items'] );
        $scanned += $r['scanned'];
        cleanup_log_add( '  ✔ Név scan kész: ' . $r['scanned'] . ' vizsgált, ' . count( $r['items'] ) . ' probléma', count( $r['items'] ) > 0 ? 'warning' : 'success' );
    }
    if ( $task === 'orphan_meta' || $task === 'full_audit' ) {
        cleanup_log_add( '  → Árva meta adatok keresése...', 'info' );
        $r = cleanup_scan_orphan_meta();
        $items   = array_merge( $items, $r['items'] );
        $scanned += $r['scanned'];
        cleanup_log_add( '  ✔ Árva meta scan kész: ' . $r['scanned'] . ' vizsgált, ' . count( $r['items'] ) . ' probléma', count( $r['items'] ) > 0 ? 'warning' : 'success' );
    }

    // ── v3 ÚJ: IDEGEN NYELVŰ ──
    if ( $task === 'foreign' || $task === 'full_audit' ) {
        cleanup_log_add( '  → Idegen nyelvű alapanyagok keresése...', 'info' );
        $r = cleanup_scan_foreign();
        $items   = array_merge( $items, $r['items'] );
        $scanned += $r['scanned'];
        cleanup_log_add( '  ✔ Idegen nyelvű scan kész: ' . $r['scanned'] . ' vizsgált, ' . count( $r['items'] ) . ' probléma', count( $r['items'] ) > 0 ? 'warning' : 'success' );
    }

    // ── v3 ÚJ: NAGYBETŰSÍTÉS ──
    if ( $task === 'capitalize' || $task === 'full_audit' ) {
        cleanup_log_add( '  → Kis kezdőbetűs nevek keresése...', 'info' );
        $r = cleanup_scan_capitalize();
        $items   = array_merge( $items, $r['items'] );
        $scanned += $r['scanned'];
        cleanup_log_add( '  ✔ Nagybetűsítés scan kész: ' . $r['scanned'] . ' vizsgált, ' . count( $r['items'] ) . ' probléma', count( $r['items'] ) > 0 ? 'warning' : 'success' );
    }

    cleanup_log_add( '✔ Scan befejezve: összesen ' . $scanned . ' db, ' . count( $items ) . ' probléma találva', count( $items ) > 0 ? 'warning' : 'success' );

    wp_send_json_success( [ 'items' => $items, 'scanned' => $scanned, 'task' => $task ] );
}
add_action( 'wp_ajax_cleanup_scan', 'cleanup_scan_handler' );


// ══════════════════════════════════════════════════════════════
// 5. SCAN: ÜRES / HIÁNYOS (SZIGORÚ SZABÁLYOK)
// ═════════���════════════════════════════════════════════════════

function cleanup_scan_empty() {
    $items   = [];
    $scanned = 0;

    $posts = get_posts( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => -1,
        'post_status'    => [ 'publish', 'draft', 'pending' ],
        'fields'         => 'ids',
    ] );

    $scanned = count( $posts );

    foreach ( $posts as $post_id ) {
        $title  = get_the_title( $post_id );
        $status = get_post_status( $post_id );

        $kcal    = floatval( get_post_meta( $post_id, 'kaloria', true ) );
        $feherje = floatval( get_post_meta( $post_id, 'feherje', true ) );
        $szh     = floatval( get_post_meta( $post_id, 'szenhidrat', true ) );
        $zsir    = floatval( get_post_meta( $post_id, 'zsir', true ) );

        $has_off  = ! empty( get_post_meta( $post_id, 'off_barcode', true ) );
        $has_usda = ! empty( get_post_meta( $post_id, 'usda_fdc_id', true ) );

        // ── 1. Üres név ──
        if ( empty( trim( $title ) ) ) {
            $items[] = [
                'post_id'    => $post_id,
                'name'       => '(üres név)',
                'type'       => 'empty',
                'type_label' => 'Üres név',
                'reason'     => 'A terméknév üres. Státusz: ' . $status,
                'action'     => 'trash',
                'kcal'       => $kcal,
                'protein'    => $feherje,
                'carb'       => $szh,
                'fat'        => $zsir,
            ];
            continue;
        }

        // ── 2. 0 kalória (bármilyen forrással!) ──
        if ( $kcal <= 0 ) {
            $sources = [];
            if ( $has_off )  $sources[] = 'OFF';
            if ( $has_usda ) $sources[] = 'USDA';
            $src_str = ! empty( $sources ) ? ' (Forrás: ' . implode( ' + ', $sources ) . ')' : ' (nincs forrás)';

            $items[] = [
                'post_id'    => $post_id,
                'name'       => $title,
                'type'       => 'zero_kcal',
                'type_label' => '0 kalória',
                'reason'     => 'Kalória = 0' . $src_str . '. Hasznavehetetlen tápérték adat.',
                'action'     => 'trash',
                'kcal'       => $kcal,
                'protein'    => $feherje,
                'carb'       => $szh,
                'fat'        => $zsir,
            ];
            continue;
        }

        // ── 3. Van kalória de minden makró 0 ──
        if ( $kcal > 0 && $feherje <= 0 && $szh <= 0 && $zsir <= 0 ) {
            $items[] = [
                'post_id'    => $post_id,
                'name'       => $title,
                'type'       => 'zero_macros',
                'type_label' => 'Hiányos makrók',
                'reason'     => 'Van kalória (' . round( $kcal ) . ' kcal) de fehérje + szénhidrát + zsír = 0. Hiányos adat.',
                'action'     => 'trash',
                'kcal'       => $kcal,
                'protein'    => $feherje,
                'carb'       => $szh,
                'fat'        => $zsir,
            ];
            continue;
        }

        // ── 4. Draft + nincs hasznos tápérték ──
        if ( $status === 'draft' && $kcal <= 0 && ! $has_off && ! $has_usda ) {
            $items[] = [
                'post_id'    => $post_id,
                'name'       => $title,
                'type'       => 'no_data',
                'type_label' => 'Draft (üres)',
                'reason'     => 'Draft piszkozat, nincs tápérték és nincs forrás.',
                'action'     => 'trash',
                'kcal'       => $kcal,
                'protein'    => $feherje,
                'carb'       => $szh,
                'fat'        => $zsir,
            ];
        }
    }

    return [ 'items' => $items, 'scanned' => $scanned ];
}


// ══════════════════════════════════════════════════════════════
// 6. SCAN: DUPLIKÁCIÓK
// ══════════════════════════════════════════════════════════════

function cleanup_scan_duplicates() {
    $items   = [];
    $scanned = 0;

    $posts = get_posts( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ] );

    $scanned = count( $posts );
    $already_flagged = [];

    // Név duplikációk
    $name_groups = [];
    foreach ( $posts as $post ) {
        $clean = mb_strtolower( trim( $post->post_title ), 'UTF-8' );
        if ( empty( $clean ) ) continue;
        $name_groups[ $clean ][] = [
            'id' => $post->ID, 'name' => $post->post_title, 'date' => $post->post_date,
        ];
    }

    foreach ( $name_groups as $group ) {
        if ( count( $group ) < 2 ) continue;
        usort( $group, function( $a, $b ) { return $a['id'] - $b['id']; } );
        $keep = array_shift( $group );
        foreach ( $group as $dup ) {
            $already_flagged[ $dup['id'] ] = true;
            $items[] = [
                'post_id'    => $dup['id'],
                'name'       => $dup['name'],
                'type'       => 'duplicate',
                'type_label' => 'Név duplikáció',
                'reason'     => 'Azonos: "' . $keep['name'] . '" (#' . $keep['id'] . ' marad)',
                'action'     => 'merge',
                'keep_id'    => $keep['id'],
                'duplicates' => [ [ 'id' => $keep['id'], 'name' => $keep['name'] ] ],
            ];
        }
    }

    // OFF vonalkód duplikációk
    $bc_groups = [];
    foreach ( $posts as $post ) {
        $bc = get_post_meta( $post->ID, 'off_barcode', true );
        if ( empty( $bc ) ) continue;
        $bc_groups[ $bc ][] = [ 'id' => $post->ID, 'name' => $post->post_title ];
    }

    foreach ( $bc_groups as $bc => $group ) {
        if ( count( $group ) < 2 ) continue;
        $keep = $group[0];
        for ( $i = 1; $i < count( $group ); $i++ ) {
            if ( isset( $already_flagged[ $group[$i]['id'] ] ) ) continue;
            $already_flagged[ $group[$i]['id'] ] = true;
            $items[] = [
                'post_id'    => $group[$i]['id'],
                'name'       => $group[$i]['name'],
                'type'       => 'duplicate',
                'type_label' => 'Vonalkód dup.',
                'reason'     => 'OFF: "' . $bc . '" – #' . $keep['id'] . ' marad.',
                'action'     => 'merge',
                'keep_id'    => $keep['id'],
                'duplicates' => [ [ 'id' => $keep['id'], 'name' => $keep['name'] ] ],
            ];
        }
    }

    // USDA FDC ID duplikációk
    $fdc_groups = [];
    foreach ( $posts as $post ) {
        $fdc = get_post_meta( $post->ID, 'usda_fdc_id', true );
        if ( empty( $fdc ) ) continue;
        $fdc_groups[ $fdc ][] = [ 'id' => $post->ID, 'name' => $post->post_title ];
    }

    foreach ( $fdc_groups as $fdc => $group ) {
        if ( count( $group ) < 2 ) continue;
        $keep = $group[0];
        for ( $i = 1; $i < count( $group ); $i++ ) {
            if ( isset( $already_flagged[ $group[$i]['id'] ] ) ) continue;
            $items[] = [
                'post_id'    => $group[$i]['id'],
                'name'       => $group[$i]['name'],
                'type'       => 'duplicate',
                'type_label' => 'FDC ID dup.',
                'reason'     => 'USDA: "' . $fdc . '" – #' . $keep['id'] . ' marad.',
                'action'     => 'merge',
                'keep_id'    => $keep['id'],
                'duplicates' => [ [ 'id' => $keep['id'], 'name' => $keep['name'] ] ],
            ];
        }
    }

    return [ 'items' => $items, 'scanned' => $scanned ];
}


// ══════════════════════════════════════════════════════════════
// 7. SCAN: NÉV PROBLÉMÁK
// ══════════════════════════════════════════════════════════════

function cleanup_scan_names() {
    $items   = [];
    $scanned = 0;

    $posts = get_posts( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ] );

    $scanned = count( $posts );

    foreach ( $posts as $post ) {
        $name    = $post->post_title;
        $reasons = [];
        $fixed   = $name;

        if ( preg_match( '/  +/', $fixed ) ) {
            $reasons[] = 'dupla szóközök';
            $fixed = preg_replace( '/  +/', ' ', $fixed );
        }
        if ( $fixed !== trim( $fixed ) ) {
            $reasons[] = 'vezető/záró szóközök';
            $fixed = trim( $fixed );
        }
        if ( preg_match( '/[,.\s]+$/', $fixed ) ) {
            $reasons[] = 'felesleges írásjel a végén';
            $fixed = rtrim( $fixed, ' ,.' );
        }
        if ( preg_match( '/^[,.\s]+/', $fixed ) ) {
            $reasons[] = 'felesleges írásjel az elején';
            $fixed = ltrim( $fixed, ' ,.' );
        }
        if ( $fixed !== html_entity_decode( $fixed, ENT_QUOTES, 'UTF-8' ) ) {
            $reasons[] = 'HTML entities';
            $fixed = html_entity_decode( $fixed, ENT_QUOTES, 'UTF-8' );
        }
        if ( strpos( $fixed, ',,' ) !== false ) {
            $reasons[] = 'dupla vesszők';
            $fixed = str_replace( ',,', ',', $fixed );
        }
        if ( mb_strlen( $fixed, 'UTF-8' ) > 150 ) {
            $reasons[] = 'nagyon hosszú (' . mb_strlen( $fixed, 'UTF-8' ) . ' kar.)';
        }

        if ( ! empty( $reasons ) && $fixed !== $name ) {
            $items[] = [
                'post_id'    => $post->ID,
                'name'       => $name,
                'type'       => 'name',
                'type_label' => 'Név probléma',
                'reason'     => implode( ', ', $reasons ),
                'action'     => 'fix_name',
                'fixed_name' => $fixed,
            ];
        }
    }

    return [ 'items' => $items, 'scanned' => $scanned ];
}


// ══════════════════════════════════════════════════════════════
// 8. SCAN: ÁRVA META
// ══════════════════════════════════════════════════════════════

function cleanup_scan_orphan_meta() {
    global $wpdb;
    $items   = [];
    $scanned = 0;

    // Kukás postok import adattal
    $trash_posts = get_posts( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => -1,
        'post_status'    => 'trash',
    ] );
    $scanned = count( $trash_posts );

    foreach ( $trash_posts as $post ) {
        $has_off  = ! empty( get_post_meta( $post->ID, 'off_barcode', true ) );
        $has_usda = ! empty( get_post_meta( $post->ID, 'usda_fdc_id', true ) );
        if ( $has_off || $has_usda ) {
            $src = [];
            if ( $has_off )  $src[] = 'OFF: ' . get_post_meta( $post->ID, 'off_barcode', true );
            if ( $has_usda ) $src[] = 'USDA: ' . get_post_meta( $post->ID, 'usda_fdc_id', true );
            $items[] = [
                'post_id'    => $post->ID,
                'name'       => $post->post_title,
                'type'       => 'orphan_meta',
                'type_label' => 'Kukás + meta',
                'reason'     => 'Kukában van, import adata: ' . implode( ', ', $src ),
                'action'     => 'delete',
            ];
        }
    }

    // Üres off_barcode
    $empty_off = $wpdb->get_results(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'off_barcode' AND (meta_value = '' OR meta_value IS NULL)"
    );
    foreach ( $empty_off as $row ) {
        $post = get_post( $row->post_id );
        if ( ! $post || $post->post_type !== 'alapanyag' ) continue;
        $items[] = [
            'post_id'    => $row->post_id,
            'name'       => $post->post_title,
            'type'       => 'orphan_meta',
            'type_label' => 'Üres vonalkód',
            'reason'     => 'off_barcode mező létezik de üres.',
            'action'     => 'delete_meta',
            'meta_key'   => 'off_barcode',
        ];
        $scanned++;
    }

    // Üres usda_fdc_id
    $empty_usda = $wpdb->get_results(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'usda_fdc_id' AND (meta_value = '' OR meta_value IS NULL)"
    );
    foreach ( $empty_usda as $row ) {
        $post = get_post( $row->post_id );
        if ( ! $post || $post->post_type !== 'alapanyag' ) continue;
        $items[] = [
            'post_id'    => $row->post_id,
            'name'       => $post->post_title,
            'type'       => 'orphan_meta',
            'type_label' => 'Üres FDC ID',
            'reason'     => 'usda_fdc_id mező létezik de üres.',
            'action'     => 'delete_meta',
            'meta_key'   => 'usda_fdc_id',
        ];
        $scanned++;
    }

    return [ 'items' => $items, 'scanned' => $scanned ];
}


// ══════════════════════════════════════════════════════════════
// 9. SCAN: IDEGEN NYELVŰ (v3 ÚJ)
// ══════════════════════════════════════════════════════════════

function cleanup_scan_foreign() {
    $items   = [];
    $scanned = 0;

    $posts = get_posts( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => -1,
        'post_status'    => [ 'publish', 'draft', 'pending' ],
    ] );

    $scanned = count( $posts );

    foreach ( $posts as $post ) {
        $title = $post->post_title;

        // Üres nevet kihagyjuk (azt az empty scan kezeli)
        if ( empty( trim( $title ) ) ) continue;

        if ( cleanup_is_non_latin( $title ) ) {
            $script = cleanup_detect_script( $title );

            $kcal    = floatval( get_post_meta( $post->ID, 'kaloria', true ) );
            $feherje = floatval( get_post_meta( $post->ID, 'feherje', true ) );
            $szh     = floatval( get_post_meta( $post->ID, 'szenhidrat', true ) );
            $zsir    = floatval( get_post_meta( $post->ID, 'zsir', true ) );

            $has_off  = ! empty( get_post_meta( $post->ID, 'off_barcode', true ) );
            $has_usda = ! empty( get_post_meta( $post->ID, 'usda_fdc_id', true ) );
            $src = [];
            if ( $has_off )  $src[] = 'OFF';
            if ( $has_usda ) $src[] = 'USDA';
            $src_str = ! empty( $src ) ? ' (Forrás: ' . implode( '+', $src ) . ')' : '';

            $items[] = [
                'post_id'         => $post->ID,
                'name'            => $title,
                'type'            => 'foreign',
                'type_label'      => 'Idegen nyelvű (' . $script . ')',
                'reason'          => 'Nem-latin karakterek dominálnak (script: ' . $script . ').' . $src_str . ' Státusz: ' . $post->post_status,
                'action'          => 'trash',
                'detected_script' => $script,
                'kcal'            => $kcal,
                'protein'         => $feherje,
                'carb'            => $szh,
                'fat'             => $zsir,
            ];
        }
    }

    return [ 'items' => $items, 'scanned' => $scanned ];
}


// ═══���══════════════════════════════════════════════════════════
// 10. SCAN: NAGYBETŰSÍTÉS (v3 ÚJ)
// ══════════════════════════════════════════════════════════════

function cleanup_scan_capitalize() {
    $items   = [];
    $scanned = 0;

    $posts = get_posts( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ] );

    $scanned = count( $posts );

    foreach ( $posts as $post ) {
        $title = $post->post_title;

        // Üres nevet kihagyjuk
        if ( empty( trim( $title ) ) ) continue;

        // Idegen nyelvűt kihagyjuk (azt a foreign scan kezeli)
        if ( cleanup_is_non_latin( $title ) ) continue;

        if ( cleanup_is_lowercase_start( $title ) ) {
            $new_title = cleanup_mb_ucfirst( trim( $title ) );

            $items[] = [
                'post_id'    => $post->ID,
                'name'       => $title,
                'type'       => 'lowercase',
                'type_label' => 'Kis kezdőbetű',
                'reason'     => 'Kisbetűvel kezdődik → nagybetűsítés (UTF-8 safe)',
                'action'     => 'capitalize',
                'fixed_name' => $new_title,
            ];
        }
    }

    return [ 'items' => $items, 'scanned' => $scanned ];
}


// ══════════════════════════════════════════════════════════════
// 11. AJAX: VÉGREHAJTÁS
// ══════════════════════════════════════════════════════════════

function cleanup_apply_handler() {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $items = json_decode( stripslashes( $_POST['items'] ?? '[]' ), true );
    if ( empty( $items ) || ! is_array( $items ) ) wp_send_json_error( 'Nincs kijelölve.' );

    $deleted = 0; $trashed = 0; $fixed = 0; $capitalized = 0; $merged = 0; $meta_del = 0; $errors = 0;

    cleanup_log_add( '▶ Végrehajtás: ' . count( $items ) . ' elem feldolgozása...', 'info' );

    foreach ( $items as $item ) {
        $post_id = intval( $item['post_id'] ?? 0 );
        $action  = sanitize_key( $item['action'] ?? '' );
        $name    = sanitize_text_field( $item['name'] ?? 'Névtelen' );

        if ( ! $post_id ) {
            cleanup_log_add( '  ✖ Érvénytelen post_id, kihagyva', 'error' );
            $errors++;
            continue;
        }

        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'alapanyag' ) {
            cleanup_log_add( '  ✖ Post nem található vagy nem alapanyag: #' . $post_id, 'error' );
            $errors++;
            continue;
        }

        switch ( $action ) {
            case 'delete':
                if ( wp_delete_post( $post_id, true ) ) {
                    cleanup_log_add( '  🗑 Véglegesen törölve: #' . $post_id . ' – ' . $name, 'warning' );
                    $deleted++;
                } else {
                    cleanup_log_add( '  ✖ Törlés sikertelen: #' . $post_id . ' – ' . $name, 'error' );
                    $errors++;
                }
                break;

            case 'trash':
                if ( wp_trash_post( $post_id ) ) {
                    cleanup_log_add( '  🗑 Kukába helyezve: #' . $post_id . ' – ' . $name, 'warning' );
                    $trashed++;
                } else {
                    cleanup_log_add( '  ✖ Kukázás sikertelen: #' . $post_id . ' – ' . $name, 'error' );
                    $errors++;
                }
                break;

            case 'fix_name':
                $fn = sanitize_text_field( $item['fixed_name'] ?? '' );
                if ( $fn ) {
                    wp_update_post( [ 'ID' => $post_id, 'post_title' => $fn ] );
                    cleanup_log_add( '  🏷 Név javítva: #' . $post_id . ' | "' . $name . '" → "' . $fn . '"', 'success' );
                    $fixed++;
                } else {
                    cleanup_log_add( '  ✖ Hiányzó javított név: #' . $post_id, 'error' );
                    $errors++;
                }
                break;

            // ── v3 ÚJ: NAGYBETŰSÍTÉS action ──
            case 'capitalize':
                $fn = $item['fixed_name'] ?? '';
                if ( $fn ) {
                    wp_update_post( [
                        'ID'         => $post_id,
                        'post_title' => $fn,
                        'post_name'  => sanitize_title( $fn ),
                    ] );
                    cleanup_log_add( '  🔠 Nagybetűsítve: #' . $post_id . ' | "' . $name . '" → "' . $fn . '"', 'success' );
                    $capitalized++;
                } else {
                    cleanup_log_add( '  ✖ Hiányzó javított név: #' . $post_id, 'error' );
                    $errors++;
                }
                break;

            case 'merge':
                $keep_id = intval( $item['keep_id'] ?? 0 );
                if ( $keep_id && get_post( $keep_id ) ) {
                    cleanup_log_add( '  🔀 Merge: #' . $post_id . ' → #' . $keep_id . ' (' . $name . ')', 'info' );
                    cleanup_merge_data( $post_id, $keep_id );
                    wp_trash_post( $post_id );
                    cleanup_log_add( '  ✔ Merge kész: #' . $post_id . ' kukába helyezve', 'success' );
                    $merged++;
                } else {
                    cleanup_log_add( '  ✖ Merge sikertelen – keep post nem található: #' . $keep_id, 'error' );
                    $errors++;
                }
                break;

            case 'delete_meta':
                $mk = sanitize_key( $item['meta_key'] ?? '' );
                if ( $mk ) {
                    delete_post_meta( $post_id, $mk );
                    delete_post_meta( $post_id, '_' . $mk );
                    cleanup_log_add( '  🔗 Meta törölve: #' . $post_id . ' | ' . $mk . ' (' . $name . ')', 'info' );
                    $meta_del++;
                } else {
                    cleanup_log_add( '  ✖ Hiányzó meta_key: #' . $post_id, 'error' );
                    $errors++;
                }
                break;

            default:
                cleanup_log_add( '  ✖ Ismeretlen action "' . $action . '" – #' . $post_id, 'error' );
                $errors++;
        }
    }

    $summary = [];
    if ( $deleted > 0 )      $summary[] = $deleted . ' törölve';
    if ( $trashed > 0 )      $summary[] = $trashed . ' kukába';
    if ( $fixed > 0 )        $summary[] = $fixed . ' név javítva';
    if ( $capitalized > 0 )  $summary[] = $capitalized . ' nagybetűsítve';
    if ( $merged > 0 )       $summary[] = $merged . ' összevonva';
    if ( $meta_del > 0 )     $summary[] = $meta_del . ' meta törölve';
    if ( $errors > 0 )       $summary[] = $errors . ' hiba';

    $summary_str = implode( ', ', $summary );
    cleanup_log_add( '✔ Végrehajtás befejezve: ' . $summary_str, $errors > 0 ? 'warning' : 'success' );

    wp_send_json_success( $summary_str );
}
add_action( 'wp_ajax_cleanup_apply', 'cleanup_apply_handler' );


// ═══════════���══════════════════════════════════════════════════
// 11b. AJAX: LOG TÖRLÉS
// ══════════════════════════════════════════════════════════════

function cleanup_log_clear_handler() {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );
    cleanup_log_clear();
    wp_send_json_success( 'Log törölve.' );
}
add_action( 'wp_ajax_cleanup_log_clear', 'cleanup_log_clear_handler' );


// ══════════════════════════════════════════════════════════════
// 12. LOMTÁR ELŐNÉZET (v3 ÚJ)
// ══════════════════════════════════════════════════════════════

function cleanup_trash_preview_handler() {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $trash_posts = get_posts( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => -1,
        'post_status'    => 'trash',
        'orderby'        => 'ID',
        'order'          => 'DESC',
    ] );

    $count       = count( $trash_posts );
    $total_meta  = 0;
    $total_thumbs = 0;
    $items       = [];

    foreach ( $trash_posts as $idx => $post ) {
        $has_off   = ! empty( get_post_meta( $post->ID, 'off_barcode', true ) );
        $has_usda  = ! empty( get_post_meta( $post->ID, 'usda_fdc_id', true ) );
        $has_thumb = ! empty( get_post_thumbnail_id( $post->ID ) );

        $src = [];
        if ( $has_off )  $src[] = 'OFF';
        if ( $has_usda ) $src[] = 'USDA';
        $src_str = ! empty( $src ) ? implode( '+', $src ) : 'nincs forrás';

        // Meta count
        global $wpdb;
        $meta_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d", $post->ID
        ) );
        $total_meta += $meta_count;
        if ( $has_thumb ) $total_thumbs++;

        // Első 100 elemet részletesen mutatjuk
        if ( $idx < 100 ) {
            $items[] = [
                'id'     => $post->ID,
                'title'  => $post->post_title,
                'source' => $src_str,
                'meta'   => $meta_count,
            ];
        }
    }

    cleanup_log_add( '���️ Lomtár előnézet: ' . $count . ' elem, ' . $total_meta . ' meta, ' . $total_thumbs . ' kép', 'info' );

    wp_send_json_success( [
        'count'        => $count,
        'total_meta'   => $total_meta,
        'total_thumbs' => $total_thumbs,
        'items'        => $items,
    ] );
}
add_action( 'wp_ajax_cleanup_trash_preview', 'cleanup_trash_preview_handler' );


// ══════════════════════════════════════════════════════════════
// 13. LOMTÁR VÉGLEGES ÜRÍTÉS – BATCH (v3 ÚJ)
// ══════════════════════════════════════════════════════════════

function cleanup_trash_empty_handler() {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );

    $batch_size = 50;

    // Mindig a kukában lévőket kérjük le – nem offset-tel, mert a törlés után eltűnnek
    $trash_posts = get_posts( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => $batch_size,
        'post_status'    => 'trash',
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ] );

    $deleted = 0;

    foreach ( $trash_posts as $post ) {
        // Először a csatolt képeket töröljük (kiemelt kép + egyéb attachmentek)
        $attachments = get_posts( [
            'post_type'      => 'attachment',
            'posts_per_page' => -1,
            'post_parent'    => $post->ID,
        ] );
        foreach ( $attachments as $att ) {
            wp_delete_attachment( $att->ID, true );
        }

        // Kiemelt kép (ha nem a post gyereke)
        $thumb_id = get_post_thumbnail_id( $post->ID );
        if ( $thumb_id ) {
            // Csak akkor töröljük ha más post nem használja
            global $wpdb;
            $usage = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %s AND post_id != %d",
                $thumb_id, $post->ID
            ) );
            if ( $usage === 0 ) {
                wp_delete_attachment( $thumb_id, true );
            }
        }

        // Post végleges törlése (összes meta-val együtt)
        $result = wp_delete_post( $post->ID, true );
        if ( $result ) {
            $deleted++;
        } else {
            cleanup_log_add( '  ✖ Nem sikerült törölni: #' . $post->ID . ' – ' . $post->post_title, 'error' );
        }
    }

    // Van-e még kukában?
    $remaining = get_posts( [
        'post_type'      => 'alapanyag',
        'posts_per_page' => 1,
        'post_status'    => 'trash',
    ] );
    $has_more = ! empty( $remaining );

    cleanup_log_add( '  🗑️ Lomtár batch: ' . $deleted . ' véglegesen törölve' . ( $has_more ? ' (folytatódik...)' : ' (KÉSZ)' ), $has_more ? 'info' : 'success' );

    wp_send_json_success( [
        'deleted'     => $deleted,
        'has_more'    => $has_more,
        'next_offset' => 0, // Mindig 0, mert a törölt elemek eltűnnek
    ] );
}
add_action( 'wp_ajax_cleanup_trash_empty', 'cleanup_trash_empty_handler' );


// ══════════════════════════════════════════════════════════════
// 14. MERGE SEGÉD
// ══════════════════════════════════════════════════════════════

function cleanup_merge_data( $from_id, $to_id ) {

    // OFF adat
    $to_off   = get_post_meta( $to_id, 'off_barcode', true );
    $from_off = get_post_meta( $from_id, 'off_barcode', true );
    if ( empty( $to_off ) && ! empty( $from_off ) && function_exists( 'update_field' ) ) {
        update_field( 'off_barcode', $from_off, $to_id );
        $off_url = get_post_meta( $from_id, 'off_url', true );
        if ( $off_url ) update_field( 'off_url', $off_url, $to_id );
    }

    // USDA adat
    $to_usda   = get_post_meta( $to_id, 'usda_fdc_id', true );
    $from_usda = get_post_meta( $from_id, 'usda_fdc_id', true );
    if ( empty( $to_usda ) && ! empty( $from_usda ) && function_exists( 'update_field' ) ) {
        update_field( 'usda_fdc_id', $from_usda, $to_id );
    }

    // Kiemelt kép
    $to_thumb   = get_post_thumbnail_id( $to_id );
    $from_thumb = get_post_thumbnail_id( $from_id );
    if ( ! $to_thumb && $from_thumb ) {
        set_post_thumbnail( $to_id, $from_thumb );
    }

    // Tápértékek
    $keys = [
        'kaloria', 'feherje', 'szenhidrat', 'zsir', 'rost', 'cukor',
        'telitett_zsir', 'natrium', 'kalium', 'kalcium', 'vas',
        'vitamin_c', 'vitamin_a', 'vitamin_d', 'vitamin_e', 'vitamin_k',
        'vitamin_b1', 'vitamin_b2', 'vitamin_b3', 'vitamin_b5',
        'vitamin_b6', 'vitamin_b9', 'vitamin_b12',
        'magnezium', 'foszfor', 'cink', 'rez', 'mangan', 'szelen',
        'koleszterin', 'egyszeresen_telitetlen_zsir', 'tobbszorosen_telitetlen_zsir',
    ];

    foreach ( $keys as $key ) {
        $to_val   = get_post_meta( $to_id, $key, true );
        $from_val = get_post_meta( $from_id, $key, true );
        if ( ( empty( $to_val ) || floatval( $to_val ) == 0 ) && ! empty( $from_val ) && floatval( $from_val ) > 0 ) {
            if ( function_exists( 'update_field' ) ) update_field( $key, $from_val, $to_id );
        }
    }
}


// ══════════════════════════════════════════════════════════════
// 15. AJAX: LEÁLLÍTÁS
// ══════════════════════════════════════════════════════════════

function cleanup_stop_handler() {
    check_ajax_referer( 'cleanup_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Nincs jogosultság.' );
    $state = get_option( CLEANUP_STATE_KEY, [] );
    $state['status'] = 'stopped';
    update_option( CLEANUP_STATE_KEY, $state );
    wp_clear_scheduled_hook( CLEANUP_CRON_HOOK );
    cleanup_log_add( '⏹ Folyamat leállítva.', 'warning' );
    wp_send_json_success( 'Leállítva.' );
}
add_action( 'wp_ajax_cleanup_stop', 'cleanup_stop_handler' );


// ══════════════════════════════════════════════════════════════
// 16. CRON SCHEDULE
// ══════════════════════════════════════════════════════════════

function cleanup_cron_schedules( $schedules ) {
    $schedules['cleanup_every_30s'] = [
        'interval' => 30,
        'display'  => 'Cleanup: 30mp',
    ];
    return $schedules;
}
add_filter( 'cron_schedules', 'cleanup_cron_schedules' );

function cleanup_ensure_cron() {
    $state = get_option( CLEANUP_STATE_KEY, [] );
    if ( ( $state['status'] ?? '' ) === 'running' ) {
        if ( ! wp_next_scheduled( CLEANUP_CRON_HOOK ) ) {
            wp_schedule_event( time(), 'cleanup_every_30s', CLEANUP_CRON_HOOK );
        }
    } else {
        if ( wp_next_scheduled( CLEANUP_CRON_HOOK ) ) {
            wp_clear_scheduled_hook( CLEANUP_CRON_HOOK );
        }
    }
}
add_action( 'admin_init', 'cleanup_ensure_cron' );
add_action( 'wp_loaded', 'cleanup_ensure_cron' );
