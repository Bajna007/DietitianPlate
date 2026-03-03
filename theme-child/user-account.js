// user-account.js v12 – Turnstile + Google OAuth href fix + dupla bind védelem
(function () {
  'use strict';

  var DP = window.DP_USER || window.DPUSER || {};
  var GDPR = window.DP_GDPR || {};
  var AJAX = DP.ajax_url || DP.ajaxurl || '';
  var NONCE = DP.nonce || '';
  var SITEKEY = DP.turnstile_sitekey || '';

  var overlay, modal;
  var tabLogin, tabReg;
  var formLogin, formReg, formForgot;
  var modalBound = false;  // ← FONTOS: dupla bind védelem

  // ── CLOUDFLARE TURNSTILE ───────────────────────────────────────
  var tsWidgets = {};

  function tsInit(id) {
    if (!SITEKEY || typeof turnstile === 'undefined') return;
    if (tsWidgets[id] !== undefined) return;
    var el = document.getElementById(id);
    if (el) tsWidgets[id] = turnstile.render(el, { sitekey: SITEKEY });
  }

  function tsReset(id) {
    if (!SITEKEY || typeof turnstile === 'undefined') return;
    var wid = tsWidgets[id];
    if (wid !== undefined) turnstile.reset(wid);
  }

  function tsToken(id) {
    if (!SITEKEY || typeof turnstile === 'undefined' || tsWidgets[id] === undefined) return '';
    return turnstile.getResponse(tsWidgets[id]) || '';
  }

  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  // ── TOAST ──────────────────────────────────────────────────────
  function showToast(msg, type) {
    var t = document.getElementById('dp-auth-toast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'dp-auth-toast';
      document.body.appendChild(t);
    }
    t.className = 'dp-auth-toast dp-toast--' + (type || 'info');
    t.textContent = msg;
    t.classList.add('is-visible');
    clearTimeout(t._timer);
    t._timer = setTimeout(function () { t.classList.remove('is-visible'); }, 3500);
  }

  // ── MODAL NYITÁS/ZÁRÁS ─────────────────────────────────────────
  function openModal(tab) {
    ensureModal();
    if (!overlay || !modal) return;

    overlay.classList.add('is-open');
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';

    switchTab(tab || 'login');
  }

  function closeModal() {
    if (!overlay || !modal) return;
    overlay.classList.remove('is-open');
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  window.openAuth = openModal;

  function switchTab(tab) {
    if (!tabLogin || !tabReg || !formLogin || !formReg) return;

    tabLogin.classList.toggle('is-active', tab === 'login');
    tabReg.classList.toggle('is-active', tab === 'register');

    formLogin.style.display = tab === 'login' ? '' : 'none';
    formReg.style.display   = tab === 'register' ? '' : 'none';

    if (formForgot) formForgot.style.display = 'none';

    if (tab === 'login')    tsInit('dp-turnstile-login');
    if (tab === 'register') tsInit('dp-turnstile-register');
  }

  // ── GOOGLE GOMB HREF BEÁLLÍTÁS ─────────────────────────────────
  function setupGoogleButton() {
    var googleBtn = document.getElementById('dp-google-login-btn');
    if (!googleBtn) return;

    var googleUrl = DP.google_url || '';

    if (googleUrl) {
      // ✅ Beállítjuk a tényleges Google OAuth URL-t (a PHP-ból jön DP_USER.google_url)
      googleBtn.setAttribute('href', googleUrl);

      // Social redirect cookie mentése kattintáskor → callback visszairányít ide
      googleBtn.addEventListener('click', function (e) {
        // NEM hívunk e.preventDefault()-ot! A böngésző követi a href-et.
        try {
          document.cookie = 'dp_social_redirect=' + encodeURIComponent(window.location.href) + ';path=/;max-age=300;SameSite=Lax';
        } catch (err) { /* silent */ }
      });
    } else {
      // ❌ Nincs Google OAuth konfigurálva → rejtsük el a teljes social részt
      var socialDiv  = document.getElementById('dp-auth-social');
      var dividerDiv = document.getElementById('dp-auth-divider');
      var footerNote = qs('.dp-auth-footer-note');
      if (socialDiv)  socialDiv.style.display  = 'none';
      if (dividerDiv) dividerDiv.style.display = 'none';
      if (footerNote) footerNote.style.display = 'none';
    }
  }

  // ── MODAL FELDERÍTÉS / FELÉPÍTÉS ───────────────────────────────
  function ensureModal() {
    // Ha már bindoltuk, ne csináljuk újra
    if (modalBound) return;

    // 1) Ha a PHP már kirakta a modált (31-es snippet), AZT használjuk.
    overlay = document.getElementById('dp-auth-overlay');
    modal   = document.getElementById('dp-auth-modal');

    if (overlay && modal) {
      bindExistingModal();
      return;
    }

    // 2) Ha nincs (pl. valahol kikapcsoltad a PHP modált), akkor fallback: építünk egyet JS-ből.
    buildModalFallback();
  }

  function bindExistingModal() {
    modalBound = true;  // ← Ne fusson le többször

    // Tabs
    tabLogin = qs('.dp-auth-tab[data-tab="login"]', modal) || qs('#dp-tab-login', modal);
    tabReg   = qs('.dp-auth-tab[data-tab="register"]', modal) || qs('#dp-tab-register', modal);

    // Forms (a 31-es snippet ezeket az ID-ket használja)
    formLogin  = document.getElementById('dp-login-form') || document.getElementById('dp-form-login');
    formReg    = document.getElementById('dp-register-form') || document.getElementById('dp-form-register');
    formForgot = document.getElementById('dp-forgot-form') || null;

    // Close
    var closeBtn = document.getElementById('dp-auth-close') || qs('.dp-auth-close', modal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    // Overlay click
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal();
    });

    // Esc
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeModal();
    });

    // Tab click
    if (tabLogin) tabLogin.addEventListener('click', function () { switchTab('login'); });
    if (tabReg)   tabReg.addEventListener('click', function () { switchTab('register'); });

    // Forgot UI (31-es snippet)
    var forgotTrigger = document.getElementById('dp-forgot-trigger');
    var forgotBack    = document.getElementById('dp-forgot-back');
    if (forgotTrigger && formForgot && formLogin) {
      forgotTrigger.addEventListener('click', function () {
        formLogin.style.display = 'none';
        if (formReg) formReg.style.display = 'none';
        formForgot.style.display = '';
        tsInit('dp-turnstile-forgot');
      });
    }
    if (forgotBack && formForgot && formLogin) {
      forgotBack.addEventListener('click', function () {
        formForgot.style.display = 'none';
        formLogin.style.display = '';
        if (tabLogin && tabReg) {
          tabLogin.classList.add('is-active');
          tabReg.classList.remove('is-active');
        }
      });
    }

    // Submit handlers
    if (formLogin)  formLogin.addEventListener('submit', handleLoginExisting);
    if (formReg)    formReg.addEventListener('submit', handleRegisterExisting);
    if (formForgot) formForgot.addEventListener('submit', handleForgotExisting);

    // ★ Google OAuth gomb href beállítás ★
    setupGoogleButton();

    // Header/login gombok
    qsa('.dp-open-auth-modal, [data-open-auth]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        openModal(this.dataset.openAuth || 'login');
      });
    });
  }

  function buildModalFallback() {
    modalBound = true;  // ← Ne fusson le többször

    // Minimal fallback, ha a PHP modált nem rendereli.
    var aszfUrl  = GDPR.aszf || '/aszf/';
    var adatkUrl = GDPR.adatkezeles || '/adatkezelesi-tajekoztato/';

    overlay = document.createElement('div');
    overlay.id = 'dp-auth-overlay';
    overlay.className = 'dp-auth-overlay';

    modal = document.createElement('div');
    modal.id = 'dp-auth-modal';
    modal.className = 'dp-auth-modal';

    modal.innerHTML =
      '<div class="dp-auth-modal-inner">' +
        '<button class="dp-auth-close" id="dp-auth-close" aria-label="Bezárás">&times;</button>' +
        '<div class="dp-auth-tabs">' +
          '<button class="dp-auth-tab is-active" id="dp-tab-login" data-tab="login">Bejelentkezés</button>' +
          '<button class="dp-auth-tab" id="dp-tab-register" data-tab="register">Regisztráció</button>' +
        '</div>' +

        '<form id="dp-form-login" class="dp-auth-form" autocomplete="on">' +
          '<input type="email" id="dp-login-email" name="email" placeholder="E-mail cím" required autocomplete="email">' +
          '<input type="password" id="dp-login-pass" name="password" placeholder="Jelszó" required autocomplete="current-password">' +
          '<input type="text" name="dp_fax_number" id="dp-fax-login" style="display:none" tabindex="-1" autocomplete="off">' +
          '<button type="submit" class="dp-auth-btn">Bejelentkezés</button>' +
          '<p class="dp-auth-msg" id="dp-login-msg"></p>' +
        '</form>' +

        '<form id="dp-form-register" class="dp-auth-form" style="display:none" autocomplete="on">' +
          '<input type="text" id="dp-reg-name" name="name" placeholder="Felhasználónév" required autocomplete="username">' +
          '<input type="email" id="dp-reg-email" name="email" placeholder="E-mail cím" required autocomplete="email">' +
          '<input type="password" id="dp-reg-pass" name="password" placeholder="Jelszó (min. 8 kar.)" required autocomplete="new-password">' +
          '<input type="text" name="dp_fax_number" id="dp-fax-reg" style="display:none" tabindex="-1" autocomplete="off">' +
          '<input type="hidden" name="gdpr_consent" value="1">' +
          '<p class="dp-auth-legal">A <strong>Regisztráció</strong> gomb megnyomásával elfogadod az ' +
          '<a href="' + adatkUrl + '" target="_blank" rel="noopener">Adatkezelési tájékoztatót</a> és az ' +
          '<a href="' + aszfUrl + '" target="_blank" rel="noopener">ÁSZF-et</a>.</p>' +
          '<button type="submit" class="dp-auth-btn">Regisztráció</button>' +
          '<p class="dp-auth-msg" id="dp-reg-msg"></p>' +
        '</form>' +
      '</div>';

    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    tabLogin  = document.getElementById('dp-tab-login');
    tabReg    = document.getElementById('dp-tab-register');
    formLogin = document.getElementById('dp-form-login');
    formReg   = document.getElementById('dp-form-register');

    tabLogin.addEventListener('click', function () { switchTab('login'); });
    tabReg.addEventListener('click', function () { switchTab('register'); });

    document.getElementById('dp-auth-close').addEventListener('click', closeModal);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

    formLogin.addEventListener('submit', handleLoginFallback);
    formReg.addEventListener('submit', handleRegisterFallback);

    qsa('.dp-open-auth-modal, [data-open-auth]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        openModal(this.dataset.openAuth || 'login');
      });
    });
  }

  // ── EXISTING MODAL: LOGIN ──────────────────────────────────────
  function handleLoginExisting(e) {
    e.preventDefault();

    var msg = document.getElementById('dp-login-message') || document.getElementById('dp-login-msg');
    var btn = qs('.dp-auth-submit', formLogin) || qs('.dp-auth-btn', formLogin);

    var emailEl = document.getElementById('dp-login-email');
    var passEl  = document.getElementById('dp-login-password') || document.getElementById('dp-login-pass');
    var honeyEl = qs('input[name="dp_fax_number"]', formLogin);

    var email = emailEl ? emailEl.value.trim() : '';
    var pass  = passEl ? passEl.value : '';
    var honey = honeyEl ? honeyEl.value : '';

    if (honey) return;

    setMsg(msg, '⏳ Bejelentkezés...', 'info');
    if (btn) btn.disabled = true;

    dpAjax('dp_login', {
      email: email,
      password: pass,
      dp_fax_number: honey,
      'cf-turnstile-response': tsToken('dp-turnstile-login')
    }, function (r) {
      if (btn) btn.disabled = false;
      tsReset('dp-turnstile-login');
      if (r.success) {
        setMsg(msg, '✅ ' + (r.data.message || 'Sikeres bejelentkezés!'), 'success');
        setTimeout(function () { window.location.reload(); }, 700);
      } else {
        setMsg(msg, '❌ ' + (r.data.message || 'Hiba történt.'), 'error');
      }
    });
  }

  // ── EXISTING MODAL: REGISTER ──────────────────��────────────────
  function handleRegisterExisting(e) {
    e.preventDefault();

    var msg = document.getElementById('dp-register-message') || document.getElementById('dp-reg-msg');
    var btn = qs('.dp-auth-submit', formReg) || qs('.dp-auth-btn', formReg);

    var nameEl  = document.getElementById('dp-reg-name');
    var emailEl = document.getElementById('dp-reg-email');
    var passEl  = document.getElementById('dp-reg-password') || document.getElementById('dp-reg-pass');
    var honeyEl = qs('input[name="dp_fax_number"]', formReg);

    var name  = nameEl ? nameEl.value.trim() : '';
    var email = emailEl ? emailEl.value.trim() : '';
    var pass  = passEl ? passEl.value : '';
    var honey = honeyEl ? honeyEl.value : '';

    if (honey) return;

    if (!name)  { setMsg(msg, '❌ Add meg a neved!', 'error'); return; }
    if (!email) { setMsg(msg, '❌ Add meg az e-mail címed!', 'error'); return; }
    if (!pass || pass.length < 8) { setMsg(msg, '❌ A jelszó legalább 8 karakter legyen!', 'error'); return; }

    setMsg(msg, '⏳ Regisztráció...', 'info');
    if (btn) btn.disabled = true;

    dpAjax('dp_register', {
      name: name,
      email: email,
      password: pass,
      gdpr_consent: '1',
      dp_fax_number: honey,
      'cf-turnstile-response': tsToken('dp-turnstile-register')
    }, function (r) {
      if (btn) btn.disabled = false;
      tsReset('dp-turnstile-register');
      if (r.success) {
        setMsg(msg, '✅ ' + (r.data.message || 'Sikeres regisztráció! Ellenőrizd az e-mailt.'), 'success');
        formReg.reset();
      } else {
        setMsg(msg, '❌ ' + (r.data.message || 'Hiba történt.'), 'error');
      }
    });
  }

  // ── EXISTING MODAL: FORGOT ─────────────────────────────────────
  function handleForgotExisting(e) {
    e.preventDefault();
    var msg = document.getElementById('dp-forgot-message');
    var btn = qs('.dp-auth-submit', formForgot);

    var emailEl = document.getElementById('dp-forgot-email');
    var honeyEl = qs('input[name="dp_fax_number"]', formForgot);

    var email = emailEl ? emailEl.value.trim() : '';
    var honey = honeyEl ? honeyEl.value : '';

    if (honey) return;
    if (!email) { setMsg(msg, '❌ Add meg az e-mail címed!', 'error'); return; }

    setMsg(msg, '⏳ Küldés...', 'info');
    if (btn) btn.disabled = true;

    dpAjax('dp_forgot_password', {
      email: email,
      dp_fax_number: honey,
      'cf-turnstile-response': tsToken('dp-turnstile-forgot')
    }, function (r) {
      if (btn) btn.disabled = false;
      tsReset('dp-turnstile-forgot');
      setMsg(msg, (r.success ? '✅ ' : '❌ ') + (r.data.message || 'Kérés elküldve.'), r.success ? 'success' : 'error');
    });
  }

  // ── FALLBACK HANDLERS ──────────────────────────────────────────
  function handleLoginFallback(e) {
    e.preventDefault();
    var msg = document.getElementById('dp-login-msg');
    var btn = qs('.dp-auth-btn', formLogin);
    var email = document.getElementById('dp-login-email').value.trim();
    var pass  = document.getElementById('dp-login-pass').value;
    var honey = document.getElementById('dp-fax-login').value;
    if (honey) return;

    setMsg(msg, '⏳ Bejelentkezés...', 'info');
    btn.disabled = true;

    dpAjax('dp_login', { email: email, password: pass, dp_fax_number: honey }, function (r) {
      btn.disabled = false;
      if (r.success) {
        setMsg(msg, '✅ ' + (r.data.message || 'Sikeres bejelentkezés!'), 'success');
        setTimeout(function () { window.location.reload(); }, 700);
      } else {
        setMsg(msg, '❌ ' + (r.data.message || 'Hiba történt.'), 'error');
      }
    });
  }

  function handleRegisterFallback(e) {
    e.preventDefault();
    var msg   = document.getElementById('dp-reg-msg');
    var btn   = qs('.dp-auth-btn', formReg);
    var name  = document.getElementById('dp-reg-name').value.trim();
    var email = document.getElementById('dp-reg-email').value.trim();
    var pass  = document.getElementById('dp-reg-pass').value;
    var honey = document.getElementById('dp-fax-reg').value;

    if (honey) return;

    setMsg(msg, '⏳ Regisztráció...', 'info');
    btn.disabled = true;

    dpAjax('dp_register', { name: name, email: email, password: pass, gdpr_consent: '1', dp_fax_number: honey }, function (r) {
      btn.disabled = false;
      if (r.success) {
        setMsg(msg, '✅ ' + (r.data.message || 'Sikeres regisztráció!'), 'success');
        formReg.reset();
      } else {
        setMsg(msg, '❌ ' + (r.data.message || 'Hiba történt.'), 'error');
      }
    });
  }

  // ── AJAX HELPER ────────────────────────────────────────────────
  function dpAjax(action, data, cb) {
    if (!AJAX || !NONCE) {
      if (cb) cb({ success: false, data: { message: 'Hiányzó AJAX URL vagy nonce (DP_USER localize).' } });
      return;
    }
    var fd = new FormData();
    fd.append('action', action);
    fd.append('nonce', NONCE);
    for (var k in data) { if (Object.prototype.hasOwnProperty.call(data, k)) fd.append(k, data[k]); }

    fetch(AJAX, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (r) { if (cb) cb(r); })
      .catch(function () { if (cb) cb({ success: false, data: { message: 'Hálózati hiba.' } }); });
  }

  // ── ÜZENET HELPER ─────────────────────────────────────────────
  function setMsg(el, txt, type) {
    if (!el) return;
    el.textContent = txt;
    if (el.classList && (el.id === 'dp-login-message' || el.id === 'dp-register-message' || el.id === 'dp-forgot-message')) {
      el.className = 'dp-auth-message ' + (type === 'success' ? 'dp-auth-message--success' : type === 'error' ? 'dp-auth-message--error' : '');
      if (type === 'info') el.className = 'dp-auth-message';
      return;
    }
    el.className = 'dp-auth-msg dp-auth-msg--' + (type || 'info');
  }

  // ── KEDVENC GOMBOK ─────────────────────────────────────────────
  function initFavButtons() {
    qsa('[data-post-id]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var postId = this.dataset.postId;
        if (!DP.logged_in && !DP.loggedin) { openModal('login'); return; }

        var self = this;
        self.classList.add('dp-fav-loading');

        dpAjax('dp_toggle_favorite', { post_id: postId }, function (r) {
          self.classList.remove('dp-fav-loading');
          if (!r.success) return;

          var nowFav = r.data.favorited;
          self.dataset.fav = nowFav ? '1' : '0';
          self.textContent = nowFav ? '♥ Kedvencemben' : '♡ Kedvencekhez';
          self.classList.toggle('dp-fav-active', nowFav);

          showToast(nowFav ? '❤️ Hozzáadva!' : '🗑 Eltávolítva.', nowFav ? 'added' : 'removed');

          var badge = qs('.dp-fav-count');
          if (badge && r.data.count !== undefined) badge.textContent = r.data.count;
        });
      });
    });
  }

  // ── INIT ───────────────────────────────────────────────────────
  function init() {
    // Auth gombok
    qsa('.dp-open-auth-modal, [data-open-auth]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        openModal(this.dataset.openAuth || 'login');
      });
    });

    initFavButtons();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

})();
