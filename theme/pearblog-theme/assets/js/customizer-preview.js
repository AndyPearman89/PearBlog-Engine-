/**
 * PearBlog PRO Customizer Live Preview
 *
 * Handles real-time preview updates in the WordPress Customizer.
 *
 * @package PearBlog
 * @version 2.0.0
 */

( function ( $ ) {
	'use strict';

	// Brand colors – live preview via postMessage transport.
	wp.customize( 'pearblog_primary_color', function ( value ) {
		value.bind( function ( newVal ) {
			document.documentElement.style.setProperty( '--pb-primary', newVal );
		} );
	} );

	wp.customize( 'pearblog_secondary_color', function ( value ) {
		value.bind( function ( newVal ) {
			document.documentElement.style.setProperty( '--pb-secondary', newVal );
		} );
	} );

	wp.customize( 'pearblog_accent_color', function ( value ) {
		value.bind( function ( newVal ) {
			document.documentElement.style.setProperty( '--pb-accent', newVal );
		} );
	} );

} )( jQuery );
