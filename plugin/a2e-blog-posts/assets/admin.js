/* A2E Blog Posts settings: add and remove sections, find pages. */
(function () {
	'use strict';
	var wrap = document.getElementById('a2ebp-sections');
	var tpl = document.getElementById('a2ebp-template');
	var add = document.getElementById('a2ebp-add');
	var empty = document.querySelector('.a2ebp-empty');
	if (!wrap || !tpl) { return; }

	function refreshEmpty() {
		if (empty) { empty.hidden = !!wrap.querySelector('.a2ebp-sec'); }
	}

	if (add) {
		add.addEventListener('click', function () {
			var id = parseInt(wrap.getAttribute('data-next'), 10) || 1;
			wrap.setAttribute('data-next', String(id + 1));
			var div = document.createElement('div');
			div.innerHTML = tpl.innerHTML.replace(/__ID__/g, String(id)).trim();
			var card = div.firstElementChild;
			wrap.appendChild(card);
			refreshEmpty();
			card.scrollIntoView({ behavior: 'smooth', block: 'start' });
			var first = card.querySelector('input[type="text"]');
			if (first) { first.focus(); }
		});
	}

	wrap.addEventListener('click', function (e) {
		var btn = e.target.closest('.a2ebp-remove');
		if (!btn) { return; }
		if (window.confirm('Remove this blog section? It disappears from your pages after you save.')) {
			btn.closest('.a2ebp-sec').remove();
			refreshEmpty();
		}
	});

	wrap.addEventListener('input', function (e) {
		if (!e.target.classList.contains('a2ebp-filter')) { return; }
		var q = e.target.value.trim().toLowerCase();
		var list = e.target.parentNode.querySelector('.a2ebp-list');
		if (!list) { return; }
		list.querySelectorAll('label').forEach(function (label) {
			var text = label.textContent.toLowerCase();
			var ticked = label.querySelector('input').checked;
			label.hidden = q !== '' && text.indexOf(q) === -1 && !ticked;
		});
	});
})();
