jQuery(document).on('click', '.xt-gtag', function() {
	const elem = jQuery(this);
	gtag('event', elem.data('xt-gtag-action'), {
		event_category: elem.data('xt-gtag-category'),
		event_label: elem.data('xt-gtag-label'),
	});
});

jQuery(document).on('click', '.xt-gtag-audio .mejs-playpause-button', function() {
	const elem = jQuery(this).closest('.xt-gtag-audio');
	gtag('event', elem.data('xt-gtag-action'), {
		event_category: elem.data('xt-gtag-category'),
		event_label: elem.data('xt-gtag-label'),
	});
});
