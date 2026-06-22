<?php
/**
 * PT24.PRO — Google AdSense integration (configurable, host-guarded).
 *
 * Outputs the AdSense loader in <head> and serves /ads.txt, but ONLY when a
 * publisher ID is set and AdSense is enabled in Enterprise V8 → Settings. With
 * no configuration nothing is emitted, so the live site is untouched until the
 * operator opts in. Loaded only on the PT24 install (host-guarded require).
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalised AdSense publisher ID (ca-pub-XXXXXXXXXXXXXXXX) or '' when unset.
 */
function pt24_adsense_publisher_id(): string {
	$raw = trim( (string) get_option( 'pt24_adsense_pub_id', '' ) );
	if ( '' === $raw ) {
		return '';
	}
	if ( 0 === strpos( $raw, 'ca-pub-' ) ) {
		return $raw;
	}
	if ( 0 === strpos( $raw, 'pub-' ) ) {
		return 'ca-' . $raw;
	}
	$digits = preg_replace( '/[^0-9]/', '', $raw );
	return '' !== $digits ? 'ca-pub-' . $digits : '';
}

/**
 * Whether AdSense output is enabled and configured.
 */
function pt24_adsense_is_active(): bool {
	return '1' === (string) get_option( 'pt24_adsense_enabled', '0' )
		&& '' !== pt24_adsense_publisher_id();
}

/**
 * Print the AdSense loader (and optional Auto Ads) in the document head.
 */
function pt24_adsense_head(): void {
	if ( ! pt24_adsense_is_active() ) {
		return;
	}
	$pub = pt24_adsense_publisher_id();

	echo "\n<!-- PT24 AdSense -->\n";
	printf(
		'<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=%s" crossorigin="anonymous"></script>' . "\n",
		esc_attr( $pub )
	);

	if ( '1' === (string) get_option( 'pt24_adsense_auto_ads', '1' ) ) {
		printf(
			'<script>(adsbygoogle = window.adsbygoogle || []).push({google_ad_client:"%s",enable_page_level_ads:true});</script>' . "\n",
			esc_js( $pub )
		);
	}
}
add_action( 'wp_head', 'pt24_adsense_head', 20 );

/**
 * Serve /ads.txt with the AdSense line when a publisher ID is configured.
 *
 * Uses an early request-path match (rewrite rules are unreliable on this host),
 * mirroring the sitemap handler.
 */
function pt24_adsense_ads_txt(): void {
	$pub = pt24_adsense_publisher_id();
	if ( '' === $pub ) {
		return;
	}

	$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$request_path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
	$home_path    = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$home_path    = ( '' === $home_path || '/' === $home_path ) ? '' : untrailingslashit( $home_path );

	$rel = $request_path;
	if ( '' !== $home_path && 0 === strpos( $rel, $home_path ) ) {
		$rel = substr( $rel, strlen( $home_path ) );
	}
	if ( 'ads.txt' !== trim( $rel, '/' ) ) {
		return;
	}

	$pub_number = preg_replace( '/^ca-/', '', $pub ); // pub-XXXXXXXXXXXXXXXX

	if ( ! headers_sent() ) {
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
	}
	echo 'google.com, ' . $pub_number . ', DIRECT, f08c47fec0942fa0' . "\n";
	exit;
}
add_action( 'init', 'pt24_adsense_ads_txt', 1 );
