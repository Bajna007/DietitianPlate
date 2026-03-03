<?php
/**
 * Archive Recept template – v7 kliens-oldali szűrés/lapozás
 * + makró chip adatok PHP-ban előkészítve JS-nek
 */
get_header();

$kategoriak = get_terms([ 'taxonomy' => 'recept_kategoria', 'hide_empty' => false ]);
$jellegek   = get_terms([ 'taxonomy' => 'recept_jelleg',    'hide_empty' => false ]);
$dietak     = get_terms([ 'taxonomy' => 'recept_dieta',     'hide_empty' => false ]);

// Makró adatok előkészítése minden recepthez (JS data)
$all_receptek = get_posts([
    'post_type'      => 'recept',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'no_found_rows'  => true,
    'fields'         => 'ids',
]);

$makro_cache = [];
foreach ( $all_receptek as $rid ) {
    $kcal = 0; $feh = 0; $szh = 0; $zsir = 0;
    $adagok = max(1, (int) get_field('adagokszama', $rid));
    $map = ['g'=>1,'ml'=>1,'ek'=>15,'tk'=>5,'db'=>50,'csipet'=>1];
    if ( have_rows('osszetevok', $rid) ) {
        while ( have_rows('osszetevok', $rid) ) {
            the_row();
            $m  = (float) get_sub_field('mennyiseg');
            $me = get_sub_field('mertekegyseg');
            $a  = get_sub_field('kapcsolodo_alapanyag');
            if ( is_object($a) ) {
                $g = isset($map[$me]) ? $m * $map[$me] : $m;
                $p = get_field('kaloria', $a->ID);
                if ($p) {
                    $kcal += (float)($p['kcal']        ?? 0) / 100 * $g;
                    $feh  += (float)($p['feherje']     ?? 0) / 100 * $g;
                    $szh  += (float)($p['szenhidrat']  ?? 0) / 100 * $g;
                    $zsir += (float)($p['zsir']        ?? 0) / 100 * $g;
                }
            }
        }
    }
    $makro_cache[$rid] = [
        'kcal' => round($kcal / $adagok),
        'feh'  => round($feh  / $adagok, 1),
        'szh'  => round($szh  / $adagok, 1),
        'zsir' => round($zsir / $adagok, 1),
    ];
}
?>

<main id="primary" class="site-main recept-archiv">

    <div class="recept-archiv-header">
        <h1>Receptek</h1>
        <p class="recept-archiv-subtitle">V&aacute;logass eg&eacute;szs&eacute;ges, dietetikus &aacute;ltal &ouml;ssze&aacute;ll&iacute;tott receptjeink k&ouml;z&ouml;tt!</p>

        <div class="recept-search-bar">
            <div class="recept-search-inner">
                <svg class="recept-search-svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="szuro-kereses" class="recept-search-input" placeholder="Keres&#233;s recept neve alapj&#225;n..." autocomplete="off">
                <button type="button" class="recept-search-clear" id="search-clear" aria-label="Keres&#233;s t&#246;rl&#233;se">&#10005;</button>
            </div>
            <div class="szuro-dropdown" id="szuro-dropdown"></div>
        </div>
    </div>

    <div class="recept-toolbar">
        <button type="button" class="szuro-toggle" id="szuro-toggle">
            <svg class="szuro-toggle-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="6" y1="12" x2="18" y2="12"/>
                <line x1="9" y1="18" x2="15" y2="18"/>
            </svg>
            <span>Sz&#369;r&eacute;s</span>
        </button>

        <div class="recept-toolbar-right">
            <span class="recept-toolbar-count" id="recept-talalat"></span>

            <div class="recept-cols" id="recept-cols">
                <span class="recept-cols-label">Oszlopok:</span>
                <button type="button" class="recept-cols-btn" data-cols="3">3</button>
                <button type="button" class="recept-cols-btn is-active" data-cols="4">4</button>
                <button type="button" class="recept-cols-btn" data-cols="5">5</button>
            </div>

            <div class="recept-perpage">
                <span class="recept-perpage-label">Oldalank&eacute;nt:</span>
                <button type="button" class="recept-perpage-btn is-active" data-pp="24">24</button>
                <button type="button" class="recept-perpage-btn" data-pp="36">36</button>
                <button type="button" class="recept-perpage-btn" data-pp="48">48</button>
            </div>
        </div>
    </div>

    <div class="szuro-panel" id="szuro-panel">
        <div class="szuro-panel-inner" id="szuro-panel-inner">
            <div class="szuro-rows">

                <?php if ( ! empty( $kategoriak ) && ! is_wp_error( $kategoriak ) ) : ?>
                <div class="szuro-row">
                    <span class="szuro-row-label">Kateg&oacute;ria</span>
                    <div class="szuro-chips" data-filter="kategoria">
                        <button type="button" class="szuro-chip is-active" data-value="all">&Ouml;sszes</button>
                        <?php foreach ( $kategoriak as $kat ) : ?>
                            <button type="button" class="szuro-chip" data-value="<?php echo esc_attr( $kat->slug ); ?>"><?php echo esc_html( $kat->name ); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="szuro-row-separator"></div>

                <?php if ( ! empty( $jellegek ) && ! is_wp_error( $jellegek ) ) : ?>
                <div class="szuro-row">
                    <span class="szuro-row-label">Jelleg</span>
                    <div class="szuro-chips" data-filter="jelleg">
                        <button type="button" class="szuro-chip is-active" data-value="all">&Ouml;sszes</button>
                        <?php foreach ( $jellegek as $jel ) : ?>
                            <button type="button" class="szuro-chip" data-value="<?php echo esc_attr( $jel->slug ); ?>"><?php echo esc_html( $jel->name ); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="szuro-row-separator"></div>

                <?php if ( ! empty( $dietak ) && ! is_wp_error( $dietak ) ) : ?>
                <div class="szuro-row">
                    <span class="szuro-row-label">Di&eacute;ta</span>
                    <div class="szuro-chips szuro-chips--multi" data-filter="dieta">
                        <button type="button" class="szuro-chip is-active" data-value="all">&Ouml;sszes</button>
                        <?php foreach ( $dietak as $diet ) : ?>
                            <button type="button" class="szuro-chip" data-value="<?php echo esc_attr( $diet->slug ); ?>"><?php echo esc_html( $diet->name ); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="szuro-row-separator"></div>

                <div class="szuro-row">
                    <span class="szuro-row-label">Neh&eacute;zs&eacute;g</span>
                    <div class="szuro-chips" data-filter="nehezseg">
                        <button type="button" class="szuro-chip is-active" data-value="all">&Ouml;sszes</button>
                        <button type="button" class="szuro-chip" data-value="k&#246;nny&#369;">K&#246;nny&#369;</button>
                        <button type="button" class="szuro-chip" data-value="k&#246;zepes">K&#246;zepes</button>
                        <button type="button" class="szuro-chip" data-value="neh&#233;z">Neh&#233;z</button>
                    </div>
                </div>
                <div class="szuro-row-separator"></div>

                <div class="szuro-row">
                    <span class="szuro-row-label">Id&#337;</span>
                    <div class="szuro-chips" data-filter="ido">
                        <button type="button" class="szuro-chip is-active" data-value="all">&Ouml;sszes</button>
                        <button type="button" class="szuro-chip" data-value="15">&#8804; 15 perc</button>
                        <button type="button" class="szuro-chip" data-value="30">&#8804; 30 perc</button>
                        <button type="button" class="szuro-chip" data-value="60">&#8804; 60 perc</button>
                        <button type="button" class="szuro-chip" data-value="61">60+ perc</button>
                    </div>
                </div>

            </div>

            <div class="szuro-footer">
                <button type="button" class="szuro-alaphelyzet" id="szuro-reset">&#8634; Alaphelyzet</button>
            </div>
        </div>
    </div>

    <div class="recept-grid" id="recept-grid">
        <div class="recept-loading">
            <div class="recept-loading-spinner"></div>
            <span>Bet&ouml;lt&eacute;s...</span>
        </div>
    </div>

    <div id="recept-pagination" class="recept-pagination"></div>

</main>

<?php
// Makró cache átadása JS-nek
wp_localize_script( 'recept-archive', 'receptMakroData', $makro_cache );
?>

<?php get_footer(); ?>