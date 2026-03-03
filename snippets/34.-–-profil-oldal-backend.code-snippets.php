<?php

/**
 * 34. – Profil oldal backend
 */
/**
 * 34 – Profil oldal backend + frontend JS
 * v6.0 – Inline JS, nincs külön fájl, bulletproof
 * 
 * Kezeli:
 * - Avatar feltöltés/törlés
 * - Banner feltöltés/törlés
 * - Név módosítás
 * - Jelszó módosítás
 * - Fiók törlés (GDPR)
 * - Profil JS (inline, footer)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ╔════════════════════════════════════��═════════╗
   ║  USER NUMBER RENDSZER                          ║
   ╚══════════════════════════════════════════════╝ */
if ( ! function_exists( 'dp_get_user_number' ) ) {
    function dp_get_user_number( $uid ) {
        $num = get_user_meta( $uid, 'dp_user_number', true );
        if ( $num ) return $num;
        $count = get_option( 'dp_user_counter', 0 );
        $count++;
        update_option( 'dp_user_counter', $count );
        $num = str_pad( $count, 4, '0', STR_PAD_LEFT );
        update_user_meta( $uid, 'dp_user_number', $num );
        return $num;
    }
}

if ( ! function_exists( 'dp_format_user_number' ) ) {
    function dp_format_user_number( $uid ) {
        return '#' . dp_get_user_number( $uid );
    }
}

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: AVATAR FELTÖLTÉS                       ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_upload_avatar', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    if ( empty( $_FILES['avatar'] ) ) {
        wp_send_json_error( array( 'message' => 'Nincs fájl kiválasztva.' ) );
    }

    $file = $_FILES['avatar'];
    $allowed = array( 'image/jpeg', 'image/png', 'image/webp' );

    if ( ! in_array( $file['type'], $allowed ) ) {
        wp_send_json_error( array( 'message' => 'Csak JPG, PNG és WebP engedélyezett.' ) );
    }

    if ( $file['size'] > 2 * 1024 * 1024 ) {
        wp_send_json_error( array( 'message' => 'Maximum 2 MB.' ) );
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attach_id = media_handle_upload( 'avatar', 0 );

    if ( is_wp_error( $attach_id ) ) {
        wp_send_json_error( array( 'message' => 'Feltöltési hiba.' ) );
    }

    $uid = get_current_user_id();
    $old = get_user_meta( $uid, 'dp_avatar_id', true );
    if ( $old ) wp_delete_attachment( intval( $old ), true );

    update_user_meta( $uid, 'dp_avatar_id', $attach_id );
    $url = wp_get_attachment_image_url( $attach_id, 'thumbnail' );
    update_user_meta( $uid, 'dp_avatar_url', $url );

    wp_send_json_success( array( 'message' => 'Profilkép frissítve!', 'url' => $url ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: AVATAR TÖRLÉS                          ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_delete_avatar', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    $uid = get_current_user_id();
    $old = get_user_meta( $uid, 'dp_avatar_id', true );
    if ( $old ) wp_delete_attachment( intval( $old ), true );

    delete_user_meta( $uid, 'dp_avatar_id' );
    delete_user_meta( $uid, 'dp_avatar_url' );

    $url = get_avatar_url( $uid, array( 'size' => 128 ) );

    wp_send_json_success( array( 'message' => 'Profilkép törölve.', 'url' => $url ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  GRAVATAR FELÜLÍRÁS CUSTOM AVATARRAL           ║
   ╚══════════════════════════════════════════════╝ */
add_filter( 'get_avatar_url', function( $url, $id_or_email ) {
    $uid = 0;
    if ( is_numeric( $id_or_email ) ) {
        $uid = intval( $id_or_email );
    } elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) ) {
        $uid = intval( $id_or_email->user_id );
    } elseif ( is_string( $id_or_email ) ) {
        $user = get_user_by( 'email', $id_or_email );
        if ( $user ) $uid = $user->ID;
    }
    if ( $uid ) {
        $custom = get_user_meta( $uid, 'dp_avatar_url', true );
        if ( $custom ) return $custom;
    }
    return $url;
}, 10, 2 );

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: BANNER FELTÖLTÉS                       ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_upload_banner', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    if ( empty( $_FILES['banner'] ) ) {
        wp_send_json_error( array( 'message' => 'Nincs fájl kiválasztva.' ) );
    }

    $file = $_FILES['banner'];
    $allowed = array( 'image/jpeg', 'image/png', 'image/webp' );

    if ( ! in_array( $file['type'], $allowed ) ) {
        wp_send_json_error( array( 'message' => 'Csak JPG, PNG és WebP engedélyezett.' ) );
    }

    if ( $file['size'] > 5 * 1024 * 1024 ) {
        wp_send_json_error( array( 'message' => 'Maximum 5 MB.' ) );
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attach_id = media_handle_upload( 'banner', 0 );

    if ( is_wp_error( $attach_id ) ) {
        wp_send_json_error( array( 'message' => 'Feltöltési hiba.' ) );
    }

    $uid = get_current_user_id();
    $old = get_user_meta( $uid, 'dp_banner_id', true );
    if ( $old ) wp_delete_attachment( intval( $old ), true );

    update_user_meta( $uid, 'dp_banner_id', $attach_id );
    $url = wp_get_attachment_image_url( $attach_id, 'full' );
    update_user_meta( $uid, 'dp_banner_url', $url );

    wp_send_json_success( array( 'message' => 'Banner frissítve!', 'url' => $url ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: BANNER TÖRLÉS                          ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_delete_banner', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    $uid = get_current_user_id();
    $old = get_user_meta( $uid, 'dp_banner_id', true );
    if ( $old ) wp_delete_attachment( intval( $old ), true );

    delete_user_meta( $uid, 'dp_banner_id' );
    delete_user_meta( $uid, 'dp_banner_url' );

    wp_send_json_success( array( 'message' => 'Banner törölve.' ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: NÉV MÓDOSÍTÁS                          ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_update_name', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    $name = sanitize_text_field( isset( $_POST['name'] ) ? $_POST['name'] : '' );
    if ( mb_strlen( $name ) < 2 || mb_strlen( $name ) > 50 ) {
        wp_send_json_error( array( 'message' => 'A név 2-50 karakter legyen.' ) );
    }

    $uid = get_current_user_id();
    wp_update_user( array( 'ID' => $uid, 'display_name' => $name, 'first_name' => $name ) );

    wp_send_json_success( array( 'message' => 'Név frissítve!', 'name' => $name ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: JELSZÓ MÓDOSÍTÁS                       ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_update_password', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    $uid  = get_current_user_id();
    $user = get_user_by( 'ID', $uid );
    $new  = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';
    $conf = isset( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : '';
    $cur  = isset( $_POST['current_password'] ) ? $_POST['current_password'] : '';

    if ( empty( $new ) ) wp_send_json_error( array( 'message' => 'Add meg az új jelszót.' ) );
    if ( $new !== $conf ) wp_send_json_error( array( 'message' => 'Az új jelszavak nem egyeznek.' ) );

    $strong = function_exists( 'dp_password_strong' ) ? dp_password_strong( $new ) : true;
    if ( $strong !== true ) wp_send_json_error( array( 'message' => $strong ) );

    $reg_via = get_user_meta( $uid, 'dp_registered_via', true );
    $has_pw  = get_user_meta( $uid, 'dp_has_password', true );

    if ( ! ( $reg_via === 'google' && ! $has_pw ) ) {
        if ( empty( $cur ) ) wp_send_json_error( array( 'message' => 'Add meg a jelenlegi jelszót.' ) );
        if ( ! wp_check_password( $cur, $user->user_pass, $uid ) ) {
            wp_send_json_error( array( 'message' => 'Hibás jelenlegi jelszó.' ) );
        }
    }

    wp_set_password( $new, $uid );
    update_user_meta( $uid, 'dp_has_password', true );

    wp_set_current_user( $uid );
    wp_set_auth_cookie( $uid, true );

    wp_send_json_success( array( 'message' => 'Jelszó módosítva!' ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  AJAX: FIÓK TÖRLÉS (GDPR)                    ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_ajax_dp_delete_account', function() {
    check_ajax_referer( 'dp_profil_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Jelentkezz be.' ) );

    $confirm = isset( $_POST['confirm_text'] ) ? sanitize_text_field( $_POST['confirm_text'] ) : '';
    if ( $confirm !== 'TÖRLÉS' ) {
        wp_send_json_error( array( 'message' => 'Írd be: TÖRLÉS' ) );
    }

    $uid = get_current_user_id();
    if ( user_can( $uid, 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Admin fiók nem törölhető innen.' ) );
    }

    $avatar_id = get_user_meta( $uid, 'dp_avatar_id', true );
    if ( $avatar_id ) wp_delete_attachment( intval( $avatar_id ), true );
    $banner_id = get_user_meta( $uid, 'dp_banner_id', true );
    if ( $banner_id ) wp_delete_attachment( intval( $banner_id ), true );

    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user( $uid );

    wp_send_json_success( array( 'message' => 'Fiók törölve. Átirányítás…', 'redirect' => home_url() ) );
});

/* ╔══════════════════════════════════════════════╗
   ║  PROFIL JS LOKALIZÁCIÓ + INLINE SCRIPT         ║
   ╚══════════════════════════════════════════════╝ */
add_action( 'wp_footer', function() {
    if ( ! is_page( 'profil' ) && ! is_page_template( 'page-profil.php' ) ) return;
    if ( ! is_user_logged_in() ) return;

    $nonce = wp_create_nonce( 'dp_profil_nonce' );
    $ajax  = admin_url( 'admin-ajax.php' );
    $colors = defined( 'DP_COLLECTION_COLORS' ) ? DP_COLLECTION_COLORS : array('#2d6a4f','#1b4332','#40916c','#52b788','#74c69d','#2563eb','#3b82f6','#7c3aed','#a855f7','#db2777','#dc2626','#ea580c','#d97706','#ca8a04','#65a30d');
    ?>
    <script id="dp-profil-inline-js">
    (function(){
    'use strict';

    var AJAX='<?php echo esc_url( $ajax ); ?>';
    var NONCE='<?php echo esc_attr( $nonce ); ?>';
    var COLORS=<?php echo json_encode( array_values( $colors ) ); ?>;

    /* ── Helpers ── */
    function q(id){return document.getElementById(id)}
    function qa(s){return document.querySelectorAll(s)}

    function ajax(action,data,cb){
        var fd=new FormData();
        fd.append('action',action);
        fd.append('nonce',NONCE);
        if(data){for(var k in data){if(data.hasOwnProperty(k))fd.append(k,data[k])}}
        fetch(AJAX,{method:'POST',body:fd,credentials:'same-origin'})
        .then(function(r){return r.text()})
        .then(function(t){
            try{var j=JSON.parse(t);if(cb)cb(j)}
            catch(e){console.error('Profil AJAX parse error:',t.substring(0,300));if(cb)cb({success:false,data:{message:'Szerver hiba.'}})}
        })
        .catch(function(e){console.error('Profil fetch error:',e);if(cb)cb({success:false,data:{message:'Hálózati hiba.'}})});
    }

    function msg(el,t,ok){
        if(!el)return;el.textContent=t;
        el.className='dp-profil-setting-msg '+(ok?'dp-profil-msg--success':'dp-profil-msg--error');
        clearTimeout(el._t);el._t=setTimeout(function(){el.textContent='';el.className='dp-profil-setting-msg'},5000);
    }

    function spin(b,on){if(!b)return;if(on){b._t=b.textContent;b.textContent='Várj…';b.disabled=true}else{b.textContent=b._t||'';b.disabled=false}}

    function fileUpload(inputId,fieldName,action,maxMB,onOk){
        var inp=q(inputId);if(!inp)return;
        inp.addEventListener('change',function(){
            var f=this.files[0];if(!f)return;
            if(f.size>maxMB*1024*1024){alert('Max '+maxMB+' MB.');this.value='';return}
            if(['image/jpeg','image/png','image/webp'].indexOf(f.type)<0){alert('Csak JPG/PNG/WebP.');this.value='';return}
            var fd=new FormData();fd.append('action',action);fd.append('nonce',NONCE);fd.append(fieldName,f);
            fetch(AJAX,{method:'POST',body:fd,credentials:'same-origin'})
            .then(function(r){return r.json()})
            .then(function(r){if(r.success&&onOk)onOk(r.data);else if(!r.success)alert(r.data.message||'Hiba');inp.value=''})
            .catch(function(){alert('Hálózati hiba.');inp.value=''});
        });
    }

    /* ══════════════════════
       TABS
       ══════════════════════ */
    qa('.dp-profil-tab').forEach(function(tab){
        tab.addEventListener('click',function(){
            var t=this.getAttribute('data-tab');
            qa('.dp-profil-tab').forEach(function(x){x.classList.remove('is-active')});
            this.classList.add('is-active');
            qa('.dp-profil-panel').forEach(function(p){p.classList.remove('is-active')});
            var p=q('dp-panel-'+t);if(p)p.classList.add('is-active');
            history.replaceState(null,'','#'+t);
        });
    });
    var hash=location.hash.replace('#','');
    if(hash){var ht=document.querySelector('.dp-profil-tab[data-tab="'+hash+'"]');if(ht)ht.click()}

    /* ══════════════════════
       AVATAR
       ══════════════════════ */
    var avatarImg=q('dp-profil-avatar');
    var avatarMsg=q('dp-avatar-msg');

    (function(){
        var eb=q('dp-avatar-edit-btn'),ub=q('dp-settings-avatar-upload'),inp=q('dp-avatar-input');
        function go(){if(inp)inp.click()}
        if(eb)eb.addEventListener('click',go);
        if(ub)ub.addEventListener('click',go);
    })();

    fileUpload('dp-avatar-input','avatar','dp_upload_avatar',2,function(d){
        msg(avatarMsg,d.message,true);
        if(avatarImg)avatarImg.src=d.url;
        qa('.ch-header-avatar img,.ch-mobile-user-avatar').forEach(function(el){el.src=d.url});
    });

    (function(){
        var db=q('dp-settings-avatar-delete');
        if(!db)return;
        db.addEventListener('click',function(){
            if(!confirm('Biztosan törlöd a profilképed?'))return;
            spin(db,true);
            ajax('dp_delete_avatar',{},function(r){spin(db,false);if(r.success){msg(avatarMsg,r.data.message,true);if(avatarImg)avatarImg.src=r.data.url}else msg(avatarMsg,r.data.message,false)});
        });
    })();

    /* ══════════════════════
       BANNER
       ══════════════════════ */
    var bannerEl=q('dp-profil-banner');
    var bannerMsg=q('dp-banner-msg');

    (function(){
        var eb=q('dp-banner-edit-btn'),ub=q('dp-settings-banner-upload'),inp=q('dp-banner-input');
        function go(){if(inp)inp.click()}
        if(eb)eb.addEventListener('click',go);
        if(ub)ub.addEventListener('click',go);
    })();

    fileUpload('dp-banner-input','banner','dp_upload_banner',5,function(d){
        msg(bannerMsg,d.message,true);
        if(bannerEl)bannerEl.style.backgroundImage='url('+d.url+')';
    });

    (function(){
        var db=q('dp-settings-banner-delete');
        if(!db)return;
        db.addEventListener('click',function(){
            if(!confirm('Biztosan törlöd a banner képed?'))return;
            spin(db,true);
            ajax('dp_delete_banner',{},function(r){spin(db,false);if(r.success){msg(bannerMsg,r.data.message,true);if(bannerEl)bannerEl.style.backgroundImage='none'}else msg(bannerMsg,r.data.message,false)});
        });
    })();

    /* ══════════════════════
       NÉV
       ══════════════════════ */
    (function(){
        var inp=q('dp-settings-name'),btn=q('dp-save-name'),m=q('dp-name-msg');
        if(!btn||!inp)return;
        function save(){
            var n=inp.value.trim();if(n.length<2||n.length>50){msg(m,'2-50 karakter.',false);return}
            spin(btn,true);
            ajax('dp_update_name',{name:n},function(r){spin(btn,false);if(r.success){msg(m,r.data.message,true);var h=q('dp-profil-display-name');if(h)h.textContent=r.data.name}else msg(m,r.data.message,false)});
        }
        btn.addEventListener('click',save);
        inp.addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();save()}});
    })();

    /* ══════════════════════
       JELSZÓ
       ══════════════════════ */
    (function(){
        var cur=q('dp-pw-current'),nw=q('dp-pw-new'),cf=q('dp-pw-confirm'),btn=q('dp-save-password'),m=q('dp-pw-msg');
        if(!btn||!nw||!cf)return;
        btn.addEventListener('click',function(){
            if(!nw.value){msg(m,'Add meg az új jelszót.',false);return}
            if(nw.value!==cf.value){msg(m,'Nem egyeznek.',false);return}
            if(nw.value.length<8){msg(m,'Min. 8 karakter.',false);return}
            var d={new_password:nw.value,confirm_password:cf.value};if(cur)d.current_password=cur.value;
            spin(btn,true);
            ajax('dp_update_password',d,function(r){spin(btn,false);if(r.success){msg(m,r.data.message,true);if(cur)cur.value='';nw.value='';cf.value=''}else msg(m,r.data.message,false)});
        });
    })();

    /* ══════════════════════
       FIÓK TÖRLÉS
       ══════════════════════ */
    (function(){
        var inp=q('dp-delete-confirm'),btn=q('dp-delete-account-btn'),m=q('dp-delete-msg');
        if(!inp||!btn)return;
        inp.addEventListener('input',function(){btn.disabled=(this.value.trim()!=='TÖRLÉS')});
        btn.addEventListener('click',function(){
            if(inp.value.trim()!=='TÖRLÉS')return;
            if(!confirm('VÉGLEGESEN törlöd a fiókodat?\nNEM vonható vissza!'))return;
            spin(btn,true);
            ajax('dp_delete_account',{confirm_text:'TÖRLÉS'},function(r){spin(btn,false);if(r.success){msg(m,r.data.message,true);setTimeout(function(){location.href=r.data.redirect||'/'},1500)}else msg(m,r.data.message,false)});
        });
    })();

    /* ══════════════════════════════════════════════
       GYŰJTEMÉNYEK
       ══════════════════════════════════════════════ */
    var collId=null,collName='',mMode='create',mEditId=null,selColor=COLORS[0],sTimer=null;
    var overlay=q('dp-coll-modal-overlay');

    if(overlay)(function(){

        var nameInp=q('dp-coll-name-input'),titleEl=q('dp-coll-modal-title');
        var saveBtn=q('dp-coll-modal-save'),cancelBtn=q('dp-coll-modal-cancel');
        var closeBtn=q('dp-coll-modal-close'),msgEl=q('dp-coll-modal-msg');
        var cpicker=q('dp-coll-color-picker'),grid=q('dp-coll-grid');
        var newBtn=q('dp-coll-new-btn');

        /* Szín picker */
        if(cpicker){
            cpicker.innerHTML='';
            COLORS.forEach(function(c){
                var b=document.createElement('button');b.type='button';
                b.className='dp-coll-color-swatch'+(c===selColor?' is-selected':'');
                b.style.background=c;b.setAttribute('data-color',c);
                b.addEventListener('click',function(){
                    cpicker.querySelectorAll('.dp-coll-color-swatch').forEach(function(x){x.classList.remove('is-selected')});
                    this.classList.add('is-selected');selColor=c;
                });
                cpicker.appendChild(b);
            });
        }

        function openM(mode,id,name,color){
            mMode=mode;mEditId=id||null;
            if(nameInp)nameInp.value=name||'';
            if(titleEl)titleEl.textContent=mode==='edit'?'Szerkesztés':'Új gyűjtemény';
            if(saveBtn)saveBtn.textContent=mode==='edit'?'Mentés':'Létrehozás';
            selColor=color||COLORS[0];
            if(cpicker)cpicker.querySelectorAll('.dp-coll-color-swatch').forEach(function(s){s.classList.toggle('is-selected',s.getAttribute('data-color')===selColor)});
            if(msgEl){msgEl.textContent='';msgEl.className='dp-profil-setting-msg'}
            overlay.classList.add('is-open');
            if(nameInp)setTimeout(function(){nameInp.focus()},150);
        }

        function closeM(){overlay.classList.remove('is-open')}

        if(newBtn)newBtn.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();openM('create')});
        if(closeBtn)closeBtn.addEventListener('click',closeM);
        if(cancelBtn)cancelBtn.addEventListener('click',closeM);
        overlay.addEventListener('click',function(e){if(e.target===overlay)closeM()});
        document.addEventListener('keydown',function(e){if(e.key==='Escape'&&overlay.classList.contains('is-open'))closeM()});

        /* Mentés */
        if(saveBtn)saveBtn.addEventListener('click',function(){
            var n=nameInp?nameInp.value.trim():'';
            if(!n){msg(msgEl,'Adj nevet.',false);return}
            spin(saveBtn,true);

            if(mMode==='edit'&&mEditId){
                ajax('dp_update_collection',{collection_id:mEditId,name:n,color:selColor},function(r){
                    spin(saveBtn,false);
                    if(r.success){
                        closeM();
                        var c=document.querySelector('.dp-coll-card[data-coll-id="'+mEditId+'"]');
                        if(c){c.setAttribute('data-coll-name',n);c.setAttribute('data-coll-color',selColor);var ne=c.querySelector('.dp-coll-card-name');if(ne)ne.textContent=n;var te=c.querySelector('.dp-coll-card-top');if(te)te.style.background=selColor}
                    }else msg(msgEl,r.data.message,false);
                });
            } else {
                ajax('dp_create_collection',{name:n,color:selColor},function(r){
                    spin(saveBtn,false);
                    if(r.success){
                        closeM();
                        var c=r.data.collection;
                        var card=document.createElement('div');
                        card.className='dp-coll-card';
                        card.setAttribute('data-coll-id',c.id);
                        card.setAttribute('data-coll-name',c.name);
                        card.setAttribute('data-coll-color',c.color);
                        card.innerHTML='<div class="dp-coll-card-top" style="background:'+c.color+'"><span class="dp-coll-card-icon">📁</span><div class="dp-coll-card-actions"><button type="button" class="dp-coll-edit-btn" data-coll-id="'+c.id+'" title="Szerkesztés">✏️</button><button type="button" class="dp-coll-delete-btn" data-coll-id="'+c.id+'" title="Törlés">🗑️</button></div></div><div class="dp-coll-card-body"><h3 class="dp-coll-card-name">'+c.name+'</h3><span class="dp-coll-card-count">0 recept</span></div>';
                        if(grid){var ref=grid.querySelector('.dp-coll-card--new');if(ref)grid.insertBefore(card,ref);else grid.appendChild(card)}
                        bindCard(card);updCounts();
                    }else msg(msgEl,r.data.message,false);
                });
            }
        });

        if(nameInp)nameInp.addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();if(saveBtn)saveBtn.click()}});

        /* Kártya binding */
        function bindCard(card){
            var eb=card.querySelector('.dp-coll-edit-btn');
            if(eb)eb.addEventListener('click',function(e){e.stopPropagation();openM('edit',this.getAttribute('data-coll-id'),card.getAttribute('data-coll-name'),card.getAttribute('data-coll-color'))});

            var db=card.querySelector('.dp-coll-delete-btn');
            if(db)db.addEventListener('click',function(e){
                e.stopPropagation();
                if(!confirm('Törlöd a "'+card.getAttribute('data-coll-name')+'" gyűjteményt?'))return;
                ajax('dp_delete_collection',{collection_id:this.getAttribute('data-coll-id')},function(r){
                    if(r.success){card.style.cssText='opacity:0;transform:scale(.9);transition:all .3s';setTimeout(function(){card.remove();updCounts()},300)}
                });
            });

            card.addEventListener('click',function(e){
                if(e.target.closest('.dp-coll-edit-btn')||e.target.closest('.dp-coll-delete-btn'))return;
                openDetail(this.getAttribute('data-coll-id'),this.getAttribute('data-coll-name'));
            });
        }

        qa('.dp-coll-card:not(.dp-coll-card--new)').forEach(bindCard);

        function updCounts(){
            var n=qa('.dp-coll-card:not(.dp-coll-card--new)').length;
            var e1=q('dp-coll-count');if(e1)e1.textContent=n;
            var e2=q('dp-stat-colls');if(e2)e2.textContent=n;
            qa('.dp-profil-tab-badge').forEach(function(b){b.textContent=n});
        }

        /* Részletes nézet */
        function openDetail(cid,name){
            collId=cid;collName=name;
            var lv=q('dp-coll-list-view'),dv=q('dp-coll-detail-view');
            if(!lv||!dv)return;
            lv.style.display='none';dv.style.display='block';
            var ne=q('dp-coll-detail-name');if(ne)ne.textContent=name;
            var si=q('dp-coll-search-input');if(si)si.value='';
            var sr=q('dp-coll-search-results');if(sr)sr.style.display='none';
            loadRecipes(cid);
        }

        var backBtn=q('dp-coll-back-btn');
        if(backBtn)backBtn.addEventListener('click',function(){
            var lv=q('dp-coll-list-view'),dv=q('dp-coll-detail-view');
            if(lv)lv.style.display='';if(dv)dv.style.display='none';
            collId=null;location.reload();
        });

        function loadRecipes(cid){
            var g=q('dp-coll-detail-grid'),e=q('dp-coll-detail-empty'),c=q('dp-coll-detail-count');
            if(!g)return;
            g.innerHTML='<div style="padding:40px;text-align:center;color:#999">Betöltés…</div>';
            if(e)e.style.display='none';

            ajax('dp_get_collection_recipes',{collection_id:cid},function(r){
                if(!r.success){g.innerHTML='<div style="padding:40px;text-align:center;color:#dc2626">'+(r.data.message||'Hiba')+'</div>';return}
                if(!r.data.html){g.innerHTML='';g.style.display='none';if(e)e.style.display='';if(c)c.textContent='0';return}

                g.innerHTML=r.data.html;g.style.display='';if(e)e.style.display='none';
                if(c)c.textContent=r.data.count||g.querySelectorAll('.dp-coll-recipe-item').length;

                g.querySelectorAll('.dp-coll-remove-btn').forEach(function(btn){
                    btn.addEventListener('click',function(ev){
                        ev.preventDefault();ev.stopPropagation();
                        var rid=this.getAttribute('data-recipe-id'),card=this.closest('.dp-coll-recipe-item');
                        if(!confirm('Eltávolítod?'))return;
                        ajax('dp_toggle_collection_recipe',{collection_id:cid,recipe_id:rid},function(r2){
                            if(r2.success&&r2.data.action==='removed'&&card){
                                card.style.cssText='opacity:0;transform:scale(.9);transition:all .3s';
                                setTimeout(function(){card.remove();var left=g.querySelectorAll('.dp-coll-recipe-item').length;if(c)c.textContent=left;if(!left){g.style.display='none';if(e)e.style.display=''}},300);
                            }
                        });
                    });
                });
            });
        }

        /* Recept kereső */
        var sInp=q('dp-coll-search-input'),sRes=q('dp-coll-search-results');
        if(sInp&&sRes){
            sInp.addEventListener('input',function(){
                var v=this.value.trim();clearTimeout(sTimer);
                if(v.length<2){sRes.style.display='none';sRes.innerHTML='';return}
                sTimer=setTimeout(function(){
                    sRes.innerHTML='<div class="dp-coll-search-loading">Keresés…</div>';sRes.style.display='block';
                    ajax('dp_search_recipes',{search:v,collection_id:collId||''},function(r){
                        if(!r.success||!r.data.results||!r.data.results.length){sRes.innerHTML='<div class="dp-coll-search-empty">Nincs találat</div>';return}
                        var h='';
                        r.data.results.forEach(function(i){
                            var ic=i.is_in?' is-in':'',it=i.is_in?'✓ Benne':'＋ Hozzáad';
                            var th=i.thumb?'<img src="'+i.thumb+'" alt="" class="dp-coll-sr-thumb">':'<span class="dp-coll-sr-nothumb">🍽</span>';
                            h+='<div class="dp-coll-sr-item'+ic+'" data-rid="'+i.id+'">'+th+'<span class="dp-coll-sr-title">'+i.title+'</span><button type="button" class="dp-coll-sr-add'+ic+'">'+it+'</button></div>';
                        });
                        sRes.innerHTML=h;
                        sRes.querySelectorAll('.dp-coll-sr-item').forEach(function(row){
                            var ab=row.querySelector('.dp-coll-sr-add');if(!ab)return;
                            ab.addEventListener('click',function(ev){
                                ev.stopPropagation();if(!collId)return;
                                var rid=row.getAttribute('data-rid'),me=this;me.textContent='…';me.disabled=true;
                                ajax('dp_toggle_collection_recipe',{collection_id:collId,recipe_id:rid},function(r2){
                                    me.disabled=false;
                                    if(r2.success){
                                        if(r2.data.action==='added'){row.classList.add('is-in');me.classList.add('is-in');me.textContent='✓ Benne'}
                                        else{row.classList.remove('is-in');me.classList.remove('is-in');me.textContent='＋ Hozzáad'}
                                        loadRecipes(collId);
                                    }else{me.textContent='Hiba';setTimeout(function(){me.textContent=row.classList.contains('is-in')?'✓ Benne':'＋ Hozzáad'},2000)}
                                });
                            });
                        });
                    });
                },350);
            });

            document.addEventListener('click',function(e){if(!e.target.closest('#dp-coll-search-wrap'))sRes.style.display='none'});
            sInp.addEventListener('focus',function(){if(sRes.innerHTML&&this.value.trim().length>=2)sRes.style.display='block'});
        }

    })();

    })();
    </script>
    <?php
}, 50 );
