/**
 * eBay Account Health Checker - browser engine.
 *
 * Everything runs locally in the visitor's browser; no numbers are sent anywhere
 * unless they choose to email themselves the report. The engine scores the account
 * against eBay US seller standards, works out how many clean transactions are needed
 * to recover Above Standard and Top Rated and roughly when, writes tailored
 * suggestions, and fills the hidden report field used by the capture form.
 */
( function () {
	'use strict';

	var data = window.a2ehcData || { ajax: '', capture: 0, business: 'A2E' };

	var CIRC = 2 * Math.PI * 52; // Score ring circumference (r = 52).

	function clamp( n, lo, hi ) {
		return Math.max( lo, Math.min( hi, n ) );
	}

	function num( v, fallback ) {
		var n = parseFloat( v );
		return isFinite( n ) ? n : ( fallback || 0 );
	}

	function round1( n ) {
		return Math.round( n * 10 ) / 10;
	}

	function esc( s ) {
		return String( s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	function fmtInt( n ) {
		return Math.round( n ).toLocaleString();
	}

	/**
	 * Clean transactions still needed so that count/(transactions + N) <= target.
	 *
	 * @param {number} count        Current count of this defect type.
	 * @param {number} transactions Current transactions in the window.
	 * @param {number} targetPct    Target rate as a percentage (e.g. 2 or 0.5).
	 * @return {number} Clean transactions needed (0 if already met).
	 */
	function cleanNeeded( count, transactions, targetPct ) {
		if ( count <= 0 || targetPct <= 0 ) {
			return 0;
		}
		var minTransactions = Math.ceil( ( count * 100 ) / targetPct );
		return Math.max( 0, minTransactions - Math.round( transactions ) );
	}

	/**
	 * The next eBay evaluation (20th of a month) on or after a given date.
	 *
	 * @param {Date} from Starting date.
	 * @return {Date}
	 */
	function nextTwentieth( from ) {
		var d = new Date( from.getFullYear(), from.getMonth(), 20 );
		if ( from.getDate() >= 20 ) {
			d = new Date( from.getFullYear(), from.getMonth() + 1, 20 );
		}
		return d;
	}

	function fmtDate( d ) {
		try {
			return d.toLocaleDateString( undefined, { year: 'numeric', month: 'long', day: 'numeric' } );
		} catch ( e ) {
			return d.toISOString().slice( 0, 10 );
		}
	}

	/**
	 * Read inputs and compute the full result.
	 *
	 * @param {HTMLElement} root Widget root.
	 * @return {Object}
	 */
	function compute( root ) {
		var get = function ( name ) {
			var el = root.querySelector( '[name="' + name + '"]' );
			return el ? el.value : '';
		};

		var period       = num( get( 'period' ), 3 );
		var transactions = Math.max( 0, num( get( 'transactions' ), 0 ) );
		var defects      = Math.max( 0, num( get( 'defects' ), 0 ) );
		var late         = Math.max( 0, num( get( 'late' ), 0 ) );
		var cases        = Math.max( 0, num( get( 'cases' ), 0 ) );
		var tracking     = clamp( num( get( 'tracking' ), 0 ), 0, 100 );
		var ordersIn     = num( get( 'orders' ), 0 );

		var safeTx    = transactions > 0 ? transactions : 1;
		var defectPct = ( defects / safeTx ) * 100;
		var latePct   = ( late / safeTx ) * 100;
		var casePct   = ( cases / safeTx ) * 100;

		// Sub-scores (0..1), weighted into a 0..100 score.
		var sDef = defectPct <= 0.5 ? 1 : ( defectPct >= 2 ? 0 : 1 - ( defectPct - 0.5 ) / 1.5 );
		var sLate = latePct <= 3 ? 1 : ( latePct >= 7 ? 0 : 1 - ( latePct - 3 ) / 4 );
		var sCase = casePct <= 0.3 ? 1 : ( casePct >= 1 ? 0 : 1 - ( casePct - 0.3 ) / 0.7 );
		var sTrk = clamp( ( tracking - 80 ) / 15, 0, 1 );
		var score = Math.round( 35 * sDef + 25 * sLate + 20 * sCase + 20 * sTrk );

		// Band against eBay US seller standards.
		var isTop = defectPct <= 0.5 && latePct <= 3 && casePct <= 0.3 && score >= 85;
		var belowStandard = defectPct > 2 || casePct > 0.3; // What actually drops a US seller below standard.
		var band, bandKey, bandText;
		if ( isTop ) {
			bandKey = 'top';
			band = 'Top Rated territory';
			bandText = 'Your numbers meet the Top Rated bar. Keep this up through the next evaluation to earn or hold the Top Rated Seller badge and the discount that comes with it.';
		} else if ( ! belowStandard ) {
			bandKey = 'above';
			band = 'Above Standard';
			bandText = 'Your account is in good standing, but not yet Top Rated. Tightening the metrics below protects you from slipping and unlocks Top Rated benefits.';
		} else {
			bandKey = 'below';
			band = 'Below Standard risk';
			bandText = 'One or more metrics are past the eBay limit. Below Standard accounts face lower search placement and selling limits, so this is worth acting on now.';
		}

		// Monthly order velocity for the timeline estimate.
		var monthly = ordersIn > 0 ? ordersIn : ( transactions > 0 ? transactions / period : 0 );
		var daily   = monthly > 0 ? monthly / 30.4 : 0;

		// Recovery targets.
		var targets = [];
		if ( bandKey === 'below' ) {
			targets.push( makeTarget( 'Above Standard', 2, 0.3, 7, defects, cases, late, transactions, daily ) );
		}
		if ( bandKey !== 'top' ) {
			targets.push( makeTarget( 'Top Rated', 0.5, 0.3, 3, defects, cases, late, transactions, daily ) );
		}

		// Evaluation date.
		var nextEval = null;
		var rawDate  = get( 'nextdate' );
		if ( rawDate ) {
			var parts = rawDate.split( '-' );
			if ( parts.length === 3 ) {
				nextEval = new Date( +parts[ 0 ], +parts[ 1 ] - 1, +parts[ 2 ] );
			}
		}
		if ( ! nextEval || isNaN( nextEval.getTime() ) ) {
			nextEval = nextTwentieth( new Date() );
		}

		return {
			period: period,
			transactions: transactions,
			defects: defects,
			late: late,
			cases: cases,
			tracking: tracking,
			defectPct: defectPct,
			latePct: latePct,
			casePct: casePct,
			score: score,
			band: band,
			bandKey: bandKey,
			bandText: bandText,
			targets: targets,
			monthly: monthly,
			daily: daily,
			nextEval: nextEval
		};
	}

	/**
	 * Build one recovery target (e.g. Above Standard or Top Rated).
	 */
	function makeTarget( label, defectT, caseT, lateT, defects, cases, late, transactions, daily ) {
		var nDef  = cleanNeeded( defects, transactions, defectT );
		var nCase = cleanNeeded( cases, transactions, caseT );
		var nLate = ( label === 'Top Rated' ) ? cleanNeeded( late, transactions, lateT ) : 0;
		var need  = Math.max( nDef, nCase, nLate );

		// Which metric is holding you back.
		var binding = [];
		if ( nDef === need && nDef > 0 ) binding.push( 'defect rate' );
		if ( nCase === need && nCase > 0 ) binding.push( 'case rate' );
		if ( nLate === need && nLate > 0 ) binding.push( 'late rate' );

		var days = daily > 0 && need > 0 ? Math.ceil( need / daily ) : 0;
		var date = null;
		if ( days > 0 ) {
			date = new Date();
			date.setDate( date.getDate() + days );
		}

		return {
			label: label,
			need: need,
			binding: binding,
			days: days,
			date: date,
			defectT: defectT
		};
	}

	/**
	 * Tailored improvement suggestions.
	 *
	 * @param {Object} r Result.
	 * @return {Array<Object>} { icon, title, text }.
	 */
	function suggestions( r ) {
		var out = [];
		if ( r.defectPct > 0.5 ) {
			out.push( {
				icon: 'check',
				title: 'Cut your defect rate',
				text: 'Defects are mostly seller cancellations and cases closed in the buyer\'s favour. Never cancel for "out of stock": keep a reliable supplier and a small buffer, and if an item truly runs out, message the buyer and refund fast instead of cancelling the order.'
			} );
		}
		if ( r.latePct > 3 ) {
			out.push( {
				icon: 'clock',
				title: 'Ship and upload tracking on time',
				text: 'Set an honest handling time, print the label and upload valid tracking before your stated cut-off, and get the first scan the same day. On-time tracking is what clears the late-shipment flag.'
			} );
		}
		if ( r.casePct > 0.3 ) {
			out.push( {
				icon: 'check',
				title: 'Resolve cases before eBay steps in',
				text: 'Answer every buyer message within 24 hours and settle "item not received" or "not as described" yourself with a replacement or refund. A case closed without your resolution is the heaviest hit on your standing.'
			} );
		}
		if ( r.tracking < 95 ) {
			out.push( {
				icon: 'target',
				title: 'Upload valid tracking on every order',
				text: 'Aim for valid, on-time tracking on at least 95% of transactions. Use carriers eBay recognises so scans flow back automatically, and avoid marking orders shipped without a real tracking number.'
			} );
		}
		if ( out.length === 0 ) {
			out.push( {
				icon: 'target',
				title: 'Protect what you have built',
				text: 'Your metrics are clean. Keep suppliers and stock tight, answer buyers quickly, and watch the dashboard weekly so a single bad week never costs you your level.'
			} );
		}
		// One steady growth nudge for everyone.
		out.push( {
			icon: 'trend',
			title: 'Grow while you stay clean',
			text: 'Once your metrics are safe, add well-researched listings steadily rather than in bursts, so volume rises without a spike in cancellations or late shipments dragging your rates back up.'
		} );
		return out;
	}

	/**
	 * Icon markup (mirrors the PHP inline icons).
	 */
	function icon( name ) {
		var paths = {
			trend: '<path d="M3 17l6-6 4 4 7-7"/><path d="M17 7h4v4"/>',
			check: '<path d="M20 6 9 17l-5-5"/>',
			clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			target: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>'
		};
		return '<svg class="a2ehc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + ( paths[ name ] || paths.check ) + '</svg>';
	}

	/**
	 * A metric row: label, value, and a bar coloured by how safe it is.
	 */
	function metricRow( label, valuePct, goodAt, failAt, displayVal ) {
		// Fill: 100% when at/under goodAt, 0% at/over failAt.
		var fill;
		if ( valuePct <= goodAt ) {
			fill = 100;
		} else if ( valuePct >= failAt ) {
			fill = 6;
		} else {
			fill = Math.round( 100 - ( ( valuePct - goodAt ) / ( failAt - goodAt ) ) * 94 );
		}
		var state = valuePct <= goodAt ? 'good' : ( valuePct >= failAt ? 'bad' : 'warn' );
		return '<li class="a2ehc-metric is-' + state + '">'
			+ '<span class="a2ehc-metric__top"><span class="a2ehc-metric__label">' + esc( label ) + '</span>'
			+ '<span class="a2ehc-metric__val">' + esc( displayVal ) + '</span></span>'
			+ '<span class="a2ehc-metric__bar"><span class="a2ehc-metric__fill" style="width:' + fill + '%"></span></span>'
			+ '</li>';
	}

	/**
	 * Render the result into the widget.
	 */
	function render( root, r ) {
		var set = function ( key, val ) {
			var el = root.querySelector( '[data-out="' + key + '"]' );
			if ( el ) {
				el.textContent = val;
			}
		};
		var setHtml = function ( key, val ) {
			var el = root.querySelector( '[data-out-html="' + key + '"]' );
			if ( el ) {
				el.innerHTML = val;
			}
		};

		// Score ring.
		var ring = root.querySelector( '[data-ring]' );
		if ( ring ) {
			ring.style.strokeDasharray = CIRC;
			ring.style.strokeDashoffset = CIRC * ( 1 - clamp( r.score, 0, 100 ) / 100 );
		}
		var scoreWrap = root.querySelector( '.a2ehc-score' );
		if ( scoreWrap ) {
			scoreWrap.setAttribute( 'data-band', r.bandKey );
		}
		set( 'score', r.transactions > 0 ? r.score : '-' );

		// Band.
		var bandEl = root.querySelector( '[data-band]' );
		if ( bandEl ) {
			bandEl.setAttribute( 'data-band', r.bandKey );
		}
		set( 'band', r.band );
		set( 'bandText', r.bandText );

		// Metrics.
		var metrics = ''
			+ metricRow( 'Transaction defect rate', r.defectPct, 0.5, 2, round1( r.defectPct ) + '%' )
			+ metricRow( 'Late shipment rate', r.latePct, 3, 7, round1( r.latePct ) + '%' )
			+ metricRow( 'Cases without resolution', r.casePct, 0.3, 1, round1( r.casePct ) + '%' )
			+ metricRow( 'Valid tracking on time', 100 - r.tracking, 5, 20, round1( r.tracking ) + '%' );
		setHtml( 'metrics', metrics );

		// Recovery.
		var recoverWrap = root.querySelector( '[data-recover]' );
		if ( r.targets.length && r.transactions > 0 ) {
			var cards = r.targets.map( function ( t ) {
				var body;
				if ( t.need <= 0 ) {
					body = '<p class="a2ehc-rc__big">On track</p>'
						+ '<p class="a2ehc-rc__sub">Your counts already meet the ' + esc( t.label ) + ' limits for this window. Hold them through your next evaluation.</p>';
				} else {
					var when = '';
					if ( t.date ) {
						when = '<p class="a2ehc-rc__sub">At your current pace, about <strong>' + fmtDate( t.date ) + '</strong>';
						if ( t.date > r.nextEval ) {
							when += ', which is after your ' + fmtDate( r.nextEval ) + ' evaluation, so plan for the one after.';
						} else {
							when += ', in time for your ' + fmtDate( r.nextEval ) + ' evaluation.';
						}
						when += '</p>';
					} else {
						when = '<p class="a2ehc-rc__sub">Enter your orders per month to estimate the date.</p>';
					}
					var bind = t.binding.length ? '<p class="a2ehc-rc__bind">Held back by your ' + esc( t.binding.join( ' and ' ) ) + '.</p>' : '';
					body = '<p class="a2ehc-rc__big">' + fmtInt( t.need ) + '</p>'
						+ '<p class="a2ehc-rc__lead">clean transactions with no new defects</p>'
						+ bind + when;
				}
				return '<div class="a2ehc-rc"><p class="a2ehc-rc__label">' + icon( 'target' ) + 'Reach ' + esc( t.label ) + '</p>' + body + '</div>';
			} ).join( '' );
			setHtml( 'recover', cards );
			set( 'recoverNote', 'eBay uses a rolling window and needs a minimum number of defects from different buyers before your level changes, so real recovery can be faster than this once old defects age out. Treat these as a floor, not a ceiling.' );
			recoverWrap.hidden = false;
		} else if ( recoverWrap ) {
			if ( r.bandKey === 'top' && r.transactions > 0 ) {
				setHtml( 'recover', '<div class="a2ehc-rc"><p class="a2ehc-rc__label">' + icon( 'target' ) + 'You are at the top</p><p class="a2ehc-rc__big">Top Rated</p><p class="a2ehc-rc__sub">Keep your defect, late and case rates where they are through the next evaluation to hold the badge.</p></div>' );
				set( 'recoverNote', '' );
				recoverWrap.hidden = false;
			} else {
				recoverWrap.hidden = true;
			}
		}

		// Suggestions.
		var sugg = suggestions( r ).map( function ( s ) {
			return '<li class="a2ehc-fix__item">' + icon( s.icon ) + '<span><strong>' + esc( s.title ) + '</strong> ' + esc( s.text ) + '</span></li>';
		} ).join( '' );
		setHtml( 'suggestions', sugg );

		// Hidden report for the capture form.
		fillReport( root, r );
	}

	/**
	 * Build the plain-text report dropped into the hidden capture field.
	 */
	function fillReport( root, r ) {
		var el = root.querySelector( '[data-out="report"]' );
		if ( ! el ) {
			return;
		}
		var L = [];
		L.push( 'eBay Account Health Checker - results' );
		L.push( '' );
		L.push( 'Health score: ' + ( r.transactions > 0 ? r.score + '/100' : 'n/a' ) );
		L.push( 'Standing: ' + r.band );
		L.push( '' );
		L.push( 'Your numbers (' + ( r.period === 12 ? 'last 12 months' : 'last 3 months' ) + '):' );
		L.push( '- Transactions: ' + fmtInt( r.transactions ) );
		L.push( '- Defect rate: ' + round1( r.defectPct ) + '% (' + fmtInt( r.defects ) + ' defects)' );
		L.push( '- Late shipment rate: ' + round1( r.latePct ) + '% (' + fmtInt( r.late ) + ' late)' );
		L.push( '- Cases without resolution: ' + round1( r.casePct ) + '% (' + fmtInt( r.cases ) + ' cases)' );
		L.push( '- Valid tracking on time: ' + round1( r.tracking ) + '%' );
		if ( r.targets.length ) {
			L.push( '' );
			L.push( 'Path back to the top:' );
			r.targets.forEach( function ( t ) {
				if ( t.need <= 0 ) {
					L.push( '- ' + t.label + ': counts already meet the limit; hold through the next evaluation.' );
				} else {
					var line = '- ' + t.label + ': about ' + fmtInt( t.need ) + ' clean transactions needed';
					if ( t.binding.length ) {
						line += ' (held back by ' + t.binding.join( ' and ' ) + ')';
					}
					if ( t.date ) {
						line += ', roughly ' + fmtDate( t.date ) + ' at current pace';
					}
					L.push( line + '.' );
				}
			} );
		}
		L.push( '' );
		L.push( 'Next evaluation: ' + fmtDate( r.nextEval ) );
		L.push( '' );
		L.push( 'Suggestions:' );
		suggestions( r ).forEach( function ( s, i ) {
			L.push( ( i + 1 ) + '. ' + s.title + ' - ' + s.text );
		} );
		el.value = L.join( '\n' );
	}

	/**
	 * Wire the capture form (AJAX, graceful fallback to a normal POST).
	 */
	function wireCapture( root ) {
		var form = root.querySelector( '[data-capture-form]' );
		if ( ! form || ! data.ajax ) {
			return;
		}
		var result = form.querySelector( '[data-result]' );
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var btn = form.querySelector( 'button[type="submit"]' );
			var email = form.querySelector( '[name="email"]' );
			if ( email && ! email.value ) {
				email.focus();
				return;
			}
			if ( btn ) {
				btn.disabled = true;
				btn.classList.add( 'is-busy' );
			}
			var show = function ( msg, ok ) {
				if ( result ) {
					result.hidden = false;
					result.textContent = msg;
					result.className = 'a2ehc-result ' + ( ok ? 'is-ok' : 'is-err' );
				}
			};
			fetch( data.ajax, {
				method: 'POST',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
				body: new FormData( form )
			} ).then( function ( res ) {
				return res.json().then( function ( json ) {
					return { ok: res.ok, json: json };
				} );
			} ).then( function ( r ) {
				if ( r.ok && r.json && r.json.success ) {
					show( ( r.json.data && r.json.data.message ) || 'Thanks! Your report is on its way.', true );
					form.querySelectorAll( 'input[name="name"], input[name="email"]' ).forEach( function ( i ) { i.value = ''; } );
					if ( btn ) { btn.style.display = 'none'; }
				} else {
					var m = ( r.json && r.json.data && r.json.data.message ) || 'Sorry, that could not be sent. Please try again.';
					show( m, false );
					if ( btn ) { btn.disabled = false; btn.classList.remove( 'is-busy' ); }
				}
			} ).catch( function () {
				show( 'Network problem. Please try again in a moment.', false );
				if ( btn ) { btn.disabled = false; btn.classList.remove( 'is-busy' ); }
			} );
		} );
	}

	/**
	 * Boot one widget.
	 */
	function boot( root ) {
		if ( root.__a2ehc ) {
			return;
		}
		root.__a2ehc = true;

		var update = function () {
			render( root, compute( root ) );
		};

		root.querySelectorAll( 'input, select' ).forEach( function ( el ) {
			el.addEventListener( 'input', update );
			el.addEventListener( 'change', update );
		} );

		// Default the evaluation date to the next 20th if empty.
		var dateEl = root.querySelector( '[name="nextdate"]' );
		if ( dateEl && ! dateEl.value ) {
			var n = nextTwentieth( new Date() );
			var mm = ( '0' + ( n.getMonth() + 1 ) ).slice( -2 );
			var dd = ( '0' + n.getDate() ).slice( -2 );
			dateEl.value = n.getFullYear() + '-' + mm + '-' + dd;
		}

		wireCapture( root );
		update();
	}

	function init() {
		var nodes = document.querySelectorAll( '[data-a2ehc]' );
		for ( var i = 0; i < nodes.length; i++ ) {
			boot( nodes[ i ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
