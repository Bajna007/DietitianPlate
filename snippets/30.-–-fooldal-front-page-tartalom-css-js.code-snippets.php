<?php

/**
 * 30. – Főoldal (front-page) tartalom + CSS + JS
 */
/**
 * 30 – Főoldal: Front Page tartalom, CSS, JS (REWORK v2)
 */

function taplex_render_frontpage() {
    if ( ! is_front_page() ) return;

    $receptek = new WP_Query([
        'post_type'               => 'recept',
        'posts_per_page'          => 4,
        'orderby'                 => 'date',
        'order'                   => 'DESC',
        'no_found_rows'           => true,
        'update_post_meta_cache'  => false,
        'update_post_term_cache'  => false,
    ]);

    $count_tapanyag = wp_count_posts('tapanyag')->publish ?? 0;
    $count_recept   = wp_count_posts('recept')->publish ?? 0;

    // 24 random alapanyag AJAX nélkül PHP-ból (hatékonyabb, nincs extra request)
    $alapanyagok = new WP_Query([
        'post_type'               => 'alapanyag',
        'no_found_rows'           => true,
        'update_post_term_cache'  => false,
        'posts_per_page' => 24,
        'orderby'        => 'rand',
        'fields'         => 'ids',
    ]);
    $aa_ids = $alapanyagok->posts;

    taplex_fp_css();
    ?>

<div class="tl-fp">

    <!-- ═══ HERO ═══ -->
    <section class="tl-hero">
        <!-- SVG FOG -->
        <svg class="tl-hero-fog" viewBox="0 0 1440 600" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" style="position:absolute;inset:0;width:100%;height:100%;z-index:0;pointer-events:none;display:block;overflow:visible">
            <defs>
                <filter id="fogBlur" x="-50%" y="-50%" width="200%" height="200%">
                    <feGaussianBlur stdDeviation="60"/>
                </filter>
            </defs>
            <!-- Nagy blob-ok – erős, szembetűnő fátyol -->
            <ellipse class="tl-fog-blob tl-fog-b1" cx="1100" cy="150" rx="420" ry="300" fill="rgba(34,100,60,0.62)"/>
            <ellipse class="tl-fog-blob tl-fog-b2" cx="200"  cy="400" rx="380" ry="280" fill="rgba(56,161,105,0.55)"/>
            <ellipse class="tl-fog-blob tl-fog-b3" cx="720"  cy="300" rx="350" ry="260" fill="rgba(34,100,60,0.48)"/>
            <ellipse class="tl-fog-blob tl-fog-b4" cx="1300" cy="480" rx="300" ry="220" fill="rgba(72,187,120,0.52)"/>
            <ellipse class="tl-fog-blob tl-fog-b5" cx="350"  cy="120" rx="320" ry="240" fill="rgba(39,117,71,0.50)"/>
            <ellipse class="tl-fog-blob tl-fog-b6" cx="900"  cy="500" rx="360" ry="200" fill="rgba(56,161,105,0.45)"/>
        </svg>
        <div class="tl-hero-bg">
            <div class="tl-hero-orb tl-hero-orb--1"></div>
            <div class="tl-hero-orb tl-hero-orb--2"></div>
            <div class="tl-hero-orb tl-hero-orb--3"></div>
            <div class="tl-hero-pattern"></div>
            <div class="tl-hero-grid"></div>
        </div>
        <div class="tl-c">
            <div class="tl-hero-inner">
                <div class="tl-hero-text">
                    <span class="tl-hero-tag tl-a" data-anim="fade-down">Táplálkozástudomány – tényszerűen, magyarázattal</span>
                    <h1 class="tl-hero-title tl-a" data-anim="fade-up">
                        Az ételed<br><span class="tl-hero-accent">formál téged.</span>
                    </h1>
                    <p class="tl-hero-desc tl-a" data-anim="fade-up" data-delay="80">
                        Célunk, hogy tudd, mi kerül a tányérodra – és azt is, hogy ez miért fontos. 							Rendbe tesszük a tévhiteket, kiszűrjük az áltudományos maszlagot, és érthetően, 						bizonyítékokkal és magyarázattal alátámasztva mutatjuk meg a lényeget.
                    </p>
                    <div class="tl-hero-btns tl-a" data-anim="fade-up" data-delay="160">
                        <a href="<?php echo esc_url(home_url('/bmr-kalkulator/')); ?>" class="tl-btn tl-btn--primary">
                            Kalória kalkulátor
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                        <a href="<?php echo esc_url(home_url('/receptek/')); ?>" class="tl-btn tl-btn--sec">Receptek böngészése</a>
                    </div>
                    <div class="tl-hero-chips tl-a" data-anim="fade-up" data-delay="240">
                        <span class="tl-chip">🍽️ Makró kalkulátor</span>
                        <span class="tl-chip">📊 Személyre szabott célok</span>
                    </div>
                </div>
                <div class="tl-hero-pillars tl-a" data-anim="fade-up" data-delay="240">
                    <div class="tl-hero-pillar">
                        <span class="tl-hero-pillar-icon">🔬</span>
                        <span class="tl-hero-pillar-title">Bizonyítékalapú</span>
                        <span class="tl-hero-pillar-desc">Tudományosan alátámasztott tartalom</span>
                    </div>
                    <div class="tl-hero-pillar">
                        <span class="tl-hero-pillar-icon">📊</span>
                        <span class="tl-hero-pillar-title">Kalkulátorok</span>
                        <span class="tl-hero-pillar-desc">BMR, fogyás- és energiaszükséglet kalkuláció</span>
                    </div>
                    <div class="tl-hero-pillar">
                        <span class="tl-hero-pillar-icon">🧬</span>
                        <span class="tl-hero-pillar-title">Tápanyag-adatbázis</span>
                        <span class="tl-hero-pillar-desc">Részletes információk minden alapanyagról és 							élelmiszerről</span>
                    </div>
                </div>
                <div class="tl-hero-trust tl-a" data-anim="fade-up" data-delay="300">
                    <span class="tl-hero-ftag">🔬 Magyarázatokkal</span>
                    <span class="tl-hero-ftag">📖 Tanulmányokkal alátámasztva</span>
                    <span class="tl-hero-ftag">✅ Reklámmentes</span>
                </div>
            </div>
        </div>
    </section>

    <!-- DIVIDER -->
    <div class="tl-divider"><span>🥗</span><span>⚖️</span><span>💪</span><span>🧬</span><span>📊</span><span>🥦</span><span>🔬</span><span>🧪</span></div>

    <!-- ═══ KALÓRIA KALKULÁTOR CTA ═══ -->
    <section class="tl-sec tl-sec--kalk">
        <div class="tl-c">
            <div class="tl-kalk-wrap tl-a" data-anim="fade-up">
                <div class="tl-kalk-left">
                    <span class="tl-badge tl-badge--accent">📊 Kalkulátor</span>
                    <h2 class="tl-kalk-title">Mennyi kalóriát kéne bevinnem naponta?</h2>
                    <div class="tl-disclaimer tl-a" data-anim="fade-up" data-delay="20">
                        <span class="tl-disclaimer-icon">⚠️</span>
                        <span class="tl-disclaimer-text">Ez a kalkulátor kizárólag tájékoztató jellegű – nem minősül orvosi tanácsadásnak. Személyre szabott étrendi tájékoztatásért vagy tanácsért fordulj dietetikushoz vagy orvoshoz.</span>
                    </div>
                    <p class="tl-kalk-desc">
                        A kalóriaszükségleted egyéni – függ a korodtól, súlyodtól,
                        aktivitásodtól - de meglehetősen pontosan ki lehet számolni. Tedd meg most, pár 							perc alatt!
                    </p>
                    <ul class="tl-kalk-list">
                        <li class="tl-a" data-anim="fade-right" data-delay="60"><span class="tl-kalk-li-dot"></span>Alapanyagcsere (BMR) és teljes napi energiaszükséglet</li>
                        <li class="tl-a" data-anim="fade-right" data-delay="120"><span class="tl-kalk-li-dot"></span>Fehérje, zsír, szénhidrát arányok célhoz kötötten</li>
                        <li class="tl-a" data-anim="fade-right" data-delay="180"><span class="tl-kalk-li-dot"></span>Fogyás, szintentartás, tömegnövelés kalkuláció</li>
                        <li class="tl-a" data-anim="fade-right" data-delay="240"><span class="tl-kalk-li-dot"></span>Ingyenesen, mobilon is, regisztráció nélkül</li>
                    </ul>
                    <a href="<?php echo esc_url(home_url('/bmr-kalkulator/')); ?>" class="tl-btn tl-btn--primary tl-a" data-anim="fade-up" data-delay="300">
                        Kalória kalkulátor megnyitása
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
                <div class="tl-kalk-right tl-a" data-anim="fade-left" data-delay="100">
                    <div class="tl-kalk-card">
                        <div class="tl-kalk-card-header">
                            <svg class="tl-kalk-card-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C12 2 8 7 8 11a4 4 0 008 0c0-1.5-.8-3-1.5-4 0 0-.2 1.5-1 2-.5-2-1.5-4.5-1.5-7z" fill="#2d6a4f" opacity=".9"/>
                                <path d="M12 13c0 1.1-.9 2-2 2s-2-.9-2-2c0-1.5 2-4 2-4s2 2.5 2 4z" fill="#52b788" opacity=".7"/>
                                <ellipse cx="12" cy="20" rx="5" ry="1.5" fill="#2d6a4f" opacity=".15"/>
                            </svg>
                            <span class="tl-kalk-card-title">Napi kalóriaszükséglet</span>
                        </div>
                        <div class="tl-kalk-demo-bars">
                            <div class="tl-kalk-bar-row">
                                <span>Fehérje</span>
                                <div class="tl-kalk-bar"><div class="tl-kalk-bar-fill" style="width:30%;background:#3B82F6" data-w="30"></div></div>
                                <span class="tl-kalk-bar-val">30%</span>
                            </div>
                            <div class="tl-kalk-bar-row">
                                <span>Szénhidrát</span>
                                <div class="tl-kalk-bar"><div class="tl-kalk-bar-fill" style="width:45%;background:#10B981" data-w="45"></div></div>
                                <span class="tl-kalk-bar-val">45%</span>
                            </div>
                            <div class="tl-kalk-bar-row">
                                <span>Zsír</span>
                                <div class="tl-kalk-bar"><div class="tl-kalk-bar-fill" style="width:25%;background:#F59E0B" data-w="25"></div></div>
                                <span class="tl-kalk-bar-val">25%</span>
                            </div>
                        </div>
                        <div class="tl-kalk-card-cta">
                            <span>Számítsd ki a sajátodat →</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- DIVIDER -->
    <div class="tl-divider tl-divider--alt"><span>🍳</span><span>🥑</span><span>🐟</span><span>🥚</span><span>🫐</span><span>🥩</span><span>🍠</span><span>🧀</span></div>

    <!-- ═══ LEGÚJABB RECEPTEK ═══ -->
    <section class="tl-sec tl-sec--recipes">
        <!-- Dekoratív háttér SVG minta -->
        <svg class="tl-rec-pattern" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <defs><pattern id="leafPat" x="0" y="0" width="80" height="80" patternUnits="userSpaceOnUse">
                <ellipse cx="20" cy="20" rx="10" ry="5" fill="none" stroke="#2d6a4f" stroke-width="1.2" transform="rotate(-30 20 20)"/>
                <ellipse cx="60" cy="55" rx="12" ry="6" fill="none" stroke="#2d6a4f" stroke-width="1" transform="rotate(20 60 55)"/>
                <circle cx="40" cy="10" r="2" fill="#2d6a4f" opacity=".5"/>
                <circle cx="10" cy="60" r="1.5" fill="#2d6a4f" opacity=".4"/>
                <line x1="20" y1="20" x2="28" y2="32" stroke="#2d6a4f" stroke-width=".8" opacity=".6"/>
                <line x1="60" y1="55" x2="52" y2="44" stroke="#2d6a4f" stroke-width=".8" opacity=".5"/>
            </pattern></defs>
            <rect width="100%" height="100%" fill="url(#leafPat)"/>
        </svg>
        <div class="tl-c">

            <div class="tl-sec-hd tl-a" data-anim="fade-up">
                <span class="tl-badge">🍳 Receptek</span>
                <h2 class="tl-sec-title">Legújabb receptek</h2>
                <p class="tl-sec-sub">Tápanyagdús receptek tájékoztató makrotápanyag adatokkal – a tudatos konyha kiindulópontja.</p>
            </div>

            <!-- Intro sor – highlight statisztikák -->
            <div class="tl-rec-intro tl-a" data-anim="fade-up" data-delay="40">
                <div class="tl-rec-intro-item">
                    <span class="tl-rec-intro-icon">🥗</span>
                    <span class="tl-rec-intro-txt">Receptjeinknél feltüntettük a <strong>makrotápanyagok</strong> mennyiségét</span>
                </div>
                <div class="tl-rec-intro-item">
                    <span class="tl-rec-intro-icon">⏱️</span>
                    <span class="tl-rec-intro-txt">Egyszerű, <strong>valódi alapanyagokból</strong> készülő ételek</span>
                </div>
                <div class="tl-rec-intro-item">
                    <span class="tl-rec-intro-icon">🎯</span>
                    <span class="tl-rec-intro-txt">Célhoz igazítható – <strong>fogyás, tömegnövelés, vagy szimplán kiegyensúlyozott, egészséges táplálkozás</strong></span>
                </div>
            </div>

            <div class="tl-recipe-grid">
                <?php $ri=0; while($receptek->have_posts()): $receptek->the_post(); $ri++;
                    // Makrók – cache-ből vagy számítás + mentés
                    $post_id=get_the_ID();
                    $c_kal=get_post_meta($post_id,'_fp_kcal',true);
                    $c_feh=get_post_meta($post_id,'_fp_feh',true);
                    $c_szh=get_post_meta($post_id,'_fp_szh',true);
                    $c_zsir=get_post_meta($post_id,'_fp_zsir',true);
                    if($c_kal!==''&&$c_kal!==false&&is_numeric($c_kal)){
                        $kal=$c_kal>0?$c_kal:'';
                        $feh=$c_feh>0?$c_feh:'';
                        $szh=$c_szh>0?$c_szh:'';
                        $zsir=$c_zsir>0?$c_zsir:'';
                    } else {
                        $kal=0;$feh=0;$szh=0;$zsir=0;
                        $me_map=array('g'=>1,'ml'=>1,'ek'=>15,'tk'=>5,'db'=>50,'csipet'=>1);
                        $osszetevok=get_field('osszetevok',$post_id);
                        if(!empty($osszetevok)&&is_array($osszetevok)){
                            foreach($osszetevok as $sor){
                                $men=floatval($sor['mennyiseg']??0);
                                $me=$sor['mertekegyseg']??'g';
                                $aa=$sor['kapcsolodo_alapanyag']??null;
                                if($aa&&is_object($aa)&&$men>0){
                                    $szorzo=isset($me_map[$me])?$me_map[$me]:1;
                                    $gramm=$men*$szorzo;
                                    $kal+=floatval(get_field('kaloria',$aa->ID))*$gramm/100;
                                    $feh+=floatval(get_field('feherje',$aa->ID))*$gramm/100;
                                    $szh+=floatval(get_field('szenhidrat',$aa->ID))*$gramm/100;
                                    $zsir+=floatval(get_field('zsir',$aa->ID))*$gramm/100;
                                }
                            }
                        }
                        $kal=round($kal); $feh=round($feh,1);
                        $szh=round($szh,1); $zsir=round($zsir,1);
                        update_post_meta($post_id,'_fp_kcal',$kal);
                        update_post_meta($post_id,'_fp_feh',$feh);
                        update_post_meta($post_id,'_fp_szh',$szh);
                        update_post_meta($post_id,'_fp_zsir',$zsir);
                        $kal=$kal>0?$kal:''; $feh=$feh>0?$feh:'';
                        $szh=$szh>0?$szh:''; $zsir=$zsir>0?$zsir:'';
                    }
                    $rc=get_the_terms(get_the_ID(),'recept_kategoria');
                    $rn=($rc&&!is_wp_error($rc))?$rc[0]->name:'';
                ?>
                <a href="<?php the_permalink(); ?>" class="tl-rcard tl-a" data-anim="<?php echo $ri%2===1?'fade-right':'fade-left'; ?>" data-delay="<?php echo $ri*90; ?>">
                    <div class="tl-rcard-img">
                        <?php if(has_post_thumbnail()): the_post_thumbnail('medium_large',['loading'=>$ri===1?'eager':'lazy','fetchpriority'=>$ri===1?'high':'auto']); else: ?>
                        <div class="tl-rcard-img-ph">🍽️</div>
                        <?php endif; ?>
                        <?php if($rn): ?><span class="tl-rcard-cat"><?php echo esc_html($rn); ?></span><?php endif; ?>
                        
                    </div>
                    <div class="tl-rcard-body">
                        <h3 class="tl-rcard-title"><?php the_title(); ?></h3>
                        <?php if($kal||$feh||$szh||$zsir): ?>
                        <div class="tl-rcard-info">
                            <?php if($kal): ?><span class="tl-rcard-info-chip tl-rcard-info-kcal"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2c0 0-4 5-4 9a4 4 0 008 0c0-1.5-.8-3-1.5-4 0 0-.2 1.5-1 2-.5-2-1.5-4.5-1.5-7z" fill="#2d6a4f"/></svg><?php echo esc_html($kal); ?> kcal</span><?php endif; ?>
                            <?php if($feh): ?><span class="tl-rcard-info-chip tl-rcard-info-prot"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12c0 0 1-3 3-4s4 0 5 2-1 5-3 6-5-1-5-4z" fill="none" stroke="#3b82f6" stroke-width="1.8" stroke-linecap="round"/><path d="M7 20c0-2 2-4 5-4s5 2 5 4" stroke="#3b82f6" stroke-width="1.8" stroke-linecap="round"/><path d="M9 8V5M12 7V4M15 8V5" stroke="#3b82f6" stroke-width="1.8" stroke-linecap="round"/></svg><?php echo esc_html($feh); ?>g fehérje</span><?php endif; ?>
                            <?php if($szh): ?><span class="tl-rcard-info-chip tl-rcard-info-carb"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 3c0 0 0 4-4 6" stroke="#d97706" stroke-width="1.8" stroke-linecap="round"/><path d="M12 3c0 0 0 4 4 6" stroke="#d97706" stroke-width="1.8" stroke-linecap="round"/><path d="M12 3v16" stroke="#d97706" stroke-width="1.8" stroke-linecap="round"/><path d="M9 8c0 0-3 2-3 5" stroke="#d97706" stroke-width="1.5" stroke-linecap="round"/><path d="M15 8c0 0 3 2 3 5" stroke="#d97706" stroke-width="1.5" stroke-linecap="round"/><path d="M9 12c0 0-2 1-2 3" stroke="#d97706" stroke-width="1.4" stroke-linecap="round"/><path d="M15 12c0 0 2 1 2 3" stroke="#d97706" stroke-width="1.4" stroke-linecap="round"/></svg><?php echo esc_html($szh); ?>g szénhidrát</span><?php endif; ?>
                            <?php if($zsir): ?><span class="tl-rcard-info-chip tl-rcard-info-fat"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><ellipse cx="12" cy="13" rx="5" ry="7" stroke="#10b981" stroke-width="1.8"/><path d="M12 6V3" stroke="#10b981" stroke-width="1.8" stroke-linecap="round"/><path d="M10 4c0 0 1-2 2-1s1 2 2 1" stroke="#10b981" stroke-width="1.5" stroke-linecap="round"/><ellipse cx="10.5" cy="11" rx="1.5" ry="2.5" fill="#10b981" opacity=".25" transform="rotate(-15 10.5 11)"/></svg><?php echo esc_html($zsir); ?>g zsír</span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <span class="tl-rcard-arrow">Tovább a recept oldalra →</span>
                    </div>
                </a>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>

            <div class="tl-sec-footer tl-a" data-anim="fade-up">
                <a href="<?php echo esc_url(home_url('/receptek/')); ?>" class="tl-btn tl-btn--outline">Összes recept megtekintése</a>
            </div>
        </div>
    </section>

    <!-- ═══ ALAPANYAG VÉGTELEN SLIDER<!-- ═══ ALAPANYAG VÉGTELEN SLIDER ═══ -->
    <section class="tl-sec tl-sec--aa-slider">
        <div class="tl-c">
            <div class="tl-sec-hd tl-a" data-anim="fade-up">
                <span class="tl-badge tl-badge--purple">🧬 Adatbázis</span>
                <h2 class="tl-sec-title">Alapanyag adatbázis</h2>
                <p class="tl-sec-sub">Több száz alapanyag tápértékadatokkal, USDA, OFF és saját forrásból – keress, böngéssz, fedezz fel.</p>
            </div>
        </div>
        <div class="tl-aa-track-wrap">
            <div class="tl-aa-track" id="tl-aa-track">
                <?php
                $all_ids = array_merge($aa_ids, $aa_ids);
                foreach($all_ids as $aa_id):
                    $aa_img   = get_the_post_thumbnail_url($aa_id, 'thumbnail');
                    $aa_title = get_the_title($aa_id);
                    $aa_orig  = get_field('eredeti_nev', $aa_id);
                    $aa_kcal  = get_field('kaloria', $aa_id);
                    $aa_prot  = get_field('feherje', $aa_id);
                    $aa_carb  = get_field('szenhidrat', $aa_id);
                    $aa_fat   = get_field('zsir', $aa_id);
                    $aa_usda  = get_field('usda', $aa_id);
                    $aa_url   = get_permalink($aa_id);
                ?>
                <a href="<?php echo esc_url($aa_url); ?>" class="tl-sl-card">
                    <?php if($aa_usda): ?><span class="tl-sl-badge tl-sl-badge--usda">USDA</span><?php endif; ?>
                    <div class="tl-sl-card-header">
                        <div class="tl-sl-card-img">
                            <?php if($aa_img): ?>
                            <img src="<?php echo esc_url($aa_img); ?>" alt="<?php echo esc_attr($aa_title); ?>" loading="lazy">
                            <?php else: ?>
                            <div class="tl-sl-card-ph">🌿</div>
                            <?php endif; ?>
                        </div>
                        <div class="tl-sl-card-meta">
                            <h3 class="tl-sl-card-title"><?php echo esc_html($aa_title); ?></h3>
                            <?php if($aa_orig && $aa_orig !== $aa_title): ?>
                            <span class="tl-sl-card-orig"><?php echo esc_html($aa_orig); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tl-sl-card-kcal">
                        <span class="tl-sl-kcal-num"><?php echo $aa_kcal ? esc_html(round((float)$aa_kcal)) : '–'; ?></span>
                        <div class="tl-sl-kcal-side">
                            <span class="tl-sl-kcal-unit">kcal</span>
                            <span class="tl-sl-kcal-per">/ 100g</span>
                        </div>
                    </div>
                    <div class="tl-sl-card-nutrients">
                        <div class="tl-sl-nutrient tl-sl-nutrient--prot">
                            <span class="tl-sl-nut-val"><?php echo $aa_prot ? number_format((float)$aa_prot,1).'<span class="tl-sl-nut-g">g</span>' : '–'; ?></span>
                            <span class="tl-sl-nut-name">fehérje</span>
                        </div>
                        <div class="tl-sl-nut-div"></div>
                        <div class="tl-sl-nutrient tl-sl-nutrient--carb">
                            <span class="tl-sl-nut-val"><?php echo $aa_carb ? number_format((float)$aa_carb,1).'<span class="tl-sl-nut-g">g</span>' : '–'; ?></span>
                            <span class="tl-sl-nut-name">szénhidrát</span>
                        </div>
                        <div class="tl-sl-nut-div"></div>
                        <div class="tl-sl-nutrient tl-sl-nutrient--fat">
                            <span class="tl-sl-nut-val"><?php echo $aa_fat ? number_format((float)$aa_fat,1).'<span class="tl-sl-nut-g">g</span>' : '–'; ?></span>
                            <span class="tl-sl-nut-name">zsír</span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="tl-c">
            <div class="tl-sec-footer tl-a" data-anim="fade-up">
                <a href="<?php echo esc_url(home_url('/alapanyagok/')); ?>" class="tl-btn tl-btn--outline">Alapanyag adatbázis böngészése</a>
            </div>
        </div>
    </section>

    <!-- ═══ STATS ═══ -->
    <section class="tl-sec tl-sec--stats">
        <div class="tl-c">
            <div class="tl-stats-grid">
                <div class="tl-stat tl-a" data-anim="fade-up" data-delay="0">
                    <span class="tl-stat-icon">🧬</span>
                    <span class="tl-stat-num" data-count="<?php echo (int)$count_tapanyag; ?>">0</span>
                    <span class="tl-stat-lbl">Tápanyag leírás</span>
                </div>
                <div class="tl-stat tl-a" data-anim="fade-up" data-delay="80">
                    <span class="tl-stat-icon">🍳</span>
                    <span class="tl-stat-num" data-count="<?php echo (int)$count_recept; ?>">0</span>
                    <span class="tl-stat-lbl">Recept</span>
                </div>
                <div class="tl-stat tl-a" data-anim="fade-up" data-delay="160">
                    <span class="tl-stat-icon">🎯</span>
                    <span class="tl-stat-num" data-count="100">0</span>
                    <span class="tl-stat-lbl">% ingyenes</span>
                </div>
                <div class="tl-stat tl-a" data-anim="fade-up" data-delay="240">
                    <span class="tl-stat-icon">📊</span>
                    <span class="tl-stat-num" data-count="4">0</span>
                    <span class="tl-stat-lbl">Kalkulátor</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ FINAL CTA ═══ -->
    <section class="tl-sec tl-sec--cta">
        <div class="tl-c">
            <div class="tl-cta-wrap tl-a" data-anim="fade-up">
                <span class="tl-cta-icon">🚀</span>
                <h2 class="tl-cta-title">Tudatosan táplálkozni jó –<br>szakemberrel még jobb.</h2>
                <p class="tl-cta-desc">Az weboldalon található információk tájékoztató jellegűek. A személyre szabott étrendet és célokat egy dietetikussal konzultálva érdemes meghatározni.</p>
                <div class="tl-cta-btns">
                    <a href="<?php echo esc_url(home_url('/bmr-kalkulator/')); ?>" class="tl-btn tl-btn--primary">Kalória kalkulátor</a>
                    <a href="<?php echo esc_url(home_url('/celjaim/')); ?>" class="tl-btn tl-btn--white">Céljaim beállítása</a>
                </div>
            </div>
        </div>
    </section>

</div>
<?php
    taplex_fp_js();
}

// ═══════════════════════════════════════════════════════════════════════════════
// CSS
// ═══════════════════════════════════════════════════════════════════════════════
function taplex_fp_css(){
    if(!is_front_page()) return;
    ?><style id="taplex-fp-css">

    .tl-fp{
        --g:#2d6a4f;--g-dk:#1b4332;--g-lt:#40b882;--g-s:rgba(45,106,79,.09);
        --or:#F59E0B;--bl:#3B82F6;--rd:#EF4444;--pu:#8B5CF6;--em:#10B981;
        --bg:#f8faf9;--bg2:#eef2ef;--card:#fff;--brd:#e4e9e6;
        --tx:#1a1f1c;--mu:#6b7a72;
        --r:16px;--rs:10px;--rl:24px;
        --sh:0 4px 24px rgba(0,0,0,.06);--shl:0 8px 40px rgba(0,0,0,.09);--shx:0 16px 56px rgba(0,0,0,.12);
        --tr:.35s cubic-bezier(.4,0,.2,1);
        overflow-x:hidden;background:var(--bg)
    }
    .tl-fp *,.tl-fp *::before,.tl-fp *::after{box-sizing:border-box}
    .tl-c{max-width:1200px;margin:0 auto;padding:0 24px;position:relative}

    /* ── ANIMÁCIÓK ── */
    .tl-a{opacity:0;transition:opacity .5s cubic-bezier(.4,0,.2,1),transform .5s cubic-bezier(.4,0,.2,1)}
    .tl-a[data-anim="fade-up"]{transform:translateY(32px)}
    .tl-a[data-anim="fade-down"]{transform:translateY(-24px)}
    .tl-a[data-anim="fade-right"]{transform:translateX(-48px)}
    .tl-a[data-anim="fade-left"]{transform:translateX(48px)}
    .tl-a[data-anim="zoom-in"]{transform:scale(.92)}
    .tl-a.is-visible{opacity:1;transform:translate(0) scale(1)}

    /* ── GOMBOK ── */
    .tl-btn{display:inline-flex;align-items:center;gap:7px;padding:12px 24px;font-size:14px;font-weight:700;border-radius:var(--rs);text-decoration:none;border:none;cursor:pointer;white-space:nowrap;transition:transform var(--tr),box-shadow var(--tr),background var(--tr),color var(--tr);letter-spacing:-.01em}
    .tl-btn--primary{background:var(--g);color:#fff;box-shadow:0 4px 16px rgba(45,106,79,.3);border:2px solid transparent}
    .tl-btn--primary:hover{background:transparent;color:var(--g);border-color:var(--g);transform:translateY(-2px);box-shadow:none}
    .tl-btn--sec{background:rgba(45,106,79,.08);color:var(--g-dk);border:1.5px solid rgba(45,106,79,.2)}
    .tl-btn--sec:hover{background:rgba(45,106,79,.14);transform:translateY(-2px)}
    .tl-btn--outline{background:transparent;color:var(--g);border:2px solid var(--g);padding:11px 24px}
    .tl-btn--outline:hover{background:var(--g);color:#fff;transform:translateY(-2px)}
    .tl-btn--white{background:#fff;color:var(--g-dk);border:none;box-shadow:0 2px 12px rgba(0,0,0,.12)}
    .tl-btn--white:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.18)}

    /* ── DISCLAIMER ── */
    .tl-disclaimer{display:flex;align-items:flex-start;gap:12px;padding:13px 16px;background:rgba(45,106,79,.07);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border-radius:11px;border:1px solid rgba(45,106,79,.18);margin-bottom:18px}
    .tl-disclaimer-icon{font-size:14px;flex-shrink:0;margin-top:2px;opacity:.9}
    .tl-disclaimer-text{font-size:12px;color:#3a5a47;line-height:1.7;font-weight:500}

    /* ── FLOATING TRUST TAGS ── */
    .tl-hero-trust{display:flex;flex-direction:column;gap:6px;margin-top:16px;padding-top:14px;border-top:1px solid var(--brd)}
    .tl-hero-ftag{font-size:11px;font-weight:700;color:var(--mu);display:flex;align-items:center;gap:6px;background:var(--bg2);padding:5px 10px;border-radius:8px;white-space:nowrap}

    /* ── DIVIDER SZALAG ── */
    .tl-divider{display:flex;align-items:center;justify-content:center;gap:28px;padding:16px 0;background:var(--bg2);border-top:1px solid var(--brd);border-bottom:1px solid var(--brd);overflow:hidden}
    .tl-divider span{font-size:1.35rem;opacity:.5;animation:tlDivPulse 4s ease-in-out infinite}
    .tl-divider span:nth-child(2n){animation-delay:.5s}.tl-divider span:nth-child(3n){animation-delay:1s}.tl-divider span:nth-child(4n){animation-delay:1.5s}
    .tl-divider--alt{background:linear-gradient(90deg,rgba(45,106,79,.04),rgba(139,92,246,.05),rgba(45,106,79,.04))}
    @keyframes tlDivPulse{0%,100%{transform:scale(1);opacity:.5}50%{transform:scale(1.2);opacity:.8}}

    /* ── KALK KÁRTYA NOTE ── */
    .tl-kalk-card-note{text-align:center;font-size:11px;color:var(--mu);margin-top:8px;display:flex;justify-content:center;align-items:center;gap:4px}
    .tl-rec-fact-icon{flex-shrink:0;margin-top:1px}
    .tl-rec-fact-txt{font-size:13px;color:#3a5a47;font-weight:500;line-height:1.6}

    @media(max-width:600px){
        .tl-rcard-img{height:150px}
        .tl-rcard-body{padding:14px 16px}
        .tl-rcard-title{font-size:14px}
        .tl-rcard-excerpt{display:none}
        .tl-recipe-grid{grid-template-columns:1fr;gap:14px}
        .tl-rec-fact{grid-column:1/-1}
        .tl-rcard{flex-direction:row;min-height:110px;border-radius:12px}
        .tl-rcard .tl-rcard-img{width:110px;height:100%;flex-shrink:0;border-radius:0;min-height:110px}
        .tl-rcard .tl-rcard-img img{height:100%;width:100%;object-fit:cover}
        .tl-rcard .tl-rcard-img::after{background:linear-gradient(to right,rgba(0,0,0,.25) 0%,transparent 50%)}
        .tl-rcard .tl-rcard-kcal-badge{bottom:6px;right:auto;left:6px;font-size:10px}
        .tl-rcard .tl-rcard-macros{display:none}
        .tl-rcard .tl-rcard-body{border-left:none;padding:10px 12px;justify-content:center;gap:4px}
        .tl-rcard .tl-rcard-excerpt{display:none}
        .tl-rcard .tl-rcard-macros{display:none}
        .tl-rcard .tl-rcard-info{display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin:2px 0 4px}
        .tl-rcard .tl-rcard-arrow{font-size:11px;margin-top:auto}
        .tl-rcard .tl-rcard-title{font-size:13px;-webkit-line-clamp:2}
    }
    /* ── RECEPT INFO SOR ── */
    .tl-rcard-info{display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin:6px 0 8px}
    .tl-rcard-info-chip{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;white-space:nowrap}
    .tl-rcard-info-kcal{background:rgba(45,106,79,.08);color:#2d6a4f}
    .tl-rcard-info-prot{background:rgba(59,130,246,.08);color:#1d4ed8}
    .tl-rcard-info-carb{background:rgba(217,119,6,.08);color:#92400e}
    .tl-rcard-info-fat{background:rgba(16,185,129,.08);color:#065f46}

    /* Makró tag-ek kompaktabb */
    .tl-rcard-macro{font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px}
    .tl-rcard-macro--prot{background:rgba(59,130,246,.1);color:#1d4ed8}
    .tl-rcard-macro--carb{background:rgba(16,185,129,.1);color:#065f46}
    .tl-rcard-macro--fat{background:rgba(245,158,11,.1);color:#78350f}

    /* ── RECEPT EXCERPT ── */
    .tl-rcard-excerpt{font-size:13px;color:var(--mu);line-height:1.65;margin:0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}

    /* ── BADGE / CHIP ── */
    .tl-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 13px;border-radius:999px;font-size:12px;font-weight:700;background:var(--g-s);color:var(--g-dk);margin-bottom:12px}
    .tl-badge--accent{background:rgba(245,158,11,.1);color:#92400e}
    .tl-badge--purple{background:rgba(139,92,246,.1);color:#5b21b6}
    .tl-chip{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:999px;font-size:12px;font-weight:600;background:#fff;color:var(--mu);border:1px solid var(--brd);box-shadow:var(--sh)}

    /* ── SZEKCIÓ KÖZÖS ── */
    .tl-sec{padding:80px 0}
    .tl-sec-hd{text-align:center;margin-bottom:48px}
    .tl-sec-title{font-size:clamp(1.6rem,3.5vw,2.2rem);font-weight:800;color:var(--tx);margin:.5rem 0 .75rem;letter-spacing:-.02em}
    .tl-sec-sub{font-size:15px;color:var(--mu);max-width:560px;margin:0 auto;line-height:1.7}
    .tl-sec-footer{text-align:center;margin-top:40px}

    /* ═══ HERO ═══ */
    .tl-hero{position:relative;padding:100px 0 0;overflow:hidden;background:#d6ede1;isolation:isolate;-webkit-transform:translateZ(0);transform:translateZ(0);display:flex;flex-direction:column}
        33% {transform:scale(1.1) translate(-5%,3%);  opacity:1}
        66% {transform:scale(.92) translate(4%,-4%);  opacity:.85}
        100%{transform:scale(1)   translate(0%,0%);   opacity:.8}
    }
    /* ── SVG FOG HÁTTÉR ── */
    .tl-hero-fog{position:absolute;inset:0;width:100%;height:100%;z-index:0;pointer-events:none;display:block;overflow:visible;will-change:transform}
    .tl-fog-blob{filter:url(#fogBlur);transform-origin:center center;-webkit-backface-visibility:hidden;backface-visibility:hidden}
    .tl-fog-b1{animation:none}
    .tl-fog-b2{animation:none}
    .tl-fog-b3{animation:none}
    .tl-fog-b4{animation:none}
    .tl-fog-b5{animation:none}
    .tl-fog-b6{animation:none}
        25% {transform:translate3d(60px,-40px,0) scale(1.12)}
        50% {transform:translate3d(-30px,50px,0) scale(.92)}
        75% {transform:translate3d(40px,20px,0)  scale(1.06)}
        100%{transform:translate3d(0px,0px,0)    scale(1)}
    }
        30% {transform:translate3d(-70px,30px,0)  scale(1.15)}
        60% {transform:translate3d(50px,-60px,0)  scale(.88)}
        100%{transform:translate3d(0px,0px,0)    scale(1)}
    }
        40% {transform:translate3d(80px,60px,0)  scale(1.1)}
        70% {transform:translate3d(-50px,-30px,0)scale(.94)}
        100%{transform:translate3d(0px,0px,0)   scale(1)}
    }
        20% {transform:translate3d(-40px,-50px,0) scale(1.08)}
        55% {transform:translate3d(60px,40px,0)   scale(.96)}
        80% {transform:translate3d(-20px,60px,0)  scale(1.12)}
        100%{transform:translate3d(0px,0px,0)    scale(1)}
    }
        35% {transform:translate3d(70px,-70px,0)  scale(1.14)}
        65% {transform:translate3d(-60px,30px,0)  scale(.9)}
        100%{transform:translate3d(0px,0px,0)    scale(1)}
    }
        45% {transform:translate3d(-80px,-40px,0)  scale(1.1)}
        75% {transform:translate3d(40px,70px,0)    scale(.93)}
        100%{transform:translate3d(0px,0px,0)     scale(1)}
    }
        45% {transform:translate(-80px,-40px) scale(1.1)}
        75% {transform:translate(40px,70px)   scale(.93)}
        100%{transform:translate(0px,0px)    scale(1)}
    }

    .tl-hero-bg{position:absolute;inset:0;pointer-events:none;z-index:0}
    .tl-hero-orb{position:absolute;border-radius:50%;filter:blur(60px);animation:tlOrb 10s ease-in-out infinite;z-index:1}
    .tl-hero-orb--1{width:500px;height:500px;background:radial-gradient(circle,rgba(45,106,79,.28),transparent 65%);top:-120px;right:-100px;animation-duration:12s;filter:blur(50px)}
    .tl-hero-orb--2{width:380px;height:380px;background:radial-gradient(circle,rgba(64,184,130,.22),transparent 65%);bottom:-80px;left:-80px;animation-duration:15s;animation-direction:reverse;filter:blur(45px)}
    .tl-hero-orb--3{width:320px;height:320px;background:radial-gradient(circle,rgba(45,106,79,.18),transparent 65%);top:20%;left:35%;animation-duration:18s;animation-delay:-5s;filter:blur(55px)}
    .tl-hero-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(45,106,79,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(45,106,79,.04) 1px,transparent 1px);background-size:40px 40px;mask-image:radial-gradient(ellipse at 50% 50%,black 20%,transparent 80%)}50%{d:path("M0,40 C300,10 600,70 900,40 C1200,10 1500,70 1800,40 L1800,80 L0,80 Z")}}
    .tl-hero-pattern{position:absolute;inset:0;opacity:.3;background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%232d6a4f' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    @keyframes tlOrb{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(20px,-15px) scale(1.05)}66%{transform:translate(-10px,20px) scale(.97)}}
    .tl-hero .tl-c{padding-top:60px;padding-bottom:56px}
    .tl-hero-inner{position:relative;z-index:2;display:flex;flex-direction:column;gap:0;max-width:780px;width:100%;margin:0 auto;background:rgba(255,255,255,.58);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border-radius:28px;padding:48px 52px 52px;border:1px solid rgba(255,255,255,.75);box-shadow:0 8px 40px rgba(45,106,79,.08);margin-bottom:0}
    .tl-hero-tag{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:999px;font-size:12px;font-weight:700;background:rgba(45,106,79,.15);color:var(--g-dk);margin-bottom:16px;border:1px solid rgba(45,106,79,.25)}
    .tl-hero-title{font-size:clamp(2.4rem,5.5vw,3.8rem);font-weight:900;color:#0f1f16;line-height:1.1;margin:0 0 20px;letter-spacing:-.03em}
    .tl-hero-accent{background:linear-gradient(135deg,var(--g),var(--g-lt));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .tl-hero-desc{font-size:16px;color:#3a5a47;line-height:1.75;max-width:560px;margin:0 0 28px}
    .tl-hero-btns{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px}
    .tl-hero-chips{display:flex;gap:8px;flex-wrap:wrap}
    /* Hero pillar sor */
    .tl-hero-pillars{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:28px}
    .tl-hero-pillar{display:flex;flex-direction:column;gap:4px;padding:16px 18px;background:rgba(255,255,255,.45);border:1px solid rgba(45,106,79,.18);border-radius:14px;transition:background .2s}
    .tl-hero-pillar:hover{background:rgba(255,255,255,.65)}
    .tl-hero-pillar-icon{font-size:1.3rem;line-height:1;margin-bottom:4px}
    .tl-hero-pillar-title{font-size:13px;font-weight:800;color:#0f1f16;line-height:1.2}
    .tl-hero-pillar-desc{font-size:11.5px;color:#3a5a47;line-height:1.55;font-weight:500}
    /* Trust tags */
    .tl-hero-trust{display:flex;gap:8px;flex-wrap:wrap;margin-top:16px}
    .tl-hero-ftag{font-size:12px;font-weight:600;color:var(--mu);display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.45);padding:5px 12px;border-radius:8px;border:1px solid rgba(45,106,79,.15);white-space:nowrap}

    /* ═══ KALKULÁTOR CTA ═══ */
    .tl-sec--kalk{background:linear-gradient(135deg,#fff 0%,#f0f7f3 100%);border-top:1px solid var(--brd);border-bottom:1px solid var(--brd)}
    .tl-kalk-wrap{display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center}
    .tl-kalk-title{font-size:clamp(1.5rem,3vw,2rem);font-weight:800;color:var(--tx);margin:.5rem 0 1rem;letter-spacing:-.02em}
    .tl-kalk-desc{font-size:15px;color:var(--mu);line-height:1.7;margin-bottom:24px}
    .tl-kalk-list{list-style:none;padding:0;margin:0 0 32px;display:flex;flex-direction:column;gap:0}
    .tl-kalk-list li{display:flex;align-items:center;gap:10px;font-size:14px;color:var(--tx);font-weight:500;padding:11px 0;border-bottom:1px solid rgba(0,0,0,.07)}
    .tl-kalk-list li:last-child{border-bottom:none}
    .tl-kalk-li-dot{width:7px;height:7px;border-radius:50%;background:var(--g);flex-shrink:0;margin-left:1px}
    .tl-kalk-card{background:#fff;border-radius:20px;padding:28px;box-shadow:var(--shx);border:1px solid var(--brd)}
    .tl-kalk-card-header{display:flex;align-items:center;gap:12px;margin-bottom:24px}
    .tl-kalk-card-svg{flex-shrink:0}
    .tl-kalk-card-title{font-size:16px;font-weight:700;color:var(--tx)}
    .tl-kalk-demo-bars{display:flex;flex-direction:column;gap:16px;margin-bottom:24px}
    .tl-kalk-bar-row{display:grid;grid-template-columns:80px 1fr 40px;align-items:center;gap:12px;font-size:13px;color:var(--mu);font-weight:600}
    .tl-kalk-bar{height:10px;background:var(--bg2);border-radius:999px;overflow:hidden}
    .tl-kalk-bar-fill{height:100%;border-radius:999px;width:0;transition:width 1.2s cubic-bezier(.4,0,.2,1)}
    .tl-kalk-bar-val{font-size:12px;font-weight:700;color:var(--tx);text-align:right}
    .tl-kalk-card-cta{text-align:center;font-size:13px;color:var(--g);font-weight:700;padding:12px;background:var(--g-s);border-radius:10px;cursor:pointer}

    /* ═══ RECEPTEK ═══ */
    .tl-rec-intro{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:40px;padding:20px;background:#fff;border-radius:16px;border:1px solid var(--brd);box-shadow:var(--sh)}
    .tl-rec-intro-item{display:flex;align-items:flex-start;gap:12px}
    .tl-rec-intro-icon{font-size:1.5rem;flex-shrink:0}
    .tl-rec-intro-txt{font-size:13px;color:var(--mu);line-height:1.6}
    .tl-rec-intro-txt strong{color:var(--tx)}
    .tl-rcard-kcal-badge{position:absolute;bottom:10px;right:10px;background:rgba(0,0,0,.55);color:#fff;font-size:11px;font-weight:700;padding:4px 9px;border-radius:8px;z-index:2;backdrop-filter:blur(4px)}
    .tl-rcard-macro--carb{background:rgba(16,185,129,.1);color:#065f46}
    .tl-rcard-macro--fat{background:rgba(245,158,11,.1);color:#78350f}
    .tl-rec-cats{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:20px}
    .tl-rec-cats-label{font-size:12px;font-weight:700;color:var(--mu);text-transform:uppercase;letter-spacing:.06em}
    .tl-rec-cat-chip{font-size:12px;font-weight:600;padding:5px 14px;background:#fff;border:1.5px solid var(--brd);border-radius:999px;color:var(--tx);text-decoration:none;transition:all var(--tr)}
    .tl-rec-cat-chip:hover{background:var(--g);color:#fff;border-color:var(--g)}
    .tl-sec--recipes{background:#f4faf7;position:relative;overflow:hidden}
    .tl-sec--recipes::before{content:'';position:absolute;inset:0;background-image:
        radial-gradient(circle at 15% 20%, rgba(45,106,79,.07) 0%, transparent 40%),
        radial-gradient(circle at 85% 80%, rgba(45,106,79,.06) 0%, transparent 35%),
        radial-gradient(circle at 50% 50%, rgba(64,184,130,.04) 0%, transparent 60%);
        pointer-events:none;z-index:0}
    .tl-sec--recipes .tl-c{position:relative;z-index:1}
    .tl-sec--recipes .tl-sec-hd{position:relative;z-index:1}
    /* Dekoratív SVG minta */
    .tl-rec-pattern{position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0;opacity:.045}
    .tl-recipe-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
    .tl-rcard{display:flex;flex-direction:row;background:var(--card);border-radius:var(--r);overflow:hidden;box-shadow:var(--sh);border:1px solid var(--brd);text-decoration:none;color:var(--tx);transition:all var(--tr);position:relative;min-height:130px}
    .tl-rcard:hover{transform:translateY(-3px);box-shadow:0 8px 32px rgba(45,106,79,.15)}
    .tl-rcard::before{content:'';position:absolute;inset:0;border-radius:var(--r);box-shadow:inset 0 0 0 2px var(--g);opacity:0;transition:opacity var(--tr);pointer-events:none}
    .tl-rcard:hover::before{opacity:1}
    .tl-rcard-img{position:relative;width:140px;flex-shrink:0;overflow:hidden;background:var(--bg2);height:100%}
    .tl-rcard-img::after{content:'';position:absolute;inset:0;background:linear-gradient(to right,transparent 55%,rgba(0,0,0,.15) 100%),linear-gradient(to bottom,rgba(0,0,0,.05) 0%,rgba(0,0,0,.12) 100%);z-index:1;pointer-events:none}
    .tl-rcard-img img{width:100%;height:100%;object-fit:cover;transition:transform .6s ease;position:absolute;inset:0}
    .tl-rcard:hover .tl-rcard-img img{transform:scale(1.06)}
    .tl-rcard-img-ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:3rem;color:var(--mu)}
    .tl-rcard-cat{position:absolute;top:12px;left:12px;background:var(--g);color:#fff;font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px}
    .tl-rcard-body{padding:14px 16px;display:flex;flex-direction:column;gap:6px;flex:1;justify-content:center;border-left:3px solid transparent;transition:border-color var(--tr)}
    .tl-rcard:hover .tl-rcard-body{border-color:var(--g);background:rgba(45,106,79,.02)}
    .tl-rcard-title{font-size:14px;font-weight:700;color:var(--tx);line-height:1.35;margin:0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .tl-rcard-macros{display:flex;gap:8px;flex-wrap:wrap}
    .tl-rcard-macro{font-size:12px;font-weight:600;padding:4px 10px;border-radius:999px}
    .tl-rcard-macro--kcal{background:rgba(245,158,11,.1);color:#92400e}
    .tl-rcard-macro--prot{background:rgba(59,130,246,.1);color:#1e3a8a}
    .tl-rcard-arrow{font-size:12px;color:var(--g);font-weight:700;margin-top:auto;display:inline-flex;align-items:center;gap:4px;transition:gap var(--tr)}
    .tl-rcard:hover .tl-rcard-arrow{gap:10px}

    /* ═══ ALAPANYAG SLIDER ═══ */
    .tl-sec--aa-slider{background:linear-gradient(160deg,#f5f0ff 0%,#f0f7f3 100%);padding-bottom:48px;overflow:hidden}
    .tl-aa-track-wrap{position:relative;width:100%;overflow:hidden;margin:0 0 32px;padding:16px 0}
    .tl-aa-track-wrap::before,.tl-aa-track-wrap::after{content:'';position:absolute;top:0;bottom:0;width:140px;z-index:2;pointer-events:none}
    .tl-aa-track-wrap::before{left:0;background:linear-gradient(90deg,#f5f0ff,transparent)}
    .tl-aa-track-wrap::after{right:0;background:linear-gradient(-90deg,#f0f7f3,transparent)}
    .tl-aa-track{display:flex;gap:16px;width:max-content;animation:tlSlide 55s linear infinite;will-change:transform}
    .tl-aa-track:hover{animation-play-state:paused}
    @keyframes tlSlide{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}

    /* Slider kártya – archív .aa-card stílus */
    .tl-sl-card{position:relative;display:flex;flex-direction:column;background:#fff;border:1px solid #e2e6e4;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.05);text-decoration:none;color:#1a1d1b;overflow:hidden;flex-shrink:0;width:200px;transition:all .25s cubic-bezier(.4,0,.2,1)}
    .tl-sl-card:hover{transform:translateY(-5px);box-shadow:0 12px 40px rgba(0,0,0,.10);border-color:#2ecc71}
    .tl-sl-badge{position:absolute;top:0;right:0;padding:4px 9px;font-size:.52rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;border-bottom-left-radius:8px}
    .tl-sl-badge--usda{background:#2980b9;color:#fff}
    .tl-sl-badge--off{background:#f39c12;color:#fff}
    .tl-sl-card-header{padding:14px 14px 0;display:flex;align-items:flex-start;gap:10px}
    .tl-sl-card-img{width:44px;height:44px;border-radius:10px;overflow:hidden;flex-shrink:0;background:#f0f2f1}
    .tl-sl-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .4s ease}
    .tl-sl-card:hover .tl-sl-card-img img{transform:scale(1.08)}
    .tl-sl-card-ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;background:linear-gradient(135deg,#f7faf8,#eef5f0)}
    .tl-sl-card-meta{flex:1;min-width:0}
    .tl-sl-card-title{font-size:.82rem;font-weight:700;line-height:1.35;margin:0;color:#1a1d1b;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;transition:color .2s}
    .tl-sl-card:hover .tl-sl-card-title{color:#27ae60}
    .tl-sl-card-orig{font-size:.65rem;color:#7c8a83;font-style:italic;opacity:.6;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;margin-top:2px}
    .tl-sl-card-kcal{display:flex;align-items:center;justify-content:center;margin:12px 14px 0;padding:14px 12px;background:linear-gradient(135deg,#f7faf8 0%,#eef5f0 100%);border:1px solid rgba(46,204,113,.1);border-radius:12px}
    .tl-sl-card:hover .tl-sl-card-kcal{background:linear-gradient(135deg,#f2f9f4,#e8f5eb);border-color:rgba(46,204,113,.18)}
    .tl-sl-kcal-num{font-size:1.9rem;font-weight:900;letter-spacing:-.04em;line-height:1;color:#1a1d1b}
    .tl-sl-kcal-side{display:flex;flex-direction:column;margin-left:5px;padding-top:2px}
    .tl-sl-kcal-unit{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#1a1d1b;line-height:1}
    .tl-sl-kcal-per{font-size:.62rem;font-weight:600;color:rgba(26,29,27,.3);margin-top:1px}
    .tl-sl-card-nutrients{display:flex;align-items:stretch;margin:10px 14px 14px;gap:2px;overflow:hidden}
    .tl-sl-nutrient{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:9px 3px;background:#f0f2f1;gap:3px}
    .tl-sl-nutrient:first-child{border-radius:8px 0 0 8px}
    .tl-sl-nutrient:last-child{border-radius:0 8px 8px 0}
    .tl-sl-card:hover .tl-sl-nutrient{background:rgba(46,204,113,.04)}
    .tl-sl-nut-val{font-size:1rem;font-weight:800;line-height:1;letter-spacing:-.02em}
    .tl-sl-nut-g{font-size:.58rem;font-weight:600;opacity:.5;margin-left:1px}
    .tl-sl-nut-name{font-size:.67rem;color:#7c8a83;font-weight:600}
    .tl-sl-nut-div{width:1px;background:#e2e6e4;flex-shrink:0}
    .tl-sl-nutrient--prot .tl-sl-nut-val{color:#27ae60}
    .tl-sl-nutrient--carb .tl-sl-nut-val{color:#2980b9}
    .tl-sl-nutrient--fat  .tl-sl-nut-val{color:#e67e22}

    /* ═══ STATS ═══ *//* ═══ STATS ═══ */
    .tl-sec--stats{background:var(--g-dk);padding:60px 0;position:relative;overflow:hidden}
    .tl-sec--stats::before{content:"TÁPANYAG";position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:clamp(4rem,12vw,10rem);font-weight:900;color:rgba(255,255,255,.03);white-space:nowrap;letter-spacing:.1em;pointer-events:none;user-select:none}
    .tl-stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:0}
    .tl-stat{text-align:center;padding:32px 24px;border-right:1px solid rgba(255,255,255,.1);position:relative}
    .tl-stat:last-child{border-right:none}
    .tl-stat-icon{display:block;font-size:2rem;margin-bottom:10px}
    .tl-stat-num{display:block;font-size:2.8rem;font-weight:900;color:#fff;letter-spacing:-.03em;line-height:1}
    .tl-stat-lbl{display:block;font-size:12px;color:rgba(255,255,255,.6);font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-top:6px}

    /* ═══ FINAL CTA ═══ */
    .tl-sec--cta{background:linear-gradient(135deg,var(--g-dk) 0%,#1b4332 100%);padding:80px 0;text-align:center;position:relative;overflow:hidden}
    .tl-sec--cta::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .tl-cta-wrap{position:relative;z-index:1;max-width:640px;margin:0 auto}
    .tl-cta-icon{display:block;font-size:3rem;margin-bottom:16px}
    .tl-cta-title{font-size:clamp(1.6rem,3.5vw,2.4rem);font-weight:900;color:#fff;margin:0 0 16px;letter-spacing:-.02em;line-height:1.2}
    .tl-cta-desc{font-size:15px;color:rgba(255,255,255,.7);line-height:1.7;margin-bottom:32px}
    .tl-cta-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}

    /* ═══ RESPONSIVE ═══ */


    @media(prefers-reduced-motion:reduce){
        .tl-fog-blob{animation:none!important}
        .tl-hero-pillar,.tl-a{transition:none!important;animation:none!important;opacity:1!important;transform:none!important}
        .tl-sl-inner{scroll-behavior:auto}
    }
    @media(max-width:768px){
        .tl-fog-b3,.tl-fog-b5,.tl-fog-b6{display:none}
        .tl-fog-b1,.tl-fog-b2,.tl-fog-b4{animation:none;transform:translate3d(0,0,0) scale(1)}
    }
    @media(max-width:768px){
        /* Backdrop filter kikapcsolás – GPU tehermentesítés */
        .tl-hero-inner{backdrop-filter:none;-webkit-backdrop-filter:none;background:rgba(255,255,255,.82)}
        .tl-disclaimer,.tl-kalk-disclaimer-wrap,.tl-kalk-info-box{backdrop-filter:none;-webkit-backdrop-filter:none}
        .tl-rcard-kcal-badge{backdrop-filter:none;-webkit-backdrop-filter:none}
        /* Kalk szekció – gradient helyett flat háttér */
        .tl-sec--kalk{background:#f8fbf9}
        /* Scroll animációk kikapcsolása – fade-in marad, transform nem */
        .tl-a{transition:opacity .4s ease!important;transform:none!important}
    }

    @media(max-width:900px){
        .tl-rec-intro{grid-template-columns:1fr}
        .tl-recipe-grid{grid-template-columns:1fr}
        .tl-hero-pillars{grid-template-columns:1fr;gap:8px}
        .tl-hero-inner{grid-template-columns:1fr}
        .tl-kalk-wrap{grid-template-columns:1fr}
        .tl-kalk-right{display:none}
        .tl-recipe-grid{grid-template-columns:repeat(2,1fr)}
        .tl-stats-grid{grid-template-columns:repeat(2,1fr)}
        .tl-stat{border-right:none;border-bottom:1px solid rgba(255,255,255,.1)}
        .tl-stat:nth-child(2n){border-right:none}
    }
    @media(max-width:600px){
        .tl-hero{padding:56px 0 40px}
        .tl-hero-inner{padding:28px 20px;border-radius:20px}
        .tl-hero-pillars{grid-template-columns:1fr}
        .tl-hero-stats{gap:0;padding:12px 8px}
        .tl-hero-stat{padding:0 8px;min-width:60px}
        .tl-hero-stat-val{font-size:1.3rem}
        .tl-hero-trust{display:none}
        .tl-sec{padding:56px 0}
        .tl-recipe-grid{grid-template-columns:1fr}
        .tl-hero-btns{flex-direction:column}
        .tl-hero-chips{display:none}
        .tl-cta-btns{flex-direction:column;align-items:center}
    }

    </style><?php
}

// ═══════════════════════════════════════════════════════════════════════════════
// JS
// ═══════════════════════════════════════════════════════════════════════════════
function taplex_fp_js(){
    if(!is_front_page()) return;
    ?><script id="taplex-fp-js">
    (function(){
        'use strict';

        // ── Scroll animáció (IntersectionObserver) ──
        var els = document.querySelectorAll('.tl-a');
        if(els.length){
            var obs = new IntersectionObserver(function(entries){
                entries.forEach(function(e){
                    if(e.isIntersecting){
                        var el=e.target, d=parseInt(el.getAttribute('data-delay'))||0;
                        setTimeout(function(){el.classList.add('is-visible');},d);
                        obs.unobserve(el);
                    }
                });
            },{threshold:.08,rootMargin:'0px 0px -24px 0px'});
            els.forEach(function(el){obs.observe(el);});
        }

        // ── Számláló animáció ──
        function ease(t){return 1-Math.pow(1-t,4);}
        var nums = document.querySelectorAll('[data-count], .tl-hero-stat-val[data-count]');
        if(nums.length){
            var cO = new IntersectionObserver(function(entries){
                entries.forEach(function(e){
                    if(e.isIntersecting){
                        var el=e.target, tgt=parseInt(el.getAttribute('data-count'))||0;
                        if(tgt===0){el.textContent='0';cO.unobserve(el);return;}
                        var dur=1600, start=performance.now();
                        function tick(now){
                            var p=Math.min((now-start)/dur,1);
                            el.textContent=Math.round(ease(p)*tgt).toLocaleString('hu-HU');
                            if(p<1) requestAnimationFrame(tick);
                        }
                        requestAnimationFrame(tick);
                        cO.unobserve(el);
                    }
                });
            },{threshold:.3});
            nums.forEach(function(el){cO.observe(el);});
        }

        // ── Kalkulátor kártya bar animáció ──
        var bars = document.querySelectorAll('.tl-kalk-bar-fill');
        if(bars.length){
            var bO = new IntersectionObserver(function(entries){
                entries.forEach(function(e){
                    if(e.isIntersecting){
                        var bar=e.target, w=bar.getAttribute('data-w')+'%';
                        bar.style.width='0%';
                        setTimeout(function(){bar.style.width=w;},300);
                        bO.unobserve(bar);
                    }
                });
            },{threshold:.3});
            bars.forEach(function(b){bO.observe(b);});
        }

        // ── Slider: hover szünet már CSS-ben van ──
        // Mobilon érintés esetén is megáll
        var track = document.getElementById('tl-aa-track');
        if(track){
            track.addEventListener('touchstart',function(){
                track.style.animationPlayState='paused';
            },{passive:true});
            track.addEventListener('touchend',function(){
                track.style.animationPlayState='running';
            },{passive:true});
        }

    })();
    </script><?php
}

add_action('wp_head', 'taplex_fp_css', 5);
add_action('wp_footer', 'taplex_fp_js', 20);

// Cache invalidálás recept mentéskor
add_action('save_post_recept', function($post_id){
    if(wp_is_post_revision($post_id)||wp_is_post_autosave($post_id)) return;
    delete_post_meta($post_id,'_fp_kcal');
    delete_post_meta($post_id,'_fp_feh');
    delete_post_meta($post_id,'_fp_szh');
    delete_post_meta($post_id,'_fp_zsir');
}, 10, 1);
