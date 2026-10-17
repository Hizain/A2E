/**
 * eBay Account Health Checker - browser engine.
 *
 * Everything runs locally in the visitor's browser. The engine works out the exact eBay
 * US seller level (Below Standard / Above Standard / Top Rated) from the numbers entered,
 * using eBay's real thresholds and minimum-count rules, then shows how many clean sales
 * it takes to reach Top Rated. No numbers are sent anywhere unless the visitor asks for
 * their report.
 *
 * eBay US standards used here:
 *  - Below Standard when transaction defect rate > 2% (counted only when 4+ different
 *    buyers are involved) OR cases closed without seller resolution > max(2, 0.3%).
 *  - Top Rated needs: defect rate <= 0.5%, cases <= max(2, 0.3%), late shipments
 *    <= max(5, 3%), valid tracking on time >= 95%, plus eligibility (90+ days, 100+
 *    sales and $1,000+ to US buyers over 12 months).
 *  - Top Rated Plus is a per-listing badge (Top Rated + 1-day handling + 30-day free
 *    returns), not an account level.
 */
( function () {
	'use strict';

	var data = window.a2ehcData || { ajax: '', capture: 0, business: 'A2E' };

	// eBay US thresholds.
	var TR_DEFECT   = 0.005; // 0.5%.
	var AS_DEFECT   = 0.02;  // 2%.
	var CASE_PCT    = 0.003; // 0.3%.
	var CASE_MIN    = 2;
	var LATE_PCT    = 0.03;  // 3%.
	var LATE_MIN    = 5;
	var TRACK_MIN   = 95;    // %.
	var DEFECT_BUYERS_MIN = 4;

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function clamp( n, lo, hi ) { return Math.max( lo, Math.min( hi, n ) ); }
	function numval( v, fb ) { var n = parseFloat( v ); return isFinite( n ) ? n : ( fb || 0 ); }
	function round1( n ) { return Math.round( n * 10 ) / 10; }
	function fmtInt( n ) { return Math.round( n ).toLocaleString(); }
	function esc( s ) {
		return String( s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	function parseDate( v ) {
		if ( ! v ) { return null; }
		var p = String( v ).split( '-' );
		if ( p.length !== 3 ) { return null; }
		var d = new Date( +p[ 0 ], +p[ 1 ] - 1, +p[ 2 ] );
		return isNaN( d.getTime() ) ? null : d;
	}

	function fmtDate( d ) {
		try { return d.toLocaleDateString( undefined, { year: 'numeric', month: 'long', day: 'numeric' } ); }
		catch ( e ) { return d.toISOString().slice( 0, 10 ); }
	}

	function nextTwentieth( from ) {
		var d = new Date( from.getFullYear(), from.getMonth(), 20 );
		if ( from.getDate() >= 20 ) { d = new Date( from.getFullYear(), from.getMonth() + 1, 20 ); }
		return d;
	}

	/** Clean sales needed so count/(txns + N) <= targetRate. */
	function needForRate( count, txns, targetRate ) {
		if ( count <= 0 || targetRate <= 0 ) { return 0; }
		return Math.max( 0, Math.ceil( count / targetRate ) - Math.round( txns ) );
	}

	/** Clean sales needed so count <= max(minCount, pct*(txns+N)). */
	function needForCapped( count, txns, pct, minCount ) {
		if ( count <= minCount ) { return 0; }
		return needForRate( count, txns, pct );
	}

	/** Clean, tracked sales needed so tracking reaches >= 95%. */
	function needForTracking( trackPct, txns ) {
		if ( trackPct >= TRACK_MIN ) { return 0; }
		var onTime = ( trackPct / 100 ) * txns;
		var target = TRACK_MIN / 100;
		// (onTime + N) / (txns + N) >= target  ->  N >= (target*txns - onTime)/(1 - target).
		return Math.max( 0, Math.ceil( ( target * txns - onTime ) / ( 1 - target ) ) );
	}

	function get( root, name ) {
		var el = root.querySelector( '[name="' + name + '"]' );
		return el ? el.value : '';
	}

	function compute( root ) {
		var txns     = Math.max( 0, numval( get( root, 'transactions' ), 0 ) );
		var defects  = Math.max( 0, numval( get( root, 'defects' ), 0 ) );
		var buyersIn = get( root, 'buyers' );
		var buyers   = '' === String( buyersIn ).trim() ? defects : Math.max( 0, numval( buyersIn, 0 ) );
		var late     = Math.max( 0, numval( get( root, 'late' ), 0 ) );
		var cases    = Math.max( 0, numval( get( root, 'cases' ), 0 ) );
		var track    = clamp( numval( get( root, 'tracking' ), 0 ), 0, 100 );
		var eligEl   = root.querySelector( '[name="eligible"]' );
		var eligible = eligEl ? eligEl.checked : true;

		var safe       = txns > 0 ? txns : 1;
		var defectRate = defects / safe;
		var caseRate   = cases / safe;
		var lateRate   = late / safe;

		var caseCap = Math.max( CASE_MIN, Math.ceil( CASE_PCT * txns ) );
		var lateCap = Math.max( LATE_MIN, Math.ceil( LATE_PCT * txns ) );

		// Below Standard triggers.
		var defectCounts = buyers >= DEFECT_BUYERS_MIN;
		var defectBad    = defectCounts && defectRate > AS_DEFECT;
		var caseBad      = cases > caseCap;
		var isBelow      = defectBad || caseBad;

		// Top Rated requirements.
		var trDefect = defectRate <= TR_DEFECT;
		var trCases  = cases <= caseCap;
		var trLate   = late <= lateCap;
		var trTrack  = track >= TRACK_MIN;
		var isTop    = ! isBelow && trDefect && trCases && trLate && trTrack && eligible;

		var level = isBelow ? 'below' : ( isTop ? 'top' : 'above' );

		// Window.
		var start = parseDate( get( root, 'startdate' ) );
		var end   = parseDate( get( root, 'enddate' ) );
		var periodDays = ( start && end && end > start ) ? Math.round( ( end - start ) / 86400000 ) : 0;
		var evalBasis  = txns >= 400 ? '3-month' : '12-month';

		var nextEval = parseDate( get( root, 'nextdate' ) ) || nextTwentieth( new Date() );

		// Velocity for the timeline.
		var days  = periodDays > 0 ? periodDays : ( evalBasis === '3-month' ? 90 : 365 );
		var daily = txns > 0 ? txns / days : 0;

		// Per-metric clean-sales needs (to be Top-Rated-ok).
		var needDef   = needForRate( defects, txns, TR_DEFECT );
		var needCase  = needForCapped( cases, txns, CASE_PCT, CASE_MIN );
		var needLate  = needForCapped( late, txns, LATE_PCT, LATE_MIN );
		var needTrack = needForTracking( track, txns );
		var needTop   = Math.max( needDef, needCase, needLate, needTrack );

		// To Above Standard (only relevant when Below).
		var needDefAS  = defectBad ? needForRate( defects, txns, AS_DEFECT ) : 0;
		var needCaseAS = caseBad ? needForCapped( cases, txns, CASE_PCT, CASE_MIN ) : 0;
		var needAbove  = Math.max( needDefAS, needCaseAS );

		return {
			txns: txns, defects: defects, buyers: buyers, late: late, cases: cases, track: track,
			defectRate: defectRate, caseRate: caseRate, lateRate: lateRate,
			caseCap: caseCap, lateCap: lateCap, defectCounts: defectCounts, eligible: eligible,
			level: level, isBelow: isBelow, isTop: isTop,
			trDefect: trDefect, trCases: trCases, trLate: trLate, trTrack: trTrack,
			evalBasis: evalBasis, periodDays: periodDays, nextEval: nextEval, daily: daily,
			needDef: needDef, needCase: needCase, needLate: needLate, needTrack: needTrack,
			needTop: needTop, needAbove: needAbove,
			ready: txns > 0
		};
	}

	function icon( name ) {
		var paths = {
			trend: '<path d="M3 17l6-6 4 4 7-7"/><path d="M17 7h4v4"/>',
			check: '<path d="M20 6 9 17l-5-5"/>',
			clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			target: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
			truck: '<path d="M3 7h11v8H3z"/><path d="M14 10h4l3 3v2h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/>',
			alert: '<path d="M12 9v4M12 17h0M10.3 4.3L2.8 18a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/>',
			award: '<circle cx="12" cy="9" r="5"/><path d="M8.5 13.5L7 21l5-2 5 2-1.5-7.5"/>'
		};
		return '<svg class="a2ehc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + ( paths[ name ] || paths.check ) + '</svg>';
	}

	var LEVELS = {
		below: { label: 'Below Standard', fill: 15 },
		above: { label: 'Above Standard', fill: 56 },
		top:   { label: 'Top Rated', fill: 94 }
	};

	function levelText( r ) {
		if ( r.level === 'top' ) {
			return 'This account meets every eBay Top Rated requirement. Keep it up through the next evaluation to hold the Top Rated badge and its final-value-fee discount.';
		}
		if ( r.level === 'below' ) {
			var why = [];
			if ( r.defectCounts && r.defectRate > AS_DEFECT ) { why.push( 'the defect rate is over 2%' ); }
			if ( r.cases > r.caseCap ) { why.push( 'there are too many cases closed without resolution' ); }
			return 'eBay would place this account Below Standard because ' + ( why.join( ' and ' ) || 'a core metric is past the limit' ) + '. Below Standard means lower search placement and selling limits, so act now.';
		}
		return 'This account is in good standing but not yet Top Rated. The breakdown below shows exactly which metric is holding it back and how many clean sales close the gap.';
	}

	/** One metric row. kind: defect|cases|late|track. */
	function metricRow( r, kind ) {
		var label, valueText, targetText, state, chip, fill, need;
		if ( kind === 'defect' ) {
			label = 'Transaction defect rate';
			valueText = round1( r.defectRate * 100 ) + '%';
			targetText = 'Top Rated ≤ 0.5%';
			if ( r.defectRate <= TR_DEFECT ) { state = 'pass'; }
			else if ( r.defectCounts && r.defectRate > AS_DEFECT ) { state = 'fail'; }
			else { state = 'warn'; }
			fill = clamp( 100 * ( 1 - r.defectRate / AS_DEFECT ), 5, 100 );
			need = r.needDef;
			if ( ! r.defectCounts && r.defectRate > TR_DEFECT ) {
				targetText = 'Not counted yet (under 4 buyers)';
			}
		} else if ( kind === 'cases' ) {
			label = 'Cases closed without resolution';
			valueText = fmtInt( r.cases ) + ( r.txns ? '  (' + round1( r.caseRate * 100 ) + '%)' : '' );
			targetText = 'Top Rated ≤ ' + r.caseCap;
			state = r.cases <= r.caseCap ? 'pass' : 'fail';
			fill = clamp( 100 * ( 1 - r.cases / Math.max( r.caseCap, 1 ) ), 5, 100 );
			need = r.needCase;
		} else if ( kind === 'late' ) {
			label = 'Late shipment rate';
			valueText = round1( r.lateRate * 100 ) + '%  (' + fmtInt( r.late ) + ')';
			targetText = 'Top Rated ≤ ' + r.lateCap + ' or 3%';
			state = r.late <= r.lateCap ? 'pass' : 'warn';
			fill = clamp( 100 * ( 1 - r.late / Math.max( r.lateCap, 1 ) ), 5, 100 );
			need = r.needLate;
		} else {
			label = 'Valid tracking uploaded on time';
			valueText = round1( r.track ) + '%';
			targetText = 'Top Rated ≥ 95%';
			state = r.track >= TRACK_MIN ? 'pass' : 'warn';
			fill = clamp( r.track, 5, 100 );
			need = r.needTrack;
		}
		var chips = { pass: 'Meets Top Rated', warn: 'Blocks Top Rated', fail: 'Below Standard' };
		chip = chips[ state ];
		var needHtml = need > 0 ? '<span class="a2ehc-metric__need">+' + fmtInt( need ) + ' clean sales to pass</span>' : '';
		return '<li class="a2ehc-metric is-' + state + '">'
			+ '<span class="a2ehc-metric__head"><span class="a2ehc-metric__label">' + esc( label ) + '</span>'
			+ '<span class="a2ehc-metric__chip">' + esc( chip ) + '</span></span>'
			+ '<span class="a2ehc-metric__row"><span class="a2ehc-metric__val">' + esc( valueText ) + '</span>'
			+ '<span class="a2ehc-metric__target">' + esc( targetText ) + '</span></span>'
			+ '<span class="a2ehc-metric__bar"><span class="a2ehc-metric__fill" style="width:' + fill + '%"></span></span>'
			+ needHtml + '</li>';
	}

	function suggestions( r ) {
		var out = [];
		if ( r.defectRate > TR_DEFECT ) {
			out.push( { icon: 'check', title: 'Cut your defect rate', text: 'Defects are your own cancellations plus cases closed in the buyer\'s favour. Never cancel for "out of stock": hold a reliable supplier and a small buffer, and if an item truly runs out, message and refund the buyer rather than cancelling.' } );
		}
		if ( r.cases > r.caseCap ) {
			out.push( { icon: 'alert', title: 'Resolve cases before eBay steps in', text: 'Answer every buyer within 24 hours and settle "item not received" or "not as described" yourself with a replacement or refund. A case eBay closes without your resolution is what pushes an account Below Standard.' } );
		}
		if ( r.track < TRACK_MIN ) {
			out.push( { icon: 'truck', title: 'Upload valid tracking on time', text: 'Top Rated needs valid, carrier-scanned tracking uploaded within your handling time on at least 95% of orders. Use carriers eBay recognises, print the label and upload the number before your cut-off, and get the first scan the same day.' } );
		}
		if ( r.late > r.lateCap ) {
			out.push( { icon: 'clock', title: 'Ship within your handling time', text: 'Set an honest handling time and dispatch with the first carrier scan before it expires. Late shipments do not drop you Below Standard, but they block Top Rated.' } );
		}
		if ( out.length === 0 ) {
			out.push( { icon: 'award', title: 'Protect what you have built', text: 'Your metrics meet the Top Rated bar. Keep suppliers and stock tight, answer buyers quickly, and watch the dashboard weekly so a single bad week never costs you your level.' } );
		}
		return out;
	}

	function recoverCards( r ) {
		if ( r.level === 'top' ) {
			return '<div class="a2ehc-rc is-good"><p class="a2ehc-rc__label">' + icon( 'award' ) + 'Status</p>'
				+ '<p class="a2ehc-rc__big">Top Rated</p>'
				+ '<p class="a2ehc-rc__sub">Every requirement is met. Hold these numbers through your ' + fmtDate( r.nextEval ) + ' evaluation to keep the badge.</p></div>';
		}
		var cards = '';
		if ( r.isBelow ) {
			cards += '<div class="a2ehc-rc"><p class="a2ehc-rc__label">' + icon( 'trend' ) + 'First, reach Above Standard</p>'
				+ '<p class="a2ehc-rc__big" data-count="' + r.needAbove + '">' + fmtInt( r.needAbove ) + '</p>'
				+ '<p class="a2ehc-rc__lead">clean sales with no new defects or cases</p>'
				+ '<p class="a2ehc-rc__sub">This clears the Below Standard triggers and restores normal search placement.</p></div>';
		}
		var bind = [];
		if ( r.needDef === r.needTop && r.needDef > 0 ) { bind.push( 'defect rate' ); }
		if ( r.needCase === r.needTop && r.needCase > 0 ) { bind.push( 'case rate' ); }
		if ( r.needLate === r.needTop && r.needLate > 0 ) { bind.push( 'late rate' ); }
		if ( r.needTrack === r.needTop && r.needTrack > 0 ) { bind.push( 'tracking' ); }

		var topBody;
		if ( r.needTop <= 0 ) {
			topBody = '<p class="a2ehc-rc__big">' + ( r.eligible ? '0' : 'Eligibility' ) + '</p>'
				+ '<p class="a2ehc-rc__sub">' + ( r.eligible
					? 'Your metrics already meet Top Rated. You should be upgraded at your next evaluation.'
					: 'Your metrics meet Top Rated, but the account still needs 90+ days, 100+ sales and $1,000+ to US buyers over 12 months.' ) + '</p>';
		} else {
			var when = '';
			if ( r.daily > 0 ) {
				var d = new Date();
				d.setDate( d.getDate() + Math.ceil( r.needTop / r.daily ) );
				when = '<p class="a2ehc-rc__sub">At your current pace, about <strong>' + fmtDate( d ) + '</strong>'
					+ ( d > r.nextEval ? ', after your ' + fmtDate( r.nextEval ) + ' evaluation, so plan for the one after.' : ', in time for your ' + fmtDate( r.nextEval ) + ' evaluation.' ) + '</p>';
			}
			topBody = '<p class="a2ehc-rc__big" data-count="' + r.needTop + '">' + fmtInt( r.needTop ) + '</p>'
				+ '<p class="a2ehc-rc__lead">clean, tracked sales with no new defects</p>'
				+ ( bind.length ? '<p class="a2ehc-rc__bind">Held back by your ' + esc( bind.join( ' and ' ) ) + '.</p>' : '' )
				+ when;
		}
		cards += '<div class="a2ehc-rc is-top"><p class="a2ehc-rc__label">' + icon( 'target' ) + 'Reach Top Rated</p>' + topBody + '</div>';
		return cards;
	}

	function render( root, r ) {
		var out = function ( key, val ) { var e = root.querySelector( '[data-out="' + key + '"]' ); if ( e ) { e.textContent = val; } };
		var outHtml = function ( key, val ) { var e = root.querySelector( '[data-out-html="' + key + '"]' ); if ( e ) { e.innerHTML = val; } };

		var lv = LEVELS[ r.level ];

		// Level pill + container colour.
		var levelBox = root.querySelector( '[data-level]' );
		if ( levelBox ) { levelBox.setAttribute( 'data-level', r.ready ? r.level : '' ); }
		out( 'level', r.ready ? lv.label : 'Enter your numbers' );
		out( 'levelText', r.ready ? levelText( r ) : 'Fill in the fields to see where this account stands against eBay US seller standards.' );
		out( 'evalNote', r.ready ? ( 'eBay would use your ' + r.evalBasis + ' figures  ·  ' + fmtInt( r.txns ) + ' transactions' ) : '' );

		// Meter.
		var fill = root.querySelector( '[data-meter-fill]' );
		if ( fill ) { fill.style.width = ( r.ready ? lv.fill : 0 ) + '%'; }
		var meter = root.querySelector( '[data-meter]' );
		if ( meter ) { meter.setAttribute( 'data-level', r.ready ? r.level : '' ); }
		root.querySelectorAll( '[data-seg]' ).forEach( function ( li ) {
			var seg = li.getAttribute( 'data-seg' );
			var onOrBelow = r.ready && ( ( 'below' === seg ) || ( 'above' === seg && r.level !== 'below' ) || ( 'top' === seg && r.level === 'top' ) );
			li.classList.toggle( 'is-on', !! onOrBelow );
			li.classList.toggle( 'is-current', r.ready && seg === r.level );
		} );

		// Metrics.
		outHtml( 'metrics', metricRow( r, 'defect' ) + metricRow( r, 'cases' ) + metricRow( r, 'late' ) + metricRow( r, 'track' ) );

		// Recovery.
		var rec = root.querySelector( '[data-recover]' );
		if ( rec ) {
			if ( r.ready ) {
				outHtml( 'recover', recoverCards( r ) );
				out( 'recoverNote', r.level === 'top' ? '' : 'Assumes the new sales have no defects or cases and valid, on-time tracking. eBay uses a rolling window, so recovery can be faster as old issues age out.' );
				rec.hidden = false;
			} else {
				rec.hidden = true;
			}
		}

		// Suggestions + Top Rated Plus note.
		outHtml( 'suggestions', suggestions( r ).map( function ( s ) {
			return '<li class="a2ehc-fix__item">' + icon( s.icon ) + '<span><strong>' + esc( s.title ) + '</strong> ' + esc( s.text ) + '</span></li>';
		} ).join( '' ) );
		outHtml( 'trplus', r.ready
			? icon( 'award' ) + '<span><strong>Top Rated Plus</strong> is a per-listing badge, not an account level. Once you are Top Rated, list with 1-day handling and 30-day free returns to earn the Top Rated Plus badge and a 10% final-value-fee discount on those listings.</span>'
			: '' );

		fillReport( root, r );
		animateCounts( root );
	}

	/** Count-up for elements carrying data-count, run once per reveal. */
	function animateCounts( root ) {
		if ( reduceMotion ) { return; }
		root.querySelectorAll( '[data-count]' ).forEach( function ( el ) {
			var target = parseFloat( el.getAttribute( 'data-count' ) );
			if ( ! isFinite( target ) ) { return; }
			var prev = el.__a2ePrev;
			if ( prev === target ) { return; }
			el.__a2ePrev = target;
			var text = el.textContent;
			var decimals = /\./.test( text ) && el.className.indexOf( 'rc__big' ) === -1 ? 1 : 0;
			var suffix = /%/.test( text ) ? '%' : '';
			if ( el.__a2eRaf ) { cancelAnimationFrame( el.__a2eRaf ); }
			var from = 0, start = null, dur = 520;
			var step = function ( ts ) {
				if ( null === start ) { start = ts; }
				var p = clamp( ( ts - start ) / dur, 0, 1 );
				var e = 1 - Math.pow( 1 - p, 3 );
				var v = from + ( target - from ) * e;
				el.textContent = ( decimals ? round1( v ) : Math.round( v ).toLocaleString() ) + suffix;
				if ( p < 1 ) { el.__a2eRaf = requestAnimationFrame( step ); }
				else { el.textContent = ( decimals ? round1( target ) : Math.round( target ).toLocaleString() ) + suffix; }
			};
			el.__a2eRaf = requestAnimationFrame( step );
		} );
	}

	function fillReport( root, r ) {
		var el = root.querySelector( '[data-out="report"]' );
		if ( ! el || ! r.ready ) { if ( el ) { el.value = ''; } return; }
		var L = [];
		L.push( 'eBay Account Health Checker - results' );
		L.push( '' );
		L.push( 'Seller level: ' + LEVELS[ r.level ].label );
		L.push( 'Evaluation basis: ' + r.evalBasis + ' figures, ' + fmtInt( r.txns ) + ' transactions' );
		L.push( '' );
		L.push( 'Metrics vs eBay Top Rated:' );
		L.push( '- Transaction defect rate: ' + round1( r.defectRate * 100 ) + '% (target <= 0.5%)' );
		L.push( '- Cases closed without resolution: ' + fmtInt( r.cases ) + ' (target <= ' + r.caseCap + ')' );
		L.push( '- Late shipment rate: ' + round1( r.lateRate * 100 ) + '% / ' + fmtInt( r.late ) + ' (target <= ' + r.lateCap + ' or 3%)' );
		L.push( '- Valid tracking on time: ' + round1( r.track ) + '% (target >= 95%)' );
		L.push( '' );
		if ( r.level === 'top' ) {
			L.push( 'This account meets every Top Rated requirement.' );
		} else {
			if ( r.isBelow ) { L.push( 'To reach Above Standard: about ' + fmtInt( r.needAbove ) + ' clean sales.' ); }
			L.push( 'To reach Top Rated: about ' + fmtInt( r.needTop ) + ' clean, tracked sales with no new defects.' );
			L.push( '' );
			L.push( 'Per metric, clean sales needed:' );
			L.push( '- Defect rate: ' + fmtInt( r.needDef ) );
			L.push( '- Cases: ' + fmtInt( r.needCase ) );
			L.push( '- Late shipments: ' + fmtInt( r.needLate ) );
			L.push( '- Tracking: ' + fmtInt( r.needTrack ) );
		}
		L.push( '' );
		L.push( 'Next evaluation: ' + fmtDate( r.nextEval ) );
		L.push( '' );
		L.push( 'Suggestions:' );
		suggestions( r ).forEach( function ( s, i ) { L.push( ( i + 1 ) + '. ' + s.title + ' - ' + s.text ); } );
		el.value = L.join( '\n' );
	}

	function wireCapture( root ) {
		var form = root.querySelector( '[data-capture-form]' );
		if ( ! form || ! data.ajax ) { return; }
		var result = form.querySelector( '[data-result]' );
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var btn = form.querySelector( 'button[type="submit"]' );
			var email = form.querySelector( '[name="email"]' );
			if ( email && ! email.value ) { email.focus(); return; }
			if ( btn ) { btn.disabled = true; btn.classList.add( 'is-busy' ); }
			var show = function ( msg, ok ) {
				if ( result ) { result.hidden = false; result.textContent = msg; result.className = 'a2ehc-result ' + ( ok ? 'is-ok' : 'is-err' ); }
			};
			fetch( data.ajax, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData( form ) } )
				.then( function ( res ) { return res.json().then( function ( j ) { return { ok: res.ok, json: j }; } ); } )
				.then( function ( x ) {
					if ( x.ok && x.json && x.json.success ) {
						show( ( x.json.data && x.json.data.message ) || 'Thanks! Your report is on its way.', true );
						form.querySelectorAll( 'input[name="name"], input[name="email"]' ).forEach( function ( i ) { i.value = ''; } );
						if ( btn ) { btn.style.display = 'none'; }
					} else {
						show( ( x.json && x.json.data && x.json.data.message ) || 'Sorry, that could not be sent. Please try again.', false );
						if ( btn ) { btn.disabled = false; btn.classList.remove( 'is-busy' ); }
					}
				} )
				.catch( function () { show( 'Network problem. Please try again in a moment.', false ); if ( btn ) { btn.disabled = false; btn.classList.remove( 'is-busy' ); } } );
		} );
	}

	function boot( root ) {
		if ( root.__a2ehc ) { return; }
		root.__a2ehc = true;

		var revealed = false;
		var update = function () {
			render( root, compute( root ) );
			if ( ! revealed ) { revealed = true; root.classList.add( 'is-revealed' ); }
		};

		root.querySelectorAll( 'input, select' ).forEach( function ( el ) {
			el.addEventListener( 'input', update );
			el.addEventListener( 'change', update );
		} );

		// Default the date window and next evaluation if empty.
		var today = new Date();
		var setDate = function ( name, d ) {
			var el = root.querySelector( '[name="' + name + '"]' );
			if ( el && ! el.value ) {
				el.value = d.getFullYear() + '-' + ( '0' + ( d.getMonth() + 1 ) ).slice( -2 ) + '-' + ( '0' + d.getDate() ).slice( -2 );
			}
		};
		var start = new Date(); start.setMonth( start.getMonth() - 3 );
		setDate( 'startdate', start );
		setDate( 'enddate', today );
		setDate( 'nextdate', nextTwentieth( today ) );

		wireCapture( root );
		update();
	}

	function init() {
		document.querySelectorAll( '[data-a2ehc]' ).forEach( boot );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
