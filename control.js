jQuery( document ).on( 'click', '.kgr-control-add', function() {
	var container = jQuery( this ).parents( '.kgr-control-container' );
	var items = container.find( '.kgr-control-items' );
	var item = container.find( '.kgr-control-item0' ).find( '.kgr-control-item' );
	item.clone().appendTo( items ).children( 'input, select' ).first().focus();
} );

jQuery( document ).on( 'click', '.kgr-control-up', function() {
	var item = jQuery( this ).parents( '.kgr-control-item' );
	var target = item.prev();
	if ( target.length === 0 )
		return;
	item.detach().insertBefore( target );
} );

jQuery( document ).on( 'click', '.kgr-control-down', function() {
	var item = jQuery( this ).parents( '.kgr-control-item' );
	var target = item.next();
	if ( target.length === 0 )
		return;
	item.detach().insertAfter( target );
} );

jQuery( document ).on( 'click', '.kgr-control-delete', function() {
	var item = jQuery( this ).parents( '.kgr-control-item' );
	item.remove();
} );
