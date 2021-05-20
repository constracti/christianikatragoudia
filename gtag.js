jQuery(document).on('click', '.kgr-gtag', function() {
	const elem = jQuery(this);
	gtag('event', elem.data('kgr-gtag-action'), {
		event_category: elem.data('kgr-gtag-category'),
		event_label: elem.data('kgr-gtag-label'),
	});
});

jQuery(document).on('click', '.kgr-gtag-audio .mejs-playpause-button', function() {
	const elem = jQuery(this).closest('.kgr-gtag-audio');
	gtag('event', elem.data('kgr-gtag-action'), {
		event_category: elem.data('kgr-gtag-category'),
		event_label: elem.data('kgr-gtag-label'),
	});
});
