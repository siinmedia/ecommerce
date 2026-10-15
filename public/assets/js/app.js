// app.js — vanilla JS: copy buttons, destination autocomplete, shipping quote, promo check.
(function () {
  'use strict';

  // Copy to clipboard with feedback
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy]');
    if (!btn) return;
    var text = btn.getAttribute('data-copy');
    navigator.clipboard.writeText(text).then(function () {
      var orig = btn.textContent;
      btn.textContent = 'Disalin!';
      setTimeout(function () { btn.textContent = orig; }, 1200);
    });
  });

  // CSRF token helper for fetch POST
  function csrf() {
    var el = document.querySelector('input[name="_csrf"]');
    return el ? el.value : '';
  }

  // ---- Checkout page logic ----
  var destInput = document.getElementById('dest-search');
  if (destInput) {
    var destList = document.getElementById('dest-results');
    var destIdField = document.getElementById('destination_id');
    var destLabelField = document.getElementById('destination_label');
    var timer = null;
    destInput.addEventListener('input', function () {
      clearTimeout(timer);
      var q = destInput.value.trim();
      if (q.length < 3) { destList.innerHTML = ''; return; }
      timer = setTimeout(function () {
        fetch('/api/destinations?q=' + encodeURIComponent(q))
          .then(function (r) { return r.json(); })
          .then(function (j) {
            if (!j.ok) { destList.innerHTML = '<li class="p-2 text-red-600">' + escapeHtml(j.error) + '</li>'; return; }
            if (!j.data.length) { destList.innerHTML = '<li class="p-2 text-gray-500">Tidak ditemukan.</li>'; return; }
            destList.innerHTML = j.data.map(function (d) {
              return '<li><button type="button" data-id="' + d.id + '" data-label="' + escapeHtml(d.label) + '" class="w-full text-left p-2 hover:bg-gray-100">' + escapeHtml(d.label) + '</button></li>';
            }).join('');
          })
          .catch(function () { destList.innerHTML = '<li class="p-2 text-red-600">Gagal memuat. Coba lagi.</li>'; });
      }, 350);
    });
    destList.addEventListener('click', function (e) {
      var b = e.target.closest('button[data-id]');
      if (!b) return;
      destIdField.value = b.getAttribute('data-id');
      destLabelField.value = b.getAttribute('data-label');
      destInput.value = b.getAttribute('data-label');
      destList.innerHTML = '';
      loadRates();
    });
  }

  var courierSel = document.getElementById('courier');
  if (courierSel) courierSel.addEventListener('change', loadRates);

  function loadRates() {
    var destId = document.getElementById('destination_id');
    var box = document.getElementById('rates');
    if (!destId || !destId.value) return;
    box.innerHTML = '<p class="text-gray-500">Memuat ongkir...</p>';
    var courier = courierSel ? courierSel.value : '';
    fetch('/api/shipping-cost?destination=' + encodeURIComponent(destId.value) + '&courier=' + encodeURIComponent(courier))
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { box.innerHTML = '<p class="text-red-600">' + escapeHtml(j.error) + ' <button type="button" onclick="location.reload()" class="underline">Coba lagi</button></p>'; return; }
        document.getElementById('weight-info').textContent = 'Berat: ' + j.weight + ' gram';
        box.innerHTML = j.data.map(function (r, i) {
          return '<label class="card p-3 flex items-start gap-2 cursor-pointer">' +
            '<input type="radio" name="rate" value="' + r.courier + '|' + escapeHtml(r.service) + '|' + r.cost + '" data-courier="' + r.courier + '" data-service="' + escapeHtml(r.service) + '"' + (i === 0 ? ' checked' : '') + '>' +
            '<span><strong class="uppercase">' + r.courier + ' ' + escapeHtml(r.service) + '</strong><br>' +
            '<span class="text-sm text-gray-600">' + escapeHtml(r.description) + ' — Estimasi ' + escapeHtml(r.etd) + '</span><br>' +
            '<strong>Rp' + Number(r.cost).toLocaleString('id-ID') + '</strong></span></label>';
        }).join('');
        syncRate(); updateTotal();
        box.querySelectorAll('input[name="rate"]').forEach(function (el) {
          el.addEventListener('change', function () { syncRate(); updateTotal(); });
        });
      })
      .catch(function () { box.innerHTML = '<p class="text-red-600">Gagal memuat ongkir. Coba lagi.</p>'; });
  }

  function syncRate() {
    var sel = document.querySelector('input[name="rate"]:checked');
    if (!sel) return;
    document.getElementById('f_courier').value = sel.getAttribute('data-courier');
    document.getElementById('f_service').value = sel.getAttribute('data-service');
    document.getElementById('f_ship_cost').value = sel.value.split('|')[2];
  }

  function money(n) { return 'Rp' + Number(n).toLocaleString('id-ID'); }

  function updateTotal() {
    var sub = Number(document.getElementById('f_subtotal').value || 0);
    var ship = Number(document.getElementById('f_ship_cost').value || 0);
    var disc = Number(document.getElementById('f_discount').value || 0);
    document.getElementById('t-ship').textContent = money(ship);
    document.getElementById('t-disc').textContent = '-' + money(disc);
    document.getElementById('t-total').textContent = money(sub - disc + ship);
  }

  var promoBtn = document.getElementById('promo-btn');
  if (promoBtn) {
    promoBtn.addEventListener('click', function () {
      var code = document.getElementById('promo_code').value.trim();
      var sub = document.getElementById('f_subtotal').value;
      var fd = new FormData();
      fd.append('_csrf', csrf());
      fd.append('code', code);
      fd.append('subtotal', sub);
      fetch('/api/promo-check', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          var msg = document.getElementById('promo-msg');
          if (!j.ok) {
            msg.textContent = j.error; msg.className = 'text-sm text-red-600';
            document.getElementById('f_discount').value = 0;
          } else {
            msg.textContent = 'Promo ' + j.data.code + ': hemat ' + money(j.data.discount);
            msg.className = 'text-sm text-green-700';
            document.getElementById('f_discount').value = j.data.discount;
          }
          updateTotal();
        });
    });
  }

  // Generate idempotency key once per checkout render
  var idem = document.getElementById('idempotency_key');
  if (idem && !idem.value) {
    var arr = new Uint8Array(16);
    crypto.getRandomValues(arr);
    idem.value = Array.from(arr).map(function (b) { return b.toString(16).padStart(2, '0'); }).join('');
  }

  // Prevent double submit on place order
  var placeForm = document.getElementById('place-order');
  if (placeForm) {
    placeForm.addEventListener('submit', function () {
      syncRate();
      var btn = placeForm.querySelector('[type="submit"]');
      if (btn) { btn.disabled = true; btn.textContent = 'Memproses...'; }
    });
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
})();
