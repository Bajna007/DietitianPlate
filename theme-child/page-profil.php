<?php
/**
 * Template Name: Profil oldal
 * v5.2 – Céljaim tab → link a /celjaim/ oldalra, duplikált kód eltávolítva
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url() );
    exit;
}

get_header();

$user       = wp_get_current_user();
$uid        = $user->ID;
$avatar     = get_avatar_url( $uid, array( 'size' => 128 ) );
$banner_url = get_user_meta( $uid, 'dp_banner_url', true );
$user_num   = function_exists( 'dp_format_user_number' ) ? dp_format_user_number( $uid ) : '#' . $uid;
$reg_date   = date_i18n( 'Y. F j.', strtotime( $user->user_registered ) );
$days_since = floor( ( time() - strtotime( $user->user_registered ) ) / 86400 );
$favorites  = function_exists( 'dp_get_user_favorites' ) ? dp_get_user_favorites() : array();
$fav_count  = count( $favorites );
$reg_via    = get_user_meta( $uid, 'dp_registered_via', true );
$has_pw     = get_user_meta( $uid, 'dp_has_password', true );
$collections = function_exists( 'dp_get_user_collections' ) ? dp_get_user_collections() : array();
$coll_count  = count( $collections );

/* Céljaim adatok */
$user_goal   = function_exists( 'dp_get_user_goal' ) ? dp_get_user_goal( $uid ) : null;
$bmr_profile = get_user_meta( $uid, 'dp_bmr_profile', true );
$has_goal    = ! empty( $user_goal );
$goal_type_labels = array(
    'cut'      => array( 'icon' => '📉', 'label' => 'Fogyás',         'color' => '#e74c3c' ),
    'maintain' => array( 'icon' => '⚖️', 'label' => 'Szintentartás',  'color' => '#2ecc71' ),
    'bulk'     => array( 'icon' => '📈', 'label' => 'Tömegnövelés',   'color' => '#3498db' ),
    'custom'   => array( 'icon' => '🎯', 'label' => 'Egyéni cél',     'color' => '#9b59b6' ),
);
?>

<main id="primary" class="site-main dp-profil">

    <!-- ═══ BANNER + AVATAR ═══ -->
    <div class="dp-profil-hero" id="dp-profil-hero">
        <div class="dp-profil-banner" id="dp-profil-banner" <?php if ( $banner_url ) : ?>style="background-image:url('<?php echo esc_url( $banner_url ); ?>')"<?php endif; ?>>
            <div class="dp-profil-banner-overlay"></div>
            <button type="button" class="dp-profil-banner-edit" id="dp-banner-edit-btn" title="Banner kép módosítása">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
            </button>
            <input type="file" id="dp-banner-input" accept="image/jpeg,image/png,image/webp" style="display:none">
        </div>
        <div class="dp-profil-hero-content">
            <div class="dp-profil-avatar-wrap">
                <img src="<?php echo esc_url( $avatar ); ?>" alt="" class="dp-profil-avatar" id="dp-profil-avatar" width="128" height="128">
                <button type="button" class="dp-profil-avatar-edit" id="dp-avatar-edit-btn" title="Profilkép módosítása">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </button>
                <input type="file" id="dp-avatar-input" accept="image/jpeg,image/png,image/webp" style="display:none">
            </div>
            <div class="dp-profil-hero-info">
                <h1 class="dp-profil-hero-name" id="dp-profil-display-name"><?php echo esc_html( $user->display_name ); ?></h1>
                <div class="dp-profil-hero-meta">
                    <span class="dp-profil-user-id"><?php echo esc_html( $user_num ); ?> tag</span>
                    <span class="dp-profil-hero-sep">·</span>
                    <span><?php echo esc_html( $reg_date ); ?> óta</span>
                    <?php if ( $reg_via === 'google' ) : ?>
                        <span class="dp-profil-hero-sep">·</span>
                        <span class="dp-profil-hero-google"><svg width="12" height="12" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg> Google</span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="dp-profil-logout-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Kijelentkezés
            </a>
        </div>
    </div>

    <!-- ═══ STAT KÁRTYÁK ═══ -->
    <div class="dp-profil-content">
        <div class="dp-profil-stats">
            <div class="dp-profil-stat-card"><span class="dp-profil-stat-icon">❤️</span><span class="dp-profil-stat-value"><?php echo $fav_count; ?></span><span class="dp-profil-stat-label">Kedvenc</span></div>
            <div class="dp-profil-stat-card"><span class="dp-profil-stat-icon">📁</span><span class="dp-profil-stat-value" id="dp-stat-colls"><?php echo $coll_count; ?></span><span class="dp-profil-stat-label">Gyűjtemény</span></div>
            <div class="dp-profil-stat-card"><span class="dp-profil-stat-icon">📅</span><span class="dp-profil-stat-value"><?php echo $days_since; ?></span><span class="dp-profil-stat-label">Napja tag</span></div>
            <div class="dp-profil-stat-card">
                <span class="dp-profil-stat-icon"><?php echo $has_goal ? $goal_type_labels[ $user_goal->goal_type ]['icon'] : '🎯'; ?></span>
                <span class="dp-profil-stat-value" id="dp-stat-kcal"><?php echo $has_goal ? intval( $user_goal->daily_kcal ) : '—'; ?></span>
                <span class="dp-profil-stat-label"><?php echo $has_goal ? 'kcal cél' : 'Nincs cél'; ?></span>
            </div>
        </div>

        <!-- ═══ TABS ═══ -->
        <div class="dp-profil-tabs">
            <button class="dp-profil-tab is-active" data-tab="favorites"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg> Kedvencek</button>
            <button class="dp-profil-tab" data-tab="collections"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg> Gyűjtemények <span class="dp-profil-tab-badge"><?php echo $coll_count; ?></span></button>
            <button class="dp-profil-tab" data-tab="goals"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Céljaim</button>
            <button class="dp-profil-tab" data-tab="settings"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg> Beállítások</button>
        </div>

        <!-- ═══ TAB: KEDVENCEK ═══ -->
        <div class="dp-profil-panel is-active" id="dp-panel-favorites">
            <div class="dp-profil-section">
                <div class="dp-profil-section-header"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg> Kedvenc receptjeim <span class="dp-profil-count"><?php echo $fav_count; ?></span></div>
                <?php if ( $fav_count > 0 ) :
                    $fav_query = new WP_Query( array( 'post_type' => 'recept', 'post__in' => $favorites, 'posts_per_page' => -1, 'orderby' => 'post__in' ) );
                    if ( $fav_query->have_posts() ) : ?>
                        <div class="dp-profil-grid">
                            <?php while ( $fav_query->have_posts() ) : $fav_query->the_post(); ?>
                                <a href="<?php the_permalink(); ?>" class="dp-profil-recept-card">
                                    <div class="dp-profil-recept-kep">
                                        <?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'medium' ); else : ?><div class="dp-profil-recept-ph">🍽</div><?php endif; ?>
                                        <button type="button" class="dp-fav-btn is-active dp-fav-btn--card" data-post-id="<?php echo get_the_ID(); ?>" title="Eltávolítás"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg></button>
                                    </div>
                                    <div class="dp-profil-recept-body">
                                        <h3><?php the_title(); ?></h3>
                                        <div class="dp-profil-recept-meta">
                                            <?php $ido = get_field( 'elkeszitesi_ido' ); $neh = get_field( 'nehezsegi_fok' ); ?>
                                            <?php if ( $ido ) : ?><span>⏱ <?php echo esc_html( $ido ); ?> perc</span><?php endif; ?>
                                            <?php if ( $neh ) : ?><span>📊 <?php echo esc_html( ucfirst( $neh ) ); ?></span><?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    <?php endif;
                else : ?>
                    <div class="dp-profil-empty"><span class="dp-profil-empty-icon">♡</span><p>Még nincs kedvenc recepted.</p><a href="<?php echo esc_url( get_post_type_archive_link( 'recept' ) ); ?>" class="dp-profil-empty-btn">Receptek böngészése →</a></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ═══ TAB: GYŰJTEMÉNYEK ═══ -->
        <div class="dp-profil-panel" id="dp-panel-collections">
            <div id="dp-coll-list-view">
                <div class="dp-profil-section">
                    <div class="dp-profil-section-header"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg> Gyűjteményeim <span class="dp-profil-count" id="dp-coll-count"><?php echo $coll_count; ?></span></div>
                    <div class="dp-coll-grid" id="dp-coll-grid">
                        <?php foreach ( $collections as $coll ) : ?>
                            <div class="dp-coll-card" data-coll-id="<?php echo intval( $coll->id ); ?>" data-coll-name="<?php echo esc_attr( $coll->name ); ?>" data-coll-color="<?php echo esc_attr( $coll->color ); ?>">
                                <div class="dp-coll-card-top" style="background:<?php echo esc_attr( $coll->color ); ?>"><span class="dp-coll-card-icon">📁</span><div class="dp-coll-card-actions"><button type="button" class="dp-coll-edit-btn" data-coll-id="<?php echo intval( $coll->id ); ?>" title="Szerkesztés">✏️</button><button type="button" class="dp-coll-delete-btn" data-coll-id="<?php echo intval( $coll->id ); ?>" title="Törlés">🗑️</button></div></div>
                                <div class="dp-coll-card-body"><h3 class="dp-coll-card-name"><?php echo esc_html( $coll->name ); ?></h3><span class="dp-coll-card-count"><?php echo intval( $coll->recipe_count ); ?> recept</span></div>
                            </div>
                        <?php endforeach; ?>
                        <div class="dp-coll-card dp-coll-card--new" id="dp-coll-new-btn"><div class="dp-coll-card-top dp-coll-card-top--new"><span class="dp-coll-new-icon">＋</span></div><div class="dp-coll-card-body"><h3 class="dp-coll-card-name">Új gyűjtemény</h3><span class="dp-coll-card-count">Létrehozás</span></div></div>
                    </div>
                </div>
            </div>
            <div id="dp-coll-detail-view" style="display:none">
                <button type="button" class="dp-coll-back-btn" id="dp-coll-back-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Vissza</button>
                <div class="dp-profil-section">
                    <div class="dp-profil-section-header" id="dp-coll-detail-header"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg> <span id="dp-coll-detail-name"></span> <span class="dp-profil-count" id="dp-coll-detail-count">0</span></div>
                    <div class="dp-coll-search-wrap" id="dp-coll-search-wrap"><div class="dp-coll-search-bar"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="text" id="dp-coll-search-input" placeholder="Recept keresése hozzáadáshoz…" autocomplete="off" class="dp-coll-search-input"></div><div class="dp-coll-search-results" id="dp-coll-search-results" style="display:none"></div></div>
                    <div id="dp-coll-detail-grid" class="dp-profil-grid"></div>
                    <div id="dp-coll-detail-empty" class="dp-profil-empty" style="display:none"><span class="dp-profil-empty-icon">📁</span><p>Ez a gyűjtemény még üres.<br>Keress recepteket a fenti keresővel!</p></div>
                </div>
            </div>
        </div>

        <!-- ═══ TAB: CÉLJAIM ═══ -->
        <div class="dp-profil-panel" id="dp-panel-goals">
            <div class="dp-profil-section">
                <div class="dp-profil-section-header">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    Céljaim
                </div>

                <?php if ( $has_goal ) : ?>
                <!-- Aktív terv összefoglaló -->
                <div class="dp-goals-summary">
                    <div class="dp-goals-summary-header">
                        <span class="dp-goals-summary-badge" style="background:<?php echo esc_attr( $goal_type_labels[ $user_goal->goal_type ]['color'] ); ?>">
                            <?php echo $goal_type_labels[ $user_goal->goal_type ]['icon'] . ' ' . $goal_type_labels[ $user_goal->goal_type ]['label']; ?>
                        </span>
                        <span class="dp-goals-summary-date">Mentve: <?php echo esc_html( $user_goal->updated_at ); ?></span>
                    </div>
                    <div class="dp-goals-summary-grid">
                        <div class="dp-goals-summary-item">
                            <span class="dp-goals-summary-val"><?php echo intval( $user_goal->daily_kcal ); ?></span>
                            <span class="dp-goals-summary-label">kcal/nap</span>
                        </div>
                        <div class="dp-goals-summary-item">
                            <span class="dp-goals-summary-val"><?php echo round( $user_goal->protein_pct ) . '/' . round( $user_goal->fat_pct ) . '/' . round( $user_goal->carb_pct ); ?></span>
                            <span class="dp-goals-summary-label">F / Zs / CH</span>
                        </div>
                        <div class="dp-goals-summary-item">
                            <span class="dp-goals-summary-val"><?php echo intval( $user_goal->meal_count ); ?></span>
                            <span class="dp-goals-summary-label">étkezés/nap</span>
                        </div>
                    </div>
                </div>
                <?php else : ?>
                <div class="dp-goals-empty">
                    <span class="dp-goals-empty-icon">🎯</span>
                    <p>Még nincs beállított célod.</p>
                </div>
                <?php endif; ?>

                <!-- CTA: Céljaim oldal -->
                <div class="dp-goals-cta">
                    <a href="<?php echo esc_url( home_url( '/celjaim/' ) ); ?>" class="dp-profil-btn dp-profil-btn--primary dp-goals-cta-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                        <?php echo $has_goal ? 'Céljaim szerkesztése →' : 'Célok beállítása →'; ?>
                    </a>
                    <p class="dp-goals-cta-sub">Személyre szabott makró célok, étkezés tervezés, prognózis és PDF export</p>
                </div>
            </div>
        </div>

        <!-- ═══ TAB: BEÁLLÍTÁSOK ═══ -->
        <div class="dp-profil-panel" id="dp-panel-settings">
            <div class="dp-profil-section">
                <div class="dp-profil-section-header"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Profil szerkesztése</div>
                <div class="dp-profil-settings-body">
                    <div class="dp-profil-setting-row"><div class="dp-profil-setting-label"><strong>Megjelenítési név</strong><span>Ez jelenik meg a profilodon</span></div><div class="dp-profil-setting-action"><input type="text" id="dp-settings-name" value="<?php echo esc_attr( $user->display_name ); ?>" maxlength="50" class="dp-profil-input"><button type="button" id="dp-save-name" class="dp-profil-btn dp-profil-btn--primary">Mentés</button></div><div class="dp-profil-setting-msg" id="dp-name-msg"></div></div>
                    <div class="dp-profil-setting-row"><div class="dp-profil-setting-label"><strong>Profilkép</strong><span>JPG, PNG, WebP · Max 2 MB</span></div><div class="dp-profil-setting-action"><button type="button" id="dp-settings-avatar-upload" class="dp-profil-btn dp-profil-btn--secondary">Feltöltés</button><button type="button" id="dp-settings-avatar-delete" class="dp-profil-btn dp-profil-btn--danger-ghost">Törlés</button></div><div class="dp-profil-setting-msg" id="dp-avatar-msg"></div></div>
                    <div class="dp-profil-setting-row"><div class="dp-profil-setting-label"><strong>Banner kép</strong><span>JPG, PNG, WebP · Max 5 MB</span></div><div class="dp-profil-setting-action"><button type="button" id="dp-settings-banner-upload" class="dp-profil-btn dp-profil-btn--secondary">Feltöltés</button><button type="button" id="dp-settings-banner-delete" class="dp-profil-btn dp-profil-btn--danger-ghost">Törlés</button></div><div class="dp-profil-setting-msg" id="dp-banner-msg"></div></div>
                    <div class="dp-profil-setting-row"><div class="dp-profil-setting-label"><strong>Email cím</strong><span>Nem módosítható</span></div><div class="dp-profil-setting-action"><input type="email" value="<?php echo esc_attr( $user->user_email ); ?>" class="dp-profil-input" disabled readonly></div></div>
                </div>
            </div>
            <div class="dp-profil-section">
                <div class="dp-profil-section-header"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg> Jelszó módosítása <?php if ( $reg_via === 'google' && ! $has_pw ) : ?><span class="dp-profil-badge dp-profil-badge--info">Beállítás</span><?php endif; ?></div>
                <div class="dp-profil-settings-body">
                    <?php if ( $reg_via === 'google' && ! $has_pw ) : ?>
                        <p class="dp-profil-settings-note">Google-lel regisztráltál. Itt jelszót állíthatsz be.</p>
                    <?php else : ?>
                        <div class="dp-profil-setting-row"><div class="dp-profil-setting-label"><strong>Jelenlegi jelszó</strong></div><div class="dp-profil-setting-action"><input type="password" id="dp-pw-current" class="dp-profil-input" placeholder="Jelenlegi jelszó" autocomplete="current-password"></div></div>
                    <?php endif; ?>
                    <div class="dp-profil-setting-row"><div class="dp-profil-setting-label"><strong>Új jelszó</strong><span>Min. 8 kar., nagy+kisbetű+szám</span></div><div class="dp-profil-setting-action"><input type="password" id="dp-pw-new" class="dp-profil-input" placeholder="Új jelszó" autocomplete="new-password" maxlength="128"></div></div>
                    <div class="dp-profil-setting-row"><div class="dp-profil-setting-label"><strong>Megerősítés</strong></div><div class="dp-profil-setting-action"><input type="password" id="dp-pw-confirm" class="dp-profil-input" placeholder="Mégegyszer" autocomplete="new-password" maxlength="128"><button type="button" id="dp-save-password" class="dp-profil-btn dp-profil-btn--primary">Módosítás</button></div><div class="dp-profil-setting-msg" id="dp-pw-msg"></div></div>
                </div>
            </div>
            <div class="dp-profil-section"><div class="dp-profil-section-header"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Fiók info</div><div class="dp-profil-settings-body"><div class="dp-profil-info-grid"><div class="dp-profil-info-item"><span class="dp-profil-info-label">User ID</span><span class="dp-profil-info-value"><?php echo esc_html( $user_num ); ?></span></div><div class="dp-profil-info-item"><span class="dp-profil-info-label">Regisztráció</span><span class="dp-profil-info-value"><?php echo esc_html( $reg_date ); ?></span></div><div class="dp-profil-info-item"><span class="dp-profil-info-label">Módja</span><span class="dp-profil-info-value"><?php echo $reg_via === 'google' ? 'Google' : 'Email'; ?></span></div><div class="dp-profil-info-item"><span class="dp-profil-info-label">Kedvencek</span><span class="dp-profil-info-value"><?php echo $fav_count; ?></span></div></div></div></div>
            <div class="dp-profil-section dp-profil-section--danger"><div class="dp-profil-section-header dp-profil-section-header--danger"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg> Fiók törlése</div><div class="dp-profil-settings-body"><div class="dp-profil-danger-zone"><p class="dp-profil-danger-text"><strong>⚠️</strong> Visszafordíthatatlan. Minden adatod törlődik.</p><div class="dp-profil-danger-confirm"><label class="dp-profil-danger-label">Írd be: <strong>TÖRLÉS</strong></label><input type="text" id="dp-delete-confirm" class="dp-profil-input dp-profil-input--danger" placeholder="TÖRLÉS" autocomplete="off" spellcheck="false"><button type="button" id="dp-delete-account-btn" class="dp-profil-btn dp-profil-btn--danger" disabled>Végleges törlés</button></div><div class="dp-profil-setting-msg" id="dp-delete-msg"></div></div></div></div>
        </div>
    </div>
</main>

<!-- Gyűjtemény modal -->
<div class="dp-coll-modal-overlay" id="dp-coll-modal-overlay">
    <div class="dp-coll-modal" id="dp-coll-modal">
        <button type="button" class="dp-auth-close" id="dp-coll-modal-close"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        <h2 class="dp-coll-modal-title" id="dp-coll-modal-title">Új gyűjtemény</h2>
        <div class="dp-coll-modal-field"><label for="dp-coll-name-input">Név</label><input type="text" id="dp-coll-name-input" maxlength="100" placeholder="pl. Hétköznap gyors" class="dp-profil-input" style="max-width:100%"></div>
        <div class="dp-coll-modal-field"><label>Szín</label><div class="dp-coll-color-picker" id="dp-coll-color-picker"></div></div>
        <div class="dp-coll-modal-actions"><button type="button" id="dp-coll-modal-save" class="dp-profil-btn dp-profil-btn--primary">Létrehozás</button><button type="button" id="dp-coll-modal-cancel" class="dp-profil-btn dp-profil-btn--secondary">Mégse</button></div>
        <div class="dp-profil-setting-msg" id="dp-coll-modal-msg"></div>
    </div>
</div>

<?php get_footer(); ?>