/*
 * A2E Consent & Insights: the banner, the preferences window, tracking codes that wait for
 * consent, and the first-party visitor counter (only for visitors who allow analytics).
 */
(function () {
	'use strict';

	var C = window.A2EC;
	if (!C || !C.v) { return; }
	var tags = C.tags || {};
	var NAME = 'a2e_consent', VID = 'a2e_vid', SID = 'a2e_sid';
	var gpc = !!(C.gpc && navigator.globalPrivacyControl);
	var loaded = { analytics: false, marketing: false };
	var current = { a: false, m: false };

	/* Cookies ---------------------------------------------------------------------- */
	function getCookie(n) {
		var m = document.cookie.match(new RegExp('(?:^|; )' + n + '=([^;]*)'));
		return m ? decodeURIComponent(m[1]) : '';
	}
	function setCookie(n, v, days) {
		var s = n + '=' + encodeURIComponent(v) + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
		if (days !== undefined) { s += '; max-age=' + Math.round(days * 86400); }
		document.cookie = s;
	}
	function delCookie(n) {
		var parts = location.hostname.split('.');
		var domains = [''];
		for (var i = 0; i < parts.length - 1; i++) { domains.push('.' + parts.slice(i).join('.')); }
		domains.forEach(function (d) { document.cookie = n + '=; path=/; max-age=0' + (d ? '; domain=' + d : ''); });
	}
	function delMatching(re) {
		document.cookie.split('; ').forEach(function (c) {
			var n = c.split('=')[0];
			if (re.test(n)) { delCookie(n); }
		});
	}
	function rid() {
		var a = new Uint8Array(8);
		(window.crypto || window.msCrypto).getRandomValues(a);
		return Array.prototype.map.call(a, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
	}
	function read() {
		var p = getCookie(NAME).split('.');
		if (p.length !== 4 || !/^[a-f0-9]{16}$/.test(p[0]) || !/^[01]{2}$/.test(p[2])) { return null; }
		return { id: p[0], v: +p[1], a: p[2].charAt(0) === '1', m: p[2].charAt(1) === '1', t: +p[3] };
	}
	function post(path, data, beacon) {
		var body = new URLSearchParams(data);
		if (beacon && navigator.sendBeacon) {
			try { if (navigator.sendBeacon(C.rest + path, body)) { return Promise.resolve(null); } } catch (e) { /* fall back */ }
		}
		if (!window.fetch) { return Promise.resolve(null); }
		return fetch(C.rest + path, { method: 'POST', body: body, credentials: 'same-origin', keepalive: true })
			.then(function (r) { return r.ok ? r.json() : null; })
			.catch(function () { return null; });
	}

	/* Loading the codes ------------------------------------------------------------- */
	function addScript(src, onload) {
		var s = document.createElement('script');
		s.async = true;
		s.src = src;
		if (onload) { s.onload = onload; }
		document.head.appendChild(s);
	}
	function runTemplate(id) {
		var t = document.getElementById(id);
		if (!t || t.getAttribute('data-done') || !t.content) { return; }
		t.setAttribute('data-done', '1');
		var frag = t.content.cloneNode(true);
		Array.prototype.forEach.call(frag.querySelectorAll('script'), function (old) {
			var s = document.createElement('script');
			Array.prototype.forEach.call(old.attributes, function (at) { s.setAttribute(at.name, at.value); });
			if (old.src) { s.async = false; }
			s.text = old.text;
			old.parentNode.replaceChild(s, old);
		});
		document.body.appendChild(frag);
	}
	function runBlocked(cat) {
		// <script type="text/plain" data-a2ec="analytics|marketing"> anywhere on the page.
		Array.prototype.forEach.call(document.querySelectorAll('script[type="text/plain"][data-a2ec="' + cat + '"]'), function (old) {
			var s = document.createElement('script');
			Array.prototype.forEach.call(old.attributes, function (at) { if (at.name !== 'type') { s.setAttribute(at.name, at.value); } });
			s.text = old.text;
			old.parentNode.replaceChild(s, old);
		});
	}
	function consentUpdate(a, m) {
		var g = function (x) { return x ? 'granted' : 'denied'; };
		window.gtag('consent', 'update', { ad_storage: g(m), ad_user_data: g(m), ad_personalization: g(m), analytics_storage: g(a) });
		window.dataLayer.push({ event: 'a2e_consent_update', a2e_analytics: a, a2e_marketing: m });
	}
	var googleDone = { ga4: false, gtm: false };
	function loadGoogle(a, m) {
		if (C.mode === 'advanced') { return; } // already loaded in the page head
		if (tags.ga4 && a && !googleDone.ga4) {
			googleDone.ga4 = true;
			window.gtag('js', new Date());
			window.gtag('config', tags.ga4);
			addScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(tags.ga4));
		}
		if (tags.gtm && (a || m) && !googleDone.gtm) {
			googleDone.gtm = true;
			window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
			addScript('https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(tags.gtm));
		}
	}
	function loadAnalytics() {
		if (loaded.analytics) { return; }
		loaded.analytics = true;
		if (tags.clarity) {
			(function (c, l, a, r, i, t, y) {
				c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments); };
				t = l.createElement(r); t.async = 1; t.src = 'https://www.clarity.ms/tag/' + i;
				y = l.getElementsByTagName(r)[0]; y.parentNode.insertBefore(t, y);
			})(window, document, 'clarity', 'script', tags.clarity);
		}
		runTemplate('a2ec-code-analytics');
		runBlocked('analytics');
		if (C.insights) { Insights.start(); }
	}
	function loadMarketing() {
		if (loaded.marketing) { return; }
		loaded.marketing = true;
		if (tags.meta) {
			/* eslint-disable */
			!function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
			/* eslint-enable */
			window.fbq('consent', 'grant');
			window.fbq('init', tags.meta);
			window.fbq('track', 'PageView');
		}
		runTemplate('a2ec-code-marketing');
		runBlocked('marketing');
	}
	function apply(a, m) {
		current = { a: a, m: m };
		consentUpdate(a, m);
		loadGoogle(a, m);
		if (a) { loadAnalytics(); }
		if (m) { loadMarketing(); }
		if (window.clarity && tags.clarity && a) {
			window.clarity('consentv2', { ad_Storage: m ? 'granted' : 'denied', analytics_Storage: 'granted' });
		}
	}

	/* Saving a choice --------------------------------------------------------------- */
	var state = read();
	var valid = !!(state && state.v === C.v);

	function save(a, m, choice) {
		if (gpc) { m = false; }
		var id = state && state.id ? state.id : rid();
		var ts = Math.floor(Date.now() / 1000);
		setCookie(NAME, id + '.' + C.v + '.' + (a ? 1 : 0) + (m ? 1 : 0) + '.' + ts, C.days);
		state = { id: id, v: C.v, a: a, m: m, t: ts };
		valid = true;
		post('consent', { id: id, a: a ? 1 : 0, m: m ? 1 : 0, choice: choice, gpc: gpc ? 1 : 0, v: C.v, path: location.pathname }, true);

		// Remove cookies of anything not allowed (also left over from an earlier choice or banner version).
		var reload = false;
		if (!a) {
			[VID, SID, '_gid', '_gat', '_clck', '_clsk', 'CLID'].forEach(delCookie);
			delMatching(/^_ga(_|$)/);
			reload = loaded.analytics || (googleDone.ga4 && C.mode !== 'advanced');
		}
		if (!m) {
			['_fbp', '_fbc', '_gcl_au', '_gcl_aw', '_gcl_dc'].forEach(delCookie);
			reload = reload || loaded.marketing;
		}
		hideBanner();
		closePrefs();
		if (reload) {
			// Codes that already ran cannot be switched off, so reload the page without them.
			location.reload();
			return;
		}
		apply(a, m);
		toast(C.i18n.saved);
		showFloat();
	}

	/* Banner and preferences window ------------------------------------------------- */
	var banner, dialog, floatBtn, toastEl, opener;
	function hideBanner() { if (banner) { banner.hidden = true; document.documentElement.classList.remove('a2ec-open'); } }
	function showBanner() { if (banner) { banner.hidden = false; document.documentElement.classList.add('a2ec-open'); } }
	function showFloat() { if (floatBtn) { floatBtn.hidden = !C.float && footerLinked; } }
	function toast(msg) {
		if (!toastEl) { return; }
		toastEl.textContent = msg;
		toastEl.classList.add('is-on');
		clearTimeout(toast.t);
		toast.t = setTimeout(function () { toastEl.classList.remove('is-on'); }, 2600);
	}
	function syncSwitches() {
		var on = valid ? { analytics: state.a, marketing: state.m } : { analytics: false, marketing: false };
		Array.prototype.forEach.call(dialog.querySelectorAll('[data-a2ec-cat]'), function (box) {
			var cat = box.getAttribute('data-a2ec-cat');
			box.checked = !!on[cat];
			if (cat === 'marketing' && gpc) { box.checked = false; box.disabled = true; }
		});
		var idRow = dialog.querySelector('[data-a2ec-id]');
		if (idRow) {
			idRow.hidden = !(state && state.id);
			if (state && state.id) { idRow.querySelector('code').textContent = state.id; }
		}
	}
	function openPrefs(from) {
		if (!dialog) { return; }
		opener = from || document.activeElement;
		syncSwitches();
		if (typeof dialog.showModal === 'function') {
			if (!dialog.open) { dialog.showModal(); }
		} else {
			dialog.setAttribute('open', '');
		}
		var first = dialog.querySelector('[data-a2ec-cat]:not([disabled])');
		if (first) { first.focus(); }
	}
	function closePrefs() {
		if (!dialog || !dialog.hasAttribute('open')) { return; }
		if (typeof dialog.close === 'function') { dialog.close(); } else { dialog.removeAttribute('open'); }
	}

	var footerLinked = false;
	function addFooterLink() {
		if (!C.footer) { return; }
		var list = document.querySelector('.footer-legal');
		if (!list || list.querySelector('[data-a2ec="open"]')) { footerLinked = !!(list && list.querySelector('[data-a2ec="open"]')); return; }
		var li = document.createElement('li');
		var b = document.createElement('button');
		b.type = 'button';
		b.className = 'link-btn a2ec-footer-link';
		b.setAttribute('data-a2ec', 'open');
		b.textContent = C.i18n.settings;
		li.appendChild(b);
		var first = list.querySelector('li');
		if (first && first.nextSibling) { list.insertBefore(li, first.nextSibling); } else { list.appendChild(li); }
		footerLinked = true;
	}

	function onClick(e) {
		var t = e.target.closest ? e.target.closest('[data-a2ec], a[href="#cookie-settings"], .a2e-cookie-settings') : null;
		if (!t) { return; }
		var act = t.getAttribute('data-a2ec') || 'open';
		if (t.tagName === 'A') { e.preventDefault(); }
		if (act === 'accept') { save(true, true, 'accept_all'); }
		else if (act === 'reject') { save(false, false, 'reject_all'); }
		else if (act === 'choose' || act === 'open') { openPrefs(t); }
		else if (act === 'close') { closePrefs(); }
		else if (act === 'save') {
			var a = dialog.querySelector('[data-a2ec-cat="analytics"]');
			var m = dialog.querySelector('[data-a2ec-cat="marketing"]');
			save(!!(a && a.checked), !!(m && m.checked), 'custom');
		}
	}

	/* Conversions for Google and Meta ------------------------------------------------ */
	function linkKind(a) {
		var href = a.getAttribute('href') || '';
		if (/^whatsapp:/i.test(href) || /^https?:\/\/(wa\.me|api\.whatsapp\.com|web\.whatsapp\.com)\/(\d|send\?phone=\d|send\/?\?phone=)/i.test(a.href)) { return 'whatsapp'; }
		if (/^https?:\/\/(wa\.me\/\?|api\.whatsapp\.com\/send\?text)|facebook\.com\/sharer|twitter\.com\/intent|x\.com\/intent|linkedin\.com\/share/i.test(a.href)) { return 'share'; }
		if (/^tel:/i.test(href)) { return 'phone'; }
		if (/^mailto:/i.test(href)) { return 'email'; }
		if (/^https?:\/\/([a-z0-9-]+\.)*whop\.com/i.test(a.href)) { return 'whop'; }
		if (/^https?:/i.test(a.href) && a.host && a.host !== location.host) { return 'outbound'; }
		if (/\.(pdf|zip|csv|xlsx?|docx?)$/i.test(a.pathname || '')) { return 'download'; }
		return '';
	}
	function onLinkClick(e) {
		var a = e.target.closest ? e.target.closest('a[href]') : null;
		if (!a || !valid) { return; }
		var kind = linkKind(a);
		if (!kind) { return; }
		if (current.a && window.gtag && /whatsapp|phone|email|whop/.test(kind)) {
			window.gtag('event', kind === 'whop' ? 'begin_checkout' : 'contact_click', { method: kind, link_url: a.href });
		}
		if (current.m && window.fbq) {
			if (/whatsapp|phone|email/.test(kind)) { window.fbq('track', 'Contact'); }
			if (kind === 'whop') { window.fbq('track', 'InitiateCheckout'); }
		}
		if (current.a && C.insights) { Insights.click(kind, a); }
	}
	function watchForms() {
		// The theme and the health checker mark a sent form with "is-ok" / "is-done".
		if (!window.MutationObserver) { return; }
		Array.prototype.forEach.call(document.querySelectorAll('form'), function (form) {
			if (!form.querySelector('[name="a2e_form"], [name="action"][value="a2ehc_lead"]')) { return; }
			var done = false;
			new MutationObserver(function () {
				if (done) { return; }
				if (form.classList.contains('is-done') || form.querySelector('.form-result.is-ok, .a2ehc-result.is-ok')) {
					done = true;
					if (current.m && window.fbq) { window.fbq('track', 'Lead'); }
				}
			}).observe(form, { attributes: true, subtree: true, attributeFilter: ['class'] });
		});
	}

	/* First-party visitor insights (analytics consent only) ---------------------------- */
	var Insights = {
		hit: 0,
		started: false,
		shown: Date.now(),
		total: 0,
		visible: true,
		sent: 0,
		start: function () {
			if (this.started) { return; }
			this.started = true;
			var vid = getCookie(VID);
			if (!/^[a-f0-9]{16}$/.test(vid)) { vid = rid(); }
			setCookie(VID, vid, C.days);
			var sid = getCookie(SID), entry = 0;
			if (!/^[a-f0-9]{16}$/.test(sid)) { sid = rid(); entry = 1; }
			setCookie(SID, sid, 1 / 48);
			var q = new URLSearchParams(location.search);
			var data = {
				path: location.pathname,
				title: (document.title || '').slice(0, 190),
				ref: entry ? document.referrer : '',
				entry: entry,
				w: Math.round(window.screen && screen.width ? screen.width : window.innerWidth),
				t: ('ontouchstart' in window || navigator.maxTouchPoints > 0) ? 1 : 0
			};
			if (entry) {
				['utm_source', 'utm_medium', 'utm_campaign'].forEach(function (k) { if (q.get(k)) { data[k] = q.get(k).slice(0, 100); } });
				['gclid', 'fbclid', 'msclkid', 'gbraid', 'wbraid'].forEach(function (k) { if (q.has(k)) { data[k === 'gbraid' || k === 'wbraid' ? 'gclid' : k] = 1; } });
			}
			var self = this;
			this.visible = document.visibilityState !== 'hidden';
			this.shown = Date.now();
			post('hit', data, false).then(function (res) { if (res && res.id) { self.hit = res.id; } });
			document.addEventListener('visibilitychange', function () {
				if (document.visibilityState === 'hidden') {
					if (self.visible) { self.total += Date.now() - self.shown; self.visible = false; }
					self.ping();
				} else {
					self.shown = Date.now();
					self.visible = true;
					setCookie(SID, getCookie(SID) || sid, 1 / 48);
				}
			});
			window.addEventListener('pagehide', function () { self.ping(); });
		},
		ping: function () {
			if (!this.hit) { return; }
			var secs = Math.round((this.total + (this.visible ? Date.now() - this.shown : 0)) / 1000);
			secs = Math.min(secs, 1800);
			if (secs < 1 || secs === this.sent) { return; }
			this.sent = secs;
			post('ping', { id: this.hit, s: secs }, true);
		},
		click: function (kind, a) {
			if (!this.started) { return; }
			post('event', {
				kind: kind,
				url: (a.href || '').slice(0, 190),
				text: (a.getAttribute('aria-label') || a.innerText || a.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 80),
				path: location.pathname
			}, true);
		}
	};

	/* Start ------------------------------------------------------------------------- */
	function boot() {
		banner = document.getElementById('a2ec');
		dialog = document.getElementById('a2ec-prefs');
		floatBtn = document.querySelector('.a2ec-float');
		toastEl = document.querySelector('.a2ec-toast');
		if (gpc) {
			Array.prototype.forEach.call(document.querySelectorAll('[data-a2ec-gpc]'), function (n) { n.hidden = false; });
		}
		addFooterLink();
		document.addEventListener('click', onClick);
		document.addEventListener('click', onLinkClick, true);
		if (dialog) {
			dialog.addEventListener('click', function (e) { if (e.target === dialog) { closePrefs(); } });
			dialog.addEventListener('close', function () { if (opener && opener.focus && document.contains(opener)) { opener.focus(); } });
		}
		watchForms();
		if (valid) {
			apply(state.a, state.m && !gpc);
			showFloat();
		} else {
			showBanner();
			if (floatBtn) { floatBtn.hidden = true; }
		}
		window.A2EConsent = {
			open: function () { openPrefs(); },
			get: function () { return valid ? { analytics: state.a, marketing: state.m && !gpc, id: state.id } : null; }
		};
	}
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
