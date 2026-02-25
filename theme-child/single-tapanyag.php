<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

while ( have_posts() ) : the_post();

    // ── ACF MEZŐK ────────────────────────────────────────────
    $kemiai_nev    = get_field('kemiai_nev');
    $keplet        = get_field('keplet');
    $osszefoglalo  = get_field('osszefoglalo');
    $kiemelt_szin  = get_field('kiemelt_szin') ?: '#F59E0B';
    $ikon_kep      = get_field('ikon_kep');

    $szerep        = get_field('szerep');
    $felszivadas   = get_field('felszivadas');
    $forma_leiras  = get_field('forma_leiras');
    $hiany_tunetek = get_field('hiany_tunetek');
    $tuladagolas   = get_field('tuladagolas');
    $kolcsonhatas  = get_field('kolcsonhatasok');

    $napi_szukseglet      = get_field('napi_szukseglet');
    $termeszetes_forrasok = get_field('termeszetes_forrasok');
    $hivatkozasok         = get_field('hivatkozasok');

    // ── TAXONÓMIÁK ───────────────────────────────────────────
    $tipus_terms   = get_the_terms( get_the_ID(), 'tapanyag_tipus' );
    $oldhat_terms  = get_the_terms( get_the_ID(), 'oldhatosag' );
    $hatas_terms   = get_the_terms( get_the_ID(), 'tapanyag_hatas' );

    // ── HERO KÉP ─────────────────────────────────────────────
    $hero_kep = get_the_post_thumbnail_url( get_the_ID(), 'full' );
    if ( ! $hero_kep && $ikon_kep ) {
        $hero_kep = $ikon_kep['url'];
    }

    // ── TERMÉSZETES FORRÁSOK MAX ÉRTÉKE (progress bar-hoz) ───
    $max_mennyiseg = 1;
    if ( $termeszetes_forrasok ) {
        foreach ( $termeszetes_forrasok as $forras ) {
            if ( $forras['mennyiseg_100g'] > $max_mennyiseg ) {
                $max_mennyiseg = $forras['mennyiseg_100g'];
            }
        }
    }

?>

<div class="tapanyag-single" style="--t-accent: <?php echo esc_attr( $kiemelt_szin ); ?>; --t-accent-dark: <?php echo esc_attr( $kiemelt_szin ); ?>;">

    <?php // ══ HERO ══ ?>
    <div class="tapanyag-hero" <?php if ( $hero_kep ) echo 'style="background-image: url(' . esc_url($hero_kep) . ');"'; ?>>
        <div class="tapanyag-hero-overlay">
            <h1><?php the_title(); ?></h1>
            <div class="tapanyag-hero-meta">
                <?php if ( $keplet ) : ?>
                    <span class="tapanyag-keplet-badge"><?php echo esc_html( $keplet ); ?></span>
                <?php endif; ?>
                <?php if ( $tipus_terms && ! is_wp_error( $tipus_terms ) ) : ?>
                    <?php foreach ( $tipus_terms as $term ) : ?>
                        <a href="<?php echo esc_url( get_term_link( $term ) ); ?>"
                           class="tapanyag-hero-chip">
                            <?php echo esc_html( $term->name ); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ( $oldhat_terms && ! is_wp_error( $oldhat_terms ) ) : ?>
                    <?php foreach ( $oldhat_terms as $term ) : ?>
                        <a href="<?php echo esc_url( get_term_link( $term ) ); ?>"
                           class="tapanyag-hero-chip">
                            <?php echo esc_html( $term->name ); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ( $hatas_terms && ! is_wp_error( $hatas_terms ) ) : ?>
                    <?php foreach ( $hatas_terms as $term ) : ?>
                        <span class="tapanyag-hero-chip">
                            <?php echo esc_html( $term->name ); ?>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php // ══ LAYOUT ══ ?>
    <div class="tapanyag-layout">

        <?php // ══ SIDEBAR ══ ?>
        <aside class="tapanyag-sidebar">
            <div class="tapanyag-sidebar-section">
                <h3 class="tapanyag-sidebar-title">📋 Tartalomjegyzék</h3>
                <ul class="tapanyag-toc">
                    <?php if ( $szerep ) : ?>
                        <li><a href="#szerep"><span class="toc-icon">🔬</span> Szerepe a szervezetben</a></li>
                    <?php endif; ?>
                    <?php if ( $felszivadas ) : ?>
                        <li><a href="#felszivadas"><span class="toc-icon">💊</span> Felszívódás</a></li>
                    <?php endif; ?>
                    <?php if ( $forma_leiras ) : ?>
                        <li><a href="#forma"><span class="toc-icon">🧪</span> Formák</a></li>
                    <?php endif; ?>
                    <?php if ( $termeszetes_forrasok ) : ?>
                        <li><a href="#forrasok"><span class="toc-icon">🥦</span> Természetes források</a></li>
                    <?php endif; ?>
                    <?php if ( $napi_szukseglet ) : ?>
                        <li><a href="#rda"><span class="toc-icon">📊</span> Napi szükséglet</a></li>
                    <?php endif; ?>
                    <?php if ( $hiany_tunetek ) : ?>
                        <li><a href="#hiany"><span class="toc-icon">⚠️</span> Hiánytünetek</a></li>
                    <?php endif; ?>
                    <?php if ( $tuladagolas ) : ?>
                        <li><a href="#tuladagolas"><span class="toc-icon">🚫</span> Túladagolás</a></li>
                    <?php endif; ?>
                    <?php if ( $kolcsonhatas ) : ?>
                        <li><a href="#kolcsonhatas"><span class="toc-icon">🔄</span> Kölcsönhatások</a></li>
                    <?php endif; ?>
                    <?php if ( $hivatkozasok ) : ?>
                        <li><a href="#hivatkozasok"><span class="toc-icon">📚</span> Hivatkozások</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if ( $osszefoglalo ) : ?>
            <div class="tapanyag-sidebar-section">
                <h3 class="tapanyag-sidebar-title">ℹ️ Összefoglaló</h3>
                <div style="padding: 14px 16px; font-size: 0.85rem; line-height: 1.65; color: var(--t-text-muted);">
                    <?php echo esc_html( $osszefoglalo ); ?>
                </div>
            </div>
            <?php endif; ?>
        </aside>

        <?php // ══ FŐ TARTALOM ══ ?>
        <main class="tapanyag-main">
            <div class="tapanyag-content-wrap">

                <?php // ── SZEREPE ── ?>
                <?php if ( $szerep ) : ?>
                <div class="tapanyag-section" id="szerep">
                    <h2 class="tapanyag-section-header">🔬 Szerepe a szervezetben</h2>
                    <div class="tapanyag-section-body">
                        <?php echo wp_kses_post( $szerep ); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php // ── FELSZÍVÓDÁS ── ?>
                <?php if ( $felszivadas ) : ?>
                <div class="tapanyag-section" id="felszivadas">
                    <h2 class="tapanyag-section-header">💊 Felszívódás és biológiai hasznosulás</h2>
                    <div class="tapanyag-section-body">
                        <?php echo wp_kses_post( $felszivadas ); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php // ── FORMÁK ── ?>
                <?php if ( $forma_leiras ) : ?>
                <div class="tapanyag-section" id="forma">
                    <h2 class="tapanyag-section-header">🧪 Formák – melyik hasznosul legjobban?</h2>
                    <div class="tapanyag-section-body">
                        <?php echo wp_kses_post( $forma_leiras ); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php // ── TERMÉSZETES FORRÁSOK (MÓDOSÍTOTT – kattintható linkek) ── ?>
                <?php if ( $termeszetes_forrasok ) : ?>
                <div class="tapanyag-section" id="forrasok">
                    <h2 class="tapanyag-section-header">🥦 Természetes források</h2>
                    <div class="tapanyag-section-body">
                        <div class="tapanyag-forras-lista">
                            <?php foreach ( $termeszetes_forrasok as $forras ) :
                                $szazalek = $max_mennyiseg > 0
                                    ? round( ( $forras['mennyiseg_100g'] / $max_mennyiseg ) * 100 )
                                    : 0;

                                // 🔧 #18 BŐVÍTÉS: Kattintható link ha van kapcsolódó alapanyag
                                $kapcsolodo = $forras['kapcsolodo_post'] ?? null;
                                $has_link = ( $kapcsolodo && is_object( $kapcsolodo ) && $kapcsolodo->ID );
                            ?>
                            <div class="tapanyag-forras-item">
                                <span class="tapanyag-forras-nev">
                                    <?php if ( $has_link ) : ?>
                                        <a href="<?php echo esc_url( get_permalink( $kapcsolodo->ID ) ); ?>">
                                            <?php echo esc_html( $forras['elemiszer_nev'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo esc_html( $forras['elemiszer_nev'] ); ?>
                                    <?php endif; ?>
                                </span>
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
                    <h2 class="tapanyag-section-header">📊 Napi szükséglet (RDA)</h2>
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
                                            <span style="color: var(--t-text-muted); font-size: 0.78rem;">–</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.82rem; color: var(--t-text-muted);">
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
                    <h2 class="tapanyag-section-header">⚠️ Hiánytünetek</h2>
                    <div class="tapanyag-section-body">
                        <?php echo wp_kses_post( $hiany_tunetek ); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php // ── TÚLADAGOLÁS ── ?>
                <?php if ( $tuladagolas ) : ?>
                <div class="tapanyag-section" id="tuladagolas">
                    <h2 class="tapanyag-section-header">🚫 Túladagolás kockázata</h2>
                    <div class="tapanyag-section-body">
                        <?php echo wp_kses_post( $tuladagolas ); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php // ── KÖLCSÖNHATÁSOK ── ?>
                <?php if ( $kolcsonhatas ) : ?>
                <div class="tapanyag-section" id="kolcsonhatas">
                    <h2 class="tapanyag-section-header">🔄 Kölcsönhatások</h2>
                    <div class="tapanyag-section-body">
                        <?php echo wp_kses_post( $kolcsonhatas ); ?>
                    </div>
                </div>
                <?php endif; ?>

            </div><?php // .tapanyag-content-wrap vége ?>

            <?php // ── HIVATKOZÁSOK ── ?>
            <?php if ( $hivatkozasok ) : ?>
            <div class="tapanyag-content-wrap" id="hivatkozasok">
                <div class="tapanyag-section">
                    <h2 class="tapanyag-section-header">📚 Hivatkozások</h2>
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

        </main><?php // .tapanyag-main vége ?>
    </div><?php // .tapanyag-layout vége ?>
</div><?php // .tapanyag-single vége ?>

<?php
endwhile;
get_footer();