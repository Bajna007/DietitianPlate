<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$total_q = new WP_Query([
    'post_type'      => 'tapanyag',
    'posts_per_page' => 1,
    'post_status'    => 'publish',
]);
$total_count = $total_q->found_posts;
wp_reset_postdata();

$tipus_terms   = get_terms([ 'taxonomy' => 'tapanyag_tipus',   'hide_empty' => true ]);
$oldhat_terms  = get_terms([ 'taxonomy' => 'oldhatosag',       'hide_empty' => true ]);
$csoport_terms = get_terms([ 'taxonomy' => 'tapanyag_csoport', 'hide_empty' => true ]);
$hatas_terms   = get_terms([ 'taxonomy' => 'tapanyag_hatas',   'hide_empty' => true ]);
$esszenc_terms = get_terms([ 'taxonomy' => 'esszencialis',     'hide_empty' => true ]);
?>

<div class="tapanyag-archiv">

    <div class="tapanyag-archiv-header">
        <h1>T&aacute;panyag lexikon</h1>
        <p class="tapanyag-archiv-subtitle">
            Vitaminok, &aacute;sv&aacute;nyi anyagok, zs&iacute;rsavak, aminosavak &ndash;
            <strong id="ta-total-count"><?php echo esc_html( $total_count ); ?></strong> t&aacute;panyag
        </p>

        <div class="ta-search-bar">
            <div class="ta-search-inner">
                <svg class="ta-search-svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="rgba(255,255,255,0.5)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text"
                       id="ta-search"
                       class="ta-search-input"
                       placeholder="Keress t&#225;panyagra, pl. C-vitamin, magn&#233;zium..."
                       autocomplete="off">
                <button id="ta-search-clear" class="ta-search-clear" style="display:none;">&#10005;</button>
            </div>
            <div id="ta-search-dropdown" class="ta-search-dropdown"></div>
        </div>
    </div>

    <div class="ta-toolbar">
        <div class="ta-toolbar-left">
            <button id="ta-filter-toggle" class="ta-filter-toggle-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" y1="6" x2="20" y2="6"/>
                    <line x1="8" y1="12" x2="16" y2="12"/>
                    <line x1="11" y1="18" x2="13" y2="18"/>
                </svg>
                Sz&#369;r&eacute;s
            </button>
        </div>

        <div class="ta-toolbar-right">
            <span id="ta-result-info" class="ta-toolbar-count"></span>

            <div class="ta-toolbar-group ta-toolbar-cols">
                <span class="ta-toolbar-label">Oszlopok:</span>
                <button class="ta-col-btn" data-cols="3">3</button>
                <button class="ta-col-btn is-active" data-cols="4">4</button>
                <button class="ta-col-btn" data-cols="5">5</button>
            </div>

            <div class="ta-toolbar-group ta-toolbar-sort">
                <span class="ta-toolbar-label">Rendez&eacute;s:</span>
                <select id="ta-sort" class="ta-toolbar-select">
                    <option value="title_asc">N&eacute;v (A&#8594;Z)</option>
                    <option value="title_desc">N&eacute;v (Z&#8594;A)</option>
                    <option value="tipus_asc">T&iacute;pus (A&#8594;Z)</option>
                </select>
            </div>

            <div class="ta-toolbar-group ta-toolbar-perpage">
                <span class="ta-toolbar-label">Oldalank&eacute;nt:</span>
                <button class="ta-pp-btn is-active" data-pp="24">24</button>
                <button class="ta-pp-btn" data-pp="36">36</button>
                <button class="ta-pp-btn" data-pp="48">48</button>
            </div>
        </div>
    </div>

    <div class="ta-szuro-panel" id="ta-szuro-panel">
        <div class="ta-szuro-panel-inner" id="ta-szuro-panel-inner">
            <div class="ta-szuro-rows">

                <?php if ( $tipus_terms && ! is_wp_error( $tipus_terms ) ) : ?>
                <div class="ta-szuro-row">
                    <span class="ta-szuro-label">T&iacute;pus</span>
                    <div class="ta-szuro-chips">
                        <?php foreach ( $tipus_terms as $term ) : ?>
                            <button class="ta-chip" data-tax="tapanyag_tipus" data-value="<?php echo esc_attr( $term->name ); ?>">
                                <?php echo esc_html( $term->name ); ?>
                                <span style="font-size:0.62rem;opacity:0.55;margin-left:3px;">(<?php echo esc_html( $term->count ); ?>)</span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="ta-szuro-row-separator"></div>
                <?php endif; ?>

                <?php if ( $oldhat_terms && ! is_wp_error( $oldhat_terms ) ) : ?>
                <div class="ta-szuro-row">
                    <span class="ta-szuro-label">Oldhat&oacute;s&aacute;g</span>
                    <div class="ta-szuro-chips">
                        <?php foreach ( $oldhat_terms as $term ) : ?>
                            <button class="ta-chip" data-tax="oldhatosag" data-value="<?php echo esc_attr( $term->name ); ?>">
                                <?php echo esc_html( $term->name ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="ta-szuro-row-separator"></div>
                <?php endif; ?>

                <?php if ( $csoport_terms && ! is_wp_error( $csoport_terms ) ) : ?>
                <div class="ta-szuro-row">
                    <span class="ta-szuro-label">Csoport</span>
                    <div class="ta-szuro-chips">
                        <?php foreach ( $csoport_terms as $term ) : ?>
                            <button class="ta-chip" data-tax="tapanyag_csoport" data-value="<?php echo esc_attr( $term->name ); ?>">
                                <?php echo esc_html( $term->name ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="ta-szuro-row-separator"></div>
                <?php endif; ?>

                <?php if ( $hatas_terms && ! is_wp_error( $hatas_terms ) ) : ?>
                <div class="ta-szuro-row">
                    <span class="ta-szuro-label">Hat&aacute;s</span>
                    <div class="ta-szuro-chips">
                        <?php foreach ( $hatas_terms as $term ) : ?>
                            <button class="ta-chip" data-tax="tapanyag_hatas" data-value="<?php echo esc_attr( $term->name ); ?>">
                                <?php echo esc_html( $term->name ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="ta-szuro-row-separator"></div>
                <?php endif; ?>

                <?php if ( $esszenc_terms && ! is_wp_error( $esszenc_terms ) ) : ?>
                <div class="ta-szuro-row">
                    <span class="ta-szuro-label">Essszenci&aacute;lis</span>
                    <div class="ta-szuro-chips">
                        <?php foreach ( $esszenc_terms as $term ) : ?>
                            <button class="ta-chip" data-tax="esszencialis" data-value="<?php echo esc_attr( $term->name ); ?>">
                                <?php echo esc_html( $term->name ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <div class="ta-szuro-footer">
                <button id="ta-szuro-reset" class="ta-szuro-reset-btn">&#8634; Alaphelyzet</button>
            </div>
        </div>
    </div>

    <div id="ta-grid" class="ta-grid">
        <div class="ta-loading">
            <div class="ta-loading-spinner"></div>
            <span>Bet&ouml;lt&eacute;s...</span>
        </div>
    </div>

    <div id="ta-pagination" class="ta-pagination"></div>

</div>

<?php get_footer(); ?>