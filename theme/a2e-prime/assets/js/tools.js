/**
 * A2E free seller tools: shared eBay fee model + one engine per tool.
 * Fee rates come from A2E → Site Settings → eBay fees via window.a2eFees.
 * Everything runs in the browser; nothing is sent to the server.
 */
(function () {
	'use strict';

	var F = window.a2eFees || {};
	function num(v) { var n = parseFloat(String(v).replace(/[^0-9.\-]/g, '')); return isFinite(n) ? n : 0; }
	['threshold', 'rateAbove', 'orderSmall', 'orderLarge', 'orderLimit', 'international', 'belowStandard', 'topRated'].forEach(function (k) { F[k] = num(F[k]); });
	if (!F.threshold) { F.threshold = 7500; F.rateAbove = F.rateAbove || 2.35; }
	if (!F.orderLimit) { F.orderLimit = 10; F.orderSmall = F.orderSmall || 0.3; F.orderLarge = F.orderLarge || 0.4; }
	F.categories = (F.categories || []).map(function (c) { return { id: c.id, label: c.label, rate: num(c.rate), noOrderFeeFrom: c.noOrderFeeFrom ? num(c.noOrderFeeFrom) : 0 }; });
	if (!F.categories.length) { F.categories = [{ id: 'default', label: 'Most categories', rate: 13.6, noOrderFeeFrom: 0 }]; }
	var CUR = F.currency || '$';

	function $(s, c) { return (c || document).querySelector(s); }
	function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
	function money(v) {
		if (!isFinite(v)) { return '-'; }
		return (v < 0 ? '−' : '') + CUR + Math.abs(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	}
	function pct(v, d) { return isFinite(v) ? v.toFixed(d === undefined ? 1 : d) + '%' : '-'; }
	function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
	function category(id) {
		for (var i = 0; i < F.categories.length; i++) { if (F.categories[i].id === id) { return F.categories[i]; } }
		return F.categories[0];
	}

	/**
	 * eBay fee model. The final value fee (and the ad fee) are charged on the total sale:
	 * item price + shipping charged + the sales tax the buyer pays.
	 */
	function ebayFees(o) {
		var base = (o.price || 0) + (o.shipping || 0);
		var total = base * (1 + (o.tax || 0) / 100);
		var cat = category(o.category || 'default');
		var rate = o.category === 'custom' ? (o.customRate || 0) : cat.rate;
		var orderFee = total <= F.orderLimit ? F.orderSmall : F.orderLarge;
		if (cat.noOrderFeeFrom) {
			if (total >= cat.noOrderFeeFrom) { orderFee = 0; } else { rate = category('default').rate; }
		}
		var fvf = rate / 100 * Math.min(total, F.threshold) + F.rateAbove / 100 * Math.max(0, total - F.threshold);
		if (o.level === 'top') { fvf *= 1 - F.topRated / 100; }
		if (o.level === 'below') { fvf += F.belowStandard / 100 * total; }
		var intl = o.international ? F.international / 100 * total : 0;
		var ad = (o.adRate || 0) / 100 * total;
		if (base <= 0) { orderFee = 0; }
		return { base: base, total: total, fvf: fvf, orderFee: orderFee, intl: intl, ad: ad, sellingFees: fvf + orderFee + intl, all: fvf + orderFee + intl + ad };
	}

	/** Smallest price where f(price) >= 0 (f increasing in price), by bisection. */
	function solve(f) {
		var lo = 0, hi = 1000;
		while (f(hi) < 0 && hi < 1e7) { hi *= 4; }
		if (f(hi) < 0) { return NaN; }
		for (var i = 0; i < 80; i++) {
			var mid = (lo + hi) / 2;
			if (f(mid) >= 0) { hi = mid; } else { lo = mid; }
		}
		return Math.ceil(hi * 100) / 100;
	}

	function read(root) {
		var d = {};
		$$('input, select, textarea', root).forEach(function (el) {
			if (!el.name) { return; }
			if (el.type === 'checkbox') { d[el.name] = el.checked; } else if (el.type === 'radio') { if (el.checked) { d[el.name] = el.value; } } else { d[el.name] = el.value; }
		});
		return d;
	}
	function out(root, key, value, state) {
		$$('[data-out="' + key + '"]', root).forEach(function (el) {
			if (el.tagName === 'TEXTAREA') { el.value = value; } else { el.textContent = value; }
			el.classList.toggle('is-bad', state === 'bad');
		});
	}
	function html(root, key, markup) { $$('[data-out-html="' + key + '"]', root).forEach(function (el) { el.innerHTML = markup; }); }
	function list(root, key, items) { $$('[data-out-list="' + key + '"]', root).forEach(function (el) { el.innerHTML = items.join(''); }); }
	function verdict(root, state, title, text) {
		var v = $('[data-verdict]', root);
		if (!v) { return; }
		v.setAttribute('data-state', state);
		out(root, 'verdict', title);
		out(root, 'verdictText', text);
	}
	function toggleField(root, name, show) { $$('[data-field="' + name + '"]', root).forEach(function (el) { el.hidden = !show; }); }
	function ring(root, score, state) {
		var r = $('[data-ring]', root);
		if (!r) { return; }
		var c = 326.73;
		r.style.strokeDashoffset = (c * (1 - clamp(score, 0, 100) / 100)).toFixed(2);
		r.style.stroke = state === 'good' ? 'var(--good)' : (state === 'warn' ? 'var(--warn)' : (state === 'bad' ? 'var(--bad)' : 'var(--c1)'));
	}
	var toast;
	function copy(text) {
		if (navigator.clipboard) { navigator.clipboard.writeText(text); } else {
			var t = document.createElement('textarea'); t.value = text; document.body.appendChild(t); t.select();
			try { document.execCommand('copy'); } catch (e) { /* ignore */ }
			document.body.removeChild(t);
		}
		if (!toast) { toast = document.createElement('div'); toast.className = 'copied'; toast.setAttribute('role', 'status'); document.body.appendChild(toast); }
		toast.textContent = 'Copied to clipboard';
		toast.classList.add('is-on');
		clearTimeout(toast._t);
		toast._t = setTimeout(function () { toast.classList.remove('is-on'); }, 1600);
	}
	function feeInput(d) { return { price: num(d.price), shipping: num(d.shipping), tax: num(d.tax), category: d.category, customRate: num(d.customRate), level: d.level, international: !!d.international, adRate: num(d.adRate) }; }

	var tools = {
		'ebay-fee-calculator': function (root, d) {
			toggleField(root, 'customRate', d.category === 'custom');
			var r = ebayFees(feeInput(d));
			var cost = num(d.cost) + num(d.shipCost);
			var profit = r.base - r.all - cost;
			out(root, 'allFees', money(r.all));
			out(root, 'profit', money(profit), profit < 0 ? 'bad' : '');
			out(root, 'fvf', money(r.fvf));
			out(root, 'orderFee', money(r.orderFee));
			out(root, 'ad', money(r.ad));
			out(root, 'intl', money(r.intl));
			out(root, 'feePct', r.base ? pct(r.all / r.base * 100) : '-');
			out(root, 'margin', r.base ? pct(profit / r.base * 100) : '-', profit < 0 ? 'bad' : '');
			out(root, 'payout', money(r.base - r.all));
			out(root, 'total', money(r.total));
			var sum = Math.max(r.base, cost + r.all, 0.01);
			var seg = function (k, v) { var el = $('[data-seg="' + k + '"]', root); if (el) { el.style.flexBasis = (Math.max(0, v) / sum * 100) + '%'; } };
			seg('cost', cost); seg('fees', r.all); seg('profit', profit);
		},

		'amazon-to-ebay-profit-calculator': function (root, d) {
			toggleField(root, 'customRate', d.category === 'custom');
			var o = feeInput(d);
			var totalCost = num(d.cost) * (1 + num(d.costTax) / 100) + num(d.other);
			var r = ebayFees(o);
			var profit = r.base - r.all - totalCost;
			var margin = r.base ? profit / r.base * 100 : NaN;
			var target = num(d.target);
			var profitAt = function (p) { var x = Object.assign({}, o, { price: p }); var f = ebayFees(x); return p + o.shipping - f.all - totalCost; };
			var breakEven = solve(profitAt);
			var targetPrice = solve(function (p) { return profitAt(p) - target / 100 * (p + o.shipping); });
			out(root, 'profit', money(profit), profit < 0 ? 'bad' : '');
			out(root, 'margin', pct(margin), profit < 0 ? 'bad' : '');
			out(root, 'roi', totalCost ? pct(profit / totalCost * 100) : '-');
			out(root, 'fees', money(r.all));
			out(root, 'totalCost', money(totalCost));
			out(root, 'breakEven', money(breakEven));
			out(root, 'targetPrice', money(targetPrice) + ' for ' + pct(target, 0));
			if (margin >= target) { verdict(root, 'good', 'Worth listing.', 'Margin meets your ' + pct(target, 0) + ' target after every fee.'); } else if (profit > 1 && margin >= 5) { verdict(root, 'warn', 'Thin margin.', 'Profitable, but below target. Try ' + money(targetPrice) + ' or a cheaper supplier.'); } else { verdict(root, 'bad', 'Skip it.', 'Fees and costs eat the margin at this price.'); }
		},

		'ebay-price-calculator': function (root, d) {
			toggleField(root, 'customRate', d.category === 'custom');
			toggleField(root, 'margin', d.mode !== 'fixed');
			toggleField(root, 'fixed', d.mode === 'fixed');
			var cost = num(d.cost) + num(d.shipCost);
			var o = { tax: num(d.tax), category: d.category, customRate: num(d.customRate), adRate: num(d.adRate) };
			var profitAt = function (p) { return p - ebayFees(Object.assign({}, o, { price: p })).all - cost; };
			var price = d.mode === 'fixed' ? solve(function (p) { return profitAt(p) - num(d.fixed); }) : solve(function (p) { return profitAt(p) - num(d.margin) / 100 * p; });
			var f = ebayFees(Object.assign({}, o, { price: price }));
			var profit = price - f.all - cost;
			var charm = Math.ceil(price) - 0.01;
			if (charm < price) { charm += 1; }
			out(root, 'listPrice', money(price));
			out(root, 'fees', money(f.all));
			out(root, 'profit', money(profit));
			out(root, 'marginOut', price ? pct(profit / price * 100) : '-');
			out(root, 'charm', money(charm));
		},

		'ebay-title-generator': function (root, d) {
			var words = function (s) { return String(s || '').split(/[\s,]+/).filter(Boolean); };
			var fit = function (parts) {
				var seen = {};
				var res = [];
				parts.forEach(function (w) {
					var k = w.toLowerCase();
					if (k !== 'for' && k !== '&' && seen[k]) { return; }
					seen[k] = 1;
					res.push(w);
				});
				var t = '';
				for (var i = 0; i < res.length; i++) {
					var next = t ? t + ' ' + res[i] : res[i];
					if (next.length > 80) { break; }
					t = next;
				}
				return t;
			};
			var b = words(d.brand), ty = words(d.type), m = words(d.model), fe = String(d.features || '').split(',').map(function (x) { return x.trim(); }).filter(Boolean), v = words(d.variant), c = words(d.condition);
			var feW = [];
			fe.forEach(function (x) { feW = feW.concat(words(x)); });
			var ideas = [
				fit([].concat(b, ty, m, feW, v, c)),
				fit([].concat(b, m, ty, feW, v, c)),
				fit([].concat(ty, ['for'], b, m, feW, v, c)),
				fit([].concat(c, b, ty, m, v, feW))
			].filter(function (t, i, a) { return t && a.indexOf(t) === i; });
			list(root, 'titles', ideas.map(function (t) {
				var n = t.length;
				return '<li><button type="button" data-copy-text="' + esc(t) + '"><span>' + esc(t) + '</span><small class="' + (n >= 65 ? '' : 'warn') + '">' + n + '/80</small></button></li>';
			}));

			// Checker.
			var title = String(d.title || '').trim();
			var issues = [];
			var score = 40;
			var len = title.length;
			if (!len) { out(root, 'score', '-'); out(root, 'chars', ''); list(root, 'issues', []); ring(root, 0); return; }
			if (len > 80) { issues.push(['bad', 'Over 80 characters, so eBay will not accept it. Trim ' + (len - 80) + ' characters.']); } else if (len >= 70) { score += 35; issues.push(['ok', 'Great length: ' + len + ' of 80 characters used.']); } else if (len >= 55) { score += 25; issues.push(['warn', 'Good, but ' + (80 - len) + ' characters are unused. Add a feature, size or compatible model.']); } else if (len >= 40) { score += 12; issues.push(['warn', 'Short title: you are missing searches. Use more of the 80 characters.']); } else { score += 3; issues.push(['bad', 'Very short title. Add brand, model, key features and size/colour.']); }
			var ws = title.split(/\s+/);
			if (ws.length >= 8) { score += 10; }
			if (/\d/.test(title)) { score += 10; issues.push(['ok', 'Includes a model, size or quantity number.']); } else { issues.push(['warn', 'No numbers. Buyers often search model numbers, sizes or pack quantities.']); }
			var caps = ws.filter(function (w) { return w.length > 3 && w === w.toUpperCase() && /[A-Z]/.test(w); });
			if (caps.length / ws.length > 0.3) { score -= 10; issues.push(['bad', 'Too many ALL-CAPS words. They are harder to read and look spammy.']); }
			var spam = title.match(/l@@k|\bwow\b|\bamazing\b|\bawesome\b|\bgreat\b|\bnice\b|\bcheap\b|\bbest\b|\blook\b|\bhot\b|\bsale\b/gi);
			if (spam) { score -= 6 * spam.length; issues.push(['bad', 'Filler words that buyers do not search: ' + spam.join(', ') + '.']); }
			if (/[!*~]{1,}|@@/.test(title)) { score -= 8; issues.push(['bad', 'Remove symbols like ! * ~ @@. They waste characters.']); }
			var lower = ws.map(function (w) { return w.toLowerCase().replace(/[^a-z0-9]/g, ''); }).filter(Boolean);
			var dupes = lower.filter(function (w, i) { return w.length > 2 && lower.indexOf(w) !== i; });
			if (dupes.length) { score -= 5 * dupes.length; issues.push(['warn', 'Repeated words do not rank you higher: ' + dupes.join(', ') + '.']); }
			if (!spam && !caps.length && !dupes.length) { score += 5; issues.push(['ok', 'Clean wording: no filler, caps or repeats.']); }
			score = clamp(Math.round(score), 0, 100);
			var st = score >= 75 ? 'good' : (score >= 50 ? 'warn' : 'bad');
			out(root, 'score', String(score));
			out(root, 'chars', len + ' / 80 characters');
			ring(root, score, st);
			var icon = { ok: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m16 9-5.5 5.5L8 12"/></svg>', warn: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>', bad: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>' };
			list(root, 'issues', issues.map(function (i) { return '<li class="' + i[0] + '">' + icon[i[0]] + '<span>' + esc(i[1]) + '</span></li>'; }));
		},

		'ebay-sell-through-rate-calculator': function (root, d) {
			var sold = num(d.sold), active = num(d.active), days = num(d.days) || 90;
			var str = sold + active > 0 ? sold / (sold + active) * 100 : 0;
			var rating = str < 20 ? 'Slow' : (str < 50 ? 'Moderate' : (str < 80 ? 'Strong' : 'Very hot'));
			var perDay = sold / days;
			out(root, 'str', pct(str));
			out(root, 'rating', rating);
			out(root, 'perDay', perDay.toFixed(2));
			out(root, 'daysToSell', perDay > 0 ? Math.round(active / perDay) + ' days' : '-');
			var fill = $('[data-meter-fill]', root);
			if (fill) { fill.style.left = clamp(str, 0, 100) + '%'; }
		},

		'ebay-defect-rate-calculator': function (root, d) {
			var t = num(d.transactions), def = num(d.defects), buyers = num(d.buyers);
			var rate = t > 0 ? def / t * 100 : 0;
			var band, state;
			if (rate <= 0.5) { band = 'Top Rated range'; state = 'good'; } else if (rate <= 2) { band = 'Above Standard'; state = 'warn'; } else if (buyers >= 5) { band = 'Below Standard'; state = 'bad'; } else { band = 'Above Standard*'; state = 'warn'; }
			out(root, 'rate', pct(rate, 2), state === 'bad' ? 'bad' : '');
			out(root, 'band', band, state === 'bad' ? 'bad' : '');
			var top = Math.floor(t * 0.005) - def;
			var std = Math.floor(t * 0.02) - def;
			out(root, 'topLeft', top >= 0 ? String(top) : 'Over by ' + Math.abs(top));
			out(root, 'stdLeft', std >= 0 ? String(std) : 'Over by ' + Math.abs(std));
			out(root, 'note', state === 'bad' ? 'Below Standard sellers pay higher fees and get less visibility. Reduce cancellations and resolve cases quickly.' : (band.indexOf('*') > -1 ? '*Your rate is above 2%, but eBay only applies the level once defects involve at least 5 different buyers.' : 'Keep it up: daily stock checks and fast tracking keep defects low.'));
		},

		'ebay-promoted-listings-calculator': function (root, d) {
			var base = num(d.price) + num(d.shipping);
			var total = base * (1 + num(d.tax) / 100);
			var fees = ebayFees({ price: num(d.price), shipping: num(d.shipping), tax: num(d.tax) }).sellingFees;
			var before = base - fees - num(d.cost);
			var ad = num(d.adRate) / 100 * total;
			var after = before - ad;
			var breakEven = total ? before / total * 100 : 0;
			var max = total ? (before - num(d.target) / 100 * base) / total * 100 : 0;
			out(root, 'profitAfter', money(after), after < 0 ? 'bad' : '');
			out(root, 'maxRate', max > 0 ? pct(max) : 'No room for ads', max <= 0 ? 'bad' : '');
			out(root, 'adFee', money(ad));
			out(root, 'profitBefore', money(before));
			out(root, 'breakEven', breakEven > 0 ? pct(breakEven) : '-');
			out(root, 'marginAfter', base ? pct(after / base * 100) : '-', after < 0 ? 'bad' : '');
		},

		'ebay-store-name-generator': function (root, d, ev) {
			if (ev && ev.type !== 'click' && root.dataset.generated) { return; }
			root.dataset.generated = '1';
			var k = String(d.keyword || 'Store').trim().replace(/\s+/g, ' ');
			var K = k.charAt(0).toUpperCase() + k.slice(1);
			var kk = K.replace(/\s+/g, '');
			var pick = function (a) { return a[Math.floor(Math.random() * a.length)]; };
			var sets = {
				brandable: function () { return pick([kk + pick(['ly', 'ify', 'io', 'Hub', 'Nest', 'Co', 'Wave', 'Haus', 'Loop', 'Nova', 'Base', 'Box']), pick(['Prime', 'True', 'Bright', 'Golden', 'Blue', 'Urban', 'North', 'Simply', 'Pure']) + ' ' + K, K + ' ' + pick(['Collective', 'Goods', 'House', 'Finds', 'Studio', 'Lab', 'Supply'])]); },
				trust: function () { return pick([K + ' ' + pick(['Supply Co.', 'Direct', 'Outlet', 'Depot', 'Essentials', 'Warehouse', 'Trading Co.', 'Wholesale', '& Co.']), pick(['The', 'Your', 'Trusted', 'Reliable', 'Family']) + ' ' + K + ' ' + pick(['Store', 'Shop', 'Outlet', 'Source'])]); },
				modern: function () { return pick([kk + pick(['ly', 'io', 'Lab', 'Box', 'Base', 'Kit', 'Hub']), pick(['Get', 'Go', 'Neo', 'Try', 'Hey', 'Ultra']) + kk, kk.slice(0, 5) + pick(['ora', 'ix', 'ivo', 'eo', 'aro'])]); },
				fun: function () { return pick(['Happy ' + K, K + ' Galaxy', 'Big ' + K + ' Energy', kk + 'topia', 'The ' + K + ' Spot', 'Snappy ' + K, K + ' Ninja', 'Planet ' + K, K + ' Party', 'Hello ' + K]); }
			};
			var fn = sets[d.style] || sets.brandable;
			var names = [];
			for (var i = 0; i < 300 && names.length < 24; i++) { var n = fn(); if (names.indexOf(n) < 0) { names.push(n); } }
			list(root, 'names', names.map(function (n, i) { return '<li><button type="button" style="--i:' + i + '" data-copy-text="' + esc(n) + '">' + esc(n) + '</button></li>'; }));
		},

		'ebay-description-template-generator': function (root, d) {
			var c = /^#[0-9a-f]{6}$/i.test(d.color) ? d.color : '#2b4cf2';
			var lines = function (s) { return String(s || '').split(/\r?\n/).map(function (x) { return x.trim(); }).filter(Boolean); };
			var h2 = function (t) { return '<h2 style="font-size:18px;margin:24px 0 10px;padding-bottom:6px;border-bottom:2px solid ' + c + ';color:#111827;">' + esc(t) + '</h2>'; };
			var out2 = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:800px;margin:0 auto;color:#1f2937;line-height:1.6;font-size:15px;">';
			out2 += '<h1 style="font-size:24px;line-height:1.3;margin:0 0 8px;color:' + c + ';">' + esc(d.title) + '</h1>';
			if (d.intro) { out2 += '<p style="font-size:16px;margin:0 0 8px;">' + esc(d.intro) + '</p>'; }
			var fe = lines(d.features);
			if (fe.length) { out2 += h2('Key features') + '<ul style="margin:0;padding-left:20px;">' + fe.map(function (f) { return '<li style="margin:4px 0;">' + esc(f) + '</li>'; }).join('') + '</ul>'; }
			var sp = lines(d.specs);
			if (sp.length) {
				out2 += h2('Specifications') + '<table style="width:100%;border-collapse:collapse;">' + sp.map(function (s, i) {
					var p = s.split(':');
					var k = p.shift();
					return '<tr style="background:' + (i % 2 ? '#ffffff' : '#f6f7fb') + ';"><td style="padding:8px 10px;font-weight:bold;width:40%;">' + esc(k.trim()) + '</td><td style="padding:8px 10px;">' + esc(p.join(':').trim()) + '</td></tr>';
				}).join('') + '</table>';
			}
			if (d.box) { out2 += h2('In the box') + '<p style="margin:0;">' + esc(d.box) + '</p>'; }
			if (d.shipping || d.returns) { out2 += h2('Shipping & returns') + (d.shipping ? '<p style="margin:0 0 6px;">' + esc(d.shipping) + '</p>' : '') + (d.returns ? '<p style="margin:0;">' + esc(d.returns) + '</p>' : ''); }
			out2 += '</div>';
			html(root, 'preview', out2);
			out(root, 'html', out2);
		},

		'ebay-bulk-profit-calculator': function (root, d) {
			toggleField(root, 'customRate', d.category === 'custom');
			var rows = String(d.rows || '').split(/\r?\n/);
			var res = [];
			rows.forEach(function (line) {
				line = line.trim();
				if (!line) { return; }
				var parts = line.indexOf('\t') > -1 ? line.split('\t') : line.split(',');
				parts = parts.map(function (p) { return p.trim(); });
				var nums = [];
				while (parts.length > 1 && /^\$?-?[\d.,]+$/.test(parts[parts.length - 1]) && nums.length < 3) { nums.unshift(num(parts.pop())); }
				if (nums.length < 2) { return; }
				var cost = nums[0], price = nums[1], ship = nums[2] || 0;
				var costT = cost * (1 + num(d.costTax) / 100);
				var f = ebayFees({ price: price, tax: num(d.tax), category: d.category, customRate: num(d.customRate), adRate: num(d.adRate) });
				var profit = price - f.all - costT - ship;
				var margin = price ? profit / price * 100 : 0;
				var v = margin >= num(d.target) ? 'list' : (profit > 0.5 && margin >= 5 ? 'test' : 'skip');
				res.push({ name: parts.join(', ') || 'Product', cost: costT + ship, price: price, fees: f.all, profit: profit, margin: margin, v: v });
			});
			root._bulk = res;
			html(root, 'tbody', res.map(function (r) {
				return '<tr><td title="' + esc(r.name) + '">' + esc(r.name) + '</td><td>' + money(r.cost) + '</td><td>' + money(r.price) + '</td><td>' + money(r.fees) + '</td><td class="' + (r.profit < 0 ? 'is-bad' : 'is-good') + '">' + money(r.profit) + '</td><td>' + pct(r.margin) + '</td><td><span class="vpill vpill--' + r.v + '">' + { list: 'List', test: 'Test', skip: 'Skip' }[r.v] + '</span></td></tr>';
			}).join('') || '<tr><td colspan="7">Paste rows like: Product name, 9.99, 19.99</td></tr>');
			var n = res.length;
			out(root, 'count', String(n));
			out(root, 'listCount', String(res.filter(function (r) { return r.v === 'list'; }).length));
			out(root, 'avgMargin', n ? pct(res.reduce(function (a, r) { return a + r.margin; }, 0) / n) : '-');
			out(root, 'avgProfit', n ? money(res.reduce(function (a, r) { return a + r.profit; }, 0) / n) : '-');
		},

		'ebay-product-research-scorecard': function (root, d) {
			var sold = num(d.sold30), str = num(d.str), margin = num(d.margin), del = num(d.delivery);
			var f = [
				['Demand', 25, sold >= 30 ? 25 : sold >= 20 ? 22 : sold >= 10 ? 18 : sold >= 5 ? 12 : sold >= 1 ? 6 : 0],
				['Margin after fees', 25, margin >= 25 ? 25 : margin >= 20 ? 22 : margin >= 15 ? 18 : margin >= 10 ? 12 : margin > 0 ? 6 : 0],
				['Sell-through', 15, Math.round(clamp(str / 80, 0, 1) * 15)],
				['Competition', 15, { low: 15, medium: 9, high: 3 }[d.competition] || 9],
				['Delivery speed', 10, del <= 3 ? 10 : del <= 5 ? 8 : del <= 7 ? 5 : del <= 10 ? 2 : 0],
				['Brand & return risk', 10, { low: 10, medium: 5, high: 0 }[d.risk] || 5]
			];
			var score = f.reduce(function (a, x) { return a + x[2]; }, 0);
			var st = score >= 75 ? 'good' : (score >= 55 ? 'warn' : 'bad');
			out(root, 'score', String(score));
			ring(root, score, st);
			if (st === 'good') { verdict(root, 'good', 'List it.', 'Strong on the factors that matter most.'); } else if (st === 'warn') { verdict(root, 'warn', 'Test carefully.', 'List a few units and improve the weakest factor.'); } else { verdict(root, 'bad', 'Skip it.', 'Your time is better spent on the next product.'); }
			html(root, 'factors', f.map(function (x) { return '<li><span>' + esc(x[0]) + '</span><span class="bar"><span style="width:' + (x[2] / x[1] * 100) + '%"></span></span><b>' + x[2] + '/' + x[1] + '</b></li>'; }).join(''));
		},

		'ebay-dropshipping-income-calculator': function (root, d) {
			var L = num(d.listings), rate = num(d.rate), price = num(d.avgPrice), p = num(d.avgProfit), g = num(d.growth), goal = num(d.goal);
			var orders = L * rate;
			var monthly = [];
			for (var m = 1; m <= 12; m++) { monthly.push((L + g * m) * rate * p); }
			out(root, 'profit', money(orders * p));
			out(root, 'profit12', money(monthly[11]));
			out(root, 'orders', Math.round(orders).toLocaleString('en-US'));
			out(root, 'revenue', money(orders * price));
			var needed = rate * p > 0 ? Math.ceil(goal / (rate * p)) : NaN;
			out(root, 'needed', isFinite(needed) ? needed.toLocaleString('en-US') : '-');
			var months = !isFinite(needed) ? '-' : (needed <= L ? 'Already there' : (g > 0 ? Math.ceil((needed - L) / g) + ' months' : 'Add listings to grow'));
			out(root, 'months', months);
			var chart = $('[data-chart]', root);
			if (chart) {
				var max = Math.max.apply(null, monthly.concat([goal, 1]));
				chart.innerHTML = monthly.map(function (v) { return '<span class="' + (v >= goal ? 'is-goal' : '') + '" style="height:' + (v / max * 100).toFixed(1) + '%" title="' + money(v) + '"></span>'; }).join('');
			}
		},

		'ebay-store-management-cost-calculator': function (root, d) {
			var week = num(d.hResearch) + num(d.hListing) + num(d.hStock) + num(d.hOrders) + num(d.hMessages);
			var month = week * 4.33;
			var diy = month * num(d.myRate);
			var va = month * num(d.vaRate) + month * 0.2 * num(d.myRate);
			var plan = num(d.plan);
			var saving = diy - plan;
			out(root, 'hours', Math.round(month) + ' h');
			out(root, 'saving', saving > 0 ? money(saving) + '/mo' : 'DIY costs less', saving <= 0 ? 'bad' : '');
			var max = Math.max(diy, va, plan, 1);
			var rows = [['Doing it yourself (your time)', diy, '#f97316'], ['Hiring a VA (+ your management time)', va, 'var(--c5)'], ['Done-for-you plan', plan, 'var(--good)']];
			html(root, 'bars', rows.map(function (r) { return '<div class="compare-bars__row"><div class="compare-bars__top"><span>' + esc(r[0]) + '</span><strong>' + money(r[1]) + '/mo</strong></div><div class="compare-bars__bar"><span style="width:' + (r[1] / max * 100).toFixed(1) + '%;background:' + r[2] + '"></span></div></div>'; }).join(''));
		}
	};

	$$('[data-tool]').forEach(function (root) {
		var slug = root.getAttribute('data-tool');
		var fn = tools[slug];
		if (!fn) { return; }
		var run = function (ev) { fn(root, read(root), ev); };
		root.addEventListener('input', run);
		root.addEventListener('change', run);
		root.addEventListener('click', function (e) {
			var t = e.target.closest('[data-copy-text]');
			if (t) { copy(t.getAttribute('data-copy-text')); return; }
			var c = e.target.closest('[data-copy]');
			if (c) { var ta = $('[data-out="html"]', root); if (ta) { copy(ta.value); } return; }
			var g = e.target.closest('[data-action="generate"]');
			if (g) { run({ type: 'click' }); return; }
			var csv = e.target.closest('[data-action="csv"]');
			if (csv && root._bulk) {
				var q = function (s) { return '"' + String(s).replace(/"/g, '""') + '"'; };
				var body = [['Product', 'Total cost', 'eBay price', 'eBay fees', 'Profit', 'Margin %', 'Verdict']].concat(root._bulk.map(function (r) { return [r.name, r.cost.toFixed(2), r.price.toFixed(2), r.fees.toFixed(2), r.profit.toFixed(2), r.margin.toFixed(1), r.v]; })).map(function (r) { return r.map(q).join(','); }).join('\n');
				var a = document.createElement('a');
				a.href = URL.createObjectURL(new Blob([body], { type: 'text/csv' }));
				a.download = 'ebay-profit-check.csv';
				document.body.appendChild(a); a.click(); a.remove();
				return;
			}
			var tab = e.target.closest('[data-tab]');
			if (tab) {
				var name = tab.getAttribute('data-tab');
				$$('[data-tab]', root).forEach(function (b) { var on = b === tab; b.classList.toggle('is-active', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
				$$('[data-panel]', root).forEach(function (p) { p.hidden = p.getAttribute('data-panel') !== name; });
				run();
			}
		});
		run();
	});
})();
