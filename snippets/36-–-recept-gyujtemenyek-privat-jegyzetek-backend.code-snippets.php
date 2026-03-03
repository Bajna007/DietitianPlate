<?php

/**
 * 36 – Recept gyűjtemények + privát jegyzetek backend
 */
/**
 * 36 – Recept gyűjtemények + privát jegyzetek backend
 * Custom DB táblák, CRUD AJAX, recept kereső
 * v2.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ╔══════════════════════════════════════════════╗
   ║  CUSTOM DB TÁBLÁK LÉTREHOZÁS                  ║
   ╚══════════════════════════════════════════════╝ */
function dp_create_custom_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    /* Gyűjtemények */
    $t1 = $wpdb->prefix . 'dp_collections';
    $sql1 = "CREATE TABLE {$t1} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        name varchar(100) NOT NULL,
        color varchar(7) NOT NULL DEFAULT '#2d6a4f',
        cover_image varchar(500) DEFAULT '',
        sort_order int(10) unsigned NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY idx_user (user_id),
        KEY idx_user_sort (user_id, sort_order)
    ) $charset;";

    /* Gyűjtemény-recept kapcsolat */
    $t2 = $wpdb->prefix . 'dp_collection_recipes';
    $sql2 = "CREATE TABLE {$t2} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        collection_id bigint(20) unsigned NOT NULL,
        recipe_id bigint(20) unsigned NOT NULL,
        added_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY idx_coll_recipe (collection_id, recipe_id),
        KEY idx_recipe (recipe_id)
    ) $charset;";

    /* Privát jegyzetek */
    $t3 = $wpdb->prefix . 'dp_recipe_notes';
    $sql3 = "CREATE TABLE {$t3} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        recipe_id bigint(20) unsigned NOT NULL,
        note_text text NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY idx_user_recipe (user_id, recipe_id),
        KEY idx_recipe (recipe_id)
    ) $charset;";

    dbDelta( $sql1 );
    dbDelta( $sql2 );
    dbDelta( $sql3 );

    update_option( 'dp_db_version', '2.0' );
}

/* Tábla létrehozás – csak ha kell */
add_action( 'admin_init', function() {
    if ( get_option( 'dp_db_version' ) !== '2.0' ) {
        dp_create_custom_tables();
    }
});

/* Manuális trigger is legyen frontenden (ha admin_init nem fut) */
add_action( 'init', function() {
    if ( get_option( 'dp_db_version' ) !== '2.0' ) {
        dp_create_custom_tables();
    }
});

/* ╔══════════════════════════════════════════════╗
   ║  LIMITEK                                      ║
   ╚══════════════════════════════════════════════╝ */
if ( ! defined( 'DP_MAX_COLLECTIONS' ) )              define( 'DP_MAX_COLLECTIONS', 20 );
if ( ! defined( 'DP_MAX_RECIPES_PER_COLLECTION' ) )   define( 'DP_MAX_RECIPES_PER_COLLECTION', 100 );
if ( ! defined( 'DP_MAX_NOTE_LENGTH' ) )              define( 'DP_MAX_NOTE_LENGTH', 2000 );
if ( ! defined( 'DP_COLLECTION_COLORS' ) ) {
    define( 'DP_COLLECTION_COLORS', array(
        '#2d6a4f', '#1b4332', '#40916c', '#52b788', '#74c69d',
        '#2563eb', '#3b82f6', '#7c3aed', '#a855f7', '#db2777',
        '#dc2626', '#ea580c', '#d97706', '#ca8a04', '#65a30d',
    ) );
}

/* ╔══════════════════════════════════════════════╗
   ║  HELPER FÜGGVÉNYEK                            ║
   ╚══════════════════════════════════════════════╝ */
if ( ! function_exists( 'dp_get_user_collections' ) ) {
    function dp_get_user_collections( $uid = null ) {
        if ( ! $uid ) $uid = get_current_user_id();
        if ( ! $uid ) return array();
        global $wpdb;
        $t1 = $wpdb->prefix . 'dp_collections';
        $t2 = $wpdb->prefix . 'dp_collection_recipes';
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT c.*, COALESCE(cnt.rc, 0) AS recipe_count
             FROM {$t1} c
             LEFT JOIN (SELECT collection_id, COUNT(*) AS rc FROM {$t2} GROUP BY collection_id) cnt
                ON cnt.collection_id = c.id
             WHERE c.user_id = %d
             ORDER BY c.sort_order ASC, c.created_at DESC",
            $uid
        ) );
    }
}

if ( ! function_exists( 'dp_collection_belongs_to_user' ) ) {
    function dp_collection_belongs_to_user( $cid, $uid ) {
        global $wpdb;
        $owner = $wpdb->get_var( $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}dp_collections WHERE id = %d", $cid
        ) );
        return intval( $owner ) === intval( $uid );
    }
}

if ( ! function_exists( 'dp_get_recipe_note' ) ) {
    function dp_get_recipe_note( $recipe_id, $uid = null ) {
        if ( ! $uid ) $uid = get_current_user_id();
        if ( ! $uid ) return '';
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare(
            "SELECT note_text FROM {$wpdb->prefix}dp_recipe_notes WHERE user_id = %d AND recipe_id = %d",
            $uid, $recipe_id
        ) );
    }
}

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: GYŰJTEMÉNY LÉTREHOZÁS                  ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_create_collection', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    $uid   = get_current_user_id();
    $name  = sanitize_text_field( isset( $_POST['name'] ) ? $_POST['name'] : '' );
    $color = isset( $_POST['color'] ) ? sanitize_text_field( $_POST['color'] ) : '#2d6a4f';

    /* Szín validáció */
    if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ) $color = '#2d6a4f';

    if ( empty( $name ) || mb_strlen( $name ) < 1 || mb_strlen( $name ) > 100 ) {
        wp_send_json_error( array( 'message' => 'A gyűjtemény neve 1-100 karakter legyen.' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'dp_collections';

    /* Tábla létezés ellenőrzés */
    $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
    if ( ! $table_exists ) {
        dp_create_custom_tables();
    }

    $count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE user_id = %d", $uid
    ) );

    if ( intval( $count ) >= DP_MAX_COLLECTIONS ) {
        wp_send_json_error( array( 'message' => 'Maximum ' . DP_MAX_COLLECTIONS . ' gyűjtemény engedélyezett.' ) );
    }

    $inserted = $wpdb->insert(
        $table,
        array(
            'user_id'    => $uid,
            'name'       => $name,
            'color'      => $color,
            'sort_order' => intval( $count ),
        ),
        array( '%d', '%s', '%s', '%d' )
    );

    if ( $inserted === false ) {
        wp_send_json_error( array( 'message' => 'Hiba a létrehozásnál. Kód: ' . $wpdb->last_error ) );
    }

    $new_id = $wpdb->insert_id;

    wp_send_json_success( array(
        'message'    => 'Gyűjtemény létrehozva!',
        'collection' => array(
            'id'           => $new_id,
            'name'         => $name,
            'color'        => $color,
            'recipe_count' => 0,
        ),
    ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: GYŰJTEMÉNY SZERKESZTÉS                 ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_update_collection', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    $uid  = get_current_user_id();
    $cid  = intval( isset( $_POST['collection_id'] ) ? $_POST['collection_id'] : 0 );

    if ( ! $cid || ! dp_collection_belongs_to_user( $cid, $uid ) ) {
        wp_send_json_error( array( 'message' => 'Nincs jogosultságod.' ) );
    }

    $name  = sanitize_text_field( isset( $_POST['name'] ) ? $_POST['name'] : '' );
    $color = isset( $_POST['color'] ) ? sanitize_text_field( $_POST['color'] ) : '';

    $update = array();
    $format = array();

    if ( ! empty( $name ) && mb_strlen( $name ) >= 1 && mb_strlen( $name ) <= 100 ) {
        $update['name'] = $name;
        $format[] = '%s';
    }
    if ( preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ) {
        $update['color'] = $color;
        $format[] = '%s';
    }

    if ( empty( $update ) ) {
        wp_send_json_error( array( 'message' => 'Nincs módosítás.' ) );
    }

    global $wpdb;
    $wpdb->update( $wpdb->prefix . 'dp_collections', $update, array( 'id' => $cid ), $format, array( '%d' ) );

    wp_send_json_success( array( 'message' => 'Gyűjtemény frissítve!' ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: GYŰJTEMÉNY TÖRLÉS                     ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_delete_collection', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    $uid = get_current_user_id();
    $cid = intval( isset( $_POST['collection_id'] ) ? $_POST['collection_id'] : 0 );

    if ( ! $cid || ! dp_collection_belongs_to_user( $cid, $uid ) ) {
        wp_send_json_error( array( 'message' => 'Nincs jogosultságod.' ) );
    }

    global $wpdb;
    $wpdb->delete( $wpdb->prefix . 'dp_collection_recipes', array( 'collection_id' => $cid ), array( '%d' ) );
    $wpdb->delete( $wpdb->prefix . 'dp_collections', array( 'id' => $cid ), array( '%d' ) );

    wp_send_json_success( array( 'message' => 'Gyűjtemény törölve.' ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: RECEPT HOZZÁADÁS/ELTÁVOLÍTÁS           ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_toggle_collection_recipe', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    $uid = get_current_user_id();
    $cid = intval( isset( $_POST['collection_id'] ) ? $_POST['collection_id'] : 0 );
    $rid = intval( isset( $_POST['recipe_id'] ) ? $_POST['recipe_id'] : 0 );

    if ( ! $cid || ! $rid ) wp_send_json_error( array( 'message' => 'Érvénytelen kérés.' ) );
    if ( ! dp_collection_belongs_to_user( $cid, $uid ) ) wp_send_json_error( array( 'message' => 'Nincs jogosultságod.' ) );
    if ( ! get_post( $rid ) || get_post_type( $rid ) !== 'recept' ) wp_send_json_error( array( 'message' => 'Érvénytelen recept.' ) );

    global $wpdb;
    $t = $wpdb->prefix . 'dp_collection_recipes';

    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$t} WHERE collection_id = %d AND recipe_id = %d", $cid, $rid
    ) );

    if ( $exists ) {
        $wpdb->delete( $t, array( 'collection_id' => $cid, 'recipe_id' => $rid ), array( '%d', '%d' ) );
        $action = 'removed';
        $msg = 'Recept eltávolítva.';
    } else {
        $cnt = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE collection_id = %d", $cid ) );
        if ( intval( $cnt ) >= DP_MAX_RECIPES_PER_COLLECTION ) {
            wp_send_json_error( array( 'message' => 'Gyűjtemény megtelt (' . DP_MAX_RECIPES_PER_COLLECTION . ').' ) );
        }
        $wpdb->insert( $t, array( 'collection_id' => $cid, 'recipe_id' => $rid ), array( '%d', '%d' ) );
        $action = 'added';
        $msg = 'Recept hozzáadva!';
    }

    $new_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE collection_id = %d", $cid ) );

    wp_send_json_success( array( 'message' => $msg, 'action' => $action, 'recipe_count' => intval( $new_count ) ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: GYŰJTEMÉNY RECEPTJEI                   ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_get_collection_recipes', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    $uid = get_current_user_id();
    $cid = intval( isset( $_POST['collection_id'] ) ? $_POST['collection_id'] : 0 );

    if ( ! $cid || ! dp_collection_belongs_to_user( $cid, $uid ) ) {
        wp_send_json_error( array( 'message' => 'Nincs jogosultságod.' ) );
    }

    global $wpdb;
    $recipe_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT recipe_id FROM {$wpdb->prefix}dp_collection_recipes WHERE collection_id = %d ORDER BY added_at DESC", $cid
    ) );

    if ( empty( $recipe_ids ) ) {
        wp_send_json_success( array( 'html' => '', 'count' => 0 ) );
        return;
    }

    $query = new WP_Query( array(
        'post_type'      => 'recept',
        'post__in'       => $recipe_ids,
        'posts_per_page' => -1,
        'orderby'        => 'post__in',
    ) );

    $html = '';
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $thumb = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'medium' ) : '';
            $ido = function_exists( 'get_field' ) ? get_field( 'elkeszitesi_ido' ) : '';
            $neh = function_exists( 'get_field' ) ? get_field( 'nehezsegi_fok' ) : '';

            $html .= '<div class="dp-profil-recept-card dp-coll-recipe-item" data-recipe-id="' . get_the_ID() . '">';
            $html .= '<a href="' . esc_url( get_permalink() ) . '" class="dp-profil-recept-kep">';
            if ( $thumb ) {
                $html .= '<img src="' . esc_url( $thumb ) . '" alt="" loading="lazy">';
            } else {
                $html .= '<div class="dp-profil-recept-ph">🍽</div>';
            }
            $html .= '</a>';
            $html .= '<div class="dp-profil-recept-body">';
            $html .= '<a href="' . esc_url( get_permalink() ) . '"><h3>' . esc_html( get_the_title() ) . '</h3></a>';
            $html .= '<div class="dp-profil-recept-meta">';
            if ( $ido ) $html .= '<span>⏱ ' . esc_html( $ido ) . ' perc</span>';
            if ( $neh ) $html .= '<span>📊 ' . esc_html( ucfirst( $neh ) ) . '</span>';
            $html .= '</div>';
            $html .= '<button type="button" class="dp-coll-remove-btn" data-recipe-id="' . get_the_ID() . '" title="Eltávolítás">✕ Eltávolítás</button>';
            $html .= '</div></div>';
        }
        wp_reset_postdata();
    }

    wp_send_json_success( array( 'html' => $html, 'count' => count( $recipe_ids ) ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: RECEPT KERESÉS (gyűjteményhez adáshoz) ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_search_recipes', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    $search = sanitize_text_field( isset( $_POST['search'] ) ? $_POST['search'] : '' );
    $cid    = intval( isset( $_POST['collection_id'] ) ? $_POST['collection_id'] : 0 );

    if ( mb_strlen( $search ) < 2 ) {
        wp_send_json_success( array( 'results' => array() ) );
        return;
    }

    $args = array(
        'post_type'      => 'recept',
        'post_status'    => 'publish',
        's'              => $search,
        'posts_per_page' => 10,
        'orderby'        => 'relevance',
    );

    $query = new WP_Query( $args );

    /* Ha van collection_id, nézzük meg melyek vannak benne */
    $in_coll = array();
    if ( $cid ) {
        global $wpdb;
        $in_coll = $wpdb->get_col( $wpdb->prepare(
            "SELECT recipe_id FROM {$wpdb->prefix}dp_collection_recipes WHERE collection_id = %d", $cid
        ) );
    }

    $results = array();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $pid = get_the_ID();
            $results[] = array(
                'id'    => $pid,
                'title' => get_the_title(),
                'thumb' => has_post_thumbnail() ? get_the_post_thumbnail_url( $pid, 'thumbnail' ) : '',
                'is_in' => in_array( $pid, $in_coll ),
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success( array( 'results' => $results ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: JEGYZET MENTÉS                         ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_save_note', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    $uid = get_current_user_id();
    $rid = intval( isset( $_POST['recipe_id'] ) ? $_POST['recipe_id'] : 0 );
    $txt = isset( $_POST['note_text'] ) ? sanitize_textarea_field( $_POST['note_text'] ) : '';

    if ( ! $rid || ! get_post( $rid ) || get_post_type( $rid ) !== 'recept' ) {
        wp_send_json_error( array( 'message' => 'Érvénytelen recept.' ) );
    }
    if ( mb_strlen( $txt ) > DP_MAX_NOTE_LENGTH ) {
        wp_send_json_error( array( 'message' => 'Maximum ' . DP_MAX_NOTE_LENGTH . ' karakter.' ) );
    }

    global $wpdb;
    $t = $wpdb->prefix . 'dp_recipe_notes';

    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$t} WHERE user_id = %d AND recipe_id = %d", $uid, $rid
    ) );

    if ( empty( trim( $txt ) ) ) {
        if ( $exists ) $wpdb->delete( $t, array( 'user_id' => $uid, 'recipe_id' => $rid ), array( '%d', '%d' ) );
        wp_send_json_success( array( 'message' => 'Jegyzet törölve.', 'has_note' => false ) );
    }

    if ( $exists ) {
        $wpdb->update( $t, array( 'note_text' => $txt ), array( 'user_id' => $uid, 'recipe_id' => $rid ), array( '%s' ), array( '%d', '%d' ) );
    } else {
        $wpdb->insert( $t, array( 'user_id' => $uid, 'recipe_id' => $rid, 'note_text' => $txt ), array( '%d', '%d', '%s' ) );
    }

    wp_send_json_success( array( 'message' => 'Jegyzet mentve!', 'has_note' => true ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  DP_USER LOCALIZE BŐVÍTÉS                     ║
   ╚══════════════════════════════════════════════╝ */
add_filter( 'dp_user_localize_data', function( $data ) {
    if ( is_user_logged_in() ) {
        $cols = dp_get_user_collections();
        $data['collections'] = array();
        foreach ( $cols as $c ) {
            $data['collections'][] = array(
                'id'           => intval( $c->id ),
                'name'         => $c->name,
                'color'        => $c->color,
                'recipe_count' => intval( $c->recipe_count ),
            );
        }
        $data['collection_colors'] = DP_COLLECTION_COLORS;
    }
    return $data;
});

/* ╔══════════════════════════════════════════════╗
   ║  FIÓK TÖRLÉS: CUSTOM TÁBLÁK TAKARÍTÁS         ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'delete_user', function( $uid ) {
    global $wpdb;
    /* Gyűjtemény receptjei */
    $coll_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}dp_collections WHERE user_id = %d", $uid
    ) );
    if ( ! empty( $coll_ids ) ) {
        $ids_str = implode( ',', array_map( 'intval', $coll_ids ) );
        $wpdb->query( "DELETE FROM {$wpdb->prefix}dp_collection_recipes WHERE collection_id IN ({$ids_str})" );
    }
    $wpdb->delete( $wpdb->prefix . 'dp_collections', array( 'user_id' => $uid ), array( '%d' ) );
    $wpdb->delete( $wpdb->prefix . 'dp_recipe_notes', array( 'user_id' => $uid ), array( '%d' ) );
});
