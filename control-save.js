(function($) {

$(document).on('click', '.multi-control-home .button-primary', function() {
	const button = $(this);
	const home = button.parents('.multi-control-home');
	const spinner = home.find('.spinner');
	if (spinner.hasClass('is-active'))
		return;
	spinner.addClass('is-active');
	data = {
		action: home.find('.multi-control-action').val(),
		id: home.find('.multi-control-id').val(),
		nonce: home.find('.multi-control-nonce').val(),
		values: [],
	};
	home.find('.multi-control-list').find('.multi-control-item').each(function() {
		const value = {};
		$(this).find('[data-multi-control-name]').each(function() {
			value[$(this).data('multi-control-name')] = $(this).val();
		});
		data.values.push(value);
	});
	$.post(ajaxurl, data).done(function(data) {
		if (data !== '')
			alert(data);
	}).fail(function(jqXHR) {
		alert(jqXHR.statusText + ' ' + jqXHR.status);
	}).always(function() {
		spinner.removeClass('is-active');
	});
});

})(jQuery);
