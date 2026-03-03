<?php
get_header();
?>

<main id="primary" class="site-main recept-single">
<div class="recept-print-header" aria-hidden="true">
    <div class="recept-print-logo">
        <?php
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'medium');
            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" class="recept-print-logo-img">';
        } else {
            echo '<span class="recept-print-site-name">' . esc_html(get_bloginfo('name')) . '</span>';
        }
        ?>
    </div>
    <div class="recept-print-title-wrap">
        <h1 class="recept-print-title"><?php the_title(); ?></h1>
        <p class="recept-print-url"><?php echo esc_url(get_the_permalink()); ?></p>
    </div>
</div>


    <?php
    while ( have_posts() ) : the_post();
        $elkeszitesi_ido = get_field( 'elkeszitesi_ido' );
        $adagok_szama    = get_field( 'adagok_szama' );
        $nehezsegi_fok   = get_field( 'nehezsegi_fok' );
        $current_id      = get_the_ID();
        $recept_url      = get_the_permalink();
        $recept_title    = get_the_title();

        $is_fav = false;
        if ( is_user_logged_in() && function_exists( 'dp_is_favorite' ) ) {
            $is_fav = dp_is_favorite( $current_id );
        }

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
                                if(have_rows('osszetevok')){while(have_rows('osszetevok')){the_row();$m=floatval(get_sub_field('mennyiseg'));$me=get_sub_field('mertekegyseg')?:'g';$a=get_sub_field('kapcsolodo_alapanyag');if($a&&is_object($a)){$gs=1;$map=array('g'=>1,'ml'=>1,'ek'=>15,'tk'=>5,'db'=>50,'csipet'=>1);if(isset($map[$me]))$gs=$map[$me];$s_kcal+=(floatval(get_field('kaloria',$a->ID))/100)*$m*$gs;}}}
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
                                    if(have_rows('osszetevok')){while(have_rows('osszetevok')){the_row();$m=floatval(get_sub_field('mennyiseg'));$me=get_sub_field('mertekegyseg')?:'g';$a=get_sub_field('kapcsolodo_alapanyag');if($a&&is_object($a)){$gs=1;$map=array('g'=>1,'ml'=>1,'ek'=>15,'tk'=>5,'db'=>50,'csipet'=>1);if(isset($map[$me]))$gs=$map[$me];$s_kcal+=(floatval(get_field('kaloria',$a->ID))/100)*$m*$gs;}}}
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
                                    if(have_rows('osszetevok')){while(have_rows('osszetevok')){the_row();$m=floatval(get_sub_field('mennyiseg'));$me=get_sub_field('mertekegyseg')?:'g';$a=get_sub_field('kapcsolodo_alapanyag');if($a&&is_object($a)){$gs=1;$map=array('g'=>1,'ml'=>1,'ek'=>15,'tk'=>5,'db'=>50,'csipet'=>1);if(isset($map[$me]))$gs=$map[$me];$s_kcal+=(floatval(get_field('kaloria',$a->ID))/100)*$m*$gs;}}}
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
                                <a href="<?php echo esc_url( get_term_link( $kat ) ); ?>" class="sidebar-kat-chip<?php if ( in_array( $kat->term_id, $current_kat_ids ) ) echo ' active'; ?>">
                                    <?php echo esc_html( $kat->name ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </aside>

                <div class="recept-main-content">
                    <div class="recept-content-wrap">

                        

                        <?php $recept_leiras = get_field( 'recept_leiras', $current_id ); if ( $recept_leiras ) : ?>
                        <div class="recept-section">
                            <div class="recept-section-header">Leírás</div>
                            <div class="recept-section-body"><?php echo wp_kses_post( $recept_leiras ); ?></div>
                        </div>
                        <?php endif; ?>

                        
<div class="recept-section">
                            <div class="recept-section-header"><span>Hozzávalók</span></div>

                            <div class="hozzavalo-toolbar">
                                <button type="button" class="toolbar-btn" id="btn-nyomtatas">🖨️ Mentés / Nyomtatás</button>
                                <button type="button" class="toolbar-btn" id="btn-bevasarlolista">📋 Lista megosztása</button>
                                <button type="button" class="toolbar-btn" id="btn-share">📤 Lista küldése</button>
                                <button type="button" class="toolbar-btn" id="btn-kedvenc" data-post-id="<?php echo $current_id; ?>" data-fav="<?php echo $is_fav ? '1' : '0'; ?>"><?php echo $is_fav ? '❤️ Hozzáadva' : '🤍 Kedvencekhez adás'; ?></button>
                            </div>

                            <div class="recept-section-body">
                                <div class="adag-stepper">
                                    <span class="adag-stepper-label">Adagok</span>
                                    <div class="adag-stepper-controls">
                                        <button type="button" id="adag-minusz" class="adag-stepper-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><line x1="2" y1="7" x2="12" y2="7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
                                        <input type="number" id="adagok-input" value="<?php echo esc_attr( $adagok_szama ? $adagok_szama : 1 ); ?>" min="1">
                                        <button type="button" id="adag-plusz" class="adag-stepper-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><line x1="7" y1="2" x2="7" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="2" y1="7" x2="12" y2="7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
                                    </div>
                                </div>
                                <ul class="recept-osszetevok">
                                    <?php if ( have_rows( 'osszetevok', $current_id ) ) :
                                        $i = 0;
                                        while ( have_rows( 'osszetevok', $current_id ) ) : the_row();
                                            $mennyiseg            = get_sub_field( 'mennyiseg' );
                                            $mertekegyseg         = get_sub_field( 'mertekegyseg' );
                                            $alapanyag            = get_sub_field( 'alapanyag' );
                                            $megjegyzes           = get_sub_field( 'megjegyzes' );
                                            $kapcsolodo_alapanyag = get_sub_field( 'kapcsolodo_alapanyag' );
                                    ?>
                                        <li class="osszetevo-sor" id="osszetevo-<?php echo $i; ?>">
                                            <label class="osszetevo-checkbox-label"><input type="checkbox"></label>
                                            <div class="osszetevo-mennyiseg-wrap">
                                                <input type="number" step="0.1" min="0" class="osszetevo-mennyiseg-input" data-index="<?php echo $i; ?>" value="<?php echo esc_attr( $mennyiseg ); ?>">
                                                <span class="osszetevo-mertekegyseg"><?php echo esc_html( $mertekegyseg ); ?></span>
                                            </div>
                                            <span class="osszetevo-nev">
                                                <?php if ( $alapanyag ) echo esc_html( $alapanyag ); ?>
                                                <?php if ( $megjegyzes ) : ?><em>(<?php echo esc_html( $megjegyzes ); ?>)</em><?php endif; ?>
                                            </span>
                                            <?php if ( $kapcsolodo_alapanyag && is_object( $kapcsolodo_alapanyag ) ) : ?>
                                                <a href="<?php echo esc_url( get_permalink( $kapcsolodo_alapanyag->ID ) ); ?>" class="osszetevo-reszletek" title="<?php echo esc_attr( $kapcsolodo_alapanyag->post_title ); ?> – részletes tápérték">
                                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.25 10.5L8.75 7L5.25 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    <span class="osszetevo-reszletek-text">Részletek</span>
                                                </a>
                                            <?php endif; ?>
                                        </li>
                                    <?php $i++; endwhile; else : echo '<li>Nincsenek összetevők megadva.</li>'; endif; ?>
                                </ul>
                                <button type="button" class="alaphelyzet-btn" id="alaphelyzet-btn">↺ Alaphelyzetbe állítás</button>
                            </div>
                        </div>

                        
<div class="recept-section">
                            <div class="recept-section-header">
                                Elkészítés
                                <span class="elkeszites-progress-wrap"><span class="elkeszites-progress-bar" id="elkeszites-progress-bar"></span></span>
                                <span class="elkeszites-progress-text" id="elkeszites-progress-text">0%</span>
                            </div>
                            <div class="recept-section-body" style="padding:0;">
                                <?php $lepesek = get_field( 'elkeszitesi_lepesek', $current_id ); ?>
                                <?php if ( $lepesek ) : ?>
                                <ol class="recept-lepesek" id="recept-lepesek">
                                    <?php foreach ( $lepesek as $lepes ) : ?>
                                        <li>
                                            <label class="lepes-checkbox-label">
                                                <input type="checkbox" class="lepes-checkbox">
                                                <span class="lepes-text"><?php echo wp_kses_post( $lepes['lepes_szoveg'] ); ?></span>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                                <?php else : echo '<p style="padding:20px 24px;color:#9ca3af;font-size:0.9rem;">Nincsenek lépések megadva.</p>'; endif; ?>
                            </div>
                        </div>

                        <?php $recept_tartalom = get_field( 'recept_tartalom', $current_id ); if ( $recept_tartalom ) : ?>
                        <div class="recept-tartalom">
                            <?php echo wp_kses_post( $recept_tartalom ); ?>
                        </div>
                        <?php endif; ?>

<div class="recept-section">
                            <div class="recept-section-header">Tápanyag (<span id="tapanyag-adag-label"><?php echo esc_html( $adagok_szama ?: 1 ); ?></span> adag)</div>
                            <div class="recept-section-body recept-section-body--makro">
                                <div class="makro-warning" style="display:none;"></div>
                                <div class="makro-strip">
                                    <div class="makro-strip-item"><span class="makro-strip-value makro-val-kcal">0<span class="makro-strip-unit">kcal</span></span><span class="makro-strip-label">Kalória</span></div>
                                    <div class="makro-strip-divider"></div>
                                    <div class="makro-strip-item"><span class="makro-strip-value makro-val-feherje">0<span class="makro-strip-unit">g</span></span><span class="makro-strip-label">Fehérje</span><span class="makro-strip-pct"><span class="makro-pct-feherje">0.0</span> energia%</span></div>
                                    <div class="makro-strip-divider"></div>
                                    <div class="makro-strip-item"><span class="makro-strip-value makro-val-szenhidrat">0<span class="makro-strip-unit">g</span></span><span class="makro-strip-label">Szénhidrát</span><span class="makro-strip-pct"><span class="makro-pct-szenhidrat">0.0</span> energia%</span></div>
                                    <div class="makro-strip-divider"></div>
                                    <div class="makro-strip-item"><span class="makro-strip-value makro-val-zsir">0<span class="makro-strip-unit">g</span></span><span class="makro-strip-label">Zsír</span><span class="makro-strip-pct"><span class="makro-pct-zsir">0.0</span> energia%</span></div>
                                </div>
                                <div class="makro-per-adag" id="makro-per-adag"></div>
                                <div class="makro-info">
                                    <p>Az <strong>energia%</strong> (energiaszázalék) az összes kalóriatartalomból az adott makrotápanyagra jutó kalória arányát jelenti – nem a tömeg (gramm) arányát.</p>
                                    <p class="makro-info-factors">A grammra számítás az Atwater-faktorokkal történik: 1 g szénhidrát/fehérje = 4,1 kcal, 1 g zsír = 9,3 kcal.</p>
                                </div>
                            </div>
                        </div>
                    </div>
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