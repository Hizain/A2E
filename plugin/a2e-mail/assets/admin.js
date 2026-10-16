/* A2E Mail Studio: load templates and show a live preview. */
(function () {
	'use strict';
	var data = window.a2emailData || {};
	function $(id) { return document.getElementById(id); }

	var tpl = $('a2email-template');
	var subject = $('a2email-subject');
	var preheader = $('a2email-preheader');
	var body = $('a2email-body');
	var segment = $('a2email-segment');
	var frame = $('a2email-preview-frame');
	var subjLabel = $('a2email-preview-subject');
	var sendBtn = $('a2email-send');

	if (tpl) {
		tpl.addEventListener('change', function () {
			var opt = tpl.options[tpl.selectedIndex];
			if (!opt || !opt.value) { return; }
			if (subject) { subject.value = opt.getAttribute('data-subject') || ''; }
			if (preheader) { preheader.value = opt.getAttribute('data-preheader') || ''; }
			if (body) { body.value = opt.getAttribute('data-body') || ''; }
			tpl.selectedIndex = 0;
			refresh();
		});
	}

	var timer = null;
	function refresh() {
		if (!frame || !data.ajax) { return; }
		if (timer) { clearTimeout(timer); }
		timer = setTimeout(doPreview, 350);
	}

	function doPreview() {
		var form = new FormData();
		form.append('action', 'a2email_preview');
		form.append('nonce', data.nonce);
		form.append('subject', subject ? subject.value : '');
		form.append('preheader', preheader ? preheader.value : '');
		form.append('body', body ? body.value : '');
		form.append('segment', segment ? segment.value : 'all');
		fetch(data.ajax, { method: 'POST', body: form, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || !res.success) { return; }
				if (subjLabel) { subjLabel.textContent = res.data.subject || ''; }
				frame.srcdoc = res.data.html || '';
			})
			.catch(function () {});
	}

	[subject, preheader, body, segment].forEach(function (el) {
		if (el) { el.addEventListener('input', refresh); el.addEventListener('change', refresh); }
	});

	if (sendBtn) {
		sendBtn.addEventListener('click', function (e) {
			var msg = sendBtn.getAttribute('data-confirm');
			if (msg && !window.confirm(msg)) { e.preventDefault(); }
		});
	}

	if (frame) { doPreview(); }

	/* Automation editor: add and remove steps. */
	var stepsWrap = $('a2email-steps');
	var addStep = $('a2email-add-step');
	var stepTpl = $('a2email-step-template');
	function reindex() {
		if (!stepsWrap) { return; }
		var rows = stepsWrap.querySelectorAll('.a2email-step');
		rows.forEach(function (row, i) {
			row.querySelectorAll('[name]').forEach(function (el) {
				el.name = el.name.replace(/steps\[[^\]]*\]/, 'steps[' + i + ']');
			});
		});
	}
	if (stepsWrap) {
		if (addStep && stepTpl) {
			addStep.addEventListener('click', function () {
				var html = stepTpl.innerHTML.replace(/__i__/g, Date.now().toString());
				var div = document.createElement('div');
				div.innerHTML = html.trim();
				stepsWrap.appendChild(div.firstChild);
				reindex();
			});
		}
		stepsWrap.addEventListener('click', function (e) {
			if (e.target && e.target.classList.contains('a2email-step__remove')) {
				var rows = stepsWrap.querySelectorAll('.a2email-step');
				if (rows.length <= 1) { return; }
				e.target.closest('.a2email-step').remove();
				reindex();
			}
		});
	}
})();
