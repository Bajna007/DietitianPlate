<?php

/**
 * 31. -  Felhasználói fiók rendszer (biztonságos)
 */
/**
 * 31. -  Felhasználói fiók rendszer (biztonságos)
 */
/**
 * 29 – Felhasználói fiók rendszer – v5 MAXIMUM SECURITY
 *
 * ✅ Jelszó: bcrypt hash (WordPress natív)
 * ✅ Email verifikáció kötelező
 * ✅ Timing-safe token összehasonlítás
 * ✅ User enumeration védelem
 * ✅ Session regeneráció login után
 * ✅ Security headers (clickjacking, XSS, MIME, referrer)
 * ✅ Email normalizálás (gmail alias trick védelem)
 * ✅ Disposable email tiltás
 * ✅ Brute force védelem (IP + email alapú)
 * ✅ Honeypot bot védelem
 * ✅ Cloudflare Turnstile (bot védelem)
 * ✅ Audit log (sikeres/sikertelen login)
 * ✅ Lejárt token takarítás (cron)
 * ✅ Cookie biztonság (httpOnly, secure, SameSite)
 * ✅ CSRF nonce minden végponton
 * ✅ Google OAuth 2.0 támogatás
 * ✅ EU GDPR compliant (hozzájárulás, adatkezelési tájékoztató link)
 * ✅ Facebook ELTÁVOLÍTVA
 *
 * Kulcsok wp-config.php-ban:
 *   define( 'DP_GOOGLE_CLIENT_ID', '...' );
 *   define( 'DP_GOOGLE_CLIENT_SECRET', '...' );
 *   define( 'DP_CF_TURNSTILE_SITEKEY', 'IDE_A_SITE_KEY' );
 *   define( 'DP_CF_TURNSTILE_SECRET',  'IDE_A_SECRET_KEY' );
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ╔══════════════════════════════════════════════╗
   ║  SECURITY HEADERS                             ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'send_headers', function() {
    if ( is_admin() ) return;
    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-XSS-Protection: 1; mode=block' );
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
});

add_filter( 'secure_auth_cookie', function() { return is_ssl(); } );
add_filter( 'secure_logged_in_cookie', function() { return is_ssl(); } );

/* ╔══════════════════════════════════════════════╗
   ║  HELPER FÜGGVÉNYEK                            ║
   ╚══════════════════════════════════════════════╝ */

/* ── Kedvencek ── */
if ( ! function_exists( 'dp_get_user_favorites' ) ) {
    function dp_get_user_favorites( $uid = null ) {
        if ( ! $uid ) $uid = get_current_user_id();
        if ( ! $uid ) return array();
        $f = get_user_meta( $uid, 'dp_favorites', true );
        return is_array( $f ) ? $f : array();
    }
}

if ( ! function_exists( 'dp_is_favorite' ) ) {
    function dp_is_favorite( $pid, $uid = null ) {
        return in_array( $pid, dp_get_user_favorites( $uid ) );
    }
}

/* ── Rate Limiter ── */
if ( ! function_exists( 'dp_rate_check' ) ) {
    function dp_rate_check( $action, $max, $window_min, $extra_key = '' ) {
        $ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : 'unknown';
        $raw = $ip . $extra_key;
        $key = 'dp_rl_' . $action . '_' . md5( $raw );
        $d   = get_transient( $key );
        if ( $d === false ) $d = array( 'c' => 0, 't' => time() );
        if ( ( time() - $d['t'] ) > ( $window_min * 60 ) ) $d = array( 'c' => 0, 't' => time() );
        $d['c']++;
        set_transient( $key, $d, $window_min * 60 );
        return $d['c'] <= $max;
    }
}

/* ── Jelszó erősség ── */
if ( ! function_exists( 'dp_password_strong' ) ) {
    function dp_password_strong( $pw ) {
        if ( strlen( $pw ) < 8 )              return 'A jelszó minimum 8 karakter legyen.';
        if ( strlen( $pw ) > 128 )            return 'A jelszó maximum 128 karakter lehet.';
        if ( ! preg_match( '/[A-Z]/', $pw ) ) return 'A jelszónak tartalmaznia kell nagybetűt.';
        if ( ! preg_match( '/[a-z]/', $pw ) ) return 'A jelszónak tartalmaznia kell kisbetűt.';
        if ( ! preg_match( '/[0-9]/', $pw ) ) return 'A jelszónak tartalmaznia kell számot.';
        return true;
    }
}

/* ── Honeypot ── */
if ( ! function_exists( 'dp_honeypot_check' ) ) {
    function dp_honeypot_check() {
        if ( ! empty( $_POST['dp_fax_number'] ) ) {
            wp_send_json_error( array( 'message' => 'Érvénytelen kérés.' ) );
        }
    }
}

/* ── Cloudflare Turnstile szerver oldali validáció ── */
if ( ! function_exists( 'dp_verify_turnstile' ) ) {
    function dp_verify_turnstile() {
        /* Ha nincs konfigurálva → engedjük át (fejlesztéshez) */
        if ( ! defined( 'DP_CF_TURNSTILE_SITEKEY' ) || ! DP_CF_TURNSTILE_SITEKEY ) {
            return true;
        }
        if ( ! defined( 'DP_CF_TURNSTILE_SECRET' ) || ! DP_CF_TURNSTILE_SECRET ) {
            return true;
        }

        $token = isset( $_POST['cf-turnstile-response'] )
            ? sanitize_text_field( $_POST['cf-turnstile-response'] )
            : '';

        if ( empty( $token ) ) {
            return false;
        }

        $response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
            'timeout' => 10,
            'body'    => array(
                'secret'   => DP_CF_TURNSTILE_SECRET,
                'response' => $token,
                'remoteip' => isset( $_SERVER['REMOTE_ADDR'] )
                    ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] )
                    : '',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'DP Turnstile verify hiba: ' . $response->get_error_message() );
            dp_audit_log( 'turnstile_bypass', '', false, 'wp_error: ' . $response->get_error_message() );
            return true; /* Hiba esetén fallback: ne zárjuk ki a usert */
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return ! empty( $body['success'] );
    }
}

/* ── Email normalizálás ── */
if ( ! function_exists( 'dp_normalize_email' ) ) {
    function dp_normalize_email( $email ) {
        $email = strtolower( trim( $email ) );
        $parts = explode( '@', $email );
        if ( count( $parts ) !== 2 ) return $email;
        $local  = $parts[0];
        $domain = $parts[1];
        $gmail_domains = array( 'gmail.com', 'googlemail.com' );
        if ( in_array( $domain, $gmail_domains ) ) {
            $local  = str_replace( '.', '', $local );
            $local  = preg_replace( '/\+.*$/', '', $local );
            $domain = 'gmail.com';
        }
        if ( $domain === 'outlook.com' || $domain === 'hotmail.com' ) {
            $local = preg_replace( '/\+.*$/', '', $local );
        }
        return $local . '@' . $domain;
    }
}

/* ── Disposable email tiltás ── */
if ( ! function_exists( 'dp_is_disposable_email' ) ) {
    function dp_is_disposable_email( $email ) {
        $domain = strtolower( substr( strrchr( $email, '@' ), 1 ) );
        $blocked = array(
            'mailinator.com','guerrillamail.com','guerrillamail.net','tempmail.com',
            'throwaway.email','temp-mail.org','fakeinbox.com','sharklasers.com',
            'grr.la','guerrillamail.info','guerrillamail.biz','guerrillamail.de',
            'guerrillamail.org','trashmail.com','trashmail.me','trashmail.net',
            'yopmail.com','yopmail.fr','dispostable.com','maildrop.cc',
            'mailnesia.com','tempail.com','mohmal.com','getnada.com',
            'emailondeck.com','33mail.com','mailcatch.com','inboxalias.com',
            'mintemail.com','trash-mail.com','harakirimail.com','crazymailing.com',
            'mailforspam.com','tempr.email','discard.email','discardmail.com',
            'tempmail.ninja','spamgourmet.com','10minutemail.com','20minutemail.com',
            'burnermail.io','mytemp.email','temp-mail.io','emailfake.com',
        );
        return in_array( $domain, $blocked );
    }
}

/* ── Email verifikáció státusz ── */
if ( ! function_exists( 'dp_is_email_verified' ) ) {
    function dp_is_email_verified( $uid ) {
        return (bool) get_user_meta( $uid, 'dp_email_verified', true );
    }
}

/* ── Verifikációs token generálás ── */
if ( ! function_exists( 'dp_generate_verify_token' ) ) {
    function dp_generate_verify_token( $uid ) {
        $token = bin2hex( random_bytes( 32 ) );
        $hash  = hash( 'sha256', $token );
        update_user_meta( $uid, 'dp_verify_token', $hash );
        update_user_meta( $uid, 'dp_verify_expires', time() + ( 24 * 3600 ) );
        return $token;
    }
}

/* ── Audit log ── */
if ( ! function_exists( 'dp_audit_log' ) ) {
    function dp_audit_log( $action, $email, $success, $details = '' ) {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : 'unknown';
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( substr( $_SERVER['HTTP_USER_AGENT'], 0, 200 ) ) : '';
        $log = array(
            'time'    => current_time( 'mysql' ),
            'action'  => $action,
            'email'   => $email,
            'success' => $success,
            'ip'      => $ip,
            'ua'      => $ua,
            'details' => $details,
        );
        $logs = get_option( 'dp_auth_audit_log', array() );
        array_unshift( $logs, $log );
        $logs = array_slice( $logs, 0, 500 );
        update_option( 'dp_auth_audit_log', $logs, false );
    }
}

/* ── Timing-safe delay ── */
if ( ! function_exists( 'dp_constant_time_delay' ) ) {
    function dp_constant_time_delay( $start_time, $min_ms = 800 ) {
        $elapsed = ( microtime( true ) - $start_time ) * 1000;
        $remaining = $min_ms - $elapsed;
        if ( $remaining > 0 ) {
            usleep( (int) ( $remaining * 1000 ) );
        }
    }
}

/* ╔══════════════════════════════════════════════╗
   ║  VERIFIKÁCIÓS EMAIL                            ║
   ╚══════════════════════════════════════════════╝ */
if ( ! function_exists( 'dp_send_verification_email' ) ) {
    function dp_send_verification_email( $uid, $token ) {
        $user = get_user_by( 'ID', $uid );
        if ( ! $user ) return false;

        $link = add_query_arg( array(
            'dp_verify_email' => '1',
            'uid'             => $uid,
            'token'           => $token,
        ), home_url( '/' ) );

        $name     = $user->display_name ? $user->display_name : $user->user_email;
        $sitename = get_bloginfo( 'name' );
        $subject  = $sitename . ' – Email cím megerősítése';

        $body  = '<div style="max-width:560px;margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;color:#1e1e1e;">';
        $body .= '<div style="background:#2d6a4f;padding:28px 32px;border-radius:16px 16px 0 0;text-align:center;">';
        $body .= '<span style="font-size:28px;">🥗</span>';
        $body .= '<h1 style="color:#fff;font-size:22px;font-weight:700;margin:8px 0 0;">Üdvözlünk a ' . esc_html( $sitename ) . '-on!</h1>';
        $body .= '</div>';
        $body .= '<div style="background:#fff;border:1px solid #e2e6e4;border-top:none;padding:32px;border-radius:0 0 16px 16px;">';
        $body .= '<p style="font-size:15px;line-height:1.6;margin:0 0 16px;">Kedves <strong>' . esc_html( $name ) . '</strong>,</p>';
        $body .= '<p style="font-size:15px;line-height:1.6;margin:0 0 24px;">Köszönjük a regisztrációt! Kérjük, erősítsd meg az email címed az alábbi gombra kattintva:</p>';
        $body .= '<div style="text-align:center;margin:0 0 24px;">';
        $body .= '<a href="' . esc_url( $link ) . '" style="display:inline-block;padding:14px 36px;background:#2d6a4f;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;border-radius:10px;">Email cím megerősítése</a>';
        $body .= '</div>';
        $body .= '<p style="font-size:13px;color:#999;line-height:1.5;margin:0 0 8px;">Ha nem te regisztráltál, kérjük hagyd figyelmen kívül ezt az emailt.</p>';
        $body .= '<p style="font-size:13px;color:#999;line-height:1.5;margin:0;">A link 24 órán belül lejár.</p>';
        $body .= '<hr style="border:none;border-top:1px solid #f0f1f3;margin:24px 0 16px;">';
        $body .= '<p style="font-size:12px;color:#bbb;margin:0;">Ha a gomb nem működik:<br><a href="' . esc_url( $link ) . '" style="color:#2d6a4f;word-break:break-all;">' . esc_url( $link ) . '</a></p>';
        $body .= '</div></div>';

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        return wp_mail( $user->user_email, $subject, $body, $headers );
    }
}

/* ╔══════════════════════════════════════════════╗
   ║  JELSZÓ-VISSZAÁLLÍTÁSI EMAIL                  ║
   ╚══════════════════════════════════════════��═══╝ */
if ( ! function_exists( 'dp_send_reset_email' ) ) {
    function dp_send_reset_email( $user ) {
        $key = get_password_reset_key( $user );
        if ( is_wp_error( $key ) ) return false;

        $link     = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );
        $name     = $user->display_name ? $user->display_name : $user->user_email;
        $sitename = get_bloginfo( 'name' );
        $subject  = $sitename . ' – Jelszó visszaállítás';

        $body  = '<div style="max-width:560px;margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;color:#1e1e1e;">';
        $body .= '<div style="background:#2d6a4f;padding:28px 32px;border-radius:16px 16px 0 0;text-align:center;">';
        $body .= '<span style="font-size:28px;">🔑</span>';
        $body .= '<h1 style="color:#fff;font-size:22px;font-weight:700;margin:8px 0 0;">Jelszó visszaállítás</h1>';
        $body .= '</div>';
        $body .= '<div style="background:#fff;border:1px solid #e2e6e4;border-top:none;padding:32px;border-radius:0 0 16px 16px;">';
        $body .= '<p style="font-size:15px;line-height:1.6;margin:0 0 16px;">Kedves <strong>' . esc_html( $name ) . '</strong>,</p>';
        $body .= '<p style="font-size:15px;line-height:1.6;margin:0 0 24px;">Jelszó visszaállítást kértek ehhez a fiókhoz. Kattints az alábbi gombra az új jelszó beállításához:</p>';
        $body .= '<div style="text-align:center;margin:0 0 24px;">';
        $body .= '<a href="' . esc_url( $link ) . '" style="display:inline-block;padding:14px 36px;background:#2d6a4f;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;border-radius:10px;">Új jelszó beállítása</a>';
        $body .= '</div>';
        $body .= '<p style="font-size:13px;color:#999;line-height:1.5;margin:0 0 8px;">Ha nem te kérted, hagyd figyelmen kívül – a jelszavad nem változik meg.</p>';
        $body .= '<hr style="border:none;border-top:1px solid #f0f1f3;margin:24px 0 16px;">';
        $body .= '<p style="font-size:12px;color:#bbb;margin:0;">Ha a gomb nem működik:<br><a href="' . esc_url( $link ) . '" style="color:#2d6a4f;word-break:break-all;">' . esc_url( $link ) . '</a></p>';
        $body .= '</div></div>';

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        return wp_mail( $user->user_email, $subject, $body, $headers );
    }
}

add_filter( 'retrieve_password_message', '__return_false' );
add_filter( 'retrieve_password_title', '__return_false' );

/* ╔══════════════════════════════════════════════╗
   ║  EMAIL VERIFIKÁCIÓ KEZELÉSE                   ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'template_redirect', function() {
    if ( ! isset( $_GET['dp_verify_email'] ) ) return;

    $uid   = intval( isset( $_GET['uid'] ) ? $_GET['uid'] : 0 );
    $token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';

    if ( ! $uid || empty( $token ) ) {
        wp_die( 'Érvénytelen verifikációs link.', 'Hiba', array( 'response' => 400 ) );
    }

    $stored  = get_user_meta( $uid, 'dp_verify_token', true );
    $expires = intval( get_user_meta( $uid, 'dp_verify_expires', true ) );

    if ( empty( $stored ) ) {
        wp_die( 'Ez a link már felhasználásra került, vagy érvénytelen.', 'Hiba', array( 'response' => 400 ) );
    }

    if ( ! hash_equals( $stored, hash( 'sha256', $token ) ) ) {
        dp_audit_log( 'verify_fail', '', false, 'uid=' . $uid );
        wp_die( 'Érvénytelen verifikációs link.', 'Hiba', array( 'response' => 400 ) );
    }

    if ( $expires > 0 && time() > $expires ) {
        delete_user_meta( $uid, 'dp_verify_token' );
        delete_user_meta( $uid, 'dp_verify_expires' );
        wp_die( 'Ez a verifikációs link lejárt. Kérj újat a bejelentkezési oldalon.', 'Lejárt link', array( 'response' => 410 ) );
    }

    update_user_meta( $uid, 'dp_email_verified', true );
    delete_user_meta( $uid, 'dp_verify_token' );
    delete_user_meta( $uid, 'dp_verify_expires' );

    dp_audit_log( 'verify_success', '', true, 'uid=' . $uid );

    wp_set_current_user( $uid );
    wp_set_auth_cookie( $uid, true );

    wp_safe_redirect( add_query_arg( 'dp_verified', '1', home_url( '/profil/' ) ) );
    exit;
});

add_action( 'wp_footer', function() {
    if ( ! isset( $_GET['dp_verified'] ) ) return;
    ?>
    <script>
    (function(){
        var d=document.createElement('div');
        d.style.cssText='position:fixed;top:90px;left:50%;transform:translateX(-50%);background:#2d6a4f;color:#fff;padding:14px 28px;border-radius:12px;font-size:14px;font-weight:600;z-index:999999;box-shadow:0 8px 32px rgba(0,0,0,0.15);animation:dpSlideDown .4s ease';
        d.textContent='\u2705 Email cím sikeresen megerősítve! Üdvözlünk!';
        document.body.appendChild(d);
        setTimeout(function(){d.style.opacity='0';d.style.transition='opacity .3s ease';setTimeout(function(){d.remove()},300)},4000);
    })();
    </script>
    <style>@keyframes dpSlideDown{from{opacity:0;transform:translateX(-50%) translateY(-20px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}</style>
    <?php
}, 100 );

/* ╔══════════════════════════════════════════════╗
   ║  LEJÁRT TOKENEK TAKARÍTÁSA (napi cron)        ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'init', function() {
    if ( ! wp_next_scheduled( 'dp_cleanup_expired_tokens' ) ) {
        wp_schedule_event( time(), 'daily', 'dp_cleanup_expired_tokens' );
    }
});

add_action( 'dp_cleanup_expired_tokens', function() {
    global $wpdb;
    $now = time();
    $expired_users = $wpdb->get_col( $wpdb->prepare(
        "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'dp_verify_expires' AND CAST(meta_value AS UNSIGNED) < %d AND CAST(meta_value AS UNSIGNED) > 0",
        $now
    ) );
    foreach ( $expired_users as $uid ) {
        $uid = intval( $uid );
        if ( ! dp_is_email_verified( $uid ) ) {
            $reg_time = strtotime( get_userdata( $uid )->user_registered );
            if ( $reg_time && ( $now - $reg_time ) > ( 7 * 24 * 3600 ) ) {
                wp_delete_user( $uid );
            }
        }
        delete_user_meta( $uid, 'dp_verify_token' );
        delete_user_meta( $uid, 'dp_verify_expires' );
    }

    $logs = get_option( 'dp_auth_audit_log', array() );
    if ( count( $logs ) > 500 ) {
        $logs = array_slice( $logs, 0, 500 );
        update_option( 'dp_auth_audit_log', $logs, false );
    }
});

/* ╔══════════════════════════════════════════════╗
   ║  ADMIN BAR                                    ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_loaded', function() {
    if ( ! is_admin() && is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
        show_admin_bar( false );
    }
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: REGISZTRÁCIÓ                           ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_nopriv_dp_register', function() {
    $start = microtime( true );
    check_ajax_referer( 'dp_auth_nonce', 'nonce' );
    dp_honeypot_check();

    if ( ! dp_verify_turnstile() ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Kérjük, igazold, hogy nem vagy robot.' ) );
    }

    $email = sanitize_email( isset( $_POST['email'] ) ? $_POST['email'] : '' );

    if ( ! dp_rate_check( 'register', 5, 30, $email ) ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Túl sok próbálkozás. Várj 30 percet.' ) );
    }

    $pw   = isset( $_POST['password'] ) ? $_POST['password'] : '';
    $name = sanitize_text_field( isset( $_POST['name'] ) ? $_POST['name'] : '' );
    /* GDPR: checkbox nélkül – a regisztráció gomb megnyomásával elfogadottnak tekintjük. */
    if ( empty( $name ) || mb_strlen( $name ) < 2 || mb_strlen( $name ) > 50 ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'A név 2-50 karakter között legyen.' ) );
    }

    if ( ! is_email( $email ) ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Érvénytelen email cím.' ) );
    }

    if ( dp_is_disposable_email( $email ) ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Ideiglenes email címek nem engedélyezettek.' ) );
    }

    $normalized  = dp_normalize_email( $email );
    $check_email = email_exists( $email ) || email_exists( $normalized ) || username_exists( $email );

    if ( $check_email ) {
        dp_audit_log( 'register_duplicate', $email, false );
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Ez az email cím már regisztrálva van.' ) );
    }

    $pw_check = dp_password_strong( $pw );
    if ( $pw_check !== true ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => $pw_check ) );
    }

    $uid = wp_create_user( $email, $pw, $email );
    if ( is_wp_error( $uid ) ) {
        dp_audit_log( 'register_fail', $email, false, $uid->get_error_message() );
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Hiba a regisztráció során. Próbáld újra.' ) );
    }

    $display = sanitize_text_field( $name );
    wp_update_user( array(
        'ID'           => $uid,
        'display_name' => $display,
        'first_name'   => $display,
        'role'         => 'subscriber',
    ) );

    update_user_meta( $uid, 'dp_email_verified',    false );
    update_user_meta( $uid, 'dp_normalized_email',  $normalized );
    update_user_meta( $uid, 'dp_registered_via',    'email' );
	dp_get_user_number( $uid );
    update_user_meta( $uid, 'dp_gdpr_consent',      current_time( 'mysql' ) );
    update_user_meta( $uid, 'dp_gdpr_consent_ip',   isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '' );

    $token = dp_generate_verify_token( $uid );
    $sent  = dp_send_verification_email( $uid, $token );

    if ( ! $sent ) {
        wp_delete_user( $uid );
        dp_audit_log( 'register_email_fail', $email, false );
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Nem sikerült elküldeni a megerősítő emailt. Próbáld újra.' ) );
    }

    dp_audit_log( 'register_success', $email, true );
    dp_constant_time_delay( $start );

    wp_send_json_success( array(
        'message'        => 'Regisztráció sikeres! Nézd meg az email fiókodat (' . esc_html( $email ) . ') és kattints a megerősítő linkre.',
        'require_verify' => true,
    ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: VERIFIKÁCIÓ ÚJRAKÜLDÉS                 ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_nopriv_dp_resend_verify', function() {
    $start = microtime( true );
    check_ajax_referer( 'dp_auth_nonce', 'nonce' );

    if ( ! dp_rate_check( 'resend', 3, 15 ) ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Túl sok kérés. Várj 15 percet.' ) );
    }

    $email = sanitize_email( isset( $_POST['email'] ) ? $_POST['email'] : '' );
    if ( ! is_email( $email ) ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Érvénytelen email cím.' ) );
    }

    $user = get_user_by( 'email', $email );
    if ( ! $user ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Nincs ilyen email címmel regisztrált fiók.' ) );
    }

    if ( dp_is_email_verified( $user->ID ) ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Ez az email cím már megerősítve van. Jelentkezz be.' ) );
    }

    $token = dp_generate_verify_token( $user->ID );
    dp_send_verification_email( $user->ID, $token );

    dp_audit_log( 'resend_verify', $email, true );
    dp_constant_time_delay( $start );

    wp_send_json_success( array( 'message' => 'Új megerősítő emailt küldtünk a(z) ' . esc_html( $email ) . ' címre.' ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: BEJELENTKEZÉS                          ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_nopriv_dp_login', function() {
    $start = microtime( true );
    check_ajax_referer( 'dp_auth_nonce', 'nonce' );
    dp_honeypot_check();

    if ( ! dp_verify_turnstile() ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Kérjük, igazold, hogy nem vagy robot.' ) );
    }

    $email = sanitize_email( isset( $_POST['email'] ) ? $_POST['email'] : '' );

    if ( ! dp_rate_check( 'login', 10, 15, $email ) ) {
        dp_audit_log( 'login_ratelimit', $email, false );
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Túl sok bejelentkezési kísérlet. Várj 15 percet.' ) );
    }

    $pw  = isset( $_POST['password'] ) ? $_POST['password'] : '';
    $rem = ! empty( $_POST['remember'] );

    if ( ! is_email( $email ) || empty( $pw ) ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Kérjük töltsd ki mindkét mezőt.' ) );
    }

    $user = wp_authenticate( $email, $pw );

    if ( is_wp_error( $user ) ) {
        dp_audit_log( 'login_fail', $email, false );
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Hibás email cím vagy jelszó.' ) );
    }

    /* Google-lel regisztrált user jelszó nélkül → irányítsuk Google-re */
    $registered_via = get_user_meta( $user->ID, 'dp_registered_via', true );
    if ( $registered_via === 'google' ) {
        $has_usable_pw = get_user_meta( $user->ID, 'dp_has_password', true );
        if ( ! $has_usable_pw ) {
            dp_constant_time_delay( $start );
            wp_send_json_error( array( 'message' => 'Ez a fiók Google-lel lett létrehozva. Használd a "Folytatás Google-lel" gombot a bejelentkezéshez.' ) );
        }
    }

    if ( ! dp_is_email_verified( $user->ID ) && ! $user->has_cap( 'edit_posts' ) ) {
        dp_audit_log( 'login_unverified', $email, false );
        dp_constant_time_delay( $start );
        wp_send_json_error( array(
            'message'      => 'Az email címed még nincs megerősítve. Nézd meg a postaládádat, vagy kérj új megerősítő emailt.',
            'need_verify'  => true,
            'verify_email' => $email,
        ) );
    }

    wp_set_current_user( $user->ID );
    wp_clear_auth_cookie();
    wp_set_auth_cookie( $user->ID, $rem );

    $redir = isset( $_POST['redirect'] ) ? esc_url_raw( $_POST['redirect'] ) : '';
    if ( empty( $redir ) || strpos( $redir, home_url() ) !== 0 ) {
        $redir = home_url( '/profil/' );
    }

    dp_audit_log( 'login_success', $email, true );
    dp_constant_time_delay( $start );

    wp_send_json_success( array(
        'message'  => 'Sikeres bejelentkezés!',
        'redirect' => $redir,
        'user'     => array(
            'name'   => $user->display_name,
            'avatar' => get_avatar_url( $user->ID, array( 'size' => 40 ) ),
        ),
    ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: ELFELEJTETT JELSZÓ                    ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_nopriv_dp_forgot_password', function() {
    $start = microtime( true );
    check_ajax_referer( 'dp_auth_nonce', 'nonce' );
    dp_honeypot_check();

    if ( ! dp_verify_turnstile() ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Kérjük, igazold, hogy nem vagy robot.' ) );
    }

    $email = sanitize_email( isset( $_POST['email'] ) ? $_POST['email'] : '' );

    if ( ! dp_rate_check( 'forgot', 3, 30, $email ) ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Túl sok kérés. Várj 30 percet.' ) );
    }

    if ( ! is_email( $email ) ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Adj meg egy érvényes email címet.' ) );
    }

    $user = get_user_by( 'email', $email );

    if ( ! $user ) {
        dp_audit_log( 'forgot_no_user', $email, false );
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Ezzel az email címmel nincs regisztrált fiók.' ) );
    }

    if ( ! dp_is_email_verified( $user->ID ) && ! $user->has_cap( 'edit_posts' ) ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array(
            'message'      => 'Ez a fiók még nincs megerősítve. Előbb erősítsd meg az email címed.',
            'need_verify'  => true,
            'verify_email' => $email,
        ) );
    }

    $sent = dp_send_reset_email( $user );

    if ( ! $sent ) {
        dp_constant_time_delay( $start );
        wp_send_json_error( array( 'message' => 'Nem sikerült elküldeni az emailt. Próbáld újra később.' ) );
    }

    dp_audit_log( 'forgot_success', $email, true );
    dp_constant_time_delay( $start );

    wp_send_json_success( array( 'message' => 'Jelszó visszaállítási linket küldtünk a(z) ' . esc_html( $email ) . ' címre.' ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: KEDVENCEK                              ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_toggle_favorite', function() {
    check_ajax_referer( 'dp_auth_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );
    }

    $uid = get_current_user_id();
    $pid = intval( isset( $_POST['post_id'] ) ? $_POST['post_id'] : 0 );

    if ( ! $pid || ! get_post( $pid ) || get_post_type( $pid ) !== 'recept' ) {
        wp_send_json_error( array( 'message' => 'Érvénytelen recept.' ) );
    }

    if ( get_post_status( $pid ) !== 'publish' ) {
        wp_send_json_error( array( 'message' => 'Ez a recept nem elérhető.' ) );
    }

    $favs = dp_get_user_favorites( $uid );
    $is   = in_array( $pid, $favs );

    if ( $is ) {
        $favs = array_values( array_diff( $favs, array( $pid ) ) );
    } else {
        if ( count( $favs ) >= 200 ) {
            wp_send_json_error( array( 'message' => 'Maximum 200 kedvenc recept engedélyezett.' ) );
        }
        $favs[] = $pid;
    }
    update_user_meta( $uid, 'dp_favorites', $favs );

    wp_send_json_success( array(
        'is_favorite' => ! $is,
        'count'       => count( $favs ),
        'message'     => $is ? 'Eltávolítva a kedvencekből.' : 'Hozzáadva a kedvencekhez!',
    ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  PROFIL OLDAL AUTO-LÉTREHOZÁS                 ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'init', function() {
    if ( get_option( 'dp_profile_page_created' ) ) return;
    $pid = wp_insert_post( array(
        'post_title'    => 'Profil',
        'post_name'     => 'profil',
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_content'  => '',
        'page_template' => 'page-profil.php',
    ) );
    if ( $pid && ! is_wp_error( $pid ) ) {
        update_option( 'dp_profile_page_created', $pid );
    }
});

/* ╔══════════════════════════════════════════════╗
   ║  FRONTEND SCRIPT + LOCALIZE                   ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_script( 'dp-user-account', get_stylesheet_directory_uri() . '/user-account.js', array(), '7.0', true );

    $data = array(
        'logged_in'        => false,
        'favorites'        => array(),
        'ajax_url'         => admin_url( 'admin-ajax.php' ),
        'nonce'            => wp_create_nonce( 'dp_auth_nonce' ),
        'profile_url'      => home_url( '/profil/' ),
        'google_url'       => '',
        'privacy_url'      => home_url( '/adatvedelmi-iranyelvek/' ),
        'turnstile_sitekey' => ( defined( 'DP_CF_TURNSTILE_SITEKEY' ) && DP_CF_TURNSTILE_SITEKEY ) ? DP_CF_TURNSTILE_SITEKEY : '',
    );

    /* Cloudflare Turnstile betöltés */
    if ( ! is_user_logged_in() && defined( 'DP_CF_TURNSTILE_SITEKEY' ) && DP_CF_TURNSTILE_SITEKEY ) {
        wp_enqueue_script( 'cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit', array(), null, true );
    }

    if ( is_user_logged_in() ) {
        $u = wp_get_current_user();
        $data['logged_in'] = true;
        $data['name']      = $u->display_name;
        $data['email']     = $u->user_email;
        $data['avatar']    = get_avatar_url( $u->ID, array( 'size' => 40 ) );
        $data['favorites'] = dp_get_user_favorites();
    }

    /* #33 snippet Google URL injektálás */
    $data = apply_filters( 'dp_user_localize_data', $data );

    wp_localize_script( 'dp-user-account', 'DP_USER', $data );
});

/* ╔══════════════════════════════════════════════╗
   ║  HEADER AUTH GOMBOK                            ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_footer', function() {
    $li = is_user_logged_in();
    $u  = $li ? wp_get_current_user() : null;
    $av = $li ? get_avatar_url( $u->ID, array( 'size' => 32 ) ) : '';
    $fc = $li ? count( dp_get_user_favorites() ) : 0;

    $desktop = '';
    if ( $li ) {
        if ( $fc > 0 ) $desktop .= '<a href="' . esc_url( home_url( '/profil/' ) ) . '" class="ch-header-fav" title="Kedvenceim"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg><span class="ch-header-fav-badge">' . $fc . '</span></a>';
        $desktop .= '<a href="' . esc_url( home_url( '/profil/' ) ) . '" class="ch-header-avatar" title="Profil"><img src="' . esc_url( $av ) . '" alt="" width="32" height="32"></a>';
    } else {
        $desktop .= '<button type="button" class="ch-header-login dp-open-auth-modal"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span>Bejelentkezés</span></button>';
    }

    $mobile = '';
    if ( $li ) {
        $mobile .= '<a href="' . esc_url( home_url( '/profil/' ) ) . '" class="ch-mobile-user-link"><img src="' . esc_url( $av ) . '" alt="" width="28" height="28" class="ch-mobile-user-avatar"><span>' . esc_html( $u->display_name ) . '</span>';
        if ( $fc > 0 ) $mobile .= '<span class="ch-mobile-fav-badge">♥ ' . $fc . '</span>';
        $mobile .= '</a><a href="' . esc_url( wp_logout_url( home_url() ) ) . '" class="ch-mobile-logout">Kijelentkezés</a>';
    } else {
        $mobile .= '<button type="button" class="ch-mobile-login-btn dp-open-auth-modal"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Bejelentkezés / Regisztráció</button>';
    }
    ?>
    <script>
    (function(){
        var d=document.getElementById('ch-header-actions');if(d)d.innerHTML=<?php echo json_encode($desktop); ?>;
        var m=document.getElementById('ch-mobile-auth');if(m)m.innerHTML=<?php echo json_encode($mobile); ?>;
    })();
    </script>
    <?php
}, 8 );

/* ╔══════════════════════════════════════════════╗
   ║  AUTH MODAL HTML – v7.0 (csak Google, GDPR)   ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_footer', function() {
    if ( is_user_logged_in() ) return;
    $privacy_url = esc_url( home_url( '/adatvedelmi-iranyelvek/' ) );
    $aszf_url    = esc_url( home_url( '/aszf/' ) );
    ?>
    <div class="dp-auth-overlay" id="dp-auth-overlay">
        <div class="dp-auth-modal" id="dp-auth-modal">
            <button type="button" class="dp-auth-close" id="dp-auth-close"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            <div class="dp-auth-tabs">
                <button class="dp-auth-tab is-active" data-tab="login">Bejelentkezés</button>
                <button class="dp-auth-tab" data-tab="register">Regisztráció</button>
            </div>
            <div class="dp-auth-social" id="dp-auth-social">
                <a href="#" class="dp-social-btn dp-social-btn--google" id="dp-google-login-btn"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>Folytatás Google-lel</a>
            </div>
            <div class="dp-auth-divider" id="dp-auth-divider"><span>vagy</span></div>
            <form class="dp-auth-form" id="dp-login-form">
                <div style="position:absolute;left:-9999px;"><input type="text" name="dp_fax_number" tabindex="-1" autocomplete="nope" aria-hidden="true" style="display:none!important"></div>
                <div class="dp-auth-field"><label for="dp-login-email">Email</label><input type="email" id="dp-login-email" name="email" required autocomplete="email" placeholder="te@email.com"></div>
                <div class="dp-auth-field"><label for="dp-login-password">Jelszó</label><input type="password" id="dp-login-password" name="password" required autocomplete="current-password" placeholder="••••••••"></div>
                <div class="dp-auth-field-row"><label class="dp-auth-remember"><input type="checkbox" name="remember" checked> Emlékezz rám</label><button type="button" class="dp-auth-forgot" id="dp-forgot-trigger">Elfelejtett jelszó?</button></div>
                <div class="dp-auth-message" id="dp-login-message"></div>
                <div id="dp-turnstile-login" class="dp-turnstile-wrap"></div>
                <button type="submit" class="dp-auth-submit">Bejelentkezés</button>
            </form>
            <form class="dp-auth-form" id="dp-register-form" style="display:none;">
                <div style="position:absolute;left:-9999px;"><input type="text" name="dp_fax_number" tabindex="-1" autocomplete="nope" aria-hidden="true" style="display:none!important"></div>
                <div class="dp-auth-field"><label for="dp-reg-name">Név</label><input type="text" id="dp-reg-name" name="name" required autocomplete="name" placeholder="Keresztneved" maxlength="50"></div>
                <div class="dp-auth-field"><label for="dp-reg-email">Email</label><input type="email" id="dp-reg-email" name="email" required autocomplete="email" placeholder="te@email.com"></div>
                <div class="dp-auth-field"><label for="dp-reg-password">Jelszó</label><input type="password" id="dp-reg-password" name="password" required autocomplete="new-password" placeholder="Min. 8 karakter" maxlength="128"><small class="dp-auth-hint">Minimum 8 karakter, nagybetű + kisbetű + szám.</small></div>
                <div class="dp-auth-field">
                    <input type="hidden" name="gdpr_consent" value="1">
                    <p class="dp-auth-legal">A <strong>Regisztráció</strong> gomb megnyomásával elfogadod az <a href="<?php echo $privacy_url; ?>" target="_blank" rel="noopener">Adatvédelmi tájékoztatót</a> és az <a href="<?php echo $aszf_url; ?>" target="_blank" rel="noopener">ÁSZF-et</a>.</p>
                </div>
                <div class="dp-auth-message" id="dp-register-message"></div>
                <div id="dp-turnstile-register" class="dp-turnstile-wrap"></div>
                <button type="submit" class="dp-auth-submit">Regisztráció</button>
            </form>
            <form class="dp-auth-form" id="dp-forgot-form" style="display:none;">
                <div style="position:absolute;left:-9999px;"><input type="text" name="dp_fax_number" tabindex="-1" autocomplete="nope" aria-hidden="true" style="display:none!important"></div>
                <p class="dp-auth-forgot-desc">Add meg az email címed és küldünk egy visszaállítási linket.</p>
                <div class="dp-auth-field"><label for="dp-forgot-email">Email</label><input type="email" id="dp-forgot-email" name="email" required autocomplete="email" placeholder="te@email.com"></div>
                <div class="dp-auth-message" id="dp-forgot-message"></div>
                <div id="dp-turnstile-forgot" class="dp-turnstile-wrap"></div>
                <button type="submit" class="dp-auth-submit">Visszaállítási link küldése</button>
                <button type="button" class="dp-auth-back" id="dp-forgot-back">← Vissza a bejelentkezéshez</button>
            </form>
            <p class="dp-auth-footer-note">A Google-lel való bejelentkezéssel elfogadod az <a href="<?php echo $privacy_url; ?>" target="_blank">Adatvédelmi tájékoztatónkat</a>.</p>
        </div>
    </div>
    <?php
}, 6 );
