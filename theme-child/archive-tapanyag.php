<?php
/**
 * Archive Tápanyag template – v2 REWORK
 * Kliens-oldali szűrés, recept kártya mérethez igazított grid,
 * chip szűrők: típus, oldhatóság, esszencialitás
 */

if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$tipus_terms    = get_terms([ 'taxonomy' => 'tapanyag_tipus',  'hide_empty' => false ]);
$oldhat_terms   = get_terms([ 'taxonomy' => 'oldhatosag',      'hide_empty' => false ]);
$esszenc_terms  = get_terms([ 'taxonomy' => 'esszencialis',    'hide_empty' => false ]);

$tapanyag_q = new WP_Query([
    'post_type'      => 'tapanyag',
    'posts_per_page' => 1,
    'post_status'    => 'publish',
]);
$total_count = $tapanyag_q->found_posts;
wp_reset_postdata();
?>

<main id="primary" class="site-main tapanyag-archiv">

    <?php // ═══════════════════════════════════════════════════ ?>
    <?php // ── HERO ──────────────────────────────────────── ?>
    <?php // ═══════════════════════════════════════════════════ ?>

    <div class="tapanyag-archiv-header">
        <h1>Tápanyag-adatbázis</h1>
        <p class="tapanyag-archiv-subtitle">
            Vitaminok, ásványi anyagok és nyomelemek – részletes tudástár
            <strong id="ta-total-count"><?php echo intval( $total_count ); ?></strong> tápanyaggal.
        </p>

        <div class="ta-search-bar">
            <div class="ta-search-inner">
                <svg class="ta-search-svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="ta-search" class="ta-search-input" placeholder="Keresés név vagy kémiai név alapján..." autocomplete="off">
                <button type="button" class="ta-search-clear" id="ta-search-clear" aria-label="Keresés törlése">✕</button>
            </div>
            <div class="ta-dropdown" id="ta-dropdown"></div>
        </div>
    </div>

    <?php // ═══════════════════════════════════════════════════ ?>
    <?php // ── TOOLBAR ───────────────────────────────────── ?>
    <?php // ═══════════════════════════════════════════════════ ?>

    <div class="ta-toolbar">
        <button type="button" class="ta-filter-toggle" id="ta-filter-toggle">
            <svg class="ta-filter-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="6" y1="12" x2="18" y2="12"/>
                <line x1="9" y1="18" x2="15" y2="18"/>
            </svg>
            <span>Szűrés</span>
        </button>

        <div class="ta-toolbar-right">
            <span class="ta-toolbar-count" id="ta-result-count"></span>

            <div class="ta-cols" id="ta-cols">
                <span class="ta-cols-label">Oszlopok:</span>
                <button type="button" class="ta-cols-btn" data-cols="3">3</button>
                <button type="button" class="ta-cols-btn is-active" data-cols="4">4</button>
                <button type="button" class="ta-cols-btn" data-cols="5">5</button>
            </div>

            <div class="ta-perpage">
                <span class="ta-perpage-label">Oldalanként:</span>
                <button type="button" class="ta-perpage-btn is-active" data-pp="24">24</button>
                <button type="button" class="ta-perpage-btn" data-pp="36">36</button>
                <button type="button" class="ta-perpage-btn" data-pp="48">48</button>
            </div>
        </div>
    </div>

    <?php // ═══════════════════════════════════════════════════ ?>
    <?php // ── SZŰRŐ PANEL ───────────────────────────────── ?>
    <?php // ═══════════════════════════════════════════════════ ?>

    <div class="ta-szuro-panel" id="ta-szuro-panel">
        <div class="ta-szuro-panel-inner" id="ta-szuro-panel-inner">
            <div class="ta-szuro-rows">

                <?php if ( ! empty( $tipus_terms ) && ! is_wp_error( $tipus_terms ) ) : ?>
                <div class="ta-szuro-row">
                    <span class="ta-szuro-label">Típus</span>
                    <div class="ta-szuro-chips" data-filter="tipus">
                        <button type="button" class="ta-chip is-active" data-value="all">Összes</button>
                        <?php foreach ( $tipus_terms as $term ) : ?>
                            <button type="button" class="ta-chip" data-value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="ta-szuro-separator"></div>
                <?php endif; ?>

                <?php if ( ! empty( $oldhat_terms ) && ! is_wp_error( $oldhat_terms ) ) : ?>
                <div class="ta-szuro-row">
                    <span class="ta-szuro-label">Oldhatóság</span>
                    <div class="ta-szuro-chips" data-filter="oldhatosag">
                        <button type="button" class="ta-chip is-active" data-value="all">Összes</button>
                        <?php foreach ( $oldhat_terms as $term ) : ?>
                            <button type="button" class="ta-chip" data-value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="ta-szuro-separator"></div>
                <?php endif; ?>

                <?php if ( ! empty( $esszenc_terms ) && ! is_wp_error( $esszenc_terms ) ) : ?>
                <div class="ta-szuro-row">
                    <span class="ta-szuro-label">Esszencialitás</span>
                    <div class="ta-szuro-chips" data-filter="esszencialis">
                        <button type="button" class="ta-chip is-active" data-value="all">Összes</button>
                        <?php foreach ( $esszenc_terms as $term ) : ?>
                            <button type="button" class="ta-chip" data-value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <div class="ta-szuro-footer">
                <button type="button" class="ta-szuro-reset" id="ta-szuro-reset">↺ Alaphelyzet</button>
            </div>
        </div>
    </div>

    <?php // ═══════════════════════════════════════════════════ ?>
    <?php // ── GRID ──────────────────────────────────────── ?>
    <?php // ══════════════════════════════════════════════════��� ?>

    <div class="ta-grid" id="ta-grid">
        <div class="ta-loading">
            <div class="ta-loading-spinner"></div>
            <span>Betöltés...</span>
        </div>
    </div>

    <?php // ═══════════════════════════════════════════════════ ?>
    <?php // ── LAPOZÁS ───────────────────────────────────── ?>
    <?php // ═══════════════════════════════════════════════════ ?>

    <div id="ta-pagination" class="ta-pagination"></div>

</main>

<?php get_footer(); ?>