jQuery( function() {

var busy = false;
var metabox = jQuery( '#kgr-tracks' );
var save = metabox.find( '.button-primary' );
var spinner = metabox.find( '.spinner' );

save.click( function() {
	if ( busy )
		return;
	busy = true;
	spinner.addClass( 'is-active' );
	data = {
		action: 'kgr_tracks',
		album: save.data( 'album' ),
		nonce: save.data( 'nonce' ),
		tracks: [],
	};
	metabox.find( '.kgr-control-items' ).find( '.kgr-control-item' ).find( 'select' ).each ( function() {
		data.tracks.push( jQuery( this ).val() );
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
