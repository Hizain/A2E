/**
 * A2E Prime — front-end interactions (no dependencies).
 */
(function () {
	'use strict';

	var doc = document;
	var root = doc.documentElement;
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var A = window.a2eApp || {};

	function $(s, c) { return (c || doc).querySelector(s); }
	function $$(s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); }
	function num(v) { var n = parseFloat(String(v).replace(/[^0-9.\-]/g, '')); return isFinite(n) ? n : 0; }
	function money(v, dp) {
		dp = dp === undefined ? 2 : dp;
		var neg = v < 0;
		return (neg ? '−$' : '$') + Math.abs(v).toLocaleString('en-US', { minimumFractionDigits: dp, maximumFractionDigits: dp });
	}
	function storage(key, value) {
		try {
			if (value === undefined) { return localStorage.getItem(key); }
			if (value === null) { localStorage.removeItem(key); } else { localStorage.setItem(key, value); }
		} catch (e) { return null; }
		return null;
	}

	/* Theme toggle ------------------------------------------------------------ */
	$$('[data-theme-toggle]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
			root.setAttribute('data-theme', next);
			storage('a2e-theme', next);
		});
	});
	if (window.matchMedia) {
		var mq = window.matchMedia('(prefers-color-scheme: dark)');
		var onChange = function (e) { if (!storage('a2e-theme')) { root.setAttribute('data-theme', e.matches ? 'dark' : 'light'); } };
		if (mq.addEventListener) { mq.addEventListener('change', onChange); } else if (mq.addListener) { mq.addListener(onChange); }
	}

	/* Header: scrolled state, back-to-top, reading progress ------------------- */
	var header = $('[data-header]');
	var toTop = $('[data-to-top]');
	var progress = $('.read-progress');
	var article = $('[data-article]');
	var ticking = false;
	function onScroll() {
		var y = window.scrollY || window.pageYOffset;
		if (header) { header.classList.toggle('is-scrolled', y > 8); }
		if (toTop) { toTop.classList.toggle('is-visible', y > 900); }
		if (progress && article) {
			var r = article.getBoundingClientRect();
			var total = r.height - window.innerHeight * 0.6;
			var p = Math.min(1, Math.max(0, -r.top / Math.max(1, total)));
			progress.style.setProperty('--p', p.toFixed(4));
		}
		ticking = false;
	}
	window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
	onScroll();
	if (toTop) { toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' }); }); }

	/* Mega menus -------------------------------------------------------------- */
	var megas = $$('[data-mega]');
	var closeTimer;
	function closeAll(except) {
		megas.forEach(function (m) {
			if (m !== except) {
				m.classList.remove('is-open');
				var b = $('.nav__link', m);
				if (b) { b.setAttribute('aria-expanded', 'false'); }
			}
		});
	}
	function open(m) {
		closeAll(m);
		m.classList.add('is-open');
		$('.nav__link', m).setAttribute('aria-expanded', 'true');
	}
	megas.forEach(function (m) {
		var btn = $('.nav__link', m);
		m.addEventListener('mouseenter', function () { if (window.matchMedia('(hover: hover)').matches) { clearTimeout(closeTimer); open(m); } });
		m.addEventListener('mouseleave', function () { if (window.matchMedia('(hover: hover)').matches) { closeTimer = setTimeout(function () { m.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); }, 160); } });
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			if (m.classList.contains('is-open')) { m.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); } else { open(m); }
		});
		m.addEventListener('focusout', function (e) { if (!m.contains(e.relatedTarget)) { m.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); } });
	});
	doc.addEventListener('click', function (e) { if (!e.target.closest('[data-mega]')) { closeAll(); } });
	doc.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeAll(); closeMobile(); } });

	/* Mobile navigation ------------------------------------------------------- */
	var burger = $('[data-burger]');
	var mnav = $('[data-mnav]');
	function closeMobile() {
		if (!mnav || mnav.hidden) { return; }
		mnav.hidden = true;
		burger.setAttribute('aria-expanded', 'false');
		doc.body.classList.remove('nav-open');
	}
	if (burger && mnav) {
		burger.addEventListener('click', function () {
			var openNow = mnav.hidden;
			mnav.hidden = !openNow;
			burger.setAttribute('aria-expanded', openNow ? 'true' : 'false');
			doc.body.classList.toggle('nav-open', openNow);
		});
		$$('a', mnav).forEach(function (a) { a.addEventListener('click', closeMobile); });
		window.addEventListener('resize', function () { if (window.innerWidth > 1120) { closeMobile(); } });
	}

	/* Reveal on scroll + counters --------------------------------------------- */
	function countUp(el) {
		var target = num(el.getAttribute('data-count'));
		var raw = el.getAttribute('data-count');
		var decimals = raw.indexOf('.') > -1 ? raw.split('.')[1].length : 0;
		var comma = raw.indexOf(',') > -1 || target >= 1000;
		if (reduce || !target) { return; }
		var start = null;
		var dur = 1600;
		function fmt(v) { return comma ? Math.round(v).toLocaleString('en-US') : v.toFixed(decimals); }
		function step(ts) {
			if (!start) { start = ts; }
			var t = Math.min(1, (ts - start) / dur);
			var e = 1 - Math.pow(1 - t, 4);
			el.textContent = fmt(target * e);
			if (t < 1) { requestAnimationFrame(step); } else { el.textContent = fmt(target); }
		}
		el.textContent = fmt(0);
		requestAnimationFrame(step);
	}
	if ('IntersectionObserver' in window) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (!en.isIntersecting) { return; }
				en.target.classList.add('is-in');
				$$('[data-count]', en.target).forEach(countUp);
				if (en.target.hasAttribute('data-count')) { countUp(en.target); }
				io.unobserve(en.target);
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
		$$('[data-reveal], .dash__kpis').forEach(function (el) { io.observe(el); });
	} else {
		$$('[data-reveal]').forEach(function (el) { el.classList.add('is-in'); });
	}

	/* Spotlight hover on service cards ---------------------------------------- */
	$$('.svc-card').forEach(function (card) {
		card.addEventListener('pointermove', function (e) {
			var r = card.getBoundingClientRect();
			card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
			card.style.setProperty('--my', (e.clientY - r.top) + 'px');
		});
	});

	/* Hero dashboard ------------------------------------------------------------ */
	var hv = $('[data-hv]');
	if (hv) {
		var dash = $('[data-tilt]', hv);
		if (dash && !reduce && window.matchMedia('(hover: hover)').matches) {
			hv.addEventListener('pointermove', function (e) {
				var r = hv.getBoundingClientRect();
				var x = (e.clientX - r.left) / r.width - 0.5;
				var y = (e.clientY - r.top) / r.height - 0.5;
				dash.style.setProperty('--ry', (-9 + x * 10).toFixed(2) + 'deg');
				dash.style.setProperty('--rx', (6 - y * 8).toFixed(2) + 'deg');
			});
			hv.addEventListener('pointerleave', function () { dash.style.removeProperty('--ry'); dash.style.removeProperty('--rx'); });
		}

		// Chart hover tooltip.
		var chartBox = $('[data-chart]', hv);
		if (chartBox) {
			var data = [];
			try { data = JSON.parse(chartBox.getAttribute('data-chart')); } catch (err) { data = []; }
			var svg = $('svg', chartBox);
			var tip = $('[data-tip]', chartBox);
			var cursor = $('.chart__cursor', chartBox);
			var dot = $('[data-dot]', chartBox);
			var wrap = $('.chart__wrap', chartBox);
			var vb = svg.viewBox.baseVal;
			chartBox.addEventListener('pointermove', function (e) {
				if (!data.length) { return; }
				var r = wrap.getBoundingClientRect();
				var x = (e.clientX - r.left) / r.width * vb.width;
				var best = data[0];
				data.forEach(function (d) { if (Math.abs(d.x - x) < Math.abs(best.x - x)) { best = d; } });
				var idx = data.indexOf(best) + 1;
				cursor.setAttribute('x1', best.x); cursor.setAttribute('x2', best.x);
				dot.style.left = (best.x / vb.width * 100) + '%'; dot.style.top = (best.y / vb.height * 100) + '%';
				tip.hidden = false;
				tip.innerHTML = 'Week ' + idx + ' · <b>' + money(best.s, 0) + '</b> sales · <b>' + money(best.p, 0) + '</b> profit';
				var sr = chartBox.getBoundingClientRect();
				tip.style.left = (r.left - sr.left + best.x / vb.width * r.width) + 'px';
				tip.style.top = (r.top - sr.top + best.y / vb.height * r.height) + 'px';
				chartBox.classList.add('is-hover');
			});
			chartBox.addEventListener('pointerleave', function () {
				tip.hidden = true;
				chartBox.classList.remove('is-hover');
				var last = data[data.length - 1];
				if (last) { dot.style.left = (last.x / vb.width * 100) + '%'; dot.style.top = (last.y / vb.height * 100) + '%'; }
			});
		}

		// Donut ↔ legend highlight.
		var donut = $('.donut', hv);
		$$('[data-seg]', hv).forEach(function (el) {
			var i = el.getAttribute('data-seg');
			el.addEventListener('pointerenter', function () {
				donut.classList.add('has-hl');
				$$('[data-seg="' + i + '"]', hv).forEach(function (x) { x.classList.add('is-hl'); });
			});
			el.addEventListener('pointerleave', function () {
				donut.classList.remove('has-hl');
				$$('[data-seg].is-hl', hv).forEach(function (x) { x.classList.remove('is-hl'); });
			});
		});

		// Rotating order feed.
		var feed = $('[data-feed]', hv);
		if (feed && !reduce) {
			setInterval(function () {
				if (doc.hidden) { return; }
				var rows = $$('.feed__row', feed);
				var on = rows.filter(function (r) { return r.classList.contains('is-on'); });
				var next = rows.filter(function (r) { return !r.classList.contains('is-on'); })[0];
				if (!next || !on.length) { return; }
				on[on.length - 1].classList.remove('is-on', 'is-new');
				feed.appendChild(on[on.length - 1]);
				next.classList.add('is-on', 'is-new');
				feed.insertBefore(next, feed.firstChild);
			}, 2800);
		}
	}

	/* Compare toggle ------------------------------------------------------------ */
	$$('[data-compare]').forEach(function (box) {
		var btns = $$('[data-side]', box);
		function set(side) {
			box.classList.toggle('is-alone', side === 'alone');
			btns.forEach(function (b) {
				var on = b.getAttribute('data-side') === side;
				b.classList.toggle('is-active', on);
				b.setAttribute('aria-selected', on ? 'true' : 'false');
			});
		}
		btns.forEach(function (b) { b.addEventListener('click', function () { set(b.getAttribute('data-side')); box.dataset.touched = '1'; }); });
		// Gentle auto-demo until the visitor interacts.
		if (!reduce && 'IntersectionObserver' in window) {
			var demo = new IntersectionObserver(function (en) {
				if (!en[0].isIntersecting) { return; }
				demo.disconnect();
				setTimeout(function () { if (!box.dataset.touched) { set('alone'); } }, 900);
				setTimeout(function () { if (!box.dataset.touched) { set('a2e'); } }, 3200);
			}, { threshold: 0.5 });
			demo.observe(box);
		}
	});

	/* Range fill helper ---------------------------------------------------------- */
	function paintRange(input) {
		var min = num(input.min), max = num(input.max), v = num(input.value);
		input.style.setProperty('--fill', ((v - min) / Math.max(1e-9, max - min) * 100).toFixed(2) + '%');
	}

	/* Profit simulator ----------------------------------------------------------- */
	var sim = $('[data-sim]');
	if (sim) {
		var chart = $('[data-sim-chart]', sim);
		var bars = [];
		for (var b = 0; b < 12; b++) {
			var bar = doc.createElement('div');
			bar.className = 'sim__bar';
			bar.innerHTML = '<i></i><span></span>';
			chart.appendChild(bar);
			bars.push(bar);
		}
		var plans = A.plans || [];
		function findPlan(add) {
			var want = add >= 500 ? /scale/i : (add > 0 ? /growth/i : /100|starter|research/i);
			for (var i = 0; i < plans.length; i++) { if (want.test(plans[i].name)) { return plans[i]; } }
			return plans[add >= 500 ? Math.min(2, plans.length - 1) : (add > 0 ? Math.min(1, plans.length - 1) : 0)] || null;
		}
		function runSim() {
			var listings = num($('[data-sim-in="listings"]', sim).value);
			var rate = num($('[data-sim-in="rate"]', sim).value);
			var profit = num($('[data-sim-in="profit"]', sim).value);
			var addInput = $('[data-sim-in="add"]:checked', sim);
			var add = addInput ? num(addInput.value) : 0;
			$$('input[type="range"]', sim).forEach(paintRange);
			$('[data-sim-out="listings"]', sim).textContent = listings.toLocaleString('en-US');
			$('[data-sim-out="rate"]', sim).textContent = rate.toFixed(2);
			$('[data-sim-out="profit"]', sim).textContent = money(profit);

			var months = [];
			var total = 0;
			for (var m = 1; m <= 12; m++) {
				var active = listings + add * m;
				var p = active * rate * profit;
				months.push(p);
				total += p;
			}
			var now = listings * rate * profit;
			var later = months[11];
			var max = Math.max(later, now, 1);
			bars.forEach(function (bar, i) {
				bar.style.setProperty('--h', (months[i] / max * 100).toFixed(2) + '%');
				bar.querySelector('i').style.setProperty('--b', (months[i] ? Math.min(100, now / months[i] * 100) : 0).toFixed(2) + '%');
				bar.querySelector('span').textContent = money(months[i], 0);
			});
			$('[data-sim-res="now"]', sim).textContent = money(now, 0);
			$('[data-sim-res="later"]', sim).textContent = money(later, 0);
			$('[data-sim-res="orders"]', sim).textContent = Math.round((listings + add * 12) * rate).toLocaleString('en-US');
			$('[data-sim-res="year"]', sim).textContent = money(total, 0);

			var plan = findPlan(add);
			if (plan) {
				$('[data-sim-plan-name]', sim).textContent = plan.name;
				$('[data-sim-plan-price]', sim).textContent = plan.price + ' ' + (plan.period || '');
				$('[data-sim-plan-link]', sim).setAttribute('href', plan.url);
			}
		}
		$$('[data-sim-in]', sim).forEach(function (el) { el.addEventListener('input', runSim); el.addEventListener('change', runSim); });
		runSim();
	}

	/* Shared eBay fee model (mirrors tools.js) ------------------------------------ */
	function ebayFee(price, cost) {
		var F = A.fees || {};
		var cat = (F.categories && F.categories[0]) || { rate: 13.6 };
		var total = price * 1.07;
		var fvf = num(cat.rate) / 100 * Math.min(total, num(F.threshold) || 7500) + num(F.rateAbove) / 100 * Math.max(0, total - (num(F.threshold) || 7500));
		var order = price <= 0 ? 0 : (total <= (num(F.orderLimit) || 10) ? num(F.orderSmall) : num(F.orderLarge));
		var fees = fvf + order;
		return { fees: fees, profit: price - fees - cost };
	}
	var qf = $('[data-quickfee]');
	if (qf) {
		var runQf = function () {
			var price = num($('[data-qf="price"]', qf).value);
			var cost = num($('[data-qf="cost"]', qf).value);
			var r = ebayFee(price, cost);
			$('[data-qf-out="fees"]', qf).textContent = money(r.fees);
			var pr = $('[data-qf-out="profit"]', qf);
			pr.textContent = money(r.profit);
			pr.classList.toggle('is-bad', r.profit < 0);
			$('[data-qf-out="margin"]', qf).textContent = price > 0 ? (r.profit / price * 100).toFixed(1) + '%' : '—';
		};
		$$('input', qf).forEach(function (i) { i.addEventListener('input', runQf); });
		runQf();
	}

	/* Lead forms: steps + inline submit ------------------------------------------- */
	$$('[data-lead-form]').forEach(function (form) {
		var result = $('[data-result]', form);
		if (form.hasAttribute('data-steps')) {
			form.classList.add('is-enhanced');
			var steps = $$('.form-step', form);
			var dots = $$('.stepper li', form);
			var prev = $('[data-prev]', form);
			var next = $('[data-next]', form);
			var submit = $('[data-submit]', form);
			var current = 0;
			var show = function (i) {
				current = i;
				steps.forEach(function (s, k) { s.classList.toggle('is-active', k === i); });
				dots.forEach(function (d, k) { d.classList.toggle('is-active', k === i); d.classList.toggle('is-done', k < i); });
				prev.hidden = i === 0;
				next.hidden = i === steps.length - 1;
				submit.hidden = i !== steps.length - 1;
				var first = $('input:not([type="hidden"]), select, textarea', steps[i]);
				if (first && i > 0) { first.focus({ preventScroll: true }); }
			};
			next.addEventListener('click', function () { show(Math.min(steps.length - 1, current + 1)); });
			prev.addEventListener('click', function () { show(Math.max(0, current - 1)); });
			show(0);
		}
		form.addEventListener('submit', function (e) {
			if (!window.fetch || !window.FormData) { return; }
			var invalid = $$('[required]', form).filter(function (f) { return !f.value || (f.type === 'email' && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(f.value)); });
			$$('.is-invalid', form).forEach(function (f) { f.classList.remove('is-invalid'); });
			if (invalid.length) {
				e.preventDefault();
				invalid.forEach(function (f) { f.classList.add('is-invalid'); });
				invalid[0].focus();
				return;
			}
			e.preventDefault();
			var btn = $('[type="submit"]', form);
			if (btn) { btn.disabled = true; btn.style.opacity = '.7'; }
			fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					result.hidden = false;
					result.className = 'form-result ' + (res.success ? 'is-ok' : 'is-err');
					result.textContent = (res.data && res.data.message) || '';
					if (res.success) {
						form.classList.add('is-done');
						if (window.gtag) { window.gtag('event', 'generate_lead', { form: form.querySelector('[name="a2e_form"]').value }); }
					}
				})
				.catch(function () { form.submit(); })
				.then(function () { if (btn) { btn.disabled = false; btn.style.opacity = ''; } });
		});
	});

	/* Checklist with saved progress ------------------------------------------------ */
	$$('[data-checklist]').forEach(function (box) {
		var key = box.getAttribute('data-checklist');
		var saved = {};
		try { saved = JSON.parse(storage(key) || '{}') || {}; } catch (e) { saved = {}; }
		var inputs = $$('input[type="checkbox"]', box);
		var bar = $('[data-progress]', box);
		var done = $('[data-done]', box);
		function update() {
			var n = 0;
			var state = {};
			inputs.forEach(function (i) { if (i.checked) { n++; state[i.getAttribute('data-key')] = 1; } });
			bar.style.width = (n / inputs.length * 100) + '%';
			done.textContent = n;
			storage(key, JSON.stringify(state));
		}
		inputs.forEach(function (i) { i.checked = !!saved[i.getAttribute('data-key')]; i.addEventListener('change', update); });
		var reset = $('[data-reset]', box);
		if (reset) { reset.addEventListener('click', function () { inputs.forEach(function (i) { i.checked = false; }); update(); }); }
		update();
	});

	/* Tool hub filter ---------------------------------------------------------------- */
	$$('[data-tool-filter]').forEach(function (bar) {
		var grid = bar.nextElementSibling;
		var empty = grid && grid.nextElementSibling;
		var search = $('[data-tool-search]', bar);
		var cat = 'all';
		function apply() {
			var q = (search.value || '').trim().toLowerCase();
			var shown = 0;
			$$('.tool-card', grid).forEach(function (c) {
				var ok = (cat === 'all' || c.getAttribute('data-cat') === cat) && (!q || c.getAttribute('data-name').indexOf(q) > -1);
				c.hidden = !ok;
				if (ok) { shown++; }
			});
			if (empty && empty.hasAttribute('data-tool-empty')) { empty.hidden = shown > 0; }
		}
		$$('.chip', bar).forEach(function (chip) {
			chip.addEventListener('click', function () {
				cat = chip.getAttribute('data-cat');
				$$('.chip', bar).forEach(function (c) { c.classList.toggle('is-active', c === chip); });
				apply();
			});
		});
		search.addEventListener('input', apply);
	});

	/* Table of contents: highlight the section in view -------------------------------- */
	var toc = $('[data-toc]');
	if (toc && 'IntersectionObserver' in window) {
		var links = $$('a', toc);
		var map = {};
		links.forEach(function (a) { map[a.getAttribute('href').slice(1)] = a; });
		var tio = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting && map[en.target.id]) {
					links.forEach(function (a) { a.classList.remove('is-active'); });
					map[en.target.id].classList.add('is-active');
				}
			});
		}, { rootMargin: '-15% 0px -70% 0px' });
		Object.keys(map).forEach(function (id) { var h = doc.getElementById(id); if (h) { tio.observe(h); } });
	}

	/* Copy link (share) --------------------------------------------------------------- */
	$$('[data-copy-link]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (navigator.clipboard) { navigator.clipboard.writeText(window.location.href); }
			var t = btn.getAttribute('title');
			btn.setAttribute('title', 'Copied!');
			setTimeout(function () { btn.setAttribute('title', t); }, 1600);
		});
	});
})();
