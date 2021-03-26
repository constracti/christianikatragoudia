jQuery(document).on('change', '.kgr-chords-interval', function() {
	let interval = jQuery(this);
	let is_primary = [0, 3, 4].includes(parseInt(interval.val()));
	let primary = interval.siblings('.kgr-chords-primary');
	let secondary = interval.siblings('.kgr-chords-secondary');
	if (is_primary) {
		primary.show();
		secondary.hide();
	} else {
		primary.hide();
		secondary.show();
	}
	primary.val('0');
	secondary.val('0');
});

jQuery(document).ready(function() {
	jQuery('.kgr-chords-interval').change();
	jQuery('.kgr-chords-hide').hide();
	jQuery('.kgr-chords-larger').hide();
	jQuery('.kgr-chords-smaller').hide();
});

let kgr_chords = {
	re: /([A-G])(bb?|#|x)?/g,
	_steps: ['C', 'D', 'E', 'F', 'G', 'A', 'B'],
	step2str: i => kgr_chords._steps[i],
	step2int: s => kgr_chords._steps.indexOf(s),
	_alters: ['bb', 'b', '', '#', 'x'],
	alter2str: i => kgr_chords._alters[i+2],
	alter2int: s => kgr_chords._alters.indexOf(s !== undefined ? s : '')-2,
	_values: [0, 2, 4, 5, 7, 9, 11],
	step2val: i => i in kgr_chords._values ? kgr_chords._values[i] : '*',
	transpose: function(m0, transpose) {
		[step, alter] = m0;
		step = kgr_chords.step2int(step);
		alter = kgr_chords.alter2int(alter);
		alter += kgr_chords.step2val(step);
		step += transpose.diatonic;
		alter += transpose.chromatic;
		while (step < 0) {
			step += 7;
			alter += 12;
		}
		while (step >= 7) {
			step -= 7;
			alter -= 12;
		}
		alter -= kgr_chords.step2val(step);
		step = kgr_chords.step2str(step);
		alter = kgr_chords.alter2str(alter);
		m1 = [step, alter];
		return m1;
	},
};

jQuery(document).on('submit', '.kgr-chords', function() {
	let form = jQuery(this);
	form.find('.kgr-chords-hide').show();
	form.find('.kgr-chords-larger').show();
	form.find('.kgr-chords-smaller').show();
	let transpose = {};
	transpose.diatonic = parseInt(form.find('.kgr-chords-interval').val());
	transpose.chromatic = 2 * transpose.diatonic;
	if (transpose.diatonic >= 3)
		transpose.chromatic -= 1;
	if ([0, 3, 4].includes(transpose.diatonic))
		transpose.chromatic += parseInt(form.find('.kgr-chords-primary').val());
	else
		transpose.chromatic += parseInt(form.find('.kgr-chords-secondary').val());
	if (form.find('.kgr-chords-direction').val() !== 'up') {
		transpose.diatonic = -transpose.diatonic;
		transpose.chromatic = -transpose.chromatic;
	}
	jQuery.get(form.data('kgr-chords')).done(function(data) {
		data = jQuery('<div>' + data + '</div>').text();
		data = data.split('\n');
		let html = [];
		for (let line of data) {
			let ws = line.match(/\s/g);
			ws = (ws !== null) ? ws.length : 0;
			if (line.length)
				ws = ws / line.length >= .5;
			else
				ws = false;
			let offset = 0;
			if (ws) {
				let miter = line.matchAll(kgr_chords.re);
				for (let m of miter) {
					let m0 = m[0];
					let m1 = kgr_chords.transpose([m[1], m[2]], transpose).join('');
					let prev = line.slice(0, m.index + offset);
					let next = line.slice(m.index + offset + m0.length);
					if (offset < 0 && prev.slice(-1) !== '/') {
						prev += ' '.repeat(-offset);
						offset = 0;
					}
					while (offset > 0) {
						if (prev.slice(-2) !== '  ')
							break;
						prev = prev.slice(0, -1);
						offset--;
					}
					line = prev + m1 + next;
					offset += m1.length - m0.length;
				}
				line = '<b>' + line + '</b>';
			}
			html.push(line);
		}
		data = html.join('\n');
		form.find('.kgr-chords-text').html(data);
	}).fail(function(jqXHR) {
		alert(jqXHR.statusText + ' ' + jqXHR.status);
	});
	return false;
});

jQuery(document).on('click', '.kgr-chords-hide', function() {
	let hide = jQuery(this);
	let form = hide.closest('.kgr-chords');
	form.find('.kgr-chords-text').html('');
	hide.hide();
	form.find('.kgr-chords-larger').hide();
	form.find('.kgr-chords-smaller').hide();
});

jQuery(document).on('click', '.kgr-chords-larger', function() {
	let form = jQuery(this).closest('.kgr-chords');
	let text = form.find('.kgr-chords-text');
	let font_size = parseInt(text.css('font-size').replace('px', ''));
	text.css('font-size', (font_size + 1) + 'px');
});

jQuery(document).on('click', '.kgr-chords-smaller', function() {
	let form = jQuery(this).closest('.kgr-chords');
	let text = form.find('.kgr-chords-text');
	let font_size = parseInt(text.css('font-size').replace('px', ''));
	text.css('font-size', (font_size - 1) + 'px');
});
