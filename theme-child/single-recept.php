<?php
get_header();
?>

<main id="primary" class="site-main recept-single">

    <?php
    while ( have_posts() ) : the_post();
        $elkeszitesi_ido = get_field( 'elkeszitesi_ido' );
        $adagok_szama    = get_field( 'adagok_szama' );
        $nehezsegi_fok   = get_field( 'nehezsegi_fok' );
        $current_id      = get_the_ID();
        $recept_url      = get_the_permalink();
        $recept_title    = get_the_title();

        $current_kats = wp_get_post_terms( $current_id, 'recept_kategoria', array( 'fields' => 'ids' ) );
        $current_kat_ids = ( ! is_wp_error( $current_kats ) && ! empty( $current_kats ) ) ? $current_kats : array();

        $hasonlo_args = array( 'post_type' => 'recept', 'posts_per_page' => 3, 'post__not_in' => array( $current_id ), 'orderby' => 'rand' );
        if ( ! empty( $current_kat_ids ) ) { $hasonlo_args['tax_query'] = array( array( 'taxonomy' => 'recept_kategoria', 'field' => 'term_id', 'terms' => $current_kat_ids ) ); }
        $hasonlo = new WP_Query( $hasonlo_args );

        $fedezd_args = array( 'post_type' => 'recept', 'posts_per_page' => 3, 'post__not_in' => array( $current_id ), 'orderby' => 'rand' );
        if ( ! empty( $current_kat_ids ) ) { $fedezd_args['tax_query'] = array( array( 'taxonomy' => 'recept_kategoria', 'field' => 'term_id', 'terms' => $current_kat_ids, 'operator' => 'NOT IN' ) ); }
        $fedezd = new WP_Query( $fedezd_args );

        if ( $fedezd->post_count < 3 ) {
            $already = array( $current_id );
            if ( $hasonlo->have_posts() ) { while ( $hasonlo->have_posts() ) { $hasonlo->the_post(); $already[] = get_the_ID(); } $hasonlo->rewind_posts(); }
            if ( $fedezd->have_posts() ) { while ( $fedezd->have_posts() ) { $fedezd->the_post(); $already[] = get_the_ID(); } $fedezd->rewind_posts(); }
            $potlas = new WP_Query( array( 'post_type' => 'recept', 'posts_per_page' => 3 - $fedezd->post_count, 'post__not_in' => $already, 'orderby' => 'rand' ) );
        } else { $potlas = null; }

        $all_kats = get_terms( array( 'taxonomy' => 'recept_kategoria', 'hide_empty' => true ) );
        $hero_img = get_the_post_thumbnail_url( $current_id, 'large' );
    ?>
        <article <?php post_class(); ?>>

            <?php if ( $hero_img ) : ?>
            <div class="recept-hero" style="background-image: url('<?php echo esc_url( $hero_img ); ?>');">
                <div class="recept-hero-overlay">
                    <h1><?php the_title(); ?></h1>
                </div>
            </div>
            <?php else : ?>
                <h1><?php the_title(); ?></h1>
            <?php endif; ?>

            <div class="recept-toast" id="recept-toast"></div>

            <div class="share-panel" id="share-panel">
                <div class="share-panel-inner">
                    <button type="button" class="share-close" id="share-close">✕</button>
                    <h4 class="share-title">🛒 Bevásárlólista küldése</h4>
                    <textarea class="share-textarea" id="share-textarea" rows="10" readonly></textarea>
                    <div class="share-buttons">
                        <button type="button" class="share-btn share-btn--copy" id="share-copy">📋 Másolás</button>
                        <a class="share-btn share-btn--whatsapp" id="share-whatsapp" href="#" target="_blank" rel="noopener">💬 WhatsApp</a>
                        <a class="share-btn share-btn--email" id="share-email" href="#">📧 Email</a>
                    </div>
                </div>
            </div>

            <div class="recept-meta">
                <?php if ( $elkeszitesi_ido ) : ?>
                    <p>⏱ <strong><?php echo esc_html( $elkeszitesi_ido ); ?></strong> perc</p>
                <?php endif; ?>
                <?php if ( $adagok_szama ) : ?>
                    <p>🍽 <strong><?php echo esc_html( $adagok_szama ); ?></strong> adag</p>
                <?php endif; ?>
                <?php if ( $nehezsegi_fok ) : ?>
                    <p>📊 <?php echo esc_html( ucfirst( $nehezsegi_fok ) ); ?></p>
                <?php endif; ?>
            </div>

            <div class="recept-layout">

                <aside class="recept-sidebar">
                    <?php if ( $hasonlo->have_posts() ) : ?>
                    <div class="sidebar-section">
                        <h3 class="sidebar-section-title">Hasonló receptek</h3>
                        <div class="sidebar-cards">
                            <?php while ( $hasonlo->have_posts() ) : $hasonlo->the_post();
                                $s_ido=intval(get_field('elkeszitesi_ido'));$s_kcal=0;
                                if(have_rows('osszetevok')){while(have_rows('osszetevok')){the_row();$m=floatval(get_sub_field('mennyiseg'));$me=get_sub_field('mertekegyseg')?:'g';$a=get_sub_field('alapanyag');if($a&&is_object($a)){$gs=1;$map=array('g'=>1,'ml'=>1,'ek'=>15,'tk'=>5,'db'=>50,'csipet'=>1);if(isset($map[$me]))$gs=$map[$me];$s_kcal+=(floatval(get_field('kaloria',$a->ID))/100)*$m*$gs;}}}
                            ?>
                                <a href="<?php the_permalink(); ?>" class="sidebar-card">
                                    <div class="sidebar-card-img"><?php if(has_post_thumbnail()):the_post_thumbnail('thumbnail');else:?><span class="sidebar-card-img-ph">🍽</span><?php endif;?></div>
                                    <div class="sidebar-card-body">
                                        <span class="sidebar-card-title"><?php the_title(); ?></span>
                                        <span class="sidebar-card-meta"><?php if($s_kcal>0):?><strong><?php echo round($s_kcal);?></strong> kcal<?php endif;?><?php if($s_ido>0):?> · ⏱ <?php echo $s_ido;?> perc<?php endif;?></span>
                                    </div>
                                </a>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ( $fedezd->have_posts() || ( $potlas && $potlas->have_posts() ) ) : ?>
                    <div class="sidebar-section">
                        <h3 class="sidebar-section-title">Fedezd fel</h3>
                        <div class="sidebar-cards">
                            <?php
                            if ( $fedezd->have_posts() ) :
                                while ( $fedezd->have_posts() ) : $fedezd->the_post();
                                    $s_ido=intval(get_field('elkeszitesi_ido'));$s_kcal=0;
                                    if(have_rows('osszetevok')){while(have_rows('osszetevok')){the_row();$m=floatval(get_sub_field('mennyiseg'));$me=get_sub_field('mertekegyseg')?:'g';$a=get_sub_field('alapanyag');if($a&&is_object($a)){$gs=1;$map=array('g'=>1,'ml'=>1,'ek'=>15,'tk'=>5,'db'=>50,'csipet'=>1);if(isset($map[$me]))$gs=$map[$me];$s_kcal+=(floatval(get_field('kaloria',$a->ID))/100)*$m*$gs;}}}
                            ?>
                                <a href="<?php the_permalink(); ?>" class="sidebar-card">
                                    <div class="sidebar-card-img"><?php if(has_post_thumbnail()):the_post_thumbnail('thumbnail');else:?><span class="sidebar-card-img-ph">🍽</span><?php endif;?></div>
                                    <div class="sidebar-card-body">
                                        <span class="sidebar-card-title"><?php the_title(); ?></span>
                                        <span class="sidebar-card-meta"><?php if($s_kcal>0):?><strong><?php echo round($s_kcal);?></strong> kcal<?php endif;?><?php if($s_ido>0):?> · ⏱ <?php echo $s_ido;?> perc<?php endif;?></span>
                                    </div>
                                </a>
                            <?php endwhile; wp_reset_postdata(); endif;
                            if ( $potlas && $potlas->have_posts() ) :
                                while ( $potlas->have_posts() ) : $potlas->the_post();
                                    $s_ido=intval(get_field('elkeszitesi_ido'));$s_kcal=0;
                                    if(have_rows('osszetevok')){while(have_rows('osszetevok')){the_row();$m=floatval(get_sub_field('mennyiseg'));$me=get_sub_field('mertekegyseg')?:'g';$a=get_sub_field('alapanyag');if($a&&is_object($a)){$gs=1;$map=array('g'=>1,'ml'=>1,'ek'=>15,'tk'=>5,'db'=>50,'csipet'=>1);if(isset($map[$me]))$gs=$map[$me];$s_kcal+=(floatval(get_field('kaloria',$a->ID))/100)*$m*$gs;}}}
                            ?>
                                <a href="<?php the_permalink(); ?>" class="sidebar-card">
                                    <div class="sidebar-card-img"><?php if(has_post_thumbnail()):the_post_thumbnail('thumbnail');else:?><span class="sidebar-card-img-ph">🍽</span><?php endif;?></div>
                                    <div class="sidebar-card-body">
                                        <span class="sidebar-card-title"><?php the_title(); ?></span>
                                        <span class="sidebar-card-meta"><?php if($s_kcal>0):?><strong><?php echo round($s_kcal);?></strong> kcal<?php endif;?><?php if($s_ido>0):?> · ⏱ <?php echo $s_ido;?> perc<?php endif;?></span>
                                    </div>
                                </a>
                            <?php endwhile; wp_reset_postdata(); endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $all_kats ) && ! is_wp_error( $all_kats ) ) : ?>
                    <div class="sidebar-section">
                        <h3 class="sidebar-section-title">Kategóriák</h3>
                        <div class="sidebar-kat-chips">
                            <?php foreach ( $all_kats as $kat ) : ?>
                                <a href="<?php echo esc_url( get_post_type_archive_link( 'recept' ) ); ?>?kategoria=<?php echo esc_attr( $kat->slug ); ?>" class="sidebar-kat-chip">
                                    <?php echo esc_html( $kat->name ); ?>
                                    <span class="sidebar-kat-count"><?php echo esc_html( $kat->count ); ?></span>
                                </a>
                            <?php endforeach; ?>
                            <a href="<?php echo esc_url( get_post_type_archive_link( 'recept' ) ); ?>" class="sidebar-kat-chip sidebar-kat-chip--all">Összes recept →</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </aside>

                <div class="recept-main-content">
                    <div class="recept-content-wrap">

                        <!-- HOZZÁVALÓK -->
                        <div class="recept-section">
                            <div class="recept-section-header"><span>Hozzávalók</span></div>

                            <!-- Akciógombok sáv -->
                            <div class="hozzavalo-toolbar">
                                <button type="button" class="toolbar-btn" id="btn-nyomtatas">🖨 Nyomtatás</button>
                                <button type="button" class="toolbar-btn" id="btn-bevasarlolista">📋 Lista megosztása</button>
                                <button type="button" class="toolbar-btn" id="btn-share">📤 Lista küldése</button>
                            </div>

                            <div class="recept-section-body">

                                <!-- Adag állító -->
                                <div class="adag-stepper">
                                    <span class="adag-stepper-label">Adagok</span>
                                    <div class="adag-stepper-controls">
                                        <button type="button" id="adag-minusz" class="adag-stepper-btn">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><line x1="2" y1="7" x2="12" y2="7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                        </button>
                                        <input type="number" id="adagok-input" value="<?php echo esc_attr( $adagok_szama ? $adagok_szama : 1 ); ?>" min="1" max="999" inputmode="numeric">
                                        <button type="button" id="adag-plusz" class="adag-stepper-btn">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><line x1="7" y1="2" x2="7" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="2" y1="7" x2="12" y2="7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <ul class="recept-osszetevok">
                                    <?php if ( have_rows( 'osszetevok', $current_id ) ) :
                                        $i = 0;
                                        while ( have_rows( 'osszetevok', $current_id ) ) : the_row();
                                            $mennyiseg    = get_sub_field( 'mennyiseg' );
                                            $mertekegyseg = get_sub_field( 'mertekegyseg' );
                                            $alapanyag    = get_sub_field( 'alapanyag' );
                                            $megjegyzes   = get_sub_field( 'megjegyzes' );
                                    ?>
                                        <li class="osszetevo-sor" id="osszetevo-<?php echo $i; ?>">
                                            <label class="osszetevo-checkbox-label"><input type="checkbox"></label>
                                            <div class="osszetevo-mennyiseg-wrap">
                                                <input type="number" step="0.1" min="0.1" max="99999" inputmode="decimal" class="osszetevo-mennyiseg-input" data-index="<?php echo $i; ?>" value="<?php echo esc_attr( $mennyiseg ); ?>">
                                                <span class="osszetevo-mertekegyseg"><?php echo esc_html( $mertekegyseg ); ?></span>
                                            </div>
                                            <span class="osszetevo-nev">
                                                <?php if ( $alapanyag ) echo esc_html( $alapanyag->post_title ); ?>
                                                <?php if ( $megjegyzes ) : ?><em>(<?php echo esc_html( $megjegyzes ); ?>)</em><?php endif; ?>
                                            </span>
                                        </li>
                                    <?php $i++; endwhile; else : echo '<li>Nincsenek összetevők megadva.</li>'; endif; ?>
                                </ul>

                                <button type="button" class="alaphelyzet-btn" id="alaphelyzet-btn">↺ Alaphelyzetbe állítás</button>
                            </div>
                        </div>

                        <!-- TÁPANYAG – kompakt -->
                        <div class="recept-section">
                            <div class="recept-section-header">Tápanyag (<span id="tapanyag-adag-label"><?php echo esc_html( $adagok_szama ?: 1 ); ?></span> adag)</div>
                            <div class="recept-section-body recept-section-body--makro">
                                <div class="makro-warning" style="display:none;"></div>
                                <div class="makro-strip">
                                    <div class="makro-strip-item">
                                        <span class="makro-strip-value makro-val-kcal">0</span>
                                        <span class="makro-strip-label">kcal</span>
                                    </div>
                                    <div class="makro-strip-divider"></div>
                                    <div class="makro-strip-item">
                                        <span class="makro-strip-value makro-val-feherje">0</span>
                                        <span class="makro-strip-label">fehérje (g)</span>
                                        <span class="makro-strip-pct"><span class="makro-pct-feherje">0</span>E%</span>
                                    </div>
                                    <div class="makro-strip-divider"></div>
                                    <div class="makro-strip-item">
                                        <span class="makro-strip-value makro-val-szenhidrat">0</span>
                                        <span class="makro-strip-label">szénhidrát (g)</span>
                                        <span class="makro-strip-pct"><span class="makro-pct-szenhidrat">0</span>E%</span>
                                    </div>
                                    <div class="makro-strip-divider"></div>
                                    <div class="makro-strip-item">
                                        <span class="makro-strip-value makro-val-zsir">0</span>
                                        <span class="makro-strip-label">zsír (g)</span>
                                        <span class="makro-strip-pct"><span class="makro-pct-zsir">0</span>E%</span>
                                    </div>
                                </div>
                                <div class="makro-per-adag" id="makro-per-adag"></div>
                                <div class="makro-info">
                                    <p><strong>Energia%</strong> (E%) azt mutatja, hogy az összes kalóriából mekkora arány jut fehérjére, szénhidrátra és zsírra &ndash; nem a tömeg (gramm) arányát.</p>
                                    <p class="makro-info-factors">Atwater-faktorok: 1 g fehérje / szénhidrát = 4,1 kcal &middot; 1 g zsír = 9,3 kcal</p>
                                </div>
                            </div>
                        </div>

                        <!-- LEÍRÁS -->
                        <?php $recept_leiras = get_field( 'recept_leiras', $current_id ); if ( $recept_leiras ) : ?>
                        <div class="recept-section">
                            <div class="recept-section-header">Leírás</div>
                            <div class="recept-section-body"><?php echo wp_kses_post( $recept_leiras ); ?></div>
                        </div>
                        <?php endif; ?>

                        <!-- ELKÉSZÍTÉS -->
                        <div class="recept-section">
                            <div class="recept-section-header">
                                Elkészítés
                                <span class="elkeszites-progress-wrap"><span class="elkeszites-progress-bar" id="elkeszites-progress-bar"></span></span>
                                <span class="elkeszites-progress-text" id="elkeszites-progress-text">0%</span>
                            </div>
                            <div class="recept-section-body recept-section-body--lepesek">
                                <ol class="recept-lepesek">
                                    <?php if ( have_rows( 'lepesek', $current_id ) ) :
                                        $lepes_idx = 0;
                                        while ( have_rows( 'lepesek', $current_id ) ) : the_row();
                                            $szoveg = get_sub_field( 'szoveg' );
                                            if ( ! $szoveg ) { continue; }
                                    ?>
                                        <li>
                                            <label class="lepes-checkbox-label">
                                                <input type="checkbox" class="lepes-checkbox" data-lepes="<?php echo $lepes_idx; ?>">
                                                <span class="lepes-text"><?php echo nl2br( esc_html( $szoveg ) ); ?></span>
                                            </label>
                                        </li>
                                    <?php $lepes_idx++; endwhile;
                                    else : echo '<li>Nincsenek lépések megadva.</li>'; endif; ?>
                                </ol>
                            </div>
                        </div>

                    </div>

                    <?php if ( trim( get_the_content() ) ) : ?>
                    <div class="recept-tartalom"><?php the_content(); ?></div>
                    <?php endif; ?>
                </div>

            </div>
        </article>
    <?php endwhile; ?>
</main>

<script id="recept-page-data">
    window.receptPageData = {
        title: <?php echo wp_json_encode( $recept_title ); ?>,
        url:   <?php echo wp_json_encode( $recept_url ); ?>
    };
</script>

<?php get_footer(); ?>