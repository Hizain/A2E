/* A2E admin: media picker for image fields. */
(function ($) {
	'use strict';
	$(document).on('click', '.a2e-media__pick', function (e) {
		e.preventDefault();
		var wrap = $(this).closest('.a2e-media');
		var frame = wp.media({ title: 'Choose image', button: { text: 'Use this image' }, library: { type: 'image' }, multiple: false });
		frame.on('select', function () {
			var img = frame.state().get('selection').first().toJSON();
			wrap.find('.a2e-media__url').val(img.url).trigger('change');
			wrap.find('.a2e-media__preview').html('<img src="' + img.url + '" alt="">');
		});
		frame.open();
	});
})(jQuery);
