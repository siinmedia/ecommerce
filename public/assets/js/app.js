/* app.js — storefront vanilla JS (tanpa framework).
   Mencakup: bottom-sheet quick-view, bottom-sheet cart, varian+qty+harga live,
   beli-sekarang, salin clipboard, preview upload, konfirmasi ringan, checkout. */
(function () {
  'use strict';

  function $(id) { return document.getElementById(id); }
  function live(msg) {
    var el = $('live');
    if (el) el.textContent = msg;
  }
  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function money(n) { return 'Rp' + Number(n || 0).toLocaleString('id-ID'); }

  /* ---- Bottom sheet generik: scrim, Escape, fokus, scroll-lock ---- */
  var lastFocus = null;
  function openSheet(sheet) {
    if (!sheet || sheet.classList.contains('is-open')) return;
    lastFocus = document.activeElement;
    sheet.classList.add('is-open');
    sheet.setAttribute('aria-hidden', 'false');
    try { document.body.style.overflow = 'hidden'; } catch (e) {}
    var sc = sheet.querySelector('.sheet__scroll');
    if (sc) sc.scrollTop = 0;
    var close = sheet.querySelector('.sheet__close');
    if (close) close.focus();
  }
  function closeSheet(sheet) {
    if (!sheet || !sheet.classList.contains('is-open')) return;
    sheet.classList.remove('is-open');
    sheet.setAttribute('aria-hidden', 'true');
    var anyOpen = document.querySelector('.sheet.is-open');
    if (!anyOpen) {
      try { document.body.style.overflow = ''; } catch (e) {}
      if (lastFocus && lastFocus.focus) {
        try { lastFocus.focus(); } catch (e) {}
      }
      lastFocus = null;
    }
  }
  document.addEventListener('click', function (e) {
    var closer = e.target.closest('[data-close]');
    if (!closer) return;
    var sheet = closer.closest('.sheet');
    if (sheet) closeSheet(sheet);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.sheet.is-open').forEach(closeSheet);
  });

  /* ---- Quick-view: klik kartu -> bottom sheet ---- */
  var qv = $('qv-sheet');
  var qvState = { variants: [], qty: 1 };
  function qvSelected() {
    var form = $('qv-form');
    if (!form) return null;
    var r = form.querySelector('input[name="variant_id"]:checked');
    if (!r) return null;
    for (var i = 0; i < qvState.variants.length; i++) {
      if (String(qvState.variants[i].id) === r.value) return qvState.variants[i];
    }
    return null;
  }
  function qvStockText(v) {
    if (!v || v.stock <= 0) return 'Stok habis untuk paket ini.';
    if (v.stock <= 10) return 'Sisa ' + v.stock + ' untuk paket ini.';
    return 'Stok tersedia untuk paket ini.';
  }
  function qvRefresh() {
    var v = qvSelected();
    var max = v ? Math.min(99, Math.max(0, v.stock)) : 1;
    if (qvState.qty < 1) qvState.qty = 1;
    if (max > 0 && qvState.qty > max) qvState.qty = max;
    var qEl = $('qv-qty');
    var qvEl = $('qv-qty-val');
    if (qEl) qEl.value = String(qvState.qty);
    if (qvEl) qvEl.textContent = String(qvState.qty);
    var priceEl = $('qv-price');
    var totalEl = $('qv-total');
    var stockEl = $('qv-stock');
    var addBtn = $('qv-add');
    var unit = v ? v.price : 0;
    if (priceEl) priceEl.textContent = v ? money(unit) : '';
    if (totalEl) totalEl.textContent = money(unit * qvState.qty);
    if (stockEl) stockEl.textContent = qvStockText(v);
    if (addBtn) addBtn.disabled = !v || v.stock <= 0;
  }
  function qvOpen(card) {
    if (!qv || !card) return;
    var name = card.getAttribute('data-name') || '';
    var cat = card.getAttribute('data-cat') || '';
    var desc = card.getAttribute('data-desc') || '';
    var slug = card.getAttribute('data-slug') || '';
    var variants = [];
    try { variants = JSON.parse(card.getAttribute('data-variants') || '[]'); } catch (e) { variants = []; }
    qvState.variants = variants.filter(function (v) { return v && v.id; });
    qvState.qty = 1;

    var ph = $('qv-ph');
    if (ph) ph.textContent = (name.trim().charAt(0) || '?').toUpperCase();
    var catEl = $('qv-cat');
    if (catEl) catEl.textContent = cat;
    var titleEl = $('qv-title');
    if (titleEl) titleEl.textContent = name;
    var descEl = $('qv-desc');
    if (descEl) descEl.textContent = desc;
    var detail = $('qv-detail');
    if (detail && slug) detail.setAttribute('href', '/products/' + slug);

    var box = $('qv-variants');
    if (box) {
      if (!qvState.variants.length) {
        box.innerHTML = '<p class="muted" style="font-size:14px">Paket belum tersedia.</p>';
      } else {
        box.innerHTML = qvState.variants.map(function (v, i) {
          return '<label class="variant">' +
            '<input type="radio" name="variant_id" value="' + esc(String(v.id)) + '"' + (i === 0 ? ' checked' : '') + '>' +
            '<span>' + esc(v.name) + ' — ' + money(v.price) + '</span>' +
          '</label>';
        }).join('');
      }
    }
    qvRefresh();
    openSheet(qv);
    live(name + ' dibuka di pratinjau cepat.');
  }
  document.addEventListener('click', function (e) {
    if (e.target.closest('a, button')) return;
    var card = e.target.closest('.mcard');
    if (card) qvOpen(card);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var card = e.target.closest ? e.target.closest('.mcard') : null;
    if (!card) return;
    if (e.target.closest('a, button')) return;
    e.preventDefault();
    qvOpen(card);
  });
  if (qv) {
    qv.addEventListener('change', function (e) {
      if (e.target && e.target.name === 'variant_id') {
        qvState.qty = 1;
        qvRefresh();
      }
    });
    var qMinus = $('qv-minus');
    var qPlus = $('qv-plus');
    if (qMinus) qMinus.addEventListener('click', function () {
      qvState.qty = Math.max(1, qvState.qty - 1);
      qvRefresh();
    });
    if (qPlus) qPlus.addEventListener('click', function () {
      qvState.qty = Math.min(99, qvState.qty + 1);
      qvRefresh();
    });
  }

  /* ---- Cart sheet: dibuka dari cartbar mengambang ---- */
  var cartSheet = $('cart-sheet');
  var cartOpen = $('cart-open');
  var cartbar = $('cartbar');
  // Halaman produk punya buybar sticky sendiri: sembunyikan cartbar agar tidak tumpuk.
  if (cartbar && $('pd-form')) cartbar.hidden = true;
  if (cartOpen && cartSheet) {
    cartOpen.addEventListener('click', function () { openSheet(cartSheet); });
  }

  /* ---- Konfirmasi ringan ---- */
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (!el) return;
    if (!window.confirm(el.getAttribute('data-confirm'))) {
      e.preventDefault();
      e.stopPropagation();
    }
  }, true);

  /* ---- Salin ke clipboard (modern + fallback) ---- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy]');
    if (!btn || btn.disabled) return;
    var text = btn.getAttribute('data-copy') || '';
    function done(ok) {
      btn.classList.toggle('is-ok', ok);
      if (!btn.getAttribute('data-html')) btn.setAttribute('data-html', btn.innerHTML);
      btn.textContent = ok ? 'Disalin!' : 'Gagal';
      live(ok ? 'Berhasil disalin: ' + text : 'Gagal menyalin');
      setTimeout(function () {
        btn.innerHTML = btn.getAttribute('data-html') || '';
        btn.classList.remove('is-ok');
      }, 1400);
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () { done(true); }, function () { done(false); });
    } else {
      try {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        var ok = document.execCommand('copy');
        document.body.removeChild(ta);
        done(ok);
      } catch (err) { done(false); }
    }
  });

  /* ---- Halaman produk: varian + stepper + harga live + beli sekarang ---- */
  var pdForm = $('pd-form');
  if (pdForm) {
    var qty = $('qty');
    var priceEl = $('pd-price');
    var totalEl = $('pd-total');
    var stockEl = $('pd-stock');
    function selVariant() {
      return pdForm.querySelector('input[name="variant_id"]:checked');
    }
    function refresh() {
      var v = selVariant();
      var q = Math.max(1, Math.min(99, parseInt(qty ? qty.value : '1', 10) || 1));
      if (qty && String(q) !== qty.value) qty.value = String(q);
      if (!v) return;
      var unit = parseInt(v.getAttribute('data-price') || '0', 10);
      var stock = parseInt(v.getAttribute('data-stock') || '0', 10);
      if (priceEl) priceEl.textContent = money(unit);
      if (totalEl) totalEl.textContent = money(unit * q);
      if (stockEl) {
        stockEl.textContent = stock <= 0 ? 'Stok habis' : (stock <= 5 ? 'Sisa ' + stock : 'Stok tersedia');
      }
    }
    pdForm.querySelectorAll('input[name="variant_id"]').forEach(function (r) {
      r.addEventListener('change', refresh);
    });
    var minus = $('qty-minus');
    var plus = $('qty-plus');
    if (minus && qty) minus.addEventListener('click', function () {
      qty.value = String(Math.max(1, (parseInt(qty.value, 10) || 1) - 1));
      refresh();
    });
    if (plus && qty) plus.addEventListener('click', function () {
      qty.value = String(Math.min(99, (parseInt(qty.value, 10) || 1) + 1));
      refresh();
    });
    if (qty) qty.addEventListener('input', refresh);
    refresh();

    var buyNow = $('buy-now');
    if (buyNow) {
      buyNow.addEventListener('click', function () {
        var v = selVariant();
        if (!v || !qty) return;
        buyNow.disabled = true;
        var orig = buyNow.textContent;
        buyNow.textContent = 'Memproses...';
        var fd = new FormData(pdForm);
        fetch('/cart/add', { method: 'POST', body: fd, redirect: 'follow' })
          .then(function (r) {
            // Sukses add -> controller redirect ke /cart; gagal -> kembali ke produk.
            var url = (r.url || '');
            if (url.indexOf('/cart') !== -1 || r.redirected) {
              window.location.href = '/checkout';
            } else {
              window.location.reload();
            }
          })
          .catch(function () { pdForm.submit(); })
          .finally(function () {
            buyNow.disabled = false;
            buyNow.textContent = orig;
          });
      });
    }
  }

  /* ---- Preview upload bukti ---- */
  var proof = $('proof');
  if (proof) {
    proof.addEventListener('change', function () {
      var box = $('fileprev');
      var img = $('fileprev-img');
      var name = $('fileprev-name');
      var size = $('fileprev-size');
      var fill = $('fileprev-fill');
      var f = proof.files && proof.files[0];
      if (!f || !box) return;
      box.classList.add('is-show');
      if (name) name.textContent = f.name;
      if (size) size.textContent = (f.size / 1024).toFixed(0) + ' KB dari maks 3072 KB' + (f.size > 3 * 1024 * 1024 ? ' — MELEBIHI BATAS' : '');
      if (fill) {
        fill.style.width = '8%';
        var p = 8;
        var t = setInterval(function () {
          p = Math.min(100, p + 22);
          fill.style.width = p + '%';
          if (p >= 100) clearInterval(t);
        }, 120);
      }
      if (img && f.type.indexOf('image/') === 0) {
        var rd = new FileReader();
        rd.onload = function () {
          img.src = String(rd.result || '');
          img.hidden = false;
        };
        rd.readAsDataURL(f);
      } else if (img) {
        img.hidden = true;
        img.removeAttribute('src');
      }
      live('File terpilih: ' + f.name);
    });
  }

  /* ---- Checkout ---- */
  function csrf() {
    var el = document.querySelector('input[name="_csrf"]');
    return el ? el.value : '';
  }
  var destInput = $('dest-search');
  if (destInput) {
    var destList = $('dest-results');
    var destIdField = $('destination_id');
    var destLabelField = $('destination_label');
    var timer = null;
    destInput.addEventListener('input', function () {
      if (timer) clearTimeout(timer);
      var q = destInput.value.trim();
      if (!destList) return;
      if (q.length < 3) { destList.innerHTML = ''; return; }
      timer = setTimeout(function () {
        fetch('/api/destinations?q=' + encodeURIComponent(q))
          .then(function (r) { return r.json(); })
          .then(function (j) {
            if (!j.ok) { destList.innerHTML = '<li><button type="button" disabled>' + esc(j.error || 'Gagal') + '</button></li>'; return; }
            if (!j.data.length) { destList.innerHTML = '<li><button type="button" disabled>Tidak ditemukan.</button></li>'; return; }
            destList.innerHTML = j.data.map(function (d) {
              return '<li><button type="button" data-id="' + esc(d.id) + '" data-label="' + esc(d.label) + '">' + esc(d.label) + '</button></li>';
            }).join('');
            destInput.setAttribute('aria-expanded', 'true');
          })
          .catch(function () { destList.innerHTML = '<li><button type="button" disabled>Gagal memuat. Coba lagi.</button></li>'; });
      }, 350);
    });
    if (destList) {
      destList.addEventListener('click', function (e) {
        var b = e.target.closest('button[data-id]');
        if (!b) return;
        if (destIdField) destIdField.value = b.getAttribute('data-id') || '';
        if (destLabelField) destLabelField.value = b.getAttribute('data-label') || '';
        destInput.value = b.getAttribute('data-label') || '';
        destInput.setAttribute('aria-expanded', 'false');
        destList.innerHTML = '';
        loadRates();
      });
    }
  }

  var courierSel = $('courier');
  if (courierSel) courierSel.addEventListener('change', loadRates);

  function loadRates() {
    var destId = $('destination_id');
    var box = $('rates');
    if (!destId || !destId.value || !box) return;
    box.innerHTML = '<p class="muted" style="font-size:14.5px">Memuat ongkir...</p>';
    var courier = courierSel ? courierSel.value : '';
    fetch('/api/shipping-cost?destination=' + encodeURIComponent(destId.value) + '&courier=' + encodeURIComponent(courier))
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { box.innerHTML = '<p class="field__err">' + esc(j.error || 'Gagal') + '</p>'; return; }
        var wi = $('weight-info');
        if (wi) wi.textContent = 'Berat: ' + j.weight + ' gram';
        box.innerHTML = j.data.map(function (r, i) {
          return '<label class="pick"><span class="row" style="align-items:flex-start">' +
            '<input type="radio" name="rate" value="' + esc(r.courier) + '|' + esc(r.service) + '|' + r.cost + '" data-courier="' + esc(r.courier) + '" data-service="' + esc(r.service) + '"' + (i === 0 ? ' checked' : '') + '>' +
            '<span><strong style="text-transform:uppercase">' + esc(r.courier) + ' ' + esc(r.service) + '</strong><br>' +
            '<span class="muted" style="font-size:13.5px">' + esc(r.description) + ' — Estimasi ' + esc(r.etd) + '</span><br>' +
            '<strong>' + money(r.cost) + '</strong></span></span></label>';
        }).join('');
        syncRate();
        updateTotal();
        box.querySelectorAll('input[name="rate"]').forEach(function (el) {
          el.addEventListener('change', function () { syncRate(); updateTotal(); });
        });
      })
      .catch(function () { box.innerHTML = '<p class="field__err">Gagal memuat ongkir. Coba lagi.</p>'; });
  }

  function syncRate() {
    var sel = document.querySelector('input[name="rate"]:checked');
    if (!sel) return;
    var c = $('f_courier');
    var s = $('f_service');
    var sc = $('f_ship_cost');
    if (c) c.value = sel.getAttribute('data-courier') || '';
    if (s) s.value = sel.getAttribute('data-service') || '';
    if (sc) sc.value = (sel.value || '').split('|')[2] || '0';
  }

  function updateTotal() {
    var sub = Number(($('f_subtotal') || {}).value || 0);
    var ship = Number(($('f_ship_cost') || {}).value || 0);
    var disc = Number(($('f_discount') || {}).value || 0);
    var ts = $('t-ship');
    var td = $('t-disc');
    var tt = $('t-total');
    if (ts) ts.textContent = money(ship);
    if (td) td.textContent = '-' + money(disc);
    if (tt) tt.textContent = money(sub - disc + ship);
  }

  var promoBtn = $('promo-btn');
  if (promoBtn) {
    promoBtn.addEventListener('click', function () {
      var codeEl = $('promo_code');
      var subEl = $('f_subtotal');
      var code = codeEl ? codeEl.value.trim() : '';
      if (!code) return;
      promoBtn.disabled = true;
      var fd = new FormData();
      fd.append('_csrf', csrf());
      fd.append('code', code);
      fd.append('subtotal', subEl ? subEl.value : '0');
      fetch('/api/promo-check', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          var msg = $('promo-msg');
          var discEl = $('f_discount');
          if (!j.ok) {
            if (msg) { msg.textContent = j.error || 'Promo tidak valid.'; msg.style.color = 'var(--danger)'; }
            if (discEl) discEl.value = '0';
          } else {
            if (msg) { msg.textContent = 'Promo ' + j.data.code + ': hemat ' + money(j.data.discount); msg.style.color = 'var(--success)'; }
            if (discEl) discEl.value = String(j.data.discount);
          }
          updateTotal();
        })
        .catch(function () {
          var msg = $('promo-msg');
          if (msg) { msg.textContent = 'Gagal memeriksa promo.'; msg.style.color = 'var(--danger)'; }
        })
        .finally(function () { promoBtn.disabled = false; });
    });
  }

  /* Kunci idempotensi sekali per render checkout */
  var idem = $('idempotency_key');
  if (idem && !idem.value) {
    try {
      var arr = new Uint8Array(16);
      crypto.getRandomValues(arr);
      idem.value = Array.from(arr).map(function (b) { return b.toString(16).padStart(2, '0'); }).join('');
    } catch (e) {
      idem.value = 'idem-' + Date.now() + '-' + Math.floor(Math.random() * 1e9);
    }
  }

  /* Sambungkan pilihan alamat/bank ke field tersembunyi order */
  document.querySelectorAll('input[name="addr_pick"]').forEach(function (el) {
    el.addEventListener('change', function () {
      var fa = $('f_address');
      var di = $('destination_id');
      var fl = $('f_dest_label');
      if (fa) fa.value = el.value;
      if (di) di.value = el.getAttribute('data-dest') || '';
      if (fl) fl.value = el.getAttribute('data-label') || '';
      loadRates();
    });
  });
  document.querySelectorAll('input[name="bank_pick"]').forEach(function (el) {
    el.addEventListener('change', function () {
      var fb = $('f_bank');
      if (fb) fb.value = el.value;
    });
  });
  (function initCheckout() {
    var a = document.querySelector('input[name="addr_pick"]:checked');
    if (a) {
      var fa = $('f_address');
      var di = $('destination_id');
      var fl = $('f_dest_label');
      if (fa) fa.value = a.value;
      if (di) di.value = a.getAttribute('data-dest') || '';
      if (fl) fl.value = a.getAttribute('data-label') || '';
      loadRates();
    }
    var b = document.querySelector('input[name="bank_pick"]');
    if (b) {
      b.checked = true;
      var fb = $('f_bank');
      if (fb) fb.value = b.value;
    }
    var pc = $('promo_code');
    var fp = $('f_promo');
    if (pc && fp) pc.addEventListener('input', function (e) { fp.value = e.target.value; });
  })();

  /* Cegah double submit buat pesanan */
  var placeForm = $('place-order');
  if (placeForm) {
    placeForm.addEventListener('submit', function () {
      syncRate();
      var btn = placeForm.querySelector('[type="submit"]');
      if (btn) { btn.disabled = true; btn.textContent = 'Memproses...'; }
    });
  }
})();
