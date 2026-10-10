/* Shop filters — results update in place instead of loading a new page.
 *
 * The server stays the source of truth: a filter change fetches the same
 * URL a normal form submit would load, and swaps <main class="wc-main"> for
 * the fetched one (results, counts, the narrowed filter options, "Wis
 * filters"). history.pushState keeps that URL in the address bar, so
 * filtered pages stay shareable and Back/Forward work. Without JS (or if a
 * fetch fails) everything falls back to plain page loads.
 *
 * Handled: filter form (change + submit), Woo's sort dropdown, pagination,
 * "Wis filters". Brand links in the Merk block go to another archive and
 * stay normal navigation. Listeners are delegated on document because the
 * markup they live in gets replaced.
 */
(function () {
  'use strict';

  var MAIN = 'main.wc-main';
  var main = document.querySelector(MAIN);
  if (!main || !window.fetch || !window.DOMParser || !history.pushState) return;

  var pending = null;

  /** URL for a form, as a GET submit would build it. */
  function formUrl(form) {
    var url = new URL(form.getAttribute('action') || location.href, location.href);
    url.search = '';
    new FormData(form).forEach(function (value, key) {
      if (value !== '') url.searchParams.append(key, value);
    });
    // The filter form has no sort field: keep the current sort order.
    var orderby = new URL(location.href).searchParams.get('orderby');
    if (orderby && !form.querySelector('[name="orderby"]')) url.searchParams.set('orderby', orderby);
    return url.toString();
  }

  /** What had focus, so it can be found again in the new markup. */
  function focusKey() {
    var el = document.activeElement;
    if (!el || !main.contains(el)) return null;
    if (el.id) return '#' + CSS.escape(el.id);
    if (el.name) return '[name="' + CSS.escape(el.name) + '"]' + (el.type === 'checkbox' ? '[value="' + CSS.escape(el.value) + '"]' : '');
    return null;
  }

  function load(url, push) {
    if (pending) pending.abort();
    pending = new AbortController();
    var current = document.querySelector(MAIN);
    var panel = current.querySelector('.shop-filters');
    var panelScroll = panel ? panel.scrollTop : 0;
    var focus = focusKey();
    current.classList.add('is-loading');
    current.setAttribute('aria-busy', 'true');

    fetch(url, { signal: pending.signal, credentials: 'same-origin', headers: { 'X-Opus-Filter': '1' } })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.text().then(function (html) { return { html: html, url: res.url || url }; });
      })
      .then(function (r) {
        var doc = new DOMParser().parseFromString(r.html, 'text/html');
        var fresh = doc.querySelector(MAIN);
        if (!fresh) throw new Error('no ' + MAIN);
        current.replaceWith(fresh);
        main = fresh;
        document.title = doc.title;
        if (push) history.pushState({ opusFilter: true }, '', r.url);

        var toggle = document.querySelector('[data-opus-filters-open]');
        if (toggle) toggle.setAttribute('aria-expanded', document.body.classList.contains('opus-filters-open') ? 'true' : 'false');
        var newPanel = fresh.querySelector('.shop-filters');
        if (newPanel) newPanel.scrollTop = panelScroll;
        var again = focus && fresh.querySelector(focus);
        if (again) again.focus({ preventScroll: true });
        announce(fresh);
        pending = null;
      })
      .catch(function (err) {
        if (err.name === 'AbortError') return;
        location.href = url; // fall back to a normal page load
      });
  }

  /** Tell screen readers the result count changed. */
  function announce(scope) {
    var live = document.getElementById('opus-filter-status');
    if (!live) {
      live = document.createElement('p');
      live.id = 'opus-filter-status';
      live.className = 'screen-reader-text';
      live.setAttribute('aria-live', 'polite');
      document.body.appendChild(live);
    }
    var count = scope.querySelector('.brand-results__count, .woocommerce-result-count');
    live.textContent = count ? count.textContent.trim() : '';
  }

  function isFilterForm(form) {
    return form && (form.classList.contains('shop-filters__form') || form.classList.contains('woocommerce-ordering'));
  }

  // Capture phase: WooCommerce binds its own change handler on the sort form
  // that calls the native form.submit() (no submit event, so a full page
  // load). Handling it here first and stopping propagation keeps it in place.
  document.addEventListener('change', function (e) {
    var form = e.target.closest('form');
    if (!isFilterForm(form) || !main.contains(form)) return;
    e.stopPropagation();
    load(formUrl(form), true);
  }, true);

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!isFilterForm(form) || !main.contains(form)) return;
    e.preventDefault();
    load(formUrl(form), true);
  });

  document.addEventListener('click', function (e) {
    var a = e.target.closest('.woocommerce-pagination a, .filter-reset');
    if (!a || !main.contains(a) || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
    e.preventDefault();
    var paging = !!a.closest('.woocommerce-pagination');
    load(a.href, true);
    if (paging) {
      var top = main.querySelector('.brand-results, ul.products') || main;
      window.scrollTo({ top: top.getBoundingClientRect().top + window.scrollY - 120, behavior: 'smooth' });
    }
  });

  history.replaceState({ opusFilter: true }, '', location.href);
  window.addEventListener('popstate', function (e) {
    if (e.state && e.state.opusFilter) load(location.href, false);
  });

  // Filters panel open/close (the modal on phones and on shop/category pages).
  function setOpen(open) {
    document.body.classList.toggle('opus-filters-open', open);
    var toggle = document.querySelector('[data-opus-filters-open]');
    if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-opus-filters-open]')) setOpen(!document.body.classList.contains('opus-filters-open'));
    else if (e.target.closest('[data-opus-filters-close]')) setOpen(false);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setOpen(false);
  });
})();
