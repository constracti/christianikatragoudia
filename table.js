(function($, pref) {

	function dash(...words) {
		return words.reduce((acc, x) => acc + '-' + x, pref);
	}

	function dotdash(...words) {
		return '.' + dash(...words);
	}

	function success(data) {
		const node = this;
		if (typeof(data) !== 'object')
			return alert(data);
		node.closest(dotdash('home')).replaceWith(data.html);
	}

	$(document).on('click', dotdash('link'), function(event) {
		event.preventDefault();
		const link = $(this);
		const home = link.closest(dotdash('home'));
		if (home.data(dash('busy')) === 'on')
			return false;
		const spinner = home.find(dotdash('spinner'));
		if (link.data(dash('confirm')) !== undefined && !confirm(link.data(dash('confirm'))))
			return false;
		home.data(dash('busy'), 'on');
		spinner.toggleClass(spinner.data(dash('spinner-toggle')));
		const data = {};
		if (link.hasClass(dash('submit'))) {
			link.closest(dotdash('form')).find(dotdash('field')).each(function() {
				const field = $(this);
				const name = field.data(dash('name'));
				data[name] = field.val();
			});
		}
		$.post(link.prop('href'), data).done(function(data) {
			success.call(link, data);
		}).fail(function(jqXHR) {
			alert(jqXHR.statusText + ' ' + jqXHR.status);
		}).always(function() {
			spinner.toggleClass(spinner.data(dash('spinner-toggle')));
			home.data(dash('busy'), 'off');
		});
	});

	$(document).on('click', dotdash('insert'), function(event) {
		event.preventDefault();
		const link = $(this);
		const home = link.closest(dotdash('home'));
		const form = home.find(link.data(dash('form')));
		form.find(dotdash('field')).each(function() {
			const field = $(this);
			const name = field.data(dash('name'));
			const value = link.data(dash('field', name));
			field.val(value);
		});
		form.find(dotdash('submit')).prop('href', link.prop('href'));
		form.show();
	});

	$(document).on('click', dotdash('cancel'), function(event) {
		event.preventDefault();
		const link = $(this);
		const form = link.closest(dotdash('form'));
		form.hide();
		form.find(dotdash('field')).each(function() {
			const field = $(this);
			field.val('');
		});
		form.find(dotdash('submit')).prop('href', '');
	});

})(jQuery, 'xt-table');
