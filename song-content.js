jQuery( document ).ready( function( $ ) {

var active = null;

MIDIjs.message_callback = console.log;

MIDIjs.player_callback = function( e ) {
	$( active ).html( Math.floor( e.time ) );
};

$( '.kgr-song-content-midijs' ).addClass( 'dashicons-before dashicons-controls-play' ).click( function() {
	if ( active === null ) {
		active = this;
		$( active ).toggleClass( 'dashicons-controls-play dashicons-controls-pause' );
		MIDIjs.play( $( active ).siblings( 'a' ).prop( 'href' ) );
	} else if ( active === this ) {
		MIDIjs.stop();
		$( active ).toggleClass( 'dashicons-controls-play dashicons-controls-pause' ).html( '' );
		active = null;
	} else {
		MIDIjs.stop();
		$( active ).toggleClass( 'dashicons-controls-play dashicons-controls-pause' ).html( '' );
		active = this;
		$( active ).toggleClass( 'dashicons-controls-play dashicons-controls-pause' );
		MIDIjs.play( $( active ).siblings( 'a' ).prop( 'href' ) );
	}
} );

} );
