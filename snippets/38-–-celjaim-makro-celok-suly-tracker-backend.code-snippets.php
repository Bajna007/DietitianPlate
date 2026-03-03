<?php

/**
 * 38 – Céljaim: Makró célok + Súly tracker backend
 */
/**
 * 38 – Céljaim: Makró célok + Súly tracker backend
 */
/**
 * 38 – Céljaim: Makró célok + Súly tracker backend
 * DB táblák, CRUD AJAX, BMR mentés
 * v2.1 – html2pdf.js betöltés PDF letöltéshez
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ╔══════════════════════════════════════════════╗
   ║  DB TÁBLÁK                                    ║
   ╚══════════════════════════════════════════════╝ */
function dp_goals_create_tables() {
    global $wpdb;

    $ver = get_option( 'dp_goals_db_version', '' );
    if ( $ver === '2.0' ) return;

    $charset = $wpdb->get_charset_collate();

    if ( ! function_exists( 'dbDelta' ) ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    $t1 = $wpdb->prefix . 'dp_user_goals';
    $sql1 = "CREATE TABLE {$t1} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        goal_type varchar(20) NOT NULL DEFAULT 'maintain',
        daily_kcal int(10) unsigned NOT NULL DEFAULT 0,
        protein_pct decimal(5,2) NOT NULL DEFAULT 25.00,
        fat_pct decimal(5,2) NOT NULL DEFAULT 30.00,
        carb_pct decimal(5,2) NOT NULL DEFAULT 45.00,
        meal_count tinyint(3) unsigned NOT NULL DEFAULT 3,
        meal_distribution text,
        target_weight decimal(5,1) DEFAULT NULL,
        weekly_change decimal(5,2) DEFAULT NULL,
        bmr_data text,
        gender varchar(10) DEFAULT '',
        age smallint(5) unsigned DEFAULT 0,
        height decimal(5,1) DEFAULT 0,
        current_weight decimal(5,1) DEFAULT 0,
        activity_level decimal(5,3) DEFAULT 0,
        is_athlete tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY idx_user (user_id)
    ) {$charset};";

    $t2 = $wpdb->prefix . 'dp_weight_log';
    $sql2 = "CREATE TABLE {$t2} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        log_date date NOT NULL,
        weight_kg decimal(5,1) NOT NULL,
        note varchar(255) DEFAULT '',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY idx_user_date (user_id, log_date),
        KEY idx_user (user_id)
    ) {$charset};";

    dbDelta( $sql1 );
    dbDelta( $sql2 );

    update_option( 'dp_goals_db_version', '2.0' );
}

/* Csak admin_init-en futtatjuk – biztonságosabb mint init */
add_action( 'admin_init', 'dp_goals_create_tables' );

/* Frontend fallback – ha admin_init nem futott még */
add_action( 'template_redirect', function() {
    $check = false;
    if ( is_page( 'profil' ) || is_page_template( 'page-profil.php' ) ) $check = true;
    if ( is_page( 'celjaim' ) || is_page_template( 'page-celjaim.php' ) ) $check = true;
    if ( $check && get_option( 'dp_goals_db_version', '' ) !== '2.0' ) {
        dp_goals_create_tables();
    }
});

/* ╔══════════════════════════════════════════════╗
   ║  HELPER: Felhasználó aktív célja               ║
   ╚══════════════════════════════════════════════╝ */
if ( ! function_exists( 'dp_get_user_goal' ) ) {
    function dp_get_user_goal( $uid = null ) {
        if ( ! $uid ) $uid = get_current_user_id();
        if ( ! $uid ) return null;

        global $wpdb;
        $table = $wpdb->prefix . 'dp_user_goals';

        $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
        if ( ! $exists ) return null;

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC LIMIT 1",
            $uid
        ) );

        if ( $row ) {
            if ( ! empty( $row->meal_distribution ) ) {
                $decoded = json_decode( $row->meal_distribution, true );
                $row->meal_distribution = is_array( $decoded ) ? $decoded : null;
            }
            if ( ! empty( $row->bmr_data ) ) {
                $decoded = json_decode( $row->bmr_data, true );
                $row->bmr_data = is_array( $decoded ) ? $decoded : null;
            }
        }

        return $row;
    }
}

/* ╔══════════════════════════════════════════════╗
   ║  HELPER: Van-e BMR profil?                     ║
   ╚══════════════════════════════════════════════╝ */
if ( ! function_exists( 'dp_user_has_bmr' ) ) {
    function dp_user_has_bmr( $uid = null ) {
        if ( ! $uid ) $uid = get_current_user_id();
        if ( ! $uid ) return false;
        $bmr = get_user_meta( $uid, 'dp_bmr_profile', true );
        return ! empty( $bmr ) && isset( $bmr['tdee'] ) && intval( $bmr['tdee'] ) >= 500;
    }
}

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: CÉL MENTÉSE / FRISSÍTÉSE               ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_save_goal', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );
        return;
    }

    $uid = get_current_user_id();

    /* BMR ellenőrzés – nem engedjük menteni ha nincs BMR profil */
    if ( ! dp_user_has_bmr( $uid ) ) {
        wp_send_json_error( array( 'message' => 'Először számold ki az alapanyagcseréd a Kalória Kalkulátorral.' ) );
        return;
    }

    $goal_type = sanitize_text_field( isset( $_POST['goal_type'] ) ? $_POST['goal_type'] : 'maintain' );
    $valid_types = array( 'cut', 'maintain', 'bulk', 'custom' );
    if ( ! in_array( $goal_type, $valid_types, true ) ) {
        $goal_type = 'maintain';
    }

    $daily_kcal = intval( isset( $_POST['daily_kcal'] ) ? $_POST['daily_kcal'] : 0 );
    if ( $daily_kcal < 800 || $daily_kcal > 10000 ) {
        wp_send_json_error( array( 'message' => 'Napi kcal 800-10000 között legyen.' ) );
        return;
    }

    $protein_pct = round( floatval( isset( $_POST['protein_pct'] ) ? $_POST['protein_pct'] : 25 ), 2 );
    $fat_pct     = round( floatval( isset( $_POST['fat_pct'] ) ? $_POST['fat_pct'] : 30 ), 2 );
    $carb_pct    = round( floatval( isset( $_POST['carb_pct'] ) ? $_POST['carb_pct'] : 45 ), 2 );

    $total_pct = $protein_pct + $fat_pct + $carb_pct;
    if ( abs( $total_pct - 100 ) > 2 ) {
        wp_send_json_error( array( 'message' => 'A makró arányok összege 100% kell legyen (jelenleg: ' . round( $total_pct ) . '%).' ) );
        return;
    }

    $meal_count = intval( isset( $_POST['meal_count'] ) ? $_POST['meal_count'] : 3 );
    if ( $meal_count < 1 ) $meal_count = 1;
    if ( $meal_count > 10 ) $meal_count = 10;

    $meal_dist_raw = isset( $_POST['meal_distribution'] ) ? $_POST['meal_distribution'] : '';
    $meal_dist = null;
    if ( is_string( $meal_dist_raw ) && ! empty( $meal_dist_raw ) ) {
        $meal_dist = json_decode( stripslashes( $meal_dist_raw ), true );
    } elseif ( is_array( $meal_dist_raw ) ) {
        $meal_dist = $meal_dist_raw;
    }

    if ( ! is_array( $meal_dist ) || count( $meal_dist ) !== $meal_count ) {
        $each = round( 100 / $meal_count, 1 );
        $meal_dist = array_fill( 0, $meal_count, $each );
        $meal_dist[ $meal_count - 1 ] = round( 100 - ( $each * ( $meal_count - 1 ) ), 1 );
    }

    $target_weight = null;
    if ( isset( $_POST['target_weight'] ) && $_POST['target_weight'] !== '' ) {
        $tw = floatval( $_POST['target_weight'] );
        if ( $tw >= 30 && $tw <= 300 ) {
            $target_weight = $tw;
        }
    }

    $weekly_change = null;
    if ( isset( $_POST['weekly_change'] ) && $_POST['weekly_change'] !== '' ) {
        $weekly_change = round( floatval( $_POST['weekly_change'] ), 2 );
    }

    $gender         = sanitize_text_field( isset( $_POST['gender'] ) ? $_POST['gender'] : '' );
    $age            = absint( isset( $_POST['age'] ) ? $_POST['age'] : 0 );
    $height         = round( floatval( isset( $_POST['height'] ) ? $_POST['height'] : 0 ), 1 );
    $current_weight = round( floatval( isset( $_POST['current_weight'] ) ? $_POST['current_weight'] : 0 ), 1 );
    $activity_level = round( floatval( isset( $_POST['activity_level'] ) ? $_POST['activity_level'] : 0 ), 3 );
    $is_athlete     = ! empty( $_POST['is_athlete'] ) ? 1 : 0;

    $bmr_data_raw = isset( $_POST['bmr_data'] ) ? $_POST['bmr_data'] : '';
    $bmr_data = null;
    if ( is_string( $bmr_data_raw ) && ! empty( $bmr_data_raw ) ) {
        $bmr_data = json_decode( stripslashes( $bmr_data_raw ), true );
    } elseif ( is_array( $bmr_data_raw ) ) {
        $bmr_data = $bmr_data_raw;
    }

    /* Tábla check */
    global $wpdb;
    $table = $wpdb->prefix . 'dp_user_goals';
    $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
    if ( ! $table_exists ) {
        dp_goals_create_tables();
    }

    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC LIMIT 1", $uid
    ) );

    $data = array(
        'user_id'           => $uid,
        'goal_type'         => $goal_type,
        'daily_kcal'        => $daily_kcal,
        'protein_pct'       => $protein_pct,
        'fat_pct'           => $fat_pct,
        'carb_pct'          => $carb_pct,
        'meal_count'        => $meal_count,
        'meal_distribution' => wp_json_encode( $meal_dist ),
        'target_weight'     => $target_weight,
        'weekly_change'     => $weekly_change,
        'gender'            => $gender,
        'age'               => $age,
        'height'            => $height,
        'current_weight'    => $current_weight,
        'activity_level'    => $activity_level,
        'is_athlete'        => $is_athlete,
        'bmr_data'          => $bmr_data ? wp_json_encode( $bmr_data ) : null,
    );

    $format = array( '%d', '%s', '%d', '%f', '%f', '%f', '%d', '%s', '%f', '%f', '%s', '%d', '%f', '%f', '%f', '%d', '%s' );

    if ( $existing ) {
        unset( $data['user_id'] );
        array_shift( $format );
        $wpdb->update( $table, $data, array( 'id' => intval( $existing ) ), $format, array( '%d' ) );
    } else {
        $wpdb->insert( $table, $data, $format );
    }

    if ( $wpdb->last_error ) {
        wp_send_json_error( array( 'message' => 'Adatbázis hiba. Próbáld újra.' ) );
        return;
    }

    $p_g = round( ( $daily_kcal * $protein_pct / 100 ) / 4.1 );
    $f_g = round( ( $daily_kcal * $fat_pct / 100 ) / 9.3 );
    $c_g = round( ( $daily_kcal * $carb_pct / 100 ) / 4.1 );

    wp_send_json_success( array(
        'message' => 'Cél mentve!',
        'goal'    => array(
            'goal_type'         => $goal_type,
            'daily_kcal'        => $daily_kcal,
            'protein_pct'       => $protein_pct,
            'fat_pct'           => $fat_pct,
            'carb_pct'          => $carb_pct,
            'protein_g'         => $p_g,
            'fat_g'             => $f_g,
            'carb_g'            => $c_g,
            'meal_count'        => $meal_count,
            'meal_distribution' => $meal_dist,
        ),
    ) );
});

/* ╔═══════════════════════════════════���══════════╗
   ║  AJAX: BMR EREDMÉNY MENTÉS PROFILBA            ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_save_bmr_to_profile', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );
        return;
    }

    $uid = get_current_user_id();

    $bmr_val  = intval( isset( $_POST['bmr'] ) ? $_POST['bmr'] : 0 );
    $tdee_val = intval( isset( $_POST['tdee'] ) ? $_POST['tdee'] : 0 );

    if ( $bmr_val < 500 || $bmr_val > 10000 || $tdee_val < 500 || $tdee_val > 15000 ) {
        wp_send_json_error( array( 'message' => 'Érvénytelen BMR/TDEE értékek.' ) );
        return;
    }

    $data = array(
        'bmr'        => $bmr_val,
        'tdee'       => $tdee_val,
        'formula'    => sanitize_text_field( isset( $_POST['formula'] ) ? $_POST['formula'] : '' ),
        'gender'     => sanitize_text_field( isset( $_POST['gender'] ) ? $_POST['gender'] : '' ),
        'age'        => absint( isset( $_POST['age'] ) ? $_POST['age'] : 0 ),
        'height'     => round( floatval( isset( $_POST['height'] ) ? $_POST['height'] : 0 ), 1 ),
        'weight'     => round( floatval( isset( $_POST['weight'] ) ? $_POST['weight'] : 0 ), 1 ),
        'activity'   => round( floatval( isset( $_POST['activity'] ) ? $_POST['activity'] : 0 ), 3 ),
        'is_athlete' => ! empty( $_POST['is_athlete'] ) ? 1 : 0,
        'deficit'    => absint( isset( $_POST['deficit'] ) ? $_POST['deficit'] : 0 ),
        'saved_at'   => current_time( 'mysql' ),
    );

    update_user_meta( $uid, 'dp_bmr_profile', $data );

    wp_send_json_success( array(
        'message' => 'BMR adatok mentve a profilodba!',
        'data'    => $data,
    ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: SÚLY BEJEGYZÉS                         ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_log_weight', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );
        return;
    }

    $uid  = get_current_user_id();

    /* BMR ellenőrzés – nem engedjük súly rögzítést ha nincs BMR profil */
    if ( ! dp_user_has_bmr( $uid ) ) {
        wp_send_json_error( array( 'message' => 'Először számold ki az alapanyagcseréd a Kalória Kalkulátorral.' ) );
        return;
    }

    $date = sanitize_text_field( isset( $_POST['log_date'] ) ? $_POST['log_date'] : '' );
    $kg   = round( floatval( isset( $_POST['weight_kg'] ) ? $_POST['weight_kg'] : 0 ), 1 );
    $note = sanitize_text_field( isset( $_POST['note'] ) ? $_POST['note'] : '' );

    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
        $date = current_time( 'Y-m-d' );
    }

    $today = current_time( 'Y-m-d' );
    if ( $date > $today ) {
        wp_send_json_error( array( 'message' => 'Jövőbeli dátumra nem lehet mérést rögzíteni.' ) );
        return;
    }

    if ( $kg < 20 || $kg > 400 ) {
        wp_send_json_error( array( 'message' => 'Súly 20-400 kg között legyen.' ) );
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'dp_weight_log';

    $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
    if ( ! $table_exists ) {
        dp_goals_create_tables();
    }

    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$table} WHERE user_id = %d AND log_date = %s", $uid, $date
    ) );

    if ( $existing ) {
        $wpdb->update(
            $table,
            array( 'weight_kg' => $kg, 'note' => $note ),
            array( 'id' => intval( $existing ) ),
            array( '%f', '%s' ),
            array( '%d' )
        );
    } else {
        $wpdb->insert(
            $table,
            array( 'user_id' => $uid, 'log_date' => $date, 'weight_kg' => $kg, 'note' => $note ),
            array( '%d', '%s', '%f', '%s' )
        );
    }

    if ( $wpdb->last_error ) {
        wp_send_json_error( array( 'message' => 'Adatbázis hiba. Próbáld újra.' ) );
        return;
    }

    wp_send_json_success( array( 'message' => 'Súly rögzítve! (' . $kg . ' kg)' ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: SÚLY BEJEGYZÉS TÖRLÉSE                 ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_delete_weight_log', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );
        return;
    }

    $uid = get_current_user_id();
    $id  = absint( isset( $_POST['log_id'] ) ? $_POST['log_id'] : 0 );

    if ( ! $id ) {
        wp_send_json_error( array( 'message' => 'Érvénytelen bejegyzés.' ) );
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'dp_weight_log';

    $owner = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$table} WHERE id = %d", $id ) );
    if ( intval( $owner ) !== $uid ) {
        wp_send_json_error( array( 'message' => 'Nincs jogosultságod.' ) );
        return;
    }

    $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

    wp_send_json_success( array( 'message' => 'Bejegyzés törölve.' ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: SÚLY ADATOK LEKÉRÉSE (grafikon)         ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_get_weight_data', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );
        return;
    }

    $uid    = get_current_user_id();
    $period = absint( isset( $_POST['period'] ) ? $_POST['period'] : 90 );

    if ( $period < 7 ) $period = 7;
    if ( $period > 365 ) $period = 365;

    $from = gmdate( 'Y-m-d', strtotime( "-{$period} days" ) );

    global $wpdb;
    $table = $wpdb->prefix . 'dp_weight_log';

    $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
    if ( ! $table_exists ) {
        wp_send_json_success( array(
            'entries'        => array(),
            'weekly_avg'     => array(),
            'stats'          => array(),
            'target_weight'  => null,
            'period'         => $period,
        ) );
        return;
    }

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, log_date, weight_kg, note FROM {$table}
         WHERE user_id = %d AND log_date >= %s
         ORDER BY log_date ASC",
        $uid, $from
    ) );

    if ( ! is_array( $rows ) ) {
        $rows = array();
    }

    /* Heti átlagok */
    $weekly = array();
    foreach ( $rows as $r ) {
        $ts = strtotime( $r->log_date );
        if ( ! $ts ) continue;
        $week_start = gmdate( 'Y-m-d', strtotime( 'monday this week', $ts ) );
        if ( ! isset( $weekly[ $week_start ] ) ) {
            $weekly[ $week_start ] = array( 'sum' => 0.0, 'count' => 0 );
        }
        $weekly[ $week_start ]['sum'] += floatval( $r->weight_kg );
        $weekly[ $week_start ]['count']++;
    }

    $weekly_avg = array();
    foreach ( $weekly as $week => $data ) {
        if ( $data['count'] > 0 ) {
            $weekly_avg[] = array(
                'week'   => $week,
                'avg_kg' => round( $data['sum'] / $data['count'], 1 ),
                'count'  => $data['count'],
            );
        }
    }

    /* Statisztikák */
    $stats = array();
    if ( count( $rows ) >= 2 ) {
        $weights = array();
        foreach ( $rows as $r ) {
            $weights[] = floatval( $r->weight_kg );
        }

        $first = $weights[0];
        $last  = $weights[ count( $weights ) - 1 ];

        $stats = array(
            'first_weight' => $first,
            'last_weight'  => $last,
            'change'       => round( $last - $first, 1 ),
            'min'          => round( min( $weights ), 1 ),
            'max'          => round( max( $weights ), 1 ),
            'entries'      => count( $rows ),
        );
    } elseif ( count( $rows ) === 1 ) {
        $w = floatval( $rows[0]->weight_kg );
        $stats = array(
            'first_weight' => $w,
            'last_weight'  => $w,
            'change'       => 0,
            'min'          => $w,
            'max'          => $w,
            'entries'      => 1,
        );
    }

    /* Cél súly */
    $target_weight = null;
    if ( function_exists( 'dp_get_user_goal' ) ) {
        $goal = dp_get_user_goal( $uid );
        if ( $goal && isset( $goal->target_weight ) && $goal->target_weight > 0 ) {
            $target_weight = floatval( $goal->target_weight );
        }
    }

    wp_send_json_success( array(
        'entries'        => $rows,
        'weekly_avg'     => $weekly_avg,
        'stats'          => $stats,
        'target_weight'  => $target_weight,
        'period'         => $period,
    ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  FIÓK TÖRLÉS: CUSTOM TÁBLÁK TAKARÍTÁS         ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'delete_user', function( $uid ) {
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . 'dp_user_goals', array( 'user_id' => $uid ), array( '%d' ) );
    $wpdb->delete( $wpdb->prefix . 'dp_weight_log', array( 'user_id' => $uid ), array( '%d' ) );
    delete_user_meta( $uid, 'dp_bmr_profile' );
}, 5 );

/* ╔══════════════════════════════════════════════╗
   ║  CHART.JS + HTML2PDF BETÖLTÉSE                 ║
   ║  PROFIL + CÉLJAIM                              ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_enqueue_scripts', function() {
    if ( ! is_user_logged_in() ) return;

    $load = false;
    if ( is_page( 'profil' ) || is_page_template( 'page-profil.php' ) ) $load = true;
    if ( is_page( 'celjaim' ) || is_page_template( 'page-celjaim.php' ) ) $load = true;
    if ( ! $load ) return;

    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js',
        array(),
        '4.4.7',
        true
    );

    wp_enqueue_script(
        'html2pdf',
        'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js',
        array(),
        '0.10.2',
        true
    );
});
