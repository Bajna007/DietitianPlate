<?php
/**
 * Single Tápanyag template – v2 REWORK
 * Child theme: single-tapanyag.php
 * Változások: emoji→SVG, chip-ek nem kattinthatók, természetes források gombos link,
 *             konzisztens struktúra az alapanyag single mintájára
 */

if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

while ( have_posts() ) : the_post();

    $post_id = get_the_ID();

    // ── ACF MEZŐK ────────────────────────────────────────────
    $kemiai_nev    = get_field( 'kemiai_nev', $post_id );
    $keplet        = get_field( 'keplet', $post_id );
    $osszefoglalo  = get_field( 'osszefoglalo', $post_id );
    $kiemelt_szin  = get_field( 'kiemelt_szin', $post_id ) ?: '#F59E0B';
    $ikon_kep      = get_field( 'ikon_kep', $post_id );

    $szerep        = get_field( 'szerep', $post_id );
    $felszivadas   = get_field( 'felszivadas', $post_id );
    $forma_leiras  = get_field( 'forma_leiras', $post_id );
    $hiany_tunetek = get_field( 'hiany_tunetek', $post_id );
    $tuladagolas   = get_field( 'tuladagolas', $post_id );
    $kolcsonhatas  = get_field( 'kolcsonhatasok', $post_id );

    $napi_szukseglet      = get_field( 'napi_szukseglet', $post_id );
    $termeszetes_forrasok = get_field( 'termeszetes_forrasok', $post_id );
    $hivatkozasok         = get_field( 'hivatkozasok', $post_id );

    // ── TAXONÓMIÁK ───────────────────────────────────────────
    $tipus_terms  = get_the_terms( $post_id, 'tapanyag_tipus' );
    $oldhat_terms = get_the_terms( $post_id, 'oldhatosag' );
    $hatas_terms  = get_the_terms( $post_id, 'tapanyag_hatas' );

    // ── HERO KÉP ─────────────────────────────────────────────
    $hero_kep = get_the_post_thumbnail_url( $post_id, 'full' );
    if ( ! $hero_kep && $ikon_kep ) {
        $hero_kep = $ikon_kep['url'];
    }

    // ── TERMÉSZETES FORRÁSOK MAX (progress bar) ──────────────
    $max_mennyiseg = 1;
    if ( $termeszetes_forrasok ) {
        foreach ( $termeszetes_forrasok as $forras ) {
            if ( $forras['mennyiseg_100g'] > $max_mennyiseg ) {
                $max_mennyiseg = $forras['mennyiseg_100g'];
            }
        }
    }

    // ── SVG IKON HELPER ────────────────���─────────────────────
    function ts_icon( $name ) {
        $icons = [
            'microscope' => '<svg class="ts-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18h8"/><path d="M3 22h18"/><path d="M14 22a7 7 0 100-14h-1"/><path d="M9 14h2"/><path d="M9 12a2 2 0 01-2-2V6h6v4a2 2 0 01-2 2H9z"/><path d="M12 6V3a1 1 0 00-1-1H9a1 1 0 00-1 1v3"/></svg>',
            'pill'       => '<svg class="ts-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.5 1.5l-8 8a4.95 4.95 0 007 7l8-8a4.95 4.95 0 00-7-7z"/><path d="M8.5 8.5l7 7"/></svg>',
            'flask'      => '<svg class="ts-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6"/><path d="M10 9V3h4v6l5 8.5a2 2 0 01-1.7 3H6.7a2 2 0 01-1.7-3L10 9z"/></svg>',
            'leaf'       => '<svg class="ts-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 019.8 6.9C15.5 4.9 17 3.5 19 2c1 2 2 4.5 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>',
            'chart'      => '<svg class="ts-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
            'alert'      => '<svg class="ts-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            'shield'     => '<svg class="ts-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
            'refresh'    => '<svg class="ts-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>',
            'book'       => '<svg class="ts-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>',
            'list'       => '<svg class="ts-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
            'info'       => '<svg class="ts-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
            'chevron'    => '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5.25 10.5L8.75 7L5.25 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ];
        return $icons[ $name ] ?? '';
    }

    // ── TOC szekció konfigok ─────────────────────────────────
    $toc_sections = [];
    if ( $szerep )                 $toc_sections[] = [ 'id' => 'szerep',       'icon' => 'microscope', 'label' => 'Szerepe a szervezetben' ];
    if ( $felszivadas )            $toc_sections[] = [ 'id' => 'felszivadas',  'icon' => 'pill',       'label' => 'Felszívódás' ];
    if ( $forma_leiras )           $toc_sections[] = [ 'id' => 'forma',        'icon' => 'flask',      'label' => 'Formák' ];
    if ( $termeszetes_forrasok )   $toc_sections[] = [ 'id' => 'forrasok',     'icon' => 'leaf',       'label' => 'Természetes források' ];
    if ( $napi_szukseglet )        $toc_sections[] = [ 'id' => 'rda',          'icon' => 'chart',      'label' => 'Napi szükséglet' ];
    if ( $hiany_tunetek )          $toc_sections[] = [ 'id' => 'hiany',        'icon' => 'alert',      'label' => 'Hiánytünetek' ];
    if ( $tuladagolas )            $toc_sections[] = [ 'id' => 'tuladagolas',  'icon' => 'shield',     'label' => 'Túladagolás' ];
    if ( $kolcsonhatas )           $toc_sections[] = [ 'id' => 'kolcsonhatas', 'icon' => 'refresh',    'label' => 'Kölcsönhatások' ];
    if ( $hivatkozasok )           $toc_sections[] = [ 'id' => 'hivatkozasok', 'icon' => 'book',       'label' => 'Hivatkozások' ];

?>

<div class="tapanyag-single" style="--t-accent: <?php echo esc_attr( $kiemelt_szin ); ?>; --t-accent-dark: <?php echo esc_attr( $kiemelt_szin ); ?>;">

    <?php // ══════════════════════════════════════════════════ ?>
    <?php // ── HERO ─────────────────────────────────────── ?>
    <?php // ══════════════════════════════════════════════════ ?>

    <div class="tapanyag-hero" <?php if ( $hero_kep ) echo 'style="background-image: url(' . esc_url( $hero_kep ) . ');"'; ?>>
        <div class="tapanyag-hero-overlay">
            <h1><?php the_title(); ?></h1>
            <div class="tapanyag-hero-meta">
                <?php if ( $keplet ) : ?>
                    <span class="tapanyag-keplet-badge"><?php echo esc_html( $keplet ); ?></span>
                <?php endif; ?>
                <?php if ( $tipus_terms && ! is_wp_error( $tipus_terms ) ) : ?>
                    <?php foreach ( $tipus_terms as $term ) : ?>
                        <span class="tapanyag-hero-chip"><?php echo esc_html( $term->name ); ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ( $oldhat_terms && ! is_wp_error( $oldhat_terms ) ) : ?>
                    <?php foreach ( $oldhat_terms as $term ) : ?>
                        <span class="tapanyag-hero-chip"><?php echo esc_html( $term->name ); ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ( $hatas_terms && ! is_wp_error( $hatas_terms ) ) : ?>
                    <?php foreach ( $hatas_terms as $term ) : ?>
                        <span class="tapanyag-hero-chip"><?php echo esc_html( $term->name ); ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php // ══════════════════════════════════════════════════ ?>
    <?php // ── LAYOUT ───────────────────────────────────── ?>
    <?php // ══════════════════════════════════════════════════ ?>

    <div class="tapanyag-layout">

        <?php // ── SIDEBAR ── ?>
        <aside class="tapanyag-sidebar">

            <div class="tapanyag-sidebar-section">
                <h3 class="tapanyag-sidebar-title">
                    <span class="ts-sidebar-icon"><?php echo ts_icon('list'); ?></span>
                    Tartalomjegyzék
                </h3>
                <ul class="tapanyag-toc">
                    <?php foreach ( $toc_sections as $sec ) : ?>
                        <li>
                            <a href="#<?php echo esc_attr( $sec['id'] ); ?>">
                                <span class="toc-icon"><?php echo ts_icon( $sec['icon'] ); ?></span>
                                <?php echo esc_html( $sec['label'] ); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ( $osszefoglalo ) : ?>
            <div class="tapanyag-sidebar-section">
                <h3 class="tapanyag-sidebar-title">
                    <span class="ts-sidebar-icon"><?php echo ts_icon('info'); ?></span>
                    Összefoglaló
                </h3>
                <div class="tapanyag-sidebar-body">
                    <?php echo esc_html( $osszefoglalo ); ?>
                </div>
            </div>
            <?php endif; ?>

        </aside>

        <?php // ── FŐ TARTALOM ── ?>
        <main class="tapanyag-main">
            <div class="tapanyag-content-wrap">

                <?php // ── SZEREPE ── ?>
                <?php if ( $szerep ) : ?>
                <div class="tapanyag-section" id="szerep">
                    <h2 class="tapanyag-section-header">
                        <span class="ts-section-icon"><?php echo ts_icon('microscope'); ?></span>
                        Szerepe a szervezetben
                    </h2>
                    <div class="tapanyag-section-body">
                        <?php echo wp_kses_post( $szerep ); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php // ── FELSZÍVÓDÁS ── ?>
                <?php if ( $felszivadas ) : ?>
                <div class="tapanyag-section" id="felszivadas">
                    <h2 class="tapanyag-section-header">
                        <span class="ts-section-icon"><?php echo ts_icon('pill'); ?></span>
                        Felszívódás és biológiai hasznosulás
                    </h2>
                    <div class="tapanyag-section-body">
                        <?php echo wp_kses_post( $felszivadas ); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php // ── FORMÁK ── ?>
                <?php if ( $forma_leiras ) : ?>
                <div class="tapanyag-section" id="forma">
                    <h2 class="tapanyag-section-header">
                        <span class="ts-section-icon"><?php echo ts_icon('flask'); ?></span>
                        Formák – melyik hasznosul legjobban?
                    </h2>
                    <div class="tapanyag-section-body">
                        <?php echo wp_kses_post( $forma_leiras ); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php // ── TERMÉSZETES FORRÁSOK ── ?>
                <?php if ( $termeszetes_forrasok ) : ?>
                <div class="tapanyag-section" id="forrasok">
                    <h2 class="tapanyag-section-header">
                        <span class="ts-section-icon"><?php echo ts_icon('leaf'); ?></span>
                        Természetes források
                    </h2>
                    <div class="tapanyag-section-body">
                        <div class="tapanyag-forras-lista">
                            <?php foreach ( $termeszetes_forrasok as $forras ) :
                                $szazalek = $max_mennyiseg > 0
                                    ? round( ( $forras['mennyiseg_100g'] / $max_mennyiseg ) * 100 )
                                    : 0;

                                $kapcsolodo = $forras['kapcsolodo_post'] ?? null;
                                $has_link   = ( $kapcsolodo && is_object( $kapcsolodo ) && $kapcsolodo->ID );
                            ?>
                            <div class="tapanyag-forras-item">
                                <span class="tapanyag-forras-nev">
                                    <?php echo esc_html( $forras['elemiszer_nev'] ); ?>
                                </span>
                                <?php if ( $has_link ) : ?>
                                    <a href="<?php echo esc_url( get_permalink( $kapcsolodo->ID ) ); ?>"
                                       class="tapanyag-forras-link"
                                       title="<?php echo esc_attr( $forras['elemiszer_nev'] ); ?> – részletes tápérték">
                                        <?php echo ts_icon('chevron'); ?>
                                        <span class="tapanyag-forras-link-text">Részletek</span>
                                    </a>
                                <?php endif; ?>
                                <div class="tapanyag-forras-bar-wrap">
                                    <div class="tapanyag-forras-bar"
                                         style="width: <?php echo esc_attr( $szazalek ); ?>%">
                                    </div>
                                </div>
                                <span class="tapanyag-forras-ertek">
                                    <?php echo esc_html( $forras['mennyiseg_100g'] . ' ' . $forras['egyseg'] ); ?>/100g
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php // ── NAPI SZÜKSÉGLET ── ?>
                <?php if ( $napi_szukseglet ) : ?>
                <div class="tapanyag-section" id="rda">
                    <h2 class="tapanyag-section-header">
                        <span class="ts-section-icon"><?php echo ts_icon('chart'); ?></span>
                        Napi szükséglet (RDA)
                    </h2>
                    <div class="tapanyag-section-body" style="padding: 0;">
                        <table class="tapanyag-rda-table">
                            <thead>
                                <tr>
                                    <th>Csoport</th>
                                    <th>Ajánlott bevitel</th>
                                    <th>Felső határ (UL)</th>
                                    <th>Megjegyzés</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $napi_szukseglet as $sor ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $sor['csoport'] ); ?></td>
                                    <td>
                                        <span class="tapanyag-rda-value">
                                            <?php echo esc_html( $sor['ertek'] . ' ' . $sor['egyseg'] ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ( $sor['felso_hatar'] ) : ?>
                                            <span class="tapanyag-rda-ul">
                                                max <span><?php echo esc_html( $sor['felso_hatar'] . ' ' . $sor['egyseg'] ); ?></span>
                                            </span>
                                        <?php else : ?>
                                            <span class="tapanyag-rda-empty">–</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="tapanyag-rda-note">
                                        <?php echo esc_html( $sor['megjegyzes'] ?: '–' ); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php // ── HIÁNYTÜNETEK ── ?>
                <?php if ( $hiany_tunetek ) : ?>
                <div class="tapanyag-section" id="hiany">
                    <h2 class="tapanyag-section-header">
                        <span class="ts-section-icon"><?php echo ts_icon('alert'); ?></span>
                        Hiánytünetek
                    </h2>
                    <div class="tapanyag-section-body">
                        <?php echo wp_kses_post( $hiany_tunetek ); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php // ── TÚLADAGOLÁS ── ?>
                <?php if ( $tuladagolas ) : ?>
                <div class="tapanyag-section" id="tuladagolas">
                    <h2 class="tapanyag-section-header">
                        <span class="ts-section-icon"><?php echo ts_icon('shield'); ?></span>
                        Túladagolás kockázata
                    </h2>
                    <div class="tapanyag-section-body">
                        <?php echo wp_kses_post( $tuladagolas ); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php // ── KÖLCSÖNHATÁSOK ── ?>
                <?php if ( $kolcsonhatas ) : ?>
                <div class="tapanyag-section" id="kolcsonhatas">
                    <h2 class="tapanyag-section-header">
                        <span class="ts-section-icon"><?php echo ts_icon('refresh'); ?></span>
                        Kölcsönhatások
                    </h2>
                    <div class="tapanyag-section-body">
                        <?php echo wp_kses_post( $kolcsonhatas ); ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <?php // ── HIVATKOZÁSOK (külön wrap) ── ?>
            <?php if ( $hivatkozasok ) : ?>
            <div class="tapanyag-content-wrap" id="hivatkozasok">
                <div class="tapanyag-section">
                    <h2 class="tapanyag-section-header">
                        <span class="ts-section-icon"><?php echo ts_icon('book'); ?></span>
                        Hivatkozások
                    </h2>
                    <div class="tapanyag-section-body">
                        <div class="tapanyag-hivatkozas-lista">
                            <?php foreach ( $hivatkozasok as $hiv ) : ?>
                            <a href="<?php echo esc_url( $hiv['url'] ); ?>"
                               class="tapanyag-hivatkozas-item"
                               target="_blank"
                               rel="noopener noreferrer">
                                <span class="tapanyag-hivatkozas-tipus tapanyag-hivatkozas-tipus--<?php echo esc_attr( $hiv['tipus'] ); ?>">
                                    <?php
                                    $tipus_label = [
                                        'tudomanyos' => 'Tudományos',
                                        'hatosagi'   => 'Hatósági',
                                        'egyeb'      => 'Egyéb',
                                    ];
                                    echo esc_html( $tipus_label[ $hiv['tipus'] ] ?? $hiv['tipus'] );
                                    ?>
                                </span>
                                <span class="tapanyag-hivatkozas-cim">
                                    <?php echo esc_html( $hiv['cim'] ); ?>
                                </span>
                                <span class="tapanyag-hivatkozas-nyil">↗</span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php
endwhile;
get_footer();