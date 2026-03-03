(function(){
'use strict';

/* ═══ DEBUG ═══ */
console.log('[profil.js v5.0] init start');
console.log('[profil.js] DP_PROFIL:', window.DP_PROFIL);
console.log('[profil.js] DP_USER:', window.DP_USER);

if(!window.DP_PROFIL || !DP_PROFIL.ajax_url || !DP_PROFIL.nonce){
    console.error('[profil.js] DP_PROFIL hiányzik vagy hiányos → kilépés');
    return;
}

var AJAX  = DP_PROFIL.ajax_url;
var NONCE = DP_PROFIL.nonce;
var COLORS = (window.DP_USER && DP_USER.collection_colors) ? DP_USER.collection_colors : ['#2d6a4f','#1b4332','#40916c','#52b788','#74c69d','#2563eb','#3b82f6','#7c3aed','#a855f7','#db2777','#dc2626','#ea580c','#d97706','#ca8a04','#65a30d'];

/* ═══ HELPERS ═══ */
function $(id){return document.getElementById(id)}
function $$(sel){return document.querySelectorAll(sel)}

function dpAjax(action, data, cb){
    console.log('[dpAjax] →', action, data);
    var fd = new FormData();
    fd.append('action', action);
    fd.append('nonce', NONCE);
    if(data){
        for(var k in data){
            if(data.hasOwnProperty(k)) fd.append(k, data[k]);
        }
    }
    fetch(AJAX, {method:'POST', body:fd, credentials:'same-origin'})
    .then(function(r){
        var ct = r.headers.get('content-type') || '';
        if(ct.indexOf('application/json') === -1){
            console.warn('[dpAjax] Nem JSON válasz:', ct);
        }
        return r.text();
    })
    .then(function(txt){
        console.log('[dpAjax] ←', action, txt.substring(0,200));
        try{
            var json = JSON.parse(txt);
            if(cb) cb(json);
        } catch(e){
            console.error('[dpAjax] JSON parse error:', e, 'Raw:', txt.substring(0,500));
            if(cb) cb({success:false, data:{message:'Szerver hiba (nem JSON válasz).'}});
        }
    })
    .catch(function(e){
        console.error('[dpAjax] fetch error:', e);
        if(cb) cb({success:false, data:{message:'Hálózati hiba.'}});
    });
}

function showMsg(el, text, ok){
    if(!el) return;
    el.textContent = text;
    el.className = 'dp-profil-setting-msg ' + (ok ? 'dp-profil-msg--success' : 'dp-profil-msg--error');
    clearTimeout(el._t);
    el._t = setTimeout(function(){el.textContent=''; el.className='dp-profil-setting-msg'}, 5000);
}

function btnLoad(btn, on){
    if(!btn) return;
    if(on){btn._txt=btn.textContent; btn.textContent='Várj…'; btn.disabled=true;}
    else{btn.textContent=btn._txt||''; btn.disabled=false;}
}

/* ═══ TABS ═══ */
function initTabs(){
    console.log('[profil.js] initTabs');
    var tabs = $$('.dp-profil-tab');
    var panels = $$('.dp-profil-panel');
    if(!tabs.length){console.warn('[profil.js] Nincsenek tab gombok'); return;}

    tabs.forEach(function(tab){
        tab.addEventListener('click', function(){
            var t = this.getAttribute('data-tab');
            tabs.forEach(function(x){x.classList.remove('is-active')});
            this.classList.add('is-active');
            panels.forEach(function(p){p.classList.remove('is-active')});
            var panel = $('dp-panel-'+t);
            if(panel) panel.classList.add('is-active');
            history.replaceState(null, '', '#'+t);
        });
    });

    var h = window.location.hash.replace('#','');
    if(h){
        var ht = document.querySelector('.dp-profil-tab[data-tab="'+h+'"]');
        if(ht) ht.click();
    }
}

/* ═══ AVATAR ═══ */
function initAvatar(){
    console.log('[profil.js] initAvatar');
    var editBtn = $('dp-avatar-edit-btn');
    var input   = $('dp-avatar-input');
    var img     = $('dp-profil-avatar');
    var upBtn   = $('dp-settings-avatar-upload');
    var delBtn  = $('dp-settings-avatar-delete');
    var msg     = $('dp-avatar-msg');

    function openPicker(){ if(input) input.click(); }

    if(editBtn) editBtn.addEventListener('click', openPicker);
    if(upBtn) upBtn.addEventListener('click', openPicker);

    if(input) input.addEventListener('change', function(){
        var f = this.files[0];
        if(!f) return;
        if(f.size > 2*1024*1024){ showMsg(msg,'Max 2 MB.',false); this.value=''; return; }
        if(['image/jpeg','image/png','image/webp'].indexOf(f.type) < 0){ showMsg(msg,'Csak JPG/PNG/WebP.',false); this.value=''; return; }
        var fd = new FormData();
        fd.append('action','dp_upload_avatar');
        fd.append('nonce', NONCE);
        fd.append('avatar', f);
        if(upBtn) btnLoad(upBtn, true);
        fetch(AJAX, {method:'POST', body:fd, credentials:'same-origin'})
        .then(function(r){return r.json()})
        .then(function(r){
            if(upBtn) btnLoad(upBtn, false);
            if(r.success){
                showMsg(msg, r.data.message, true);
                if(img) img.src = r.data.url;
                $$('.ch-header-avatar img,.ch-mobile-user-avatar').forEach(function(el){el.src=r.data.url});
            } else {
                showMsg(msg, r.data.message, false);
            }
            input.value='';
        })
        .catch(function(e){
            if(upBtn) btnLoad(upBtn, false);
            showMsg(msg, 'Hiba: '+e.message, false);
            input.value='';
        });
    });

    if(delBtn) delBtn.addEventListener('click', function(){
        if(!confirm('Biztosan törlöd a profilképed?')) return;
        btnLoad(delBtn, true);
        dpAjax('dp_delete_avatar', {}, function(r){
            btnLoad(delBtn, false);
            if(r.success){ showMsg(msg, r.data.message, true); if(img) img.src = r.data.url; }
            else showMsg(msg, r.data.message, false);
        });
    });
}

/* ═══ BANNER ═══ */
function initBanner(){
    console.log('[profil.js] initBanner');
    var editBtn = $('dp-banner-edit-btn');
    var input   = $('dp-banner-input');
    var banner  = $('dp-profil-banner');
    var upBtn   = $('dp-settings-banner-upload');
    var delBtn  = $('dp-settings-banner-delete');
    var msg     = $('dp-banner-msg');

    function openPicker(){ if(input) input.click(); }

    if(editBtn) editBtn.addEventListener('click', openPicker);
    if(upBtn) upBtn.addEventListener('click', openPicker);

    if(input) input.addEventListener('change', function(){
        var f = this.files[0];
        if(!f) return;
        if(f.size > 5*1024*1024){ showMsg(msg,'Max 5 MB.',false); this.value=''; return; }
        if(['image/jpeg','image/png','image/webp'].indexOf(f.type) < 0){ showMsg(msg,'Csak JPG/PNG/WebP.',false); this.value=''; return; }
        var fd = new FormData();
        fd.append('action','dp_upload_banner');
        fd.append('nonce', NONCE);
        fd.append('banner', f);
        if(upBtn) btnLoad(upBtn, true);
        fetch(AJAX, {method:'POST', body:fd, credentials:'same-origin'})
        .then(function(r){return r.json()})
        .then(function(r){
            if(upBtn) btnLoad(upBtn, false);
            if(r.success){ showMsg(msg, r.data.message, true); if(banner) banner.style.backgroundImage='url('+r.data.url+')'; }
            else showMsg(msg, r.data.message, false);
            input.value='';
        })
        .catch(function(e){
            if(upBtn) btnLoad(upBtn, false);
            showMsg(msg, 'Hiba: '+e.message, false);
            input.value='';
        });
    });

    if(delBtn) delBtn.addEventListener('click', function(){
        if(!confirm('Biztosan törlöd a banner képed?')) return;
        btnLoad(delBtn, true);
        dpAjax('dp_delete_banner', {}, function(r){
            btnLoad(delBtn, false);
            if(r.success){ showMsg(msg, r.data.message, true); if(banner) banner.style.backgroundImage='none'; }
            else showMsg(msg, r.data.message, false);
        });
    });
}

/* ═══ NÉV ═══ */
function initName(){
    console.log('[profil.js] initName');
    var input = $('dp-settings-name');
    var btn   = $('dp-save-name');
    var msg   = $('dp-name-msg');
    if(!btn || !input){console.warn('[profil.js] Név elemek hiányoznak'); return;}

    function save(){
        var n = input.value.trim();
        if(n.length<2 || n.length>50){ showMsg(msg,'2-50 karakter.',false); return; }
        btnLoad(btn, true);
        dpAjax('dp_update_name', {name:n}, function(r){
            btnLoad(btn, false);
            if(r.success){
                showMsg(msg, r.data.message, true);
                var h = $('dp-profil-display-name');
                if(h) h.textContent = r.data.name;
            } else {
                showMsg(msg, r.data.message, false);
            }
        });
    }
    btn.addEventListener('click', save);
    input.addEventListener('keydown', function(e){if(e.key==='Enter'){e.preventDefault();save();}});
}

/* ═══ JELSZÓ ═══ */
function initPassword(){
    console.log('[profil.js] initPassword');
    var cur = $('dp-pw-current');
    var nw  = $('dp-pw-new');
    var cf  = $('dp-pw-confirm');
    var btn = $('dp-save-password');
    var msg = $('dp-pw-msg');
    if(!btn || !nw || !cf){console.warn('[profil.js] Jelszó elemek hiányoznak'); return;}

    btn.addEventListener('click', function(){
        if(!nw.value){ showMsg(msg,'Add meg az új jelszót.',false); return; }
        if(nw.value !== cf.value){ showMsg(msg,'Nem egyeznek.',false); return; }
        if(nw.value.length < 8){ showMsg(msg,'Min. 8 karakter.',false); return; }
        var d = {new_password: nw.value, confirm_password: cf.value};
        if(cur) d.current_password = cur.value;
        btnLoad(btn, true);
        dpAjax('dp_update_password', d, function(r){
            btnLoad(btn, false);
            if(r.success){ showMsg(msg, r.data.message, true); if(cur) cur.value=''; nw.value=''; cf.value=''; }
            else showMsg(msg, r.data.message, false);
        });
    });
}

/* ═══ FIÓK TÖRLÉS ═══ */
function initDelete(){
    console.log('[profil.js] initDelete');
    var input = $('dp-delete-confirm');
    var btn   = $('dp-delete-account-btn');
    var msg   = $('dp-delete-msg');
    if(!input || !btn){console.warn('[profil.js] Törlés elemek hiányoznak'); return;}

    input.addEventListener('input', function(){ btn.disabled = (this.value.trim() !== 'TÖRLÉS'); });
    btn.addEventListener('click', function(){
        if(input.value.trim() !== 'TÖRLÉS') return;
        if(!confirm('UTOLSÓ FIGYELMEZTETÉS!\n\nVéglegesen törlöd a fiókodat?\nNEM vonható vissza!')) return;
        btnLoad(btn, true);
        dpAjax('dp_delete_account', {confirm_text:'TÖRLÉS'}, function(r){
            btnLoad(btn, false);
            if(r.success){ showMsg(msg, r.data.message, true); setTimeout(function(){window.location.href=r.data.redirect||'/'},1500); }
            else showMsg(msg, r.data.message, false);
        });
    });
}

/* ═══════════════════════════════════════════════════
   GYŰJTEMÉNYEK – TELJES RENDSZER (null-safe, debug)
   ═══════════════════════════════════════════════════ */
var currentCollId   = null;
var currentCollName = '';
var modalMode       = 'create';
var modalEditId     = null;
var selectedColor   = COLORS[0];
var searchTimer     = null;

function initCollections(){
    console.log('[profil.js] initCollections START');

    var overlay     = $('dp-coll-modal-overlay');
    var modal       = $('dp-coll-modal');
    var nameInput   = $('dp-coll-name-input');
    var titleEl     = $('dp-coll-modal-title');
    var saveBtn     = $('dp-coll-modal-save');
    var cancelBtn   = $('dp-coll-modal-cancel');
    var closeBtn    = $('dp-coll-modal-close');
    var msgEl       = $('dp-coll-modal-msg');
    var colorPicker = $('dp-coll-color-picker');
    var newBtn      = $('dp-coll-new-btn');
    var grid        = $('dp-coll-grid');

    if(!overlay){console.error('[profil.js] dp-coll-modal-overlay HIÁNYZIK'); return;}
    if(!newBtn){console.error('[profil.js] dp-coll-new-btn HIÁNYZIK'); return;}

    console.log('[profil.js] Gyűjtemény DOM elemek OK');

    /* ── Szín picker ── */
    if(colorPicker){
        colorPicker.innerHTML = '';
        COLORS.forEach(function(c){
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'dp-coll-color-swatch' + (c===selectedColor?' is-selected':'');
            b.style.background = c;
            b.setAttribute('data-color', c);
            b.addEventListener('click', function(){
                colorPicker.querySelectorAll('.dp-coll-color-swatch').forEach(function(x){x.classList.remove('is-selected')});
                this.classList.add('is-selected');
                selectedColor = c;
            });
            colorPicker.appendChild(b);
        });
        console.log('[profil.js] Szín picker felépítve:', COLORS.length, 'szín');
    }

    /* ── Modal megnyitás ── */
    function openModal(mode, id, name, color){
        console.log('[profil.js] openModal:', mode, id, name, color);
        modalMode = mode;
        modalEditId = id || null;
        if(nameInput) nameInput.value = name || '';
        if(titleEl) titleEl.textContent = (mode==='edit' ? 'Gyűjtemény szerkesztése' : 'Új gyűjtemény');
        if(saveBtn) saveBtn.textContent = (mode==='edit' ? 'Mentés' : 'Létrehozás');

        /* Szín kijelölés */
        var targetColor = color || COLORS[0];
        selectedColor = targetColor;
        if(colorPicker){
            colorPicker.querySelectorAll('.dp-coll-color-swatch').forEach(function(s){
                s.classList.toggle('is-selected', s.getAttribute('data-color') === targetColor);
            });
        }

        if(msgEl){msgEl.textContent=''; msgEl.className='dp-profil-setting-msg';}

        overlay.classList.add('is-open');
        if(nameInput) setTimeout(function(){nameInput.focus()}, 200);
    }

    /* ── Modal bezárás ── */
    function closeModal(){
        console.log('[profil.js] closeModal');
        overlay.classList.remove('is-open');
    }

    /* ── Események: modal ── */
    newBtn.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        console.log('[profil.js] "Új gyűjtemény" klikk');
        openModal('create');
    });

    if(closeBtn) closeBtn.addEventListener('click', closeModal);
    if(cancelBtn) cancelBtn.addEventListener('click', closeModal);

    overlay.addEventListener('click', function(e){
        if(e.target === overlay) closeModal();
    });

    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape' && overlay.classList.contains('is-open')) closeModal();
    });

    /* ── Modal mentés ── */
    if(saveBtn) saveBtn.addEventListener('click', function(){
        var name = nameInput ? nameInput.value.trim() : '';
        console.log('[profil.js] Modal mentés:', modalMode, name, selectedColor);

        if(!name || name.length < 1){
            showMsg(msgEl, 'Adj meg egy nevet.', false);
            return;
        }

        btnLoad(saveBtn, true);

        if(modalMode === 'edit' && modalEditId){
            dpAjax('dp_update_collection', {collection_id:modalEditId, name:name, color:selectedColor}, function(r){
                btnLoad(saveBtn, false);
                if(r.success){
                    console.log('[profil.js] Szerkesztés sikeres');
                    closeModal();
                    var card = document.querySelector('.dp-coll-card[data-coll-id="'+modalEditId+'"]');
                    if(card){
                        card.setAttribute('data-coll-name', name);
                        card.setAttribute('data-coll-color', selectedColor);
                        var nameEl = card.querySelector('.dp-coll-card-name');
                        if(nameEl) nameEl.textContent = name;
                        var topEl = card.querySelector('.dp-coll-card-top');
                        if(topEl) topEl.style.background = selectedColor;
                    }
                } else {
                    console.error('[profil.js] Szerkesztés hiba:', r.data.message);
                    showMsg(msgEl, r.data.message, false);
                }
            });
        } else {
            dpAjax('dp_create_collection', {name:name, color:selectedColor}, function(r){
                btnLoad(saveBtn, false);
                if(r.success){
                    console.log('[profil.js] Létrehozás sikeres:', r.data.collection);
                    closeModal();
                    var c = r.data.collection;

                    /* Új kártya DOM felépítés */
                    var newCard = document.createElement('div');
                    newCard.className = 'dp-coll-card';
                    newCard.setAttribute('data-coll-id', c.id);
                    newCard.setAttribute('data-coll-name', c.name);
                    newCard.setAttribute('data-coll-color', c.color);
                    newCard.innerHTML = '<div class="dp-coll-card-top" style="background:'+c.color+'">'
                        +'<span class="dp-coll-card-icon">📁</span>'
                        +'<div class="dp-coll-card-actions">'
                        +'<button type="button" class="dp-coll-edit-btn" data-coll-id="'+c.id+'" title="Szerkesztés">✏️</button>'
                        +'<button type="button" class="dp-coll-delete-btn" data-coll-id="'+c.id+'" title="Törlés">🗑️</button>'
                        +'</div></div>'
                        +'<div class="dp-coll-card-body">'
                        +'<h3 class="dp-coll-card-name">'+c.name+'</h3>'
                        +'<span class="dp-coll-card-count">0 recept</span>'
                        +'</div>';

                    /* Beszúrás az "Új gyűjtemény" kártya ELÉ */
                    if(grid){
                        var ref = grid.querySelector('.dp-coll-card--new');
                        if(ref){
                            grid.insertBefore(newCard, ref);
                        } else {
                            grid.appendChild(newCard);
                        }
                        console.log('[profil.js] Kártya hozzáadva a DOM-hoz');
                    }

                    /* Event binding */
                    bindCardEvents(newCard);
                    updateCollCounts();
                } else {
                    console.error('[profil.js] Létrehozás hiba:', r.data.message);
                    showMsg(msgEl, r.data.message, false);
                }
            });
        }
    });

    /* Enter a név mezőben */
    if(nameInput) nameInput.addEventListener('keydown', function(e){
        if(e.key === 'Enter'){ e.preventDefault(); if(saveBtn) saveBtn.click(); }
    });

    /* ── Kártya event binding ── */
    function bindCardEvents(card){
        if(!card) return;

        var editBtn = card.querySelector('.dp-coll-edit-btn');
        if(editBtn) editBtn.addEventListener('click', function(e){
            e.stopPropagation();
            var id    = this.getAttribute('data-coll-id');
            var name  = card.getAttribute('data-coll-name');
            var color = card.getAttribute('data-coll-color');
            console.log('[profil.js] Szerkesztés klikk:', id, name, color);
            openModal('edit', id, name, color);
        });

        var delBtn = card.querySelector('.dp-coll-delete-btn');
        if(delBtn) delBtn.addEventListener('click', function(e){
            e.stopPropagation();
            var id   = this.getAttribute('data-coll-id');
            var name = card.getAttribute('data-coll-name');
            if(!confirm('Biztosan törlöd a "'+name+'" gyűjteményt?\nA receptek nem törlődnek.')) return;
            console.log('[profil.js] Törlés:', id);
            dpAjax('dp_delete_collection', {collection_id:id}, function(r){
                if(r.success){
                    card.style.opacity='0'; card.style.transform='scale(0.9)'; card.style.transition='all .3s ease';
                    setTimeout(function(){card.remove(); updateCollCounts();}, 300);
                }
            });
        });

        card.addEventListener('click', function(e){
            if(e.target.closest('.dp-coll-edit-btn') || e.target.closest('.dp-coll-delete-btn')) return;
            var cid  = this.getAttribute('data-coll-id');
            var name = this.getAttribute('data-coll-name');
            console.log('[profil.js] Gyűjtemény megnyitás:', cid, name);
            openDetail(cid, name);
        });
    }

    /* Meglévő kártyák binding */
    var existingCards = $$('.dp-coll-card:not(.dp-coll-card--new)');
    console.log('[profil.js] Meglévő kártyák:', existingCards.length);
    existingCards.forEach(bindCardEvents);

    /* ── Számok frissítés ── */
    function updateCollCounts(){
        var n = $$('.dp-coll-card:not(.dp-coll-card--new)').length;
        var c1 = $('dp-coll-count');       if(c1) c1.textContent = n;
        var c2 = $('dp-stat-colls');       if(c2) c2.textContent = n;
        var badges = $$('.dp-profil-tab-badge');
        if(badges.length) badges[0].textContent = n;
    }

    /* ══ RÉSZLETES NÉZET ══ */
    function openDetail(cid, name){
        console.log('[profil.js] openDetail:', cid, name);
        currentCollId = cid;
        currentCollName = name;

        var listView   = $('dp-coll-list-view');
        var detailView = $('dp-coll-detail-view');
        var nameEl     = $('dp-coll-detail-name');
        var detGrid    = $('dp-coll-detail-grid');
        var detEmpty   = $('dp-coll-detail-empty');
        var detCount   = $('dp-coll-detail-count');
        var searchIn   = $('dp-coll-search-input');
        var searchRes  = $('dp-coll-search-results');

        if(!listView || !detailView){console.error('[profil.js] Detail view elemek hiányoznak'); return;}

        listView.style.display = 'none';
        detailView.style.display = 'block';
        if(nameEl) nameEl.textContent = name;
        if(searchIn) searchIn.value = '';
        if(searchRes) searchRes.style.display = 'none';

        loadDetailRecipes(cid, detGrid, detEmpty, detCount);
    }

    /* Vissza gomb */
    var backBtn = $('dp-coll-back-btn');
    if(backBtn) backBtn.addEventListener('click', function(){
        var listView   = $('dp-coll-list-view');
        var detailView = $('dp-coll-detail-view');
        if(listView) listView.style.display = '';
        if(detailView) detailView.style.display = 'none';
        currentCollId = null;
        /* Oldal frissítés a számok miatt */
        location.reload();
    });

    /* ── Receptek betöltése ── */
    function loadDetailRecipes(cid, gridEl, emptyEl, countEl){
        if(!gridEl){console.error('[profil.js] detail grid hiányzik'); return;}

        gridEl.innerHTML = '<div style="padding:40px;text-align:center;color:#999">Betöltés…</div>';
        if(emptyEl) emptyEl.style.display = 'none';

        dpAjax('dp_get_collection_recipes', {collection_id:cid}, function(r){
            if(r.success){
                if(r.data.html){
                    gridEl.innerHTML = r.data.html;
                    gridEl.style.display = '';
                    if(emptyEl) emptyEl.style.display = 'none';
                    if(countEl) countEl.textContent = r.data.count || gridEl.querySelectorAll('.dp-coll-recipe-item').length;

                    gridEl.querySelectorAll('.dp-coll-remove-btn').forEach(function(btn){
                        btn.addEventListener('click', function(e){
                            e.preventDefault();
                            e.stopPropagation();
                            var rid  = this.getAttribute('data-recipe-id');
                            var card = this.closest('.dp-coll-recipe-item');
                            if(!confirm('Eltávolítod a receptet?')) return;

                            dpAjax('dp_toggle_collection_recipe', {collection_id:cid, recipe_id:rid}, function(r2){
                                if(r2.success && r2.data.action==='removed'){
                                    if(card){
                                        card.style.opacity='0'; card.style.transform='scale(0.9)'; card.style.transition='all .3s ease';
                                        setTimeout(function(){
                                            card.remove();
                                            var left = gridEl.querySelectorAll('.dp-coll-recipe-item').length;
                                            if(countEl) countEl.textContent = left;
                                            if(left===0){ gridEl.style.display='none'; if(emptyEl) emptyEl.style.display=''; }
                                        }, 300);
                                    }
                                }
                            });
                        });
                    });
                } else {
                    gridEl.innerHTML = '';
                    gridEl.style.display = 'none';
                    if(emptyEl) emptyEl.style.display = '';
                    if(countEl) countEl.textContent = '0';
                }
            } else {
                gridEl.innerHTML = '<div style="padding:40px;text-align:center;color:#dc2626">Hiba: '+(r.data.message||'')+'</div>';
            }
        });
    }

    /* ══ RECEPT KERESŐ ══ */
    var searchInput   = $('dp-coll-search-input');
    var searchResults = $('dp-coll-search-results');

    if(searchInput && searchResults){
        console.log('[profil.js] Recept kereső inicializálva');

        searchInput.addEventListener('input', function(){
            var q = this.value.trim();
            clearTimeout(searchTimer);

            if(q.length < 2){
                searchResults.style.display = 'none';
                searchResults.innerHTML = '';
                return;
            }

            searchTimer = setTimeout(function(){
                searchResults.innerHTML = '<div class="dp-coll-search-loading">Keresés…</div>';
                searchResults.style.display = 'block';

                dpAjax('dp_search_recipes', {search:q, collection_id:currentCollId||''}, function(r){
                    if(!r.success || !r.data.results || r.data.results.length === 0){
                        searchResults.innerHTML = '<div class="dp-coll-search-empty">Nincs találat: "'+q+'"</div>';
                        return;
                    }

                    var html = '';
                    r.data.results.forEach(function(item){
                        var inClass = item.is_in ? ' is-in' : '';
                        var inText  = item.is_in ? '✓ Hozzáadva' : '＋ Hozzáadás';
                        var thumb   = item.thumb
                            ? '<img src="'+item.thumb+'" alt="" class="dp-coll-sr-thumb">'
                            : '<span class="dp-coll-sr-nothumb">🍽</span>';
                        html += '<div class="dp-coll-sr-item'+inClass+'" data-recipe-id="'+item.id+'">';
                        html += thumb;
                        html += '<span class="dp-coll-sr-title">'+item.title+'</span>';
                        html += '<button type="button" class="dp-coll-sr-add'+inClass+'">'+inText+'</button>';
                        html += '</div>';
                    });
                    searchResults.innerHTML = html;

                    searchResults.querySelectorAll('.dp-coll-sr-item').forEach(function(row){
                        var addBtn = row.querySelector('.dp-coll-sr-add');
                        if(!addBtn) return;

                        addBtn.addEventListener('click', function(e){
                            e.stopPropagation();
                            if(!currentCollId) return;
                            var rid = row.getAttribute('data-recipe-id');
                            var self = this;
                            self.textContent = '…';
                            self.disabled = true;

                            dpAjax('dp_toggle_collection_recipe', {collection_id:currentCollId, recipe_id:rid}, function(r2){
                                self.disabled = false;
                                if(r2.success){
                                    if(r2.data.action === 'added'){
                                        row.classList.add('is-in');
                                        self.classList.add('is-in');
                                        self.textContent = '✓ Hozzáadva';
                                    } else {
                                        row.classList.remove('is-in');
                                        self.classList.remove('is-in');
                                        self.textContent = '＋ Hozzáadás';
                                    }
                                    /* Recept grid frissítés */
                                    var detGrid  = $('dp-coll-detail-grid');
                                    var detEmpty = $('dp-coll-detail-empty');
                                    var detCount = $('dp-coll-detail-count');
                                    loadDetailRecipes(currentCollId, detGrid, detEmpty, detCount);
                                } else {
                                    self.textContent = 'Hiba';
                                    setTimeout(function(){
                                        self.textContent = row.classList.contains('is-in') ? '✓ Hozzáadva' : '＋ Hozzáadás';
                                    }, 2000);
                                }
                            });
                        });
                    });
                });
            }, 350);
        });

        /* Kívül kattintás → bezárás */
        document.addEventListener('click', function(e){
            if(!e.target.closest('#dp-coll-search-wrap')){
                searchResults.style.display = 'none';
            }
        });

        /* Focus → ha van tartalom, mutasd */
        searchInput.addEventListener('focus', function(){
            if(searchResults.innerHTML && this.value.trim().length >= 2){
                searchResults.style.display = 'block';
            }
        });
    }

    console.log('[profil.js] initCollections KÉSZ ✅');
}

/* ═══ INIT ═══ */
function initAll(){
    console.log('[profil.js] ══ initAll START ══');
    try { initTabs();        console.log('[profil.js] ✅ initTabs OK');        } catch(e){ console.error('[profil.js] ❌ initTabs HIBA:', e); }
    try { initAvatar();      console.log('[profil.js] ✅ initAvatar OK');      } catch(e){ console.error('[profil.js] ❌ initAvatar HIBA:', e); }
    try { initBanner();      console.log('[profil.js] ✅ initBanner OK');      } catch(e){ console.error('[profil.js] ❌ initBanner HIBA:', e); }
    try { initName();        console.log('[profil.js] ✅ initName OK');        } catch(e){ console.error('[profil.js] ❌ initName HIBA:', e); }
    try { initPassword();    console.log('[profil.js] ✅ initPassword OK');    } catch(e){ console.error('[profil.js] ❌ initPassword HIBA:', e); }
    try { initDelete();      console.log('[profil.js] ✅ initDelete OK');      } catch(e){ console.error('[profil.js] ❌ initDelete HIBA:', e); }
    try { initCollections(); console.log('[profil.js] ✅ initCollections OK'); } catch(e){ console.error('[profil.js] ❌ initCollections HIBA:', e); }
    console.log('[profil.js] ══ initAll KÉSZ ══');
}

if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', initAll);
} else {
    initAll();
}

})();