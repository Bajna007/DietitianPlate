<?php

/**
 * 33 – Google OAuth 2.0 Login
 */
/**
 * 33 – Google OAuth 2.0 Login
 * Biztonságos OAuth flow custom /auth/google/callback URL-lel
 * Kulcsok a wp-config.php-ból: DP_GOOGLE_CLIENT_ID, DP_GOOGLE_CLIENT_SECRET
 * v1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ═══ KONSTANSOK ═══ */
define( 'DP_GOOGLE_REDIRECT_URI', home_url( '/auth/google/callback' ) );
define( 'DP_GOOGLE_AUTH_URL',     'https://accounts.google.com/o/oauth2/v2/auth' );
define( 'DP_GOOGLE_TOKEN_URL',    'https://oauth2.googleapis.com/token' );
define( 'DP_GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v3/userinfo' );

/* ═══ REWRITE RULE: /auth/google/callback ═══ */
add_action( 'init', function() {
    add_rewrite_rule(
        '^auth/google/callback/?$',
        'index.php?dp_google_callback=1',
        'top'
    );
});

add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'dp_google_callback';
    return $vars;
});

/* Egyszer kell – permalink flush */
add_action( 'init', function() {
    if ( get_option( 'dp_google_rewrite_flushed_v1' ) ) return;
    flush_rewrite_rules();
    update_option( 'dp_google_rewrite_flushed_v1', true );
});

/* ═══ GOOGLE AUTH URL GENERÁLÁS ═══ */
function dp_get_google_auth_url() {
    if ( ! defined( 'DP_GOOGLE_CLIENT_ID' ) || ! DP_GOOGLE_CLIENT_ID ) return '';

    /* CSRF védelem: state token */
    $state = wp_create_nonce( 'dp_google_oauth_state' );

    $params = array(
        'client_id'     => DP_GOOGLE_CLIENT_ID,
        'redirect_uri'  => DP_GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    );

    return DP_GOOGLE_AUTH_URL . '?' . http_build_query( $params );
}

/* ═══ CALLBACK HANDLER ═══ */
add_action( 'template_redirect', function() {
    if ( ! get_query_var( 'dp_google_callback' ) ) return;

    /* Kulcsok ellenőrzés */
    if ( ! defined( 'DP_GOOGLE_CLIENT_ID' ) || ! defined( 'DP_GOOGLE_CLIENT_SECRET' ) ) {
        wp_die( 'Google OAuth nincs konfigurálva.', 'Hiba', array( 'response' => 500 ) );
    }

    $code  = isset( $_GET['code'] )  ? sanitize_text_field( $_GET['code'] )  : '';
    $state = isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '';
    $error = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : '';

    /* Felhasználó visszavonta */
    if ( $error ) {
        wp_safe_redirect( home_url( '/?login=cancelled' ) );
        exit;
    }

    /* CSRF védelem: state ellenőrzés */
    if ( ! $code || ! wp_verify_nonce( $state, 'dp_google_oauth_state' ) ) {
        wp_die( 'Érvénytelen kérés. Próbáld újra.', 'Biztonsági hiba', array( 'response' => 403 ) );
    }

    /* ── 1. Authorization code → Access token ── */
    $token_response = wp_remote_post( DP_GOOGLE_TOKEN_URL, array(
        'timeout' => 15,
        'body'    => array(
            'code'          => $code,
            'client_id'     => DP_GOOGLE_CLIENT_ID,
            'client_secret' => DP_GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => DP_GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ),
    ) );

    if ( is_wp_error( $token_response ) ) {
        error_log( 'DP Google OAuth token hiba: ' . $token_response->get_error_message() );
        wp_safe_redirect( home_url( '/?login=error' ) );
        exit;
    }

    $token_body = json_decode( wp_remote_retrieve_body( $token_response ), true );

    if ( empty( $token_body['access_token'] ) ) {
        error_log( 'DP Google OAuth: nincs access_token – ' . wp_remote_retrieve_body( $token_response ) );
        wp_safe_redirect( home_url( '/?login=error' ) );
        exit;
    }

    $access_token = sanitize_text_field( $token_body['access_token'] );

    /* ── 2. Access token → User info ── */
    $user_response = wp_remote_get( DP_GOOGLE_USERINFO_URL, array(
        'timeout' => 15,
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
        ),
    ) );

    if ( is_wp_error( $user_response ) ) {
        error_log( 'DP Google OAuth userinfo hiba: ' . $user_response->get_error_message() );
        wp_safe_redirect( home_url( '/?login=error' ) );
        exit;
    }

    $user_data = json_decode( wp_remote_retrieve_body( $user_response ), true );

    if ( empty( $user_data['email'] ) ) {
        error_log( 'DP Google OAuth: nincs email a válaszban' );
        wp_safe_redirect( home_url( '/?login=error' ) );
        exit;
    }

    /* ── 3. Sanitizálás ── */
    $google_email   = sanitize_email( $user_data['email'] );
    $google_name    = sanitize_text_field( isset( $user_data['name'] ) ? $user_data['name'] : '' );
    $google_picture = esc_url_raw( isset( $user_data['picture'] ) ? $user_data['picture'] : '' );
    $google_sub     = sanitize_text_field( isset( $user_data['sub'] ) ? $user_data['sub'] : '' );

    if ( ! is_email( $google_email ) ) {
        wp_die( 'Érvénytelen email cím a Google fióktól.', 'Hiba', array( 'response' => 400 ) );
    }

    /* ── 4. Felhasználó keresése / létrehozása ── */
    $existing_user = get_user_by( 'email', $google_email );

    if ( $existing_user ) {
        /* Létező user → bejelentkeztetés */
        $user_id = $existing_user->ID;

        /* Google adatok frissítése */
        update_user_meta( $user_id, 'dp_google_sub', $google_sub );
        if ( $google_picture ) {
            update_user_meta( $user_id, 'dp_google_avatar', $google_picture );
        }

    } else {
        /* Új user → regisztráció */
        $random_password = wp_generate_password( 24, true, true );

        $user_id = wp_create_user( $google_email, $random_password, $google_email );

        if ( is_wp_error( $user_id ) ) {
            error_log( 'DP Google OAuth: user létrehozás hiba – ' . $user_id->get_error_message() );
            wp_safe_redirect( home_url( '/?login=error' ) );
            exit;
        }

        $display_name = $google_name ? $google_name : strstr( $google_email, '@', true );

        wp_update_user( array(
            'ID'           => $user_id,
            'display_name' => $display_name,
            'first_name'   => $google_name,
            'role'         => 'subscriber',
        ) );

        update_user_meta( $user_id, 'dp_google_sub',      $google_sub );
        update_user_meta( $user_id, 'dp_google_avatar',   $google_picture );
        update_user_meta( $user_id, 'dp_registered_via',   'google' );
		    if ( function_exists( 'dp_get_user_number' ) ) {
        dp_get_user_number( $user_id );
    }
        update_user_meta( $user_id, 'dp_email_verified',   true );
    }

    /* ── 5. Bejelentkeztetés ── */
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );

    $userdata = get_userdata( $user_id );
    do_action( 'wp_login', $userdata->user_login, $userdata );

    /* ── 6. Redirect ── */
    $redirect_to = home_url( '/profil/' );

    if ( isset( $_COOKIE['dp_social_redirect'] ) ) {
        $cookie_redirect = esc_url_raw( $_COOKIE['dp_social_redirect'] );
        if ( $cookie_redirect && wp_validate_redirect( $cookie_redirect, home_url() ) ) {
            $redirect_to = $cookie_redirect;
        }
        setcookie( 'dp_social_redirect', '', time() - 3600, '/' );
    }

    wp_safe_redirect( $redirect_to );
    exit;
});

/* ═══ GOOGLE URL INJEKTÁLÁS A DP_USER OBJEKTUMBA ═══ */
add_filter( 'dp_user_localize_data', function( $data ) {
    $google_url = dp_get_google_auth_url();
    if ( $google_url ) {
        $data['google_url'] = $google_url;
    }
    return $data;
});

/* ═══ GOOGLE AVATAR HASZNÁLATA ═══ */
add_filter( 'get_avatar_url', function( $url, $id_or_email, $args ) {
    $user = false;

    if ( is_numeric( $id_or_email ) ) {
        $user = get_user_by( 'id', $id_or_email );
    } elseif ( is_string( $id_or_email ) ) {
        $user = get_user_by( 'email', $id_or_email );
    } elseif ( $id_or_email instanceof WP_User ) {
        $user = $id_or_email;
    } elseif ( $id_or_email instanceof WP_Comment ) {
        if ( $id_or_email->user_id ) {
            $user = get_user_by( 'id', $id_or_email->user_id );
        }
    }

    if ( $user ) {
        $google_avatar = get_user_meta( $user->ID, 'dp_google_avatar', true );
        if ( $google_avatar ) {
            return $google_avatar;
        }
    }

    return $url;
}, 10, 3 );
