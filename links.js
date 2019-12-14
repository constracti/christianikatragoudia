jQuery( function() {

var busy = false;
var metabox = jQuery( '#kgr-links' );
var save = metabox.find( '.button-primary' );
var spinner = metabox.find( '.spinner' );

save.click( function() {
	if ( busy )
		return;
	busy = true;
	spinner.addClass( 'is-active' );
	data = {
		action: 'kgr_links',
		post: save.data( 'post' ),
		nonce: save.data( 'nonce' ),
		links: [],
	};
	metabox.find( '.kgr-control-items' ).find( '.kgr-control-item' ).each ( function() {
		var item = jQuery( this );
		data.links.push( {
			url: item.find( '.kgr-link-url' ).val(),
			caption: item.find( '.kgr-link-caption' ).val(),
			description: item.find( '.kgr-link-description' ).val(),
		} );
	} );
	jQuery.post( ajaxurl, data ).done( function( data ) {
		if ( data !== '' )
			alert( data );
	} ).always( function() {
		spinner.removeClass( 'is-active' );
		busy = false;
	} );
} );

} );
