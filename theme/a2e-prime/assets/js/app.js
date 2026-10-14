/**
 * A2E Prime: front-end interactions (no dependencies).
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

	/* Hero: floating supplier tiles ----------------------------------------- */
	var floats = $('[data-floats]');
	var heroEl = $('.hero');
	if (floats && heroEl && !reduce && window.matchMedia('(hover: hover)').matches) {
		heroEl.addEventListener('pointermove', function (e) {
			var r = heroEl.getBoundingClientRect();
			floats.style.setProperty('--px', ((e.clientX - r.left) / r.width - 0.5).toFixed(3));
			floats.style.setProperty('--py', ((e.clientY - r.top) / r.height - 0.5).toFixed(3));
		});
		heroEl.addEventListener('pointerleave', function () {
			floats.style.removeProperty('--px');
			floats.style.removeProperty('--py');
		});
	}

	/* Hero: example board (supplier → listing → profit) --------------------- */
	var jr = $('[data-journey]');
	if (jr) {
		var ex = [];
		try { ex = JSON.parse(jr.getAttribute('data-journey')) || []; } catch (err) { ex = []; }
		var tabs = $$('[data-jr-tab]', jr);
		var panel = $('.jr__grid', jr);
		var checks = $$('[data-jr-check]', jr);
		var links = $$('.jr__link', jr);
		var segs = $$('[data-jr-seg]', jr);
		var f = function (k) { return $('[data-jr="' + k + '"]', jr); };
		var searchIcon = jr.getAttribute('data-search-icon') || '';
		var DUR = 8500;
		var cur = 0, elapsed = 0, last = 0, held = false, inView = false, loop = 0, seen = false;
		var timers = [], frames = [];
		var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };

		var later = function (fn, ms) { timers.push(setTimeout(fn, ms)); };
		var stopAll = function () {
			timers.forEach(clearTimeout); timers = [];
			frames.forEach(cancelAnimationFrame); frames = [];
			f('title').classList.remove('is-typing');
		};
		var tween = function (ms, step) {
			var t0 = 0;
			var run = function (now) {
				if (!t0) { t0 = now; }
				var p = Math.min(1, (now - t0) / ms);
				step(p);
				if (p < 1) { frames.push(requestAnimationFrame(run)); }
			};
			frames.push(requestAnimationFrame(run));
		};
		var specsHtml = function (d) {
			return d.specs.map(function (s) { return '<li><span>' + esc(s[0]) + '</span>' + esc(s[1]) + '</li>'; }).join('');
		};
		var fill = function (d) {
			f('mark').innerHTML = d.mark;
			f('art').innerHTML = d.artSvg;
			f('name').textContent = d.name;
			f('cost').textContent = d.costFmt;
			f('sold').textContent = d.soldText;
			f('bars').innerHTML = d.bars.map(function (h, k) { return '<i style="--i:' + k + ';height:' + (+h) + '%"></i>'; }).join('');
			f('str').textContent = d.strText;
			f('specs').innerHTML = specsHtml(d);
			f('searches').innerHTML = (d.searches || []).map(function (q) { return '<li>' + searchIcon + esc(q) + '</li>'; }).join('');
			f('price').textContent = d.priceFmt;
			f('priceRow').textContent = d.priceFmt;
			f('costRow').textContent = '−' + d.costFmt;
			f('fees').textContent = '−' + d.feesFmt;
			f('profitRow').textContent = d.profitFmt;
			f('margin').textContent = d.marginFmt;
			f('month').innerHTML = d.monthHtml;
		};
		var finish = function (d) {
			checks.forEach(function (c) { c.classList.add('is-on'); });
			f('title').textContent = d.title;
			f('chars').textContent = d.chars;
			f('meter').style.width = (d.chars / 80 * 100) + '%';
			segs.forEach(function (s, k) { s.style.width = d.split[k] + '%'; });
			f('profit').textContent = '+' + d.profitFmt;
		};
		var select = function (i) {
			tabs.forEach(function (t, k) {
				var on = k === i;
				t.classList.toggle('is-active', on);
				t.setAttribute('aria-selected', on ? 'true' : 'false');
				t.tabIndex = on ? 0 : -1;
				t.style.setProperty('--p', 0);
			});
			panel.setAttribute('aria-labelledby', tabs[i].id);
		};
		var play = function (i, animate) {
			stopAll();
			cur = i; elapsed = 0;
			select(i);
			var d = ex[i];
			if (!d) { return; }
			if (!animate) { fill(d); finish(d); return; }

			panel.classList.add('is-swap');
			later(function () {
				fill(d);
				checks.forEach(function (c) { c.classList.remove('is-on'); });
				f('title').textContent = '';
				f('chars').textContent = '0';
				f('meter').style.width = '0%';
				$$('li', f('specs')).concat($$('li', f('searches'))).forEach(function (li) { li.classList.add('is-off'); });
				segs.forEach(function (s) { s.style.width = '0%'; });
				f('profit').textContent = '+$0.00';
				panel.classList.remove('is-swap');
			}, 230);
			checks.forEach(function (c, k) { later(function () { c.classList.add('is-on'); }, 520 + k * 300); });
			later(function () { links[0].classList.remove('is-go'); void links[0].offsetWidth; links[0].classList.add('is-go'); }, 1400);
			later(function () {
				var title = f('title');
				title.classList.add('is-typing');
				tween(Math.min(1300, d.title.length * 16), function (p) {
					var n = Math.round(p * d.title.length);
					title.textContent = d.title.slice(0, n);
					f('chars').textContent = n;
					f('meter').style.width = (n / 80 * 100) + '%';
					if (p >= 1) { title.classList.remove('is-typing'); }
				});
			}, 1600);
			later(function () {
				$$('li', f('specs')).concat($$('li', f('searches'))).forEach(function (li, k) { later(function () { li.classList.remove('is-off'); }, k * 90); });
			}, 3000);
			later(function () { links[1].classList.remove('is-go'); void links[1].offsetWidth; links[1].classList.add('is-go'); }, 3500);
			later(function () {
				segs.forEach(function (s, k) { s.style.width = d.split[k] + '%'; });
				tween(1000, function (p) {
					var e = 1 - Math.pow(1 - p, 3);
					f('profit').textContent = '+' + money(d.profit * e);
				});
			}, 3700);
		};

		var tick = function (now) {
			loop = 0;
			if (!inView || reduce || ex.length < 2) { last = 0; return; }
			if (last && !held && !doc.hidden) { elapsed += now - last; }
			last = now;
			var p = Math.min(1, elapsed / DUR);
			if (tabs[cur]) { tabs[cur].style.setProperty('--p', p.toFixed(3)); }
			if (p >= 1) { play((cur + 1) % ex.length, true); }
			loop = requestAnimationFrame(tick);
		};
		var startLoop = function () { if (!loop) { last = 0; loop = requestAnimationFrame(tick); } };

		tabs.forEach(function (t, k) {
			t.addEventListener('click', function () { play(k, !reduce); startLoop(); });
			t.addEventListener('keydown', function (e) {
				var dir = e.key === 'ArrowRight' ? 1 : (e.key === 'ArrowLeft' ? -1 : 0);
				if (!dir) { return; }
				e.preventDefault();
				var n = (k + dir + tabs.length) % tabs.length;
				tabs[n].focus();
				play(n, !reduce);
			});
		});
		jr.addEventListener('pointerenter', function () { held = true; });
		jr.addEventListener('pointerleave', function () { held = false; });
		jr.addEventListener('focusin', function () { held = true; });
		jr.addEventListener('focusout', function () { held = false; });

		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (entries) {
				inView = entries[0].isIntersecting;
				if (inView && !seen && !reduce) { seen = true; play(0, true); }
				if (inView) { startLoop(); }
			}, { threshold: 0.25 }).observe(jr);
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
			$('[data-qf-out="margin"]', qf).textContent = price > 0 ? (r.profit / price * 100).toFixed(1) + '%' : '-';
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
			fetch(form.getAttribute('action'), { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
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
